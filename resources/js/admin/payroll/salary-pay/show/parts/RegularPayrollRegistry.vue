<template>
    <PayrollRegistryLayout
        :status="status"
        :payroll_no="payroll_no"
        :loading="loading"
        :downloads="[
            { key: 'registry', label: 'Payroll Registry' },
            { key: 'payslip', label: 'Payslip' },
        ]"
        @print="handlePrint"
    >
        <template #actions>
            <button
                type="button"
                class="toolbar-btn left"
                :disabled="loading || autModalLoading || savingAut"
                @click="openAutDeductionModal"
            >
                <i class="fa-solid fa-calculator"></i>
                Deduct AUTs
            </button>
        </template>

        <template #summary>
            <div class="aut-status-banner" :class="autStatusBannerClass">
                <div class="aut-status-copy">
                    <span class="aut-status-label">AUT deduction status</span>
                    <strong>{{ autStatusLabel }}</strong>
                </div>
                <div class="aut-status-note">
                    {{ autStatusDescription }}
                </div>
            </div>
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
                        <th class="deduction">AUT</th>
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
                        <th>actions</th>
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

                        <td class="text-center deduction" style="min-width: 120px">
                            {{ formatMoney(emp.aut ?? emp.ut) }}
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
                        <td class="number-cell deduction">
                            {{ formatMoney(grandTotals("autLike")) }}
                        </td>
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
        id="aut-deduction-modal"
        title="Deduct AUTs"
        subtitle="Apply equivalent leave-credit deductions for regular salary payroll."
        header-icon="fa-solid fa-business-time"
        size="modal-xl"
    >
        <template #default>
            <div class="modal-body">
                <div class="aut-modal-meta">
                    <div
                        class="aut-stat-card"
                        :class="{ 'aut-stat-card-loading': autModalLoading }"
                    >
                        <div class="aut-stat-label">AUT Range</div>
                        <div v-if="autModalLoading" class="aut-stat-loading">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </div>
                        <div v-else class="aut-stat-value">{{ autRangeLabel }}</div>
                    </div>
                    <div
                        class="aut-stat-card"
                        :class="{ 'aut-stat-card-loading': autModalLoading }"
                    >
                        <div class="aut-stat-label">Employees with AUT</div>
                        <div v-if="autModalLoading" class="aut-stat-loading">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </div>
                        <div v-else class="aut-stat-value">{{ autDeductionRows.length }}</div>
                    </div>
                    <div
                        class="aut-stat-card"
                        :class="{ 'aut-stat-card-loading': autModalLoading }"
                    >
                        <div class="aut-stat-label">Least AUT</div>
                        <div v-if="autModalLoading" class="aut-stat-loading">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </div>
                        <div v-else class="aut-stat-value">{{ leastAutLabel }}</div>
                    </div>
                    <div
                        class="aut-stat-card"
                        :class="{ 'aut-stat-card-loading': autModalLoading }"
                    >
                        <div class="aut-stat-label">Longest AUT</div>
                        <div v-if="autModalLoading" class="aut-stat-loading">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </div>
                        <div v-else class="aut-stat-value">{{ longestAutLabel }}</div>
                    </div>
                </div>

                <div
                    v-if="canEditAutRows"
                    class="aut-reset-actions mb-3 d-flex justify-content-end"
                >
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        :disabled="autModalLoading || savingAut"
                        @click="fetchAutDeductions"
                    >
                        <span
                            v-if="autModalLoading"
                            class="d-inline-flex align-items-center gap-2"
                        >
                            <span
                                class="spinner-border spinner-border-sm"
                                role="status"
                                aria-hidden="true"
                            ></span>
                            Refreshing...
                        </span>
                        <span v-else>Refresh Data</span>
                    </button>
                </div>

                <div v-if="autModalLoading" class="aut-table-loading text-center text-body-secondary">
                    <span class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></span>
                    <div>Loading AUT deduction records...</div>
                </div>

                <div v-else-if="!autDeductionRows.length" class="py-5 text-center text-body-secondary">
                    No AUT deductions available for this payroll.
                </div>

                <div v-else class="table-responsive aut-deduction-table-wrapper">
                    <table class="table table-sm table-striped table-bordered align-middle aut-deduction-table">
                        <thead>
                            <tr>
                                <th>Employee No</th>
                                <th>Name / Position</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">AUT</th>
                                <th class="text-end">Equivalent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in autDeductionRows"
                                :key="row.pspe_id"
                                :class="{ 'table-success-subtle': row.is_saved }"
                            >
                                <td>{{ row.employee_no }}</td>
                                <td>
                                    <div class="employee-name fw-bold">{{ row.name }}</div>
                                    <div class="aut-employee-position">{{ row.position }}</div>
                                </td>
                                <td class="text-end">{{ formatMoney(row.monthly_rate) }}</td>
                                <td class="text-end">{{ formatAutDuration(row.total_minutes) }}</td>
                                <td class="text-end">
                                    <input
                                        v-if="isAutRowEditable(row)"
                                        :value="row.equivalent_input"
                                        type="text"
                                        inputmode="decimal"
                                        class="form-control form-control-sm aut-equivalent-input text-end"
                                        @input="updateEquivalentInput(row, $event.target.value)"
                                        @blur="normalizeEquivalent(row)"
                                    />
                                    <span v-else>{{ formatEquivalent(row.equivalent) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-danger"
                    :disabled="savingAut"
                    @click="closeAutDeductionModal"
                >
                    Close
                </button>
                <button
                    v-if="canEditAutRows && !autModalLoading"
                    type="button"
                    class="btn btn-primary"
                    :disabled="savingAut || autModalLoading || !canSaveAutRows"
                    @click="applyAutDeductions"
                >
                    <span v-if="savingAut" class="d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Applying deductions...
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
            baseColumnCount: 13, // Emp#, Name, Monthly, SG, AUT, Overtime, Holiday, Total Deduction, Adjustment, Net Salary, Remarks + colspan setup
            autModalLoading: false,
            savingAut: false,
            autDeductionRows: [],
            autPayrollStatus: this.status,
            autIsDeducted: this.is_aut_deducted,
            autRangeLabel: this.period_covered || "N/A",
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

        leastAutRow() {
            if (!this.autDeductionRows.length) {
                return null;
            }

            return this.autDeductionRows.reduce((least, row) => {
                if (!least) {
                    return row;
                }

                return this.toNumber(row.total_minutes) < this.toNumber(least.total_minutes)
                    ? row
                    : least;
            }, null);
        },

        longestAutRow() {
            if (!this.autDeductionRows.length) {
                return null;
            }

            return this.autDeductionRows.reduce((longest, row) => {
                if (!longest) {
                    return row;
                }

                return this.toNumber(row.total_minutes) > this.toNumber(longest.total_minutes)
                    ? row
                    : longest;
            }, null);
        },

        leastAutLabel() {
            if (!this.leastAutRow) {
                return "N/A";
            }

            return `${this.leastAutRow.employee_no} • ${this.formatAutDuration(this.leastAutRow.total_minutes)}`;
        },

        longestAutLabel() {
            if (!this.longestAutRow) {
                return "N/A";
            }

            return `${this.longestAutRow.employee_no} • ${this.formatAutDuration(this.longestAutRow.total_minutes)}`;
        },

        canEditAutRows() {
            return !this.autIsDeducted;
        },

        canSaveAutRows() {
            return this.canEditAutRows && this.autDeductionRows.length > 0;
        },

        autStatusLabel() {
            return this.autIsDeducted ? "Applied" : "Not Applied";
        },

        autStatusDescription() {
            return this.autIsDeducted
                ? "AUT values are now using the saved applied snapshot for this payroll."
                : "AUT values are still using live timelog computation until deductions are applied.";
        },

        autStatusBannerClass() {
            return this.autIsDeducted
                ? "aut-status-banner-applied"
                : "aut-status-banner-pending";
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

        formatEditableEquivalent(value) {
            return this.toNumber(value).toFixed(3);
        },

        computeEquivalentFromMinutes(totalMinutes) {
            const minutes = Math.max(0, this.toNumber(totalMinutes));
            return Number((minutes / 480).toFixed(3));
        },

        isAutRowEditable(row) {
            return this.canEditAutRows && !row.already_applied;
        },

        formatAutDuration(totalMinutes) {
            const minutes = Math.max(0, Math.round(this.toNumber(totalMinutes)));
            const totalHours = Math.floor(minutes / 60);
            const days = Math.floor(totalHours / 24);
            const hours = totalHours % 24;
            const mins = minutes % 60;

            if (days > 0) {
                return `${days} day${days === 1 ? "" : "s"} ${hours} hr${hours === 1 ? "" : "s"} ${mins} min${mins === 1 ? "" : "s"}`;
            }

            return `${hours} hr${hours === 1 ? "" : "s"} ${mins} min${mins === 1 ? "" : "s"}`;
        },

        getDeductionAmount(emp, type) {
            const deduction = (emp.deductions || []).find(
                (d) => d.deduction_type === type,
            );

            return deduction ? this.toNumber(deduction.amount) : 0;
        },

        grandTotals(field, subfield = null) {
            return this.filteredEmployees.reduce((total, emp) => {
                if (field === "deductions" && subfield) {
                    return total + this.getDeductionAmount(emp, subfield);
                }

                if (field === "autLike") {
                    return total + this.toNumber(emp.aut ?? emp.ut);
                }

                return total + this.toNumber(emp[field]);
            }, 0);
        },

        openAutDeductionModal() {
            this.$refs.autDeductionModal.open();
            this.fetchAutDeductions();
        },

        closeAutDeductionModal() {
            this.$refs.autDeductionModal.close();
        },

        async fetchAutDeductions() {
            this.autModalLoading = true;

            try {
                const response = await axios.get(
                    `/api/payroll/salary-pay/${this.payroll_id}/aut-deductions/preview`,
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                        },
                    },
                );

                this.autPayrollStatus = response.data.meta?.status || this.status;
                this.autIsDeducted = Boolean(
                    response.data.meta?.is_aut_deducted ?? this.is_aut_deducted,
                );
                this.autRangeLabel =
                    response.data.meta?.range_label ||
                    response.data.meta?.period_covered ||
                    this.period_covered ||
                    "N/A";
                this.autDeductionRows = (response.data.data || []).map((row) => ({
                    ...row,
                    equivalent_input: this.formatEditableEquivalent(row.equivalent),
                }));
            } catch (error) {
                this.autDeductionRows = [];
                this.autIsDeducted = this.is_aut_deducted;
                this.autRangeLabel = this.period_covered || "N/A";
                Swal.fire({
                    icon: "error",
                    title: "Unable to load AUT deductions",
                    text:
                        error.response?.data?.message ||
                        "An error occurred while loading the AUT deduction preview.",
                });
            } finally {
                this.autModalLoading = false;
            }
        },

        async applyAutDeductions() {
            const confirmation = await Swal.fire({
                icon: "warning",
                title: "Confirm AUT deduction",
                text: "Once you proceed, the equivalent amount will be deducted from the employees' leave credits and cannot be undone through the system. If a reversal is needed, it must be handled manually.",
                showCancelButton: true,
                confirmButtonText: "Apply Deductions",
                cancelButtonText: "Cancel",
                reverseButtons: true,
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            this.savingAut = true;

            try {
                const response = await axios.post(
                    `/api/payroll/salary-pay/${this.payroll_id}/aut-deductions/apply`,
                    {
                        rows: this.autDeductionRows.map((row) => ({
                            pspe_id: row.pspe_id,
                            equivalent: this.toNumber(row.equivalent),
                        })),
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                        },
                    },
                );

                await Swal.fire({
                    icon: "success",
                    title: "AUT changes applied",
                    text:
                        response.data.message ||
                        "Equivalent AUT changes have been applied.",
                });

                await this.fetchAutDeductions();
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "AUT changes failed",
                    text:
                        error.response?.data?.message ||
                        "An error occurred while applying the AUT changes.",
                });
            } finally {
                this.savingAut = false;
            }
        },

        normalizeEquivalent(row) {
            const parsedValue = this.toNumber(row.equivalent);
            row.equivalent = Math.max(0, Number(parsedValue.toFixed(3)));
            row.equivalent_input = this.formatEditableEquivalent(row.equivalent);
        },

        updateEquivalentInput(row, value) {
            row.equivalent_input = value;
            row.equivalent = this.toNumber(value);
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

.aut-status-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.85rem;
}

