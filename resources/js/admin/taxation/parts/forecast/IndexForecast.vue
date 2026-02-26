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
                @update:search="search = $event"
                @update:selectedDivision="selectedDivision = $event"
                @update:selectedUnit="selectedUnit = $event"
                @pull-reconcile="pullFromPayrollAndReconcile"
                @clear="clearFilters"
            />
        </template>

        <!-- LEFT SIDE -->
        <template #left>
            <div v-if="filteredRows.length">
                <TaxForecastTable
                    :rows="filteredRows"
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
                />
            </transition>
        </template>
    </TwoColLayout>
</template>

<script>
import TwoColLayout from "../../components/TwoColLayout .vue";
import TaxForecastTable from "./TaxForecastTable.vue";
import TaxForecastFilters from "./TaxForecastFilters.vue";
import EmptyState from "./components/EmptyState.vue";

import ActionEmptyState from "./actions/ActionEmptyState.vue";
import ViewBreakdown from "./actions/ViewBreakdown.vue";
import EditInputs from "./actions/EditInputs.vue";
// If you have these, import them. If not, remove edit/recompute handlers or point them to breakdown.
// import EditForecast from "./actions/EditForecast.vue";
// import RecomputeForecast from "./actions/RecomputeForecast.vue";

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
        // RecomputeForecast,
    },
    props: {
        body: { type: Array, required: true },
    },
    data() {
        return {
            search: "",
            selectedDivision: "",
            selectedUnit: "",

            // RIGHT PANEL STATE
            activeRow: null,
            selectedActionId: "empty",

            actions: [
                {
                    id: "empty",
                    name: "No Employee Selected",
                    component: ActionEmptyState,
                },
                {
                    id: "breakdown",
                    name: "View Breakdown",
                    component: ViewBreakdown,
                },

                {
                    id: "edit",
                    name: "Edit Inputs",
                    component: EditInputs,
                },

                // If you have separate components:
                // { id: "edit", name: "Edit Forecast", component: EditForecast },
                // { id: "recompute", name: "Recompute", component: RecomputeForecast },
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
            const set = new Set(this.body.map((r) => r.division).filter(Boolean));
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

            return this.body.filter((r) => {
                const matchSearch =
                    !s ||
                    String(r.employee_no || "").toLowerCase().includes(s) ||
                    String(r.full_name || "").toLowerCase().includes(s);

                const matchDivision =
                    !this.selectedDivision || r.division === this.selectedDivision;

                const matchUnit = !this.selectedUnit || r.unit === this.selectedUnit;

                return matchSearch && matchDivision && matchUnit;
            });
        },

        selectedAction() {
            return this.actions.find((a) => a.id === this.selectedActionId) || null;
        },
    },

    watch: {
        selectedDivision() {
            this.selectedUnit = "";
        },
    },

    methods: {
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
        },

        recomputeRow(row) {
            this.setAction("breakdown", row);
        },

        deleteRow(row) {
            this.setAction("empty", null);
        },
    },
};
</script>

<!-- IMPORTANT: keep this NOT scoped so transition classes apply -->
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