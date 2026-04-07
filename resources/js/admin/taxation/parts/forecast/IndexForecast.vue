<template>
    <TwoColLayout>
        <template #top>
            <TaxForecastFilters
                :search="search"
                :selected-division="selectedDivision"
                :selected-unit="selectedUnit"
                :divisions="divisions"
                :units="units"
                :filtered-count="filteredRows.length"
                :total-count="body.length"
                :has-active-filters="hasActiveFilters"
                :is-applying="isApplyingToPayroll"
                @update:search="search = $event"
                @update:selectedDivision="selectedDivision = $event"
                @update:selectedUnit="selectedUnit = $event"
                @apply-to-tax="$emit('apply-to-tax')"
                @pull-reconcile="pullFromPayrollAndReconcile"
                @clear="clearFilters"
            />
        </template>

        <!-- LEFT SIDE -->
        <template #left>
            <div v-if="filteredRows.length">
                <TaxForecastTable
                    :rows="filteredRows"
                    :is-recomputing="is_recomputing"
                    :recomputing-key="recomputing_key"
                    :focus-row-key="pending_focus_row_key"
                    :selected-row-key="selected_row_key"
                    @view="viewRow"
                    @edit="editRow"
                    @recompute="recomputeRow"
                    @delete="deleteRow"
                />
            </div>

            <EmptyState v-else />
        </template>

        <!-- RIGHT SIDE -->
        <template #right>
            <transition
                enter-active-class="slideInRight"
                leave-active-class="fadeOut"
                mode="out-in"
            >
                <component
                    v-if="selectedAction"
                    :key="selectedActionId"
                    :is="selectedAction.component"
                    :row="activeRow"
                    @close="setAction('empty')"
                    @refresh-forecast="handleRefreshForecast"
                />
            </transition>
        </template>
    </TwoColLayout>
</template>

<script>
import axios from "axios";
import { markRaw } from "vue";

import TwoColLayout from "../../components/TwoColLayout .vue";
import TaxForecastTable from "./TaxForecastTable.vue";
import TaxForecastFilters from "./TaxForecastFilters.vue";
import EmptyState from "./components/EmptyState.vue";

import ActionEmptyState from "./actions/ActionEmptyState.vue";
import ViewBreakdown from "./actions/breakdown/ViewBreakdown.vue";
import EditInputs from "./actions/edit-inputs/EditInputs.vue";