.aut-status-banner-applied {
    background: #e9f8ef;
    border-color: #69c18f;
    color: #1f7a4d;
}

.aut-status-banner-pending {
    background: #fff6de;
    border-color: #efc15a;
    color: #8a5a00;
}

.aut-status-copy {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.aut-status-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.8;
}

.aut-status-note {
    font-size: 0.92rem;
    line-height: 1.35;
}

.aut-modal-meta {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.aut-stat-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
    background: rgba(var(--bs-secondary-rgb), 0.08);
    min-height: 88px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.aut-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.72;
    margin-bottom: 0.3rem;
}

.aut-stat-value {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.25;
}

.aut-stat-loading {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    min-height: 36px;
}

.aut-stat-card-loading {
    background: rgba(var(--bs-secondary-rgb), 0.12);
    border-color: rgba(var(--bs-secondary-rgb), 0.28);
}

.aut-deduction-table-wrapper {
    max-height: 60vh;
}

.aut-table-loading {
    min-height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.aut-deduction-table thead th {
    position: sticky;
    top: 0;
    background: var(--bs-body-bg);
    z-index: 1;
}

.aut-employee-position {
    font-size: 0.68rem;
    opacity: 0.8;
    line-height: 1.2;
}

.aut-equivalent-input {
    min-width: 84px;
    max-width: 110px;
    margin-left: auto;
}

@media (max-width: 768px) {
    .aut-status-banner {
        flex-direction: column;
        align-items: flex-start;
    }

    .aut-modal-meta {
        grid-template-columns: 1fr;
    }
}
</style>
