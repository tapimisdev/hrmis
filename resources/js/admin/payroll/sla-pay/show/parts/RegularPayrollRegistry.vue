<template>
    <div
        class="payroll-registry-container"
        :class="status"
        :data-bs-theme="theme"
    >
        <div class="excel-toolbar">
            <div class="status-badge">
                <i :class="['fa-solid', statusConfig.icon]"></i>
                {{ statusConfig.label }}
            </div>
            <div class="toolbar-left d-flex gap-2">
                <button class="toolbar-btn">
                    <i class="fa-solid fa-print"></i> Print
                </button>
                <button
                    @click="download('payslip', payroll_no)"
                    class="toolbar-btn left"
                    type="button"
                    aria-expanded="false"
                >
                    <i class="fa-solid fa-download"></i> Download
                </button>
            </div>
        </div>

        <div class="excel-sheet">
            <LoaderVue
                :visible="loading"
                :hasBackground="true"
                status="uploading"
                message="Uploading, please wait..."
            />

            <div class="sheet-header">
                <h1 class="sheet-title mb-2">
                    TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE
                </h1>
                <h1 class="sheet-title mb-3">
                  PAYROLL OF SUBSISTENCE AND LAUNDRY ALLOWANCE PAY FOR THE MONTH OF {{ month }}
                </h1>
            </div>

            <div class="sheet-info">
                <div class="info-text">
                    We hereby acknowledge to have received the sums therein
                    specified opposite our respective names for our services
                    rendered:
                </div>
                <div class="info-period">
                    Month: <strong>{{ month }}</strong>
                </div>
            </div>

            <div class="excel-table-wrapper table-responsive">
                <table class="excel-table">
                    <thead>
                        <tr class="header-labels">
                            <th>Emp#</th>
                            <th>Name / Position</th>
                            <th>
                              Subsistence <br/>
                              Allowance<br/>
                              (22 days)
                            </th>
                            <th>
                              Laundry <br/>
                              Allowance<br/>
                              (₱500)
                            </th>
                            <th>Total SLA</th>
                            <th>
                                Deduction <br />
                                Late/UT's <br />
                                <small>
                                  per DOST AO <br />
                                  No. 003
                                </small>
                            </th>
                            <th>
                              Uniform <br/>
                              Deduction<br/>
                            </th>
                            <th>
                              Less: Health <br/>
                              Card c/o <br />
                              TAPIEA
                            </th>
                            <th style="width: 150px">Adjustments</th>
                            <th>Net Amount</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            class="data-row"
                            v-for="(emp, index) in employees"
                            :key="index"
                        >
                            <td class="text-center">{{ emp.employee_no }}</td>
                            <td class="name-cell">
                                <div class="employee-name">{{ emp.name }}</div>
                                <div class="employee-position">
                                    {{ emp.position }}
                                </div>
                            </td>
                            <td class="text-center">{{ emp.subsistence_allowance }}</td>
                            <td class="text-center">{{ emp.laundry_allowance }}</td>
                            <td class="text-center">{{ emp.total_sla }}</td>
                            <td class="text-center">
                                {{ emp.ut_deductions }}
                            </td>
                            <td class="text-center">{{ emp.uniform_deduction }}</td>
                            <td class="text-center">{{ emp.healthcard }}</td>
                            <td class="text-center">
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model.number="emp.adjustment"
                                />
                            </td>
                            <td class="text-center">{{ emp.net_pay }}</td>
                            <td class="text-center">
                                <textarea class="form-control my-3"></textarea>
                            </td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr class="grand-total text-center">
                            <td colspan="2" class="text-end">
                                <strong>GRAND TOTAL</strong>
                            </td>
                            <td class="number-cell">
                                {{ formatNumber(grandTotals("monthly_rate")) }}
                            </td>
                            <td class="number-cell">-</td>
                            <td class="number-cell">
                                {{ formatNumber(grandTotals("hazard_pay")) }}
                            </td>
                            <td class="number-cell">
                                {{
                                  formatNumber(grandTotals("witholding_tax"))
                                }}
                            </td>
                            <td class="number-cell">
                                {{ formatNumber(grandTotals("healthcard")) }}
                            </td>
                            <td></td>
                            <td class="number-cell">
                                {{ formatNumber(grandTotals("adjustment")) }}
                            </td>
                            <td class="number-cell net-salary">
                                <strong>{{
                                    formatNumber(grandTotals("net_pay"))
                                }}</strong>
                            </td>
                            <td></td>
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
    name: "RegularPayrollRegistry",
    components: { LoaderVue },
    props: {
        employees: { type: Array, required: true },
        status: { type: String, required: true },
        payroll_no: { type: String, required: true },
        month: { type: String, required: true },
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
        async download(payroll_no) {
            try {
                const response = await axios.get(urlArr[type], {
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
        grandTotals(field) {
            return this.employees.reduce(
                (total, emp) => total + (Number(emp[field]) || 0),
                0
            );
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
    --bs-dark-rgb: 33, 37, 41;
    background: var(--status-bg);
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    transition: all 0.3s ease;
    padding-bottom: 24px;
    color: var(--bs-body-color);
}
[data-bs-theme="dark"] .payroll-registry-container {
    --status-bg: #1a1d20;
    background: var(--bs-secondary-bg, #212529);
}
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
.data-row .name-cell .employee-name {
    font-weight: bold;
}
.data-row .name-cell .employee-position {
    font-style: italic;
    font-size: 8px;
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
