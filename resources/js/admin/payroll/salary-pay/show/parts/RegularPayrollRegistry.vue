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
                    </tr>
                </tfoot>
            </table>
        </template>
    </PayrollRegistryLayout>
</template>

<script>
import axios from "axios";
import PayrollRegistryLayout from "../../../PayrollRegistryLayout.vue";

export default {
    name: "PermanentPayrollRegistry",
    components: { PayrollRegistryLayout },
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
            baseColumnCount: 12, // Emp#, Name, Monthly, SG, AUT, Overtime, Holiday, Total Deduction, Adjustment, Net Salary, Remarks + colspan setup
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
                console.error("Failed to update payroll row:", error);
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
</style>
