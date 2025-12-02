<template>
    <div
        class="payroll-registry-container"
        :class="status"
        :data-bs-theme="theme"
    >
        <!-- Toolbar -->
        <div class="excel-toolbar">
            <div class="status-badge">
                <i :class="['fa-solid', statusConfig.icon]"></i>
                {{ statusConfig.label }}
            </div>
            <div class="toolbar-left d-flex gap-2">
                <button class="toolbar-btn">
                    <i class="fa-solid fa-print"></i> Print
                </button>
                <div class="dropdown">
                    <button
                        class="toolbar-btn left dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="fa-solid fa-download"></i> Downloads
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a
                                class="dropdown-item"
                                href="javascript:void(0)"
                                @click="downloadPayroll('registry', payroll_no)"
                                >Payroll Registry</a
                            >
                        </li>
                        <li>
                            <a
                                class="dropdown-item"
                                href="javascript:void(0)"
                                @click="downloadPayroll('aut', payroll_no)"
                                >Absences & Leaves</a
                            >
                        </li>
                        <li>
                            <a
                                class="dropdown-item"
                                href="javascript:void(0)"
                                @click="downloadPayroll('payslip', payroll_no)"
                                >Payslip</a
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sheet -->
        <div class="excel-sheet">
            <LoaderVue
                :visible="loading"
                :hasBackground="true"
                status="uploading"
                message="Uploading, please wait..."
            />
            <div class="sheet-header">
                <h1 class="sheet-title">
                    <div class="toolbar-description mb-1">
                        ( PERMANENT )
                    </div>
                    TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE																					
                </h1>
                <p class="sheet-subtitle">
                    GENERAL PAYROLL FOR SALARY																					
                </p>
            </div>

            <div class="sheet-info">
                <div class="info-text">
                    We hereby acknowledge to have received the sums therein
                    specified opposite our respective names for our services
                    rendered:
                </div>
                <div class="info-period">
                    Period: <strong>1–15 September 2025</strong>
                </div>
            </div>
            <div class="excel-table-wrapper table-responsive">
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

                            <!-- Dynamic Deductions -->
                            <th
                                v-for="(deduction, dIndex) in dynamicDeductions"
                                :key="'deduction-' + dIndex"
                                class="deduction"
                            >
                                {{ deduction }}
                            </th>

                            <th class="earning">Total Deductions</th>
                            <th>Adjustment</th>
                            <th class="net-salary">Net <br />Salary</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="data-row" v-for="(emp, index) in employees" :key="index">
                            <!-- <td colspan="100">asdasd</td> -->
                            <td class="text-center">{{ emp.employee_no }}</td>
                            <td class="name-cell">
                                <div class="employee-name">{{ emp.name }}</div>
                                <div class="employee-position">{{ emp.position }}</div>
                            </td>
                            <td class="text-center">{{ emp.monthly_rate }}</td>
                            <td class="text-center">{{ emp.salary_grade }}</td>
                            <td class="text-center">{{ emp.aut }}</td>
                            <td class="text-center">{{ emp.overtime }}</td>
                            <td class="text-center">{{ emp.holiday }}</td>
                            
                            <!-- Dynamic Deductions -->
                            <td
                                v-for="(deduction, dIndex) in dynamicDeductions"
                                :key="'deduction-' + dIndex"
                                class="number-cell deduction"
                            >
                                {{ formatNumber(getDeductionAmount(emp, deduction)) }}
                            </td>

                            <td class="text-center">{{ emp.total_deductions }}</td>
                            <td class="number-cell p-0">
                                <input 
                                type="number" 
                                v-model="emp.adjustment"
                                @change="adjustRow(emp)"
                                class="w-100 border-0 p-2 bg-transparent focus:ring-0 text-right"
                                />
                            </td>
                            <td class="text-center">{{ emp.net_pay }}</td>
                        </tr>
                    </tbody>
                    <!-- Grand Total Row -->
                    <tfoot>
                        <tr class="grand-total">
                        <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
                        <td class="number-cell">{{ formatNumber(grandTotals('monthly_rate')) }}</td>
                        <td class="number-cell">N/A</td>
                        <td class="number-cell deduction">{{ formatNumber(grandTotals('aut')) }}</td>
                        <td class="number-cell">{{ formatNumber(grandTotals('overtime')) }}</td>
                        <td class="number-cell">{{ formatNumber(grandTotals('holiday')) }}</td>

                        <td
                            v-for="(deduction, dIndex) in dynamicDeductions"
                            :key="'deduction-grand-' + dIndex"
                            class="number-cell deduction"
                        > 
                            {{ formatNumber(grandTotals('deductions', deduction)) }}
                        </td>

                        <td class="number-cell">{{ formatNumber(grandTotals('total_deductions')) }}</td>
                        <td class="number-cell earning">{{ formatNumber(grandTotals('salary_adjustment')) }}</td>
                        <td class="number-cell net-salary"><strong>{{ formatNumber(grandTotals('net_pay')) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>


<script>
import axios from "axios";
import LoaderVue from "../../../../../components/LoaderVue.vue";
const token = localStorage.getItem("auth_token");

export default {
    name: "CosPayrollRegistry",
    components: { LoaderVue },
    props: {
        employees: { type: Array, required: true },
        status: { type: String, required: true },
        payroll_no: { type: String, required: true },
    },
    data() {
        return {
            token: token,
            loading: false,
            theme:
                document.documentElement.getAttribute("data-bs-theme") ||
                "light",
        };
    },
    computed: {
        dynamicDeductions() {
            const names = new Set();
            this.employees.forEach((p) =>
                p.deductions?.forEach((d) => names.add(d.deduction_type))
            );
            return Array.from(names);
        },
        statusConfig() {
            const configs = {
                draft: {
                    label: "Draft",
                    icon: "fa-file-pen",
                    color: "#f39c12",
                    bg: "#fef9e7",
                    darkColor: "#f5a623",
                    darkBg: "#3a2a0f",
                },
                pending: {
                    label: "Pending Review",
                    icon: "fa-clock",
                    color: "#3498db",
                    bg: "#ebf5fb",
                    darkColor: "#5dade2",
                    darkBg: "#1a2f3a",
                },
                approved: {
                    label: "Approved",
                    icon: "fa-circle-check",
                    color: "#27ae60",
                    bg: "#eafaf1",
                    darkColor: "#2ecc71",
                    darkBg: "#1a3a28",
                },
                for_releasing: {
                    label: "For Releasing",
                    icon: "fa-paper-plane",
                    color: "#9b59b6",
                    bg: "#f5eef8",
                    darkColor: "#af7ac5",
                    darkBg: "#2d1f35",
                },
                completed: {
                    label: "Completed",
                    icon: "fa-circle-check",
                    color: "#16a085",
                    bg: "#e8f8f5",
                    darkColor: "#1abc9c",
                    darkBg: "#183a32",
                },
                cancelled: {
                    label: "Cancelled",
                    icon: "fa-ban",
                    color: "#e74c3c",
                    bg: "#fadbd8",
                    darkColor: "#ec7063",
                    darkBg: "#3a1f1c",
                },
                failed: {
                    label: "Failed",
                    icon: "fa-ban",
                    color: "#454444",
                    bg: "#949292",
                    darkColor: "#7f8c8d",
                    darkBg: "#2c2c2c",
                },
            };
            return configs[this.status] || configs.draft;
        },
    },
    methods: {
        async downloadPayroll(type, payroll_no) {
            const urlArr = {
                registry: `/api/payroll/salary/${payroll_no}/download`,
                aut: `/api/payroll/absences-leaves/${payroll_no}/download`,
                payslip: `/api/payroll/payslip/${payroll_no}/download`,
            };

            const endPoint = urlArr[type];

            try {
                const response = await axios.get(endPoint, {
                    headers: { Authorization: `Bearer ${this.token}` },
                    responseType: "blob",
                });

                const url = window.URL.createObjectURL(
                    new Blob([response.data])
                );
                const a = document.createElement("a");
                a.href = url;
                a.download = `payroll_registry_${payroll_no}.xlsx`;
                document.body.appendChild(a);
                a.click();

                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Download failed:", error);
            }
        },
        formatNumber(value) {
            const num = Number(value);
            return !isNaN(num) && num !== 0
                ? num.toLocaleString(undefined, { minimumFractionDigits: 2 })
                : "-";
        },
        getDeductionAmount(emp, type) {
            const deduction = emp.deductions?.find(
                (d) => d.deduction_type === type
            );
            return deduction ? Number(deduction.amount) : 0;
        },
        grandTotals(field, subfield = null) {
            return this.employees.reduce((total, emp) => {
                // Case 1: Dynamic deduction columns
                if (field === "deductions" && subfield) {
                    const found = emp.deductions?.find(d => d.deduction_type === subfield);
                    return total + (found ? Number(found.amount) : 0);
                }

                // Case 2: Normal numeric fields (monthly_rate, aut, overtime, holiday, etc.)
                const val = Number(emp[field]) || 0;
                return total + val;
            }, 0);
        },
        async adjustRow(emp) {
            this.loading = true;
            try {
                const res = await axios.post(
                    `/api/payroll/salary-item/${emp.id}`,
                    {
                        adjustment: emp.adjustment,
                    },
                    {
                        headers: { Authorization: `Bearer ${this.token}` },
                        responseType: "blob",
                    }
                );
                console.log(res);
                this.$emit("fetch_data");
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        applyStatusTheme() {
            const { color, bg, darkColor, darkBg } = this.statusConfig;
            const root = this.$el;
            if (this.theme === "dark") {
                root.style.setProperty("--status-color", darkColor);
                root.style.setProperty("--status-bg", darkBg);
            } else {
                root.style.setProperty("--status-color", color);
                root.style.setProperty("--status-bg", bg);
            }
        },
        watchGlobalTheme() {
            const observer = new MutationObserver(() => {
                const newTheme =
                    document.documentElement.getAttribute("data-bs-theme");
                if (newTheme && newTheme !== this.theme) {
                    this.theme = newTheme;
                    this.applyStatusTheme();
                }
            });
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ["data-bs-theme"],
            });
        },
    },
    mounted() {
        this.applyStatusTheme();
        this.watchGlobalTheme();
    },
    watch: {
        status() {
            this.applyStatusTheme();
        },
    },
};
</script>

<style scoped>
.payroll-registry-container {
    --status-color: #ccc;
    --status-bg: #f9f9f9;

    --bs-success-rgb: 25, 135, 84;
    --bs-danger-rgb: 220, 53, 69;
    --bs-primary-rgb: 13, 110, 253;
    --bs-warning-rgb: 255, 193, 7;
    --bs-dark-rgb: 33, 37, 41;

    background: var(--status-bg);
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    transition: all 0.3s ease;
    padding-bottom: 24px;
    color: var(--bs-body-color);
}

/* Dark mode adjustments */
[data-bs-theme="dark"] .payroll-registry-container {
    --status-bg: #1a1d20;
    background: var(--bs-secondary-bg, #212529);
}

/* Toolbar */
.excel-toolbar {
    background: var(--status-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px;
    color: white;
    transition: background 0.3s ease;
}

.toolbar-left {
    display: flex;
    gap: 4px;
}

.toolbar-btn {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.4);
    color: white;
    padding: 6px 12px;
    border-radius: 3px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.toolbar-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.toolbar-btn i {
    margin-right: 6px;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    color: var(--status-color);
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

/* Excel Sheet */
.excel-sheet {
    background: var(--bs-body-bg, white);
    margin: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    border: 1px solid var(--bs-border-color, #d0d0d0);
    position: relative;
}

[data-bs-theme="dark"] .excel-sheet {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.sheet-header {
    padding: 24px 24px 8px 24px;
    text-align: center;
    border-bottom: 2px solid var(--bs-border-color, #e0e0e0);
}

.sheet-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--bs-body-color, #333);
    margin: 0 0 8px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sheet-subtitle {
    font-size: 12px;
    color: var(--bs-secondary-color, #666);
}

.sheet-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: var(--bs-secondary-bg, #f9f9f9);
    border-bottom: 1px solid var(--bs-border-color, #e0e0e0);
}

[data-bs-theme="dark"] .sheet-info {
    background: rgba(255, 255, 255, 0.05);
}

.excel-table {
    width: 100%;
    border-collapse: collapse;
}

.excel-table th,
.excel-table td {
    border: 1px solid var(--bs-border-color, #d0d0d0);
    padding: 2px 8px;
    font-size: 11px;
}

.header-labels th {
    text-align: center;
    font-weight: 700;
    color: var(--status-color);
    background: var(--bs-table-bg, white);
}

[data-bs-theme="dark"] .header-labels th {
    background: var(--bs-body-bg);
}

.earning {
    background-color: rgba(var(--bs-success-rgb), 0.1);
    max-width: 76px;
    word-wrap: break-word;
    white-space: normal;
}

[data-bs-theme="dark"] .earning {
    background-color: rgba(var(--bs-success-rgb), 0.2);
}

.deduction {
    background-color: rgba(var(--bs-danger-rgb), 0.1);
    max-width: 76px;
    word-wrap: break-word;
    white-space: normal;
}

[data-bs-theme="dark"] .deduction {
    background-color: rgba(var(--bs-danger-rgb), 0.2);
}

.net-salary {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    font-weight: bold;
}

[data-bs-theme="dark"] .net-salary {
    background-color: rgba(var(--bs-primary-rgb), 0.2);
}

.project-header .row-number {
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    padding: 4px;
}

.project-header .project-cell {
    padding: 8px 12px;
    font-weight: bold;
    font-size: 12px;
    text-transform: uppercase;
    text-align: center;
    color: var(--bs-body-color);
}

.data-row .name-cell .employee-name {
    font-weight: bold;
}

.data-row .name-cell .employee-position {
    font-style: italic;
    font-size: 8px;
}

.total {
    border-top: 2px solid rgba(var(--bs-dark-rgb), 0.4);
    font-weight: bold;
}

.project-total {
    background-color: rgba(var(--bs-warning-rgb), 0.2);
}

[data-bs-theme="dark"] .project-total {
    background-color: rgba(var(--bs-warning-rgb), 0.15);
}

.project-total td {
    font-weight: bold;
}

.grand-total {
    border-top: 2px solid rgba(var(--bs-dark-rgb), 1);
    font-weight: bolder;
    height: 60px;
    background-color: var(--status-color);
    color: var(--status-bg);
}

[data-bs-theme="dark"] .grand-total {
    background-color: var(--status-color);
    color: white;
}
</style>
