<template>
    <!-- Header -->
    <div
        class="card-header d-md-flex justify-content-between align-items-center pb-3"
    >
        <div>
            <Printables />
            <button
                v-if="parent_table.slug != 'longetivity-pay'"
                @click="handleOpenaddModal"
                class="btn bg-warning text-black mt-3"
            >
                <i class="fa-solid fa-plus me-1"></i>
                Bulk
            </button>

            <button
                @click="toggleComputationMode"
                class="btn btn-outline-primary mt-3 ms-2"
            >
                <i class="fa-solid fa-calculator me-1"></i>
                {{ computationMode ? "Close Computations" : "Apply Computations" }}
            </button>

            <!-- Modal -->
            <ModalVue
                ref="addModal"
                headerIcon="fa-solid fa-plus"
                :title="parent_table.name"
                id="add-modal"
                size="modal-md"
                subtitle="Add employee's loan/deduction in here by range."
                type="default"
                v-if="parent_table.slug != 'longetivity-pay'"
            >
                <AddAmountForm
                    ref="addForm"
                    :module_tab="parent_table.slug"
                    @cancel="$refs.addModal.close()"
                    @success="handleAddAmountSuccess"
                    :id="parent_table.id"
                    :year="year"
                    :url="`/admin/payroll-components/${parent_table.slug}/bulk/employees`"
                />
            </ModalVue>
        </div>

        <div class="fw-bold display-6">{{ year }}</div>

        <div
            class="search-pill mt-3 d-flex align-items-center px-2 py-1 bg-body-bg rounded-pill"
        >
            <i class="fa-solid fa-magnifying-glass me-2"></i>
            <input
                type="text"
                class="form-control border px-3 py-1 small-text"
                v-model="search"
                @input="filteredItems"
                placeholder="Search"
                style="max-width: 220px"
            />
        </div>
    </div>

    <div
        class="shadow-sm border rounded-3 overflow-hidden modern-card position-relative"
    >
        <div v-if="computationMode" class="computation-toolbar border-bottom p-3 bg-body-tertiary">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div class="computation-help">
                    <div class="fw-bold">Computation Mode</div>
                    <div class="small text-muted">
                        Select month cells in the table, choose an operation, then apply it to all selected cells.
                    </div>
                    <div class="small text-muted">
                        {{ selectedCells.length }} cell{{ selectedCells.length === 1 ? "" : "s" }} selected
                    </div>
                </div>

                <div>
                    <label class="form-label small fw-bold mb-1">Operation</label>
                    <select v-model="computation.operation" class="form-select form-select-sm">
                        <option value="set">Set (=)</option>
                        <option value="add">Add (+)</option>
                        <option value="subtract">Subtract (-)</option>
                        <option value="multiply">Multiply (x)</option>
                        <option value="divide">Divide (/)</option>
                        <option value="percent_add">Increase by %</option>
                        <option value="percent_subtract">Decrease by %</option>
                        <option value="power">Power (^)</option>
                        <option value="round">Round</option>
                        <option value="ceil">Ceiling</option>
                        <option value="floor">Floor</option>
                        <option value="min">Minimum</option>
                        <option value="max">Maximum</option>
                        <option value="abs">Absolute</option>
                        <option value="negate">Negate (+/-)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label small fw-bold mb-1">Value</label>
                    <input
                        v-model="computation.value"
                        type="number"
                        step="0.01"
                        class="form-control form-control-sm"
                        placeholder="Enter value"
                    />
                </div>

                <div class="d-flex gap-2">
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        :disabled="selectedCells.length === 0 || requiresOperand && computation.value === ''"
                        @click="applyComputation"
                    >
                        Apply
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        :disabled="selectedCells.length === 0"
                        @click="clearSelectedCells"
                    >
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper custom-scrollbar">
            <LoaderVue
                :visible="loading"
                :hasBackground="true"
                status="loading"
                message="loading, please wait..."
            />
            <table class="table table-hover mb-0 compact-table">
                <thead>
                    <tr>
                        <th class="sticky-col ps-1">Employee</th>
                        <th class="sticky-header text-end gradient-text">
                            TOTAL
                        </th>
                        <th
                            class="sticky-header text-end text-muted-light"
                            v-for="month in months"
                            :key="month"
                        >
                            {{ month }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="filtered.length === 0">
                        <td colspan="14" class="text-center">
                            <div class="alert alert-secondary mx-2 mt-2 py-4">
                                No employee(s) found.
                            </div>
                        </td>
                    </tr>

                    <!-- Employee rows -->
                    <tr
                        v-for="item in filtered"
                        :key="item.employee_no"
                        :data-employee_no="item.employee_no"
                        class="row-hover"
                    >
                        <td
                            class="sticky-col border-end ps-3"
                            :class="{
                                'bg-primary fw-bold':
                                    selected_employee === item.employee_no,
                            }"
                        >
                            <div class="d-flex align-items-center">
                                <div class="avatar">
                                    {{ item.firstname?.charAt(0) || "N"
                                    }}{{ item.lastname?.charAt(0) || "A" }}
                                </div>
                                <div class="ms-2">
                                    <span
                                        class="fw-bold text-body text-nowrap me-2"
                                    >
                                        {{ item.lastname ?? "-------" }},
                                        {{ item.firstname ?? "-------" }}
                                    </span>
                                    <br />
                                    <span class="badge text-body">{{
                                        item.employee_no
                                    }}</span>
                                    <span class="badge tiny-badge me-2">{{
                                        item.division_code
                                    }}</span>
                                </div>
                            </div>
                        </td>

                        <td
                            class="text-end total-score"
                            :class="{
                                'bg-primary fw-bold':
                                    selected_employee === item.employee_no,
                            }"
                        >
                            {{ line_total(item) }}
                        </td>

                        <td
                            v-for="monthKey in monthKeys"
                            :key="monthKey"
                            :class="{
                                'bg-primary fw-bold':
                                    selected_employee === item.employee_no,
                                'selected-computation-cell': isCellSelected(item.employee_no, monthKey),
                            }"
                            @click="handleCellSelection(item, monthKey, $event)"
                        >
                            <input
                                type="number"
                                v-model="item[monthKey]"
                                :readonly="computationMode"
                                @change="
                                    create_update(
                                        item[monthKey + '_id'],
                                        item[monthKey],
                                        monthKey,
                                        item.employee_no,
                                    )
                                "
                                class="border-less-input"
                            />
                        </td>
                    </tr>

                    <!-- Grand total row -->
                    <tr v-if="filtered.length > 0" class="grand-total">
                        <td class="sticky-col text-end fw-bold bg-body-color">
                            Grand Total
                        </td>
                        <td class="text-end">
                            {{ formatNumber(total_all_line_total()) }}
                        </td>
                        <td v-for="monthKey in monthKeys" :key="monthKey">
                            <div class="text-end">
                                {{ formatNumber(grand_total(monthKey)) }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import LoaderVue from "../../components/LoaderVue.vue";
import Printables from "../../components/Printables.vue";
import axios from "axios";
import AddAmountForm from "./AddAmountForm.vue";
import ModalVue from "../../components/ModalVue.vue";

export default {
    name: "PayrollEmployeeComponentForm",
    components: { LoaderVue, Printables, AddAmountForm, ModalVue },
    props: {
        selected_employee: { type: String, required: false },
        url: { type: String, required: true },
        parent_table: { type: Object, required: true },
        year: { type: String, required: true },
    },
    data() {
        return {
            months: [
                "Jan",
                "Feb",
                "Mar",
                "Apr",
                "May",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec",
            ],
            monthKeys: [
                "january",
                "february",
                "march",
                "april",
                "may",
                "june",
                "july",
                "august",
                "september",
                "october",
                "november",
                "december",
            ],
            items: [],
            filtered: [],
            search: "",
            loading: false,
            computationMode: false,
            computation: {
                operation: "add",
                value: 0,
            },
            selectedCells: [],
        };
    },
    computed: {
        requiresOperand() {
            return !["round", "ceil", "floor", "abs", "negate"].includes(this.computation.operation);
        },
    },
    created() {
        this.fetchTable();
    },
    methods: {
        handleOpenaddModal() {
            this.$refs.addModal.open();
            this.$refs.addForm.resetForm();
        },
        handleAddAmountSuccess() {
            this.$refs.addModal.close();
            this.fetchTable();
        },
        fetchTable() {
            this.loading = true;
            return axios
                .get(this.url)
                .then((res) => {
                    this.items = res.data;
                    this.filtered = res.data;
                    this.clearSelectedCells();
                    this.filteredItems();
                })
                .catch((error) => {
                    ErrorToast.fire({
                        title:
                            error.response?.data?.message ||
                            "An error occurred",
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        create_update(id, amount, month, employee_no) {
            return this.persistCellUpdate(id, amount, month, employee_no, true, true);
        },
        persistCellUpdate(id, amount, month, employee_no, shouldRefresh = false, showToast = true) {
            this.loading = true;
            return axios
                .post(this.url, { id, amount, month, employee_no })
                .then((res) => {
                    if (showToast) {
                        SuccesToast.fire({
                            title: res.data.message || "successfully added!",
                        });
                    }

                    if (shouldRefresh) {
                        this.fetchTable();
                    }

                    return res;
                })
                .catch((error) => {
                    if (showToast) {
                        ErrorToast.fire({
                            title:
                                error.response?.data?.message ||
                                "An error occurred",
                        });
                    }

                    throw error;
                })
                .finally(() => {
                    if (!shouldRefresh) {
                        this.loading = false;
                    }
                });
        },
        line_total(employee) {
            let line_total = 0;

            this.monthKeys.forEach((month) => {
                line_total += parseFloat(employee[month]) || 0;
            });

            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(line_total);
        },
        total_all_line_total() {
            return this.filtered.reduce(
                (sum, item) => sum + this.line_total(item),
                0,
            );
        },
        grand_total(month) {
            return this.filtered.reduce(
                (sum, item) => sum + (parseFloat(item[month]) || 0),
                0,
            );
        },
        formatNumber(number) {
            return Number(number).toLocaleString();
        },
        filteredItems() {
            const query = (this.search ?? "").toLowerCase().trim();
            this.filtered = !query
                ? this.items
                : this.items.filter(
                      (item) =>
                          (item.firstname ?? "")
                              .toLowerCase()
                              .includes(query) ||
                          (item.lastname ?? "").toLowerCase().includes(query) ||
                          (item.division_code ?? "")
                              .toLowerCase()
                              .includes(query) ||
                          (item.division_name ?? "")
                              .toLowerCase()
                              .includes(query),
                  );
        },
        toggleComputationMode() {
            this.computationMode = !this.computationMode;

            if (!this.computationMode) {
                this.clearSelectedCells();
            }
        },
        cellSelectionKey(employeeNo, monthKey) {
            return `${employeeNo}:${monthKey}`;
        },
        isCellSelected(employeeNo, monthKey) {
            return this.selectedCells.some(
                (cell) => cell.key === this.cellSelectionKey(employeeNo, monthKey)
            );
        },
        handleCellSelection(item, monthKey, event) {
            if (!this.computationMode) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const key = this.cellSelectionKey(item.employee_no, monthKey);
            const index = this.selectedCells.findIndex((cell) => cell.key === key);

            if (index >= 0) {
                this.selectedCells.splice(index, 1);
                return;
            }

            this.selectedCells.push({
                key,
                employee_no: item.employee_no,
                record_id: item[monthKey + "_id"],
                monthKey,
            });
        },
        clearSelectedCells() {
            this.selectedCells = [];
        },
        resetComputationForm() {
            this.computation.operation = "add";
            this.computation.value = 0;
        },
        computeCellValue(currentValue) {
            const baseValue = parseFloat(currentValue) || 0;
            const operand = parseFloat(this.computation.value);

            if (this.requiresOperand && !Number.isFinite(operand)) {
                throw new Error("Please enter a valid computation value.");
            }

            if (this.computation.operation === "divide" && operand === 0) {
                throw new Error("Cannot divide by zero.");
            }

            if (this.computation.operation === "power" && baseValue === 0 && operand < 0) {
                throw new Error("Cannot raise zero to a negative power.");
            }

            const nextValue = {
                set: operand,
                add: baseValue + operand,
                subtract: baseValue - operand,
                multiply: baseValue * operand,
                divide: baseValue / operand,
                percent_add: baseValue + (baseValue * (operand / 100)),
                percent_subtract: baseValue - (baseValue * (operand / 100)),
                power: baseValue ** operand,
                round: Math.round(baseValue),
                ceil: Math.ceil(baseValue),
                floor: Math.floor(baseValue),
                min: Math.min(baseValue, operand),
                max: Math.max(baseValue, operand),
                abs: Math.abs(baseValue),
                negate: baseValue * -1,
            }[this.computation.operation];

            return Number.isFinite(nextValue) ? Number(nextValue.toFixed(2)) : baseValue;
        },
        async applyComputation() {
            if (this.selectedCells.length === 0) {
                ErrorToast.fire({ title: "Select at least one cell first." });
                return;
            }

            this.loading = true;

            try {
                const updates = this.selectedCells.map((cell) => {
                    const row = this.items.find((item) => item.employee_no === cell.employee_no);

                    if (!row) {
                        return Promise.resolve();
                    }

                    const nextValue = this.computeCellValue(row[cell.monthKey]);
                    row[cell.monthKey] = nextValue;

                    return this.persistCellUpdate(
                        row[cell.monthKey + "_id"],
                        nextValue,
                        cell.monthKey,
                        row.employee_no,
                        false,
                        false
                    );
                });

                await Promise.all(updates);
                this.clearSelectedCells();
                this.resetComputationForm();
                await this.fetchTable();
                SuccesToast.fire({
                    title: "Computation applied successfully.",
                });
            } catch (error) {
                ErrorToast.fire({
                    title:
                        error?.message ||
                        error?.response?.data?.message ||
                        "Failed to apply computation.",
                });
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.small-title {
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    color: var(--bs-body-color, #212529);
}
.tiny-text {
    font-size: 0.7rem;
    color: var(--bs-secondary-text, #6c757d);
}
.search-pill input {
    font-size: 0.75rem;
    color: var(--bs-body-color, #212529);
}

.computation-toolbar {
    position: sticky;
    top: 0;
    z-index: 25;
}

.computation-help {
    min-width: 220px;
}

/* Table */
.table-wrapper {
    max-height: 70vh;
    overflow: auto;
}
.compact-table {
    border-collapse: separate;
    th,
    td {
        padding: 0.4rem 0.625rem;
        font-size: 0.7rem;
    }
    th {
        background: var(--bs-body-bg, #fff);
        font-weight: 600;
        text-transform: uppercase;
        color: var(--bs-body-color, #212529);
    }
}

/* Sticky & Glass */
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--bs-body-bg, #fff);
}
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 20;
    background: var(--bs-body-bg, #fff);
    min-width: 320px;
}

.gradient-text {
    color: var(--bs-body-bg);
}
.total-score {
    background: rgba(var(--bs-primary-rgb, 79, 70, 229), 0.1);
    color: var(--bs-primary);
    padding: 0.125rem 0.5rem;
    font-weight: 700;
    font-size: 0.8rem;
}

/* Avatar */
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 100%;
    background-color: var(--bs-secondary);
    color: var(--bs-body-bg);
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Badge */
.tiny-badge {
    font-size: 0.6rem;
    padding: 0.125rem 0.375rem;
    background: var(--bs-body-color);
    color: var(--bs-body-bg);
}

/* Hover effect */
.row-hover {
    &:hover {
        background: transparent;
    }
}

/* Mono font for data cells */
.mono-cell {
    color: var(--bs-body-color);
    font-size: 0.72rem;
}

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: var(--bs-body-secondary);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--bs-primary);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--bs-primary);
}

.border-less-input {
    margin: 0;
    padding: 0;
    height: 100%;
    width: 100%;
    // width: 86px;
    background-color: var(--bs-transparent);
    border: none;
    outline: none;
    text-align: end;
    &:focus,
    &:active {
        border: none;
        outline: none;
        box-shadow: none;
    }
    /* Chrome, Edge, Safari */
    &::-webkit-outer-spin-button,
    &::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    -moz-appearance: textfield;
}

td.selected-computation-cell {
    background: transparent !important;
    box-shadow: inset 0 0 0 2px #0d6efd;
}

.row-hover:hover td.selected-computation-cell {
    background: transparent !important;
}

.row-hover:hover td.selected-computation-cell .border-less-input {
    background-color: transparent !important;
}

.grand-total {
    td {
        background-color: var(--bs-secondary-bg);
        font-weight: bolder;

        &:not(:first-child) {
            background-color: var(--bs-secondary-bg);
            color: var(--bs-body-color);
        }
    }
}

@media (max-width: 768px) {
    .sticky-header,
    .sticky-col {
        position: static; /* remove sticky */
        z-index: auto;
    }

    .sticky-col {
        min-width: auto; /* allow full width on small screens */
    }
}
</style>
