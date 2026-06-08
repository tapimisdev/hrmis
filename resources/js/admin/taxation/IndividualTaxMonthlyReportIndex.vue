<template>
    <div class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                            <div>
                                <div class="text-uppercase small fw-semibold text-body-secondary mb-2">Taxation</div>
                                <h1 class="h3 mb-2">Monthly Tax Report</h1>
                                <p class="mb-0 text-body-secondary">
                                    Per-employee salary, hazard pay, and longevity tax amounts with source status.
                                </p>
                            </div>
                            <div class="text-body-secondary fw-semibold">
                                {{ selectedMonthLabel }} {{ form.year }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Filters</h2>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1" for="tax-report-month">Month</label>
                                <select
                                    id="tax-report-month"
                                    v-model.number="form.month"
                                    class="form-select form-select-sm"
                                    :disabled="isLoading"
                                    @change="fetchReport"
                                >
                                    <option
                                        v-for="option in monthOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1" for="tax-report-year">Year</label>
                                <select
                                    id="tax-report-year"
                                    v-model.number="form.year"
                                    class="form-select form-select-sm"
                                    :disabled="isLoading"
                                    @change="fetchReport"
                                >
                                    <option v-for="year in availableYears" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1" for="tax-report-search">Search</label>
                                <input
                                    id="tax-report-search"
                                    v-model.trim="search"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :disabled="isLoading"
                                    placeholder="Search employee no, name, position, division, unit"
                                />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1" for="tax-report-group-by">Group By</label>
                                <select
                                    id="tax-report-group-by"
                                    v-model="groupBy"
                                    class="form-select form-select-sm"
                                    :disabled="isLoading"
                                >
                                    <option value="">None</option>
                                    <option
                                        v-for="option in groupByOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="small text-body-secondary pt-md-3">
                                    <span v-if="isLoading">Loading report...</span>
                                    <span v-else>Report refreshes when month or year changes.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap gap-2 small">
                            <span
                                v-for="item in statusLegend"
                                :key="item.value"
                                class="status-pill status-pill--legend"
                                :class="statusPillClass(item.value)"
                            >
                                {{ item.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                            <div>
                                <h2 class="h5 mb-1">Employee Breakdown</h2>
                                <p class="text-body-secondary mb-0">Shared tax computation source for each employee and month.</p>
                            </div>
                            <div class="small text-body-secondary">
                                {{ filteredRows.length }} record<span v-if="filteredRows.length !== 1">s</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="isLoading" class="p-4">
                            <div class="d-flex align-items-center gap-2 text-body-secondary">
                                <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                                <span>Loading monthly tax report...</span>
                            </div>
                        </div>

                        <div v-else-if="!reportHasTaxationData" class="p-4 text-body-secondary">
                            No individual tax data exists for the selected year.
                        </div>

                        <div v-else-if="filteredRows.length === 0" class="p-4 text-body-secondary">
                            No employees matched your search.
                        </div>

                        <div v-else class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0 report-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th class="text-end">Salary Tax</th>
                                        <th class="text-end">Hazard Pay Tax</th>
                                        <th class="text-end">Longevity Tax</th>
                                        <th class="text-end">Total Tax</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="item in visibleTableRows" :key="item.rowKey">
                                        <tr v-if="item.type === 'group'">
                                            <td colspan="5" class="table-light fw-semibold">
                                                {{ item.label }}
                                            </td>
                                        </tr>
                                        <tr v-else>
                                            <td>
                                                <div class="employee-cell">
                                                    <div class="employee-cell__name">
                                                        {{ item.employee_name || item.employee_no }}
                                                    </div>
                                                    <div class="employee-cell__meta">
                                                        <span class="employee-cell__badge">{{ item.employee_no }}</span>
                                                    </div>
                                                    <div class="employee-cell__line">
                                                        {{ item.position }}
                                                    </div>
                                                    <div class="employee-cell__line">
                                                        {{ item.division_name }}<span v-if="item.unit_name"> | {{ item.unit_name }}</span>
                                                    </div>
                                                    <div class="employee-cell__salary">
                                                        {{ item.salary_display }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                            <div class="fw-semibold">{{ peso(item.salary_tax.amount) }}</div>
                                            <div>
                                                    <span class="status-pill status-pill--compact" :class="statusPillClass(item.salary_tax.status)">
                                                        {{ item.salary_tax.status_label }}
                                                    </span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-semibold">{{ peso(item.hazard_pay_tax.amount) }}</div>
                                            <div>
                                                    <span class="status-pill status-pill--compact" :class="statusPillClass(item.hazard_pay_tax.status)">
                                                        {{ item.hazard_pay_tax.status_label }}
                                                    </span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-semibold">{{ peso(item.longevity_tax.amount) }}</div>
                                            <div>
                                                    <span class="status-pill status-pill--compact" :class="statusPillClass(item.longevity_tax.status)">
                                                        {{ item.longevity_tax.status_label }}
                                                    </span>
                                            </div>
                                            </td>
                                            <td class="text-end total-column">
                                                {{ peso(item.total_tax) }}
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="filteredRows.length > 0" class="table-light">
                                        <td class="fw-semibold">Total</td>
                                        <td class="text-end fw-semibold">
                                            {{ peso(summary.salary) }}
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ peso(summary.hazard_pay) }}
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ peso(summary.longevity) }}
                                        </td>
                                        <td class="text-end fw-semibold total-column">
                                            {{ peso(summary.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Details</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <div class="small text-body-secondary mb-1">Selected Period</div>
                            <div class="fw-semibold">{{ selectedMonthLabel }} {{ form.year }}</div>
                        </div>

                        <div class="mb-4">
                            <div class="small text-body-secondary mb-2">Status Reference</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    v-for="item in statusLegend"
                                    :key="`detail-${item.value}`"
                                    class="status-pill status-pill--legend"
                                    :class="statusPillClass(item.value)"
                                >
                                    {{ item.label }}
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="small text-body-secondary mb-1">Search Result</div>
                            <div class="fw-semibold">{{ filteredRows.length }} employee<span v-if="filteredRows.length !== 1">s</span></div>
                        </div>

                        <div class="mb-4">
                            <div class="small text-body-secondary mb-1">Grouped By</div>
                            <div class="fw-semibold">{{ selectedGroupByLabel }}</div>
                        </div>

                        <div class="mb-4">
                            <div class="small text-body-secondary mb-1">Total Tax</div>
                            <div class="fw-semibold">{{ peso(summary.total) }}</div>
                        </div>

                        <div>
                            <div class="small text-body-secondary mb-1">Notes</div>
                            <div class="text-body-secondary">
                                Status labels come from the shared individual tax amount service for the selected month.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        apiUrl: {
            type: String,
            required: true,
        },
        selectedYear: {
            type: Number,
            required: true,
        },
        selectedMonth: {
            type: Number,
            required: true,
        },
        availableYears: {
            type: Array,
            default: () => [],
        },
        monthOptions: {
            type: Array,
            default: () => [],
        },
        initialRows: {
            type: Array,
            default: () => [],
        },
        initialHasTaxationData: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            isLoading: false,
            search: "",
            groupBy: "",
            form: {
                month: this.selectedMonth,
                year: this.selectedYear,
            },
            rows: [...this.initialRows],
            reportHasTaxationData: this.initialHasTaxationData,
        };
    },
    computed: {
        selectedMonthLabel() {
            return this.monthOptions.find((option) => option.value === this.form.month)?.label || "";
        },
        filteredRows() {
            const keyword = this.search.toLowerCase();

            return [...this.rows]
                .filter((row) => {
                    if (!keyword) {
                        return true;
                    }

                    return [
                        row.employee_no,
                        row.employee_name,
                        row.position,
                        row.division_name,
                        row.unit_name,
                        row.salary_display,
                    ]
                        .filter(Boolean)
                        .some((value) => String(value).toLowerCase().includes(keyword));
                })
                .sort((left, right) => {
                    if (this.groupBy) {
                        const leftGroup = this.groupLabel(left);
                        const rightGroup = this.groupLabel(right);
                        const grouped = String(leftGroup).localeCompare(String(rightGroup));

                        if (grouped !== 0) {
                            return grouped;
                        }
                    }

                    return String(left.employee_name || "").localeCompare(String(right.employee_name || ""));
                });
        },
        visibleTableRows() {
            if (!this.groupBy) {
                return this.filteredRows.map((row) => ({
                    ...row,
                    type: "employee",
                    rowKey: `employee-${row.employee_no}`,
                }));
            }

            const rows = [];
            let currentGroup = null;

            this.filteredRows.forEach((row) => {
                const label = this.groupLabel(row);

                if (label !== currentGroup) {
                    rows.push({
                        type: "group",
                        label,
                        rowKey: `group-${this.groupBy}-${label}`,
                    });
                    currentGroup = label;
                }

                rows.push({
                    ...row,
                    type: "employee",
                    rowKey: `employee-${row.employee_no}`,
                });
            });

            return rows;
        },
        summary() {
            return this.filteredRows.reduce(
                (carry, row) => {
                    carry.salary += Number(row.salary_tax?.amount || 0);
                    carry.hazard_pay += Number(row.hazard_pay_tax?.amount || 0);
                    carry.longevity += Number(row.longevity_tax?.amount || 0);
                    carry.total += Number(row.total_tax || 0);

                    return carry;
                },
                {
                    salary: 0,
                    hazard_pay: 0,
                    longevity: 0,
                    total: 0,
                },
            );
        },
        statusLegend() {
            return [
                { value: "completed", label: "Completed" },
                { value: "draft", label: "Draft" },
                { value: "pending", label: "Pending" },
                { value: "approved", label: "Approved" },
                { value: "for_releasing", label: "For Releasing" },
                { value: "override", label: "Override" },
                { value: "forecasted", label: "Forecasted" },
            ];
        },
        groupByOptions() {
            return [
                { value: "division_name", label: "Division" },
                { value: "unit_name", label: "Unit" },
                { value: "position", label: "Position" },
                { value: "salary_grade", label: "SG" },
            ];
        },
        selectedGroupByLabel() {
            return this.groupByOptions.find((option) => option.value === this.groupBy)?.label || "None";
        },
    },
    methods: {
        peso(amount) {
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(Number(amount || 0));
        },
        buildAuthHeaders() {
            const token = localStorage.getItem("auth_token");

            return token
                ? {
                    Authorization: `Bearer ${token}`,
                }
                : {};
        },
        async fetchReport() {
            this.isLoading = true;

            try {
                const { data } = await axios.get(this.apiUrl, {
                    headers: this.buildAuthHeaders(),
                    params: {
                        month: this.form.month,
                        year: this.form.year,
                    },
                });

                this.rows = data.rows || [];
                this.reportHasTaxationData = Boolean(data.hasTaxationData);
            } catch (error) {
                console.error("Failed to load monthly tax report:", error);
            } finally {
                this.isLoading = false;
            }
        },
        statusPillClass(status) {
            const classes = {
                completed: "status-pill--completed",
                draft: "status-pill--draft",
                pending: "status-pill--pending",
                approved: "status-pill--approved",
                for_releasing: "status-pill--for-releasing",
                override: "status-pill--override",
                forecasted: "status-pill--forecasted",
            };

            return classes[status] || classes.forecasted;
        },
        groupLabel(row) {
            if (this.groupBy === "salary_grade") {
                return row.salary_grade ? `SG-${row.salary_grade}` : "No SG";
            }

            if (!this.groupBy) {
                return "";
            }

            return String(row[this.groupBy] || `No ${this.selectedGroupByLabel}`).trim();
        },
    },
};
</script>