export default {
    name: "IndexForecast",
    components: {
        TwoColLayout,
        TaxForecastTable,
        TaxForecastFilters,
        EmptyState,
        ActionEmptyState,
        ViewBreakdown,
        EditInputs,
    },
    props: {
        body: { type: Array, required: true },
        isApplyingToPayroll: { type: Boolean, default: false },
    },
    data() {
        return {
            search: "",
            selectedDivision: "",
            selectedUnit: "",
            token: localStorage.getItem("auth_token"),

            activeRow: null,
            selectedActionId: "empty",
            is_recomputing: false,
            recomputing_key: null,
            pending_focus_row_key: null,

            actions: [
                {
                    id: "empty",
                    name: "No Employee Selected",
                    component: markRaw(ActionEmptyState),
                },
                {
                    id: "breakdown",
                    name: "View Breakdown",
                    component: markRaw(ViewBreakdown),
                },
                {
                    id: "edit",
                    name: "Edit Inputs",
                    component: markRaw(EditInputs),
                },
            ],
        };
    },

    computed: {
        hasActiveFilters() {
            return (
                this.search.trim() !== "" ||
                this.selectedDivision !== "" ||
                this.selectedUnit !== ""
            );
        },

        divisions() {
            const set = new Set(
                this.body.map((r) => r.division).filter(Boolean),
            );
            return Array.from(set).sort();
        },

        units() {
            const base = this.selectedDivision
                ? this.body.filter((r) => r.division === this.selectedDivision)
                : this.body;

            const set = new Set(base.map((r) => r.unit).filter(Boolean));
            return Array.from(set).sort();
        },

        filteredRows() {
            const s = this.search.trim().toLowerCase();

            return this.body
                .filter((r) => {
                    const matchSearch =
                        !s ||
                        String(r.employee_no || "")
                            .toLowerCase()
                            .includes(s) ||
                        String(r.full_name || "")
                            .toLowerCase()
                            .includes(s);

                    const matchDivision =
                        !this.selectedDivision ||
                        r.division === this.selectedDivision;

                    const matchUnit =
                        !this.selectedUnit || r.unit === this.selectedUnit;

                    return matchSearch && matchDivision && matchUnit;
                })
                .slice()
                .sort((a, b) => this.compareBySurname(a, b));
        },

        selectedAction() {
            return (
                this.actions.find((a) => a.id === this.selectedActionId) || null
            );
        },
        selected_row_key() {
            return this.getRowUiKey(this.activeRow);
        },
    },

    watch: {
        selectedDivision() {
            this.selectedUnit = "";
        },
        body(newBody) {
            if (!this.activeRow) return;

            const activeKey = this.getEmployeeStableKey(this.activeRow);
            if (!activeKey) {
                this.deleteRow();
                return;
            }

            const matched = (newBody || []).find(
                (row) => this.getEmployeeStableKey(row) === activeKey,
            );

            if (!matched) {
                this.deleteRow();
                return;
            }

            this.activeRow = matched;
            const activeUiKey = this.getRowUiKey(matched);
            if (
                this.pending_focus_row_key &&
                activeUiKey &&
                this.pending_focus_row_key === activeUiKey
            ) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.pending_focus_row_key = null;
                    }, 350);
                });
            }
        },
    },

    methods: {
        getEmployeeStableKey(row) {
            return (
                (row?.employee_no !== undefined &&
                row?.employee_no !== null &&
                String(row.employee_no).trim() !== ""
                    ? `emp:${String(row.employee_no).trim()}`
                    : null) ??
                row?.id ??
                null
            );
        },
        getSurnameSortValue(row) {
            const explicitSurname =
                row?.surname ||
                row?.last_name ||
                row?.lastname ||
                row?.family_name;

            if (explicitSurname) {
                return String(explicitSurname).trim().toLowerCase();
            }

            const full = String(row?.full_name || "").trim();
            if (!full) return "";

            if (full.includes(",")) {
                return full.split(",")[0].trim().toLowerCase();
            }

            const parts = full.split(/\s+/).filter(Boolean);
            return (parts[parts.length - 1] || "").toLowerCase();
        },
        compareBySurname(a, b) {
            const surnameA = this.getSurnameSortValue(a);
            const surnameB = this.getSurnameSortValue(b);
            if (surnameA !== surnameB) return surnameA.localeCompare(surnameB);

            const nameA = String(a?.full_name || "").toLowerCase();
            const nameB = String(b?.full_name || "").toLowerCase();
            if (nameA !== nameB) return nameA.localeCompare(nameB);

            const empA = String(a?.employee_no || "");
            const empB = String(b?.employee_no || "");
            return empA.localeCompare(empB);
        },
        getRowUiKey(row) {
            const emp = String(row?.employee_no ?? "").trim();
            if (emp) return `emp-${emp}`;

            const id = String(row?.id ?? "").trim();
            if (id) return `id-${id}`;

            return null;
        },
        toRowUiKey(value) {
            if (value === null || value === undefined || value === "") return null;
            const raw = String(value).trim();
            if (!raw) return null;
            if (raw.startsWith("emp-") || raw.startsWith("id-") || raw.startsWith("fallback-")) {
                return raw;
            }
            if (raw.startsWith("emp:")) return `emp-${raw.slice(4)}`;
            if (/^\d+$/.test(raw)) return `id-${raw}`;
            return raw;
        },
        setAction(id, row = null) {
            this.selectedActionId = id;
            this.activeRow = row;
        },

        clearFilters() {
            this.search = "";
            this.selectedDivision = "";
            this.selectedUnit = "";
        },

        pullFromPayrollAndReconcile() {
            console.log("PULL FROM PAYROLL & RECONCILE");
        },

        viewRow(row) {
            this.setAction("breakdown", row);
        },

        editRow(row) {
            this.setAction("edit", row);
            console.log(row);
        },

        async recomputeRow(row) {
            if (!row?.id || this.is_recomputing) return;

            this.is_recomputing = true;
            this.recomputing_key = this.getRowUiKey(row);

            try {
                const response = await axios.get(
                    `/admin/taxation/recompute/${row.id}`,
                    {
                        headers: { Authorization: `Bearer ${this.token}` },
                    },
                );

                await Swal.fire({
                    title: "Recompute Started",
                    text:
                        response?.data?.message ||
                        "Employee forecast recomputation has been queued.",
                    icon: "success",
                });

                this.setAction("breakdown", row);
                this.pending_focus_row_key = this.getRowUiKey(row);
                this.$emit("refresh-forecast", {
                    source: "recompute",
                    employee_key: this.getEmployeeStableKey(row),
                    row_ui_key: this.getRowUiKey(row),
                    action: "breakdown",
                });
            } catch (error) {
                await Swal.fire({
                    title: "Error",
                    text:
                        error?.response?.data?.message ||
                        "Failed to recompute forecast.",
                    icon: "error",
                });
            } finally {
                this.is_recomputing = false;
                this.recomputing_key = null;
            }
        },
        handleRefreshForecast(payload = {}) {
            if (payload?.action && this.activeRow) {
                this.setAction(payload.action, this.activeRow);
            }
            const incomingFocusKey = this.toRowUiKey(
                payload?.row_ui_key ?? payload?.employee_key ?? null,
            );
            if (incomingFocusKey) {
                this.pending_focus_row_key = incomingFocusKey;
            }
            this.$emit("refresh-forecast", payload);
        },

        deleteRow() {
            this.setAction("empty", null);
        },
    },
};
</script>

<style>
/* ENTER: slide from right */
.slideInRight {
    animation: slideInRight 0.2s ease both;
}
@keyframes slideInRight {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

/* LEAVE: fade out */
.fadeOut {
    animation: fadeOut 0.1s ease both;
}
@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}
</style>
