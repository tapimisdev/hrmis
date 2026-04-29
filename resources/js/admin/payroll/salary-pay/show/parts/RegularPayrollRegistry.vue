<template>
    <PayrollRegistryLayout
        :status="status"
        :payroll_no="payroll_no"
        :loading="loading"
        :downloads="[
            { key: 'registry', label: 'Payroll Registry' },
            { key: 'aut', label: 'Absences & Leaves' },
            { key: 'payslip', label: 'Payslip' },
        ]"
        @print="handlePrint"
    >
        <template #actions>
            <button
                v-if="canShowAutDeductionButton"
                type="button"
                class="toolbar-btn"
                :disabled="loading || autDeducted"
                @click="openAutDeductionModal"
            >
                <i
                    :class="[
                        'fa-solid',
                        autDeducted ? 'fa-circle-check' : 'fa-calendar-minus',
                    ]"
                ></i>
                {{ autDeducted ? "AUT Deducted" : "Deduct AUTs" }}
            </button>
        </template>

        <template #sheet-type>( PERMANENT )</template>
        <template #agency
            >TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE</template
        >
        <template #title>GENERAL PAYROLL FOR SALARY</template>

        <template #period>
            Period: <strong>{{ period_covered }}</strong>
        </template>

        <template #filters>
            <div class="payroll-filter-bar">
                <input
                    v-model.trim="searchTerm"
                    type="text"
                    class="payroll-filter-input"
                    placeholder="Search name or employee number"
                />

                <select
                    v-model="selectedPosition"
                    class="payroll-filter-select"
                >
                    <option value="">All positions</option>
                    <option
                        v-for="position in positionOptions"
                        :key="position"
                        :value="position"
                    >
                        {{ position }}
                    </option>
                </select>

                <select v-model="remarksFilter" class="payroll-filter-select">
                    <option value="all">All remarks</option>
                    <option value="with">With remarks</option>
                    <option value="without">Without remarks</option>
                </select>

                <div class="payroll-filter-meta">
                    Showing {{ filteredEmployees.length }} of
                    {{ employees.length }}
                </div>
            </div>
        </template>

        <template #table>
            <table class="excel-table">
                <thead>
                    <tr class="header-labels">
                        <th>Emp#</th>
                        <th>Name / Position</th>
                        <th>Monthly <br />Rate</th>
                        <th>Salary <br />Grade</th>
                        <th>Overtime</th>
                        <th>Holiday <br />Excess</th>

                        <th
                            v-for="deduction in dynamicDeductions"
                            :key="`head-deduction-${deduction}`"
                            class="deduction text-center"
                            style="
                                min-width: 120px;
                                
                                max-width: 300px;
                            "
                        >
                            {{ deduction }}
                        </th>

                        <th
                            class="deduction text-center"
                            style="
                                min-width: 120px;
                                
                                max-width: 300px;
                            "
                        >
                            Total Deductions
                        </th>

                        <th style="min-width: 150px">Adjustment</th>

                        <th
                            class="net-salary text-center"
                            style="
                                min-width: 120px;
                                max-width: 300px;
                            "
                        >
                            15th
                        </th>

                        <th
                            class="net-salary text-center"
                            style="
                                min-width: 120px;
                                max-width: 300px;
                            "
                        >
                            30th
                        </th>

                        <th
                            class="net-salary text-center"
                            style="
                                min-width: 120px;
                                max-width: 300px;
                            "
                        >
                            Net <br />Salary
                        </th>

                        <th
                            style="
                                min-width: 200px;
                                max-width: 300px;
                                text-align: center;
                            "
                        >
                            Remarks
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="emp in filteredEmployees"
                        :key="emp.id"
                        class="data-row"
                    >
                        <td class="text-center" style="min-width: 100px">
                            {{ emp.employee_no }}
                        </td>

                        <td class="name-cell" style="min-width: 200px">
                            <div class="employee-name">{{ emp.name }}</div>
                            <div class="employee-position">
                                {{ emp.position }}
                            </div>
                        </td>

                        <td class="text-center" style="min-width: 120px">
                            {{ formatMoney(emp.monthly_rate) }}
                        </td>

                        <td class="text-center" style="min-width: 120px">
                            {{ emp.salary_grade }}
                        </td>

                        <td class="text-center" style="min-width: 120px">
                            {{ formatMoney(emp.overtime) }}
                        </td>

                        <td class="text-center" style="min-width: 120px">
                            {{ formatMoney(emp.holiday) }}
                        </td>

                        <td
                            v-for="deduction in dynamicDeductions"
                            :key="`row-${emp.id}-deduction-${deduction}`"
                            class="number-cell deduction text-center"
                        >
                            {{
                                formatMoney(getDeductionAmount(emp, deduction))
                            }}
                        </td>

                        <td class="text-center deduction">
                            {{ formatMoney(emp.total_deductions) }}
                        </td>

                        <td class="number-cell">
                            <input
                                v-model.number="emp.salary_adjustment"
                                type="number"
                                class="form-control border-0 bg-body"
                                style="
                                    min-width: 150px;
                                    width: 100%;
                                    max-width: 300px;
                                    text-align: center;
                                "
                                @change="adjustRow(emp)"
                            />
                        </td>

                        <td class="text-center">
                            {{ formatMoney(netSalary15th(emp)) }}
                        </td>

                        <td class="text-center">
                            {{ formatMoney(netSalary30th(emp)) }}
                        </td>

                        <td class="text-center">
                            {{ formatMoney(emp.net_pay) }}
                        </td>

                        <td class="text-center">
                            <textarea
                                v-model="emp.remarks"
                                class="form-control border-0 bg-body"
                                @change="adjustRow(emp)"
                            ></textarea>
                        </td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                @click="$emit('delete', emp)"
                                title="Delete"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <tr v-if="!filteredEmployees.length">
                        <td
                            :colspan="
                                baseColumnCount + dynamicDeductions.length
                            "
                            class="text-center py-3"
                        >
                            No employees found for the selected filters.
                        </td>
                    </tr>
                </tbody>

                <tfoot>
                    <tr class="grand-total text-center">
                        <td colspan="2" class="text-end">
                            <strong>GRAND TOTAL</strong>
                        </td>
                        <td class="number-cell">
                            {{ formatMoney(grandTotals("monthly_rate")) }}
                        </td>
                        <td class="number-cell">-</td>
                        <td class="number-cell">
                            {{ formatMoney(grandTotals("overtime")) }}
                        </td>
                        <td class="number-cell">
                            {{ formatMoney(grandTotals("holiday")) }}
                        </td>

                        <td
                            v-for="deduction in dynamicDeductions"
                            :key="`grand-deduction-${deduction}`"
                            class="number-cell deduction"
                        >
                            {{
                                formatMoney(
                                    grandTotals("deductions", deduction),
                                )
                            }}
                        </td>

                        <td class="number-cell">
                            {{ formatMoney(grandTotals("total_deductions")) }}
                        </td>
                        <td class="number-cell earning">
                            {{ formatMoney(grandTotals("salary_adjustment")) }}
                        </td>
                        <td class="number-cell net-salary">
                            {{ formatMoney(grandTotals("net_salary_15th")) }}
                        </td>
                        <td class="number-cell net-salary">
                            {{ formatMoney(grandTotals("net_salary_30th")) }}
                        </td>
                        <td class="number-cell net-salary">
                            <strong>{{
                                formatMoney(grandTotals("net_pay"))
                            }}</strong>
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </template>
    </PayrollRegistryLayout>

    <ModalVue
        ref="autDeductionModal"
        id="regular-aut-deduction-modal"
        title="Deduct AUTs"
        subtitle="Review and apply leave-credit deductions for regular salary payroll."
        header-icon="fa-solid fa-calendar-minus"
        size="modal-xl"
    >
        <template #default>
            <div class="modal-body">
                <div
                    v-if="autModalMessage.text"
                    class="alert aut-modal-message mb-3"
                    :class="`alert-${autModalMessage.type}`"
                >
                    {{ autModalMessage.text }}
                </div>

                <div class="aut-modal-meta">
                    <div class="aut-stat-card">
                        <div class="aut-stat-label">AUT Range</div>
                        <div class="aut-stat-value">{{ autRangeLabel }}</div>
                    </div>
                    <div class="aut-stat-card">
                        <div class="aut-stat-label">Employees</div>
                        <div class="aut-stat-value">{{ autDeductionRows.length }}</div>
                    </div>
                    <div class="aut-stat-card">
                        <div class="aut-stat-label">Total AUT</div>
                        <div class="aut-stat-value">{{ formatMoney(autTotalAmount) }}</div>
                    </div>
                    <div class="aut-stat-card">
                        <div class="aut-stat-label">Total VL</div>
                        <div class="aut-stat-value">{{ formatEquivalent(autTotalEquivalent) }}</div>
                    </div>
                </div>

                <p class="text-body-secondary small mb-3">
                    This deducts AUTs from vacation leave credits only. Regular salary totals remain unchanged.
                </p>

                <div v-if="autModalLoading" class="aut-table-loading text-center text-body-secondary">
                    <span class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></span>
                    <div>Loading AUT deduction records...</div>
                </div>

                <div v-else-if="!autDeductionRows.length" class="py-5 text-center text-body-secondary">
                    No absences, lates, or undertime were found for this payroll period.
                </div>

                <div v-else class="table-responsive aut-deduction-table-wrapper">
                    <table class="table table-sm table-striped table-bordered align-middle aut-deduction-table">
                        <thead>
                            <tr>
                                <th>Emp#</th>
                                <th>Name / Position</th>
                                <th class="text-end">AUT Amount</th>
                                <th class="text-end">VL Deduction</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in autDeductionRows" :key="row.pspe_id">
                                <td>{{ row.employee_no }}</td>
                                <td>
                                    <div class="fw-bold">{{ row.name }}</div>
                                    <div class="aut-employee-position">{{ row.position }}</div>
                                </td>
                                <td class="text-end">{{ formatMoney(row.aut) }}</td>
                                <td class="text-end">
                                    <input
                                        v-model.number="row.equivalent"
                                        type="number"
                                        min="0"
                                        step="0.001"
                                        class="form-control form-control-sm aut-equivalent-input text-end"
                                        :disabled="savingAut || autDeducted"
                                        @blur="normalizeEquivalent(row)"
                                    />
                                </td>
                                <td>{{ row.remarks }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    :disabled="savingAut"
                    @click="closeAutDeductionModal"
                >
                    Close
                </button>
                <button
                    v-if="!autDeducted"
                    type="button"
                    class="btn btn-outline-primary"
                    :disabled="autModalLoading || savingAut"
                    @click="fetchAutDeductions"
                >
                    Refresh
                </button>
                <button
                    v-if="!autDeducted"
                    type="button"
                    class="btn btn-primary"
                    :disabled="!canApplyAutDeductions"
                    @click="confirmAutDeductions"
                >
                    <span v-if="savingAut" class="d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Applying...
                    </span>
                    <span v-else>Apply Deductions</span>
                </button>
            </div>
        </template>
    </ModalVue>

</template>

<script>
import axios from "axios";
import ModalVue from "../../../../../components/ModalVue.vue";
import PayrollRegistryLayout from "../../../PayrollRegistryLayout.vue";

export default {
    name: "PermanentPayrollRegistry",
    components: { ModalVue, PayrollRegistryLayout },
    props: {
        employees: {
            type: Array,
            required: true,
            default: () => [],
        },
        status: {
            type: String,
            required: true,
        },
        payroll_no: {
            type: String,
            required: true,
        },
        payroll_id: {
            type: [Number, String],
            required: true,
        },
        is_aut_deducted: {
            type: Boolean,
            default: false,
        },
        period_covered: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            token: localStorage.getItem("auth_token") || "",
            loading: false,
            searchTerm: "",
            selectedPosition: "",
            remarksFilter: "all",
            autDeducted: this.is_aut_deducted,
            autModalLoading: false,
            savingAut: false,
            autDeductionRows: [],
            autMeta: {},
            autModalMessage: {
                type: "info",
                text: "",
            },
            baseColumnCount: 14, // Emp#, Name, Monthly, SG, Overtime, Holiday, deductions, adjustment, 15th, 30th, net, remarks, actions
        };
    },
    computed: {
        positionOptions() {
            const positions = new Set();

            this.employees.forEach((emp) => {
                if (emp.position) positions.add(emp.position);
            });

            return Array.from(positions).sort((a, b) => a.localeCompare(b));
        },

        filteredEmployees() {
            const keyword = this.searchTerm.toLowerCase();

            return this.employees.filter((emp) => {
                const name = String(emp.name || "").toLowerCase();
                const employeeNo = String(emp.employee_no || "").toLowerCase();
                const remarks = String(emp.remarks || "").trim();

                const matchesSearch =
                    !keyword ||
                    name.includes(keyword) ||
                    employeeNo.includes(keyword);

                const matchesPosition =
                    !this.selectedPosition ||
                    emp.position === this.selectedPosition;

                const hasRemarks = Boolean(remarks);
                const matchesRemarks =
                    this.remarksFilter === "all" ||
                    (this.remarksFilter === "with" && hasRemarks) ||
                    (this.remarksFilter === "without" && !hasRemarks);

                return matchesSearch && matchesPosition && matchesRemarks;
            });
        },

        dynamicDeductions() {
            const names = new Set();

            this.employees.forEach((emp) => {
                (emp.deductions || []).forEach((d) => {
                    if (d.deduction_type) {
                        names.add(d.deduction_type);
                    }
                });
            });

            return Array.from(names).sort((a, b) => a.localeCompare(b));
        },

        isPayrollApproved() {
            return this.status === "approved";
        },

        canShowAutDeductionButton() {
            return this.isPayrollApproved;
        },

        autRangeLabel() {
            return (
                this.autMeta.range_label ||
                this.autMeta.period_covered ||
                this.period_covered ||
                "N/A"
            );
        },

        autTotalAmount() {
            return this.autDeductionRows.reduce(
                (total, row) => total + this.toNumber(row.aut),
                0,
            );
        },

        autTotalEquivalent() {
            return this.autDeductionRows.reduce(
                (total, row) => total + this.toNumber(row.equivalent),
                0,
            );
        },

        canApplyAutDeductions() {
            return (
                !this.autModalLoading &&
                !this.savingAut &&
                !this.autDeducted &&
                this.isPayrollApproved &&
                this.autDeductionRows.length > 0
            );
        },

    },
    methods: {
        handlePrint() {
            window.print();
        },

        toNumber(value) {
            const num = Number(value);
            return Number.isFinite(num) ? num : 0;
        },

        formatMoney(value) {
            return new Intl.NumberFormat("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(this.toNumber(value));
        },

        formatEquivalent(value) {
            return new Intl.NumberFormat("en-PH", {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3,
            }).format(this.toNumber(value));
        },

        getDeductionAmount(emp, type) {
            const deduction = (emp.deductions || []).find(
                (d) => d.deduction_type === type,
            );

            return deduction ? this.toNumber(deduction.amount) : 0;
        },

        netSalary15th(emp) {
            if (emp.net_salary_15th !== undefined && emp.net_salary_15th !== null) {
                return this.toNumber(emp.net_salary_15th);
            }

            return Number((this.toNumber(emp.net_pay) / 2).toFixed(2));
        },

        netSalary30th(emp) {
            if (emp.net_salary_30th !== undefined && emp.net_salary_30th !== null) {
                return this.toNumber(emp.net_salary_30th);
            }

            return Number((this.toNumber(emp.net_pay) - this.netSalary15th(emp)).toFixed(2));
        },

        grandTotals(field, subfield = null) {
            return this.filteredEmployees.reduce((total, emp) => {
                if (field === "deductions" && subfield) {
                    return total + this.getDeductionAmount(emp, subfield);
                }

                if (field === "net_salary_15th") {
                    return total + this.netSalary15th(emp);
                }

                if (field === "net_salary_30th") {
                    return total + this.netSalary30th(emp);
                }

                return total + this.toNumber(emp[field]);
            }, 0);
        },

        async adjustRow(emp) {
            this.loading = true;

            try {
                await axios.post(
                    `/api/payroll/salary-pay/items/${this.payroll_no}/${emp.id}`,
                    {
                        adjustment: this.toNumber(emp.salary_adjustment),
                        remarks: emp.remarks || "",
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                        },
                    },
                );

                this.$emit("fetch_data");
            } catch (error) {
            } finally {
                this.loading = false;
            }
        },

        openAutDeductionModal() {
            if (!this.isPayrollApproved) return;

            this.$refs.autDeductionModal.open();
            this.fetchAutDeductions();
        },

        closeAutDeductionModal() {
            this.$refs.autDeductionModal.close();
        },

        async fetchAutDeductions() {
            this.autModalLoading = true;
            this.autModalMessage = { type: "info", text: "" };

            try {
                const response = await axios.get(
                    `/api/payroll/salary-pay/${this.payroll_id}/aut-deductions/preview`,
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: "application/json",
                        },
                    },
                );

                const rows = response.data.data || [];
                const meta = response.data.meta || {};
                this.autMeta = meta;

                if (meta.is_aut_deducted) {
                    this.autDeducted = true;
                }

                this.autDeductionRows = rows.map((row) => ({
                    ...row,
                    equivalent: this.toNumber(row.equivalent).toFixed(3),
                }));
            } catch (error) {
                this.autDeductionRows = [];
                this.autModalMessage = {
                    type: "danger",
                    text:
                        error.response?.data?.message ||
                        "Unable to load AUT deduction records. Please try again.",
                };
            } finally {
                this.autModalLoading = false;
            }
        },

        normalizeEquivalent(row) {
            row.equivalent = Math.max(
                0,
                this.toNumber(row.equivalent),
            ).toFixed(3);
        },

        async confirmAutDeductions() {
            const result = await Swal.fire({
                title: "Deduct AUTs?",
                text: "This will apply the displayed VL deductions to the employees' leave credits.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Apply deductions",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#0d6efd",
            });

            if (!result.isConfirmed) return;

            await this.applyAutDeductions();
        },

        async applyAutDeductions() {
            this.savingAut = true;
            this.autModalMessage = { type: "info", text: "" };

            const rows = this.autDeductionRows.map((row) => ({
                pspe_id: row.pspe_id,
                equivalent: this.toNumber(row.equivalent),
            }));

            try {
                const response = await axios.post(
                    `/api/payroll/salary-pay/${this.payroll_id}/aut-deductions/apply`,
                    { rows },
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: "application/json",
                        },
                    },
                );

                this.autDeducted = true;
                this.$emit("fetch_data");

                this.autModalMessage = {
                    type: "success",
                    text:
                        response.data?.message ||
                        "AUT deductions were applied successfully.",
                };
            } catch (error) {
                this.autModalMessage = {
                    type: "danger",
                    text:
                        error.response?.data?.message ||
                        "Please try again.",
                };
            } finally {
                this.savingAut = false;
                this.autModalLoading = false;
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.excel-table {
    min-width: 100%;
    border-collapse: collapse;
}

.earning,
.deduction {
    max-width: 96px;
    overflow-wrap: anywhere;
    white-space: normal;
}

.net-salary {
    font-weight: 700;
}

.data-row .name-cell .employee-name {
    font-weight: 700;
}

.data-row .name-cell .employee-position {
    font-size: 10px;
}

.grand-total {
    font-weight: 700;
}

.aut-modal-meta {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.aut-stat-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.75rem;
    background: rgba(var(--bs-secondary-rgb), 0.08);
}

.aut-stat-label {
    color: var(--bs-secondary-color);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
}

.aut-stat-value {
    font-size: 1rem;
    font-weight: 700;
}

.aut-modal-message {
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
}

.aut-table-loading {
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.aut-table-loading .spinner-border {
    margin-left: auto;
    margin-right: auto;
}

.aut-deduction-table-wrapper {
    max-height: 55vh;
}

.aut-deduction-table thead th {
    background: var(--bs-body-bg);
    position: sticky;
    top: 0;
    z-index: 1;
}

.aut-employee-position {
    color: var(--bs-secondary-color);
    font-size: 0.72rem;
}

.aut-equivalent-input {
    margin-left: auto;
    max-width: 120px;
    min-width: 92px;
}

@media (max-width: 768px) {
    .aut-modal-meta {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