<style scoped>
.report-table thead th {
    white-space: nowrap;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--bs-secondary-color);
    border-bottom-width: 1px;
}

.report-table tbody td {
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.employee-cell {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.employee-cell__name {
    font-weight: 700;
    font-size: 0.98rem;
    line-height: 1.2;
    color: var(--bs-body-color);
}

.employee-cell__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.employee-cell__badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    background: rgba(var(--bs-secondary-rgb), 0.12);
    color: var(--bs-secondary-color);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.employee-cell__line {
    font-size: 0.78rem;
    line-height: 1.35;
    color: var(--bs-secondary-color);
}

.employee-cell__salary {
    font-size: 0.76rem;
    line-height: 1.35;
    color: var(--bs-body-color);
    font-weight: 600;
}

.total-column {
    font-weight: 700;
    color: var(--bs-body-color);
}

.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.28rem 0.65rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.status-pill--compact {
    padding: 0.18rem 0.5rem;
    font-size: 0.66rem;
    font-weight: 600;
}

.status-pill--legend {
    padding: 0.2rem 0.5rem;
    font-size: 0.68rem;
    font-weight: 600;
}

.status-pill--completed {
    background: rgba(var(--bs-success-rgb), 0.15);
    color: var(--bs-success-text-emphasis, var(--bs-success));
}

.status-pill--draft {
    background: rgba(var(--bs-secondary-rgb), 0.15);
    color: var(--bs-secondary-text-emphasis, var(--bs-secondary));
}

.status-pill--pending {
    background: rgba(var(--bs-warning-rgb), 0.18);
    color: var(--bs-warning-text-emphasis, var(--bs-warning));
}

.status-pill--approved {
    background: rgba(var(--bs-info-rgb), 0.18);
    color: var(--bs-info-text-emphasis, var(--bs-info));
}

.status-pill--for-releasing {
    background: rgba(var(--bs-primary-rgb), 0.15);
    color: var(--bs-primary-text-emphasis, var(--bs-primary));
}

.status-pill--override {
    background: rgba(var(--bs-danger-rgb), 0.15);
    color: var(--bs-danger-text-emphasis, var(--bs-danger));
}

.status-pill--forecasted {
    background: rgba(var(--bs-secondary-rgb), 0.1);
    color: var(--bs-secondary-color);
}
</style>
