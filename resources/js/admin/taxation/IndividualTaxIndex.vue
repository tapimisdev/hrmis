<template>
    <div class="individual-tax-page">
        <div class="individual-tax-sheet">
            <div class="individual-tax-toolbar">
                <div>
                    <h1 class="individual-tax-title">Income Tax Estimated Computation</h1>
                    <p class="individual-tax-subtitle">
                        First active regular employee in the system. For the year {{ selectedYearValue }}.
                    </p>
                </div>

                <div ref="toolbarForm" class="individual-tax-toolbar-form">
                    <select
                        ref="employeeSelect"
                        v-model="selectedEmployeeNo"
                        class="form-select individual-tax-employee-select"
                        :disabled="isLoading"
                    >
                        <option
                            v-for="employeeOption in currentEmployees"
                            :key="employeeOption.employee_no"
                            :value="String(employeeOption.employee_no)"
                        >
                            {{ employeeOption.display_name }} - {{ employeeOption.employee_no }}
                        </option>
                    </select>

                    <select
                        v-model="selectedYearValue"
                        class="individual-tax-select"
                        :disabled="isLoading"
                        @change="fetchData"
                    >
                        <option v-for="year in normalizedYears" :key="year" :value="year">
                            Year {{ year }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="isLoading" class="individual-tax-loading-bar"></div>

            <div class="individual-tax-meta">
                <div class="individual-tax-meta-card">
                    <span class="individual-tax-meta-label">Employee Name</span>
                    <div class="individual-tax-meta-value">{{ employeeName }}</div>
                </div>
                <div class="individual-tax-meta-card">
                    <span class="individual-tax-meta-label">Employee No</span>
                    <div class="individual-tax-meta-value">{{ currentEmployee.employee_no }}</div>
                </div>
                <div class="individual-tax-meta-card">
                    <span class="individual-tax-meta-label">Position</span>
                    <div class="individual-tax-meta-value">{{ currentEmployee.position || "N/A" }}</div>
                </div>
            </div>

            <div class="individual-tax-grid">
                <section class="individual-tax-panel">
                    <h2 class="individual-tax-heading">Gross Compensation Income</h2>

                    <table class="individual-tax-table">
                        <tbody>
                            <tr>
                                <td>Annual Basic Salary</td>
                                <td class="amount individual-tax-highlight-blue">
                                    {{ peso(currentSummary.annual_basic_salary) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Hazard Pay</td>
                                <td class="amount">{{ peso(currentSummary.annual_hazard_pay) }}</td>
                            </tr>
                            <tr>
                                <td>Longevity Pay</td>
                                <td class="amount">{{ peso(currentSummary.annual_longevity_pay) }}</td>
                            </tr>
                            <tr>
                                <td>Other Earnings</td>
                                <td class="amount">{{ peso(currentSummary.other_earnings) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gross Taxable Income</strong></td>
                                <td class="amount individual-tax-highlight-pink">
                                    {{ peso(currentSummary.gross_taxable_income) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h2 class="individual-tax-heading mt-4">Tax Computation</h2>

                    <table class="individual-tax-table">
                        <tbody>
                            <tr>
                                <td>Total Tax Withheld</td>
                                <td class="amount individual-tax-highlight-orange">
                                    {{ peso(currentSummary.total_tax_withheld) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Net After Tax</td>
                                <td class="amount individual-tax-highlight-yellow">
                                    {{ peso(currentSummary.net_after_tax) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="individual-tax-panel">
                    <h2 class="individual-tax-heading">Breakdown</h2>

                    <table class="individual-tax-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="amount">Basic Salary</th>
                                <th class="amount">Hazard Pay</th>
                                <th class="amount">Longevity</th>
                                <th class="amount">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in currentMonthlyBreakdown" :key="row.month_number">
                                <td>{{ row.month_label }}</td>
                                <td class="amount">{{ peso(row.basic_salary) }}</td>
                                <td class="amount">{{ peso(row.hazard_pay) }}</td>
                                <td class="amount">{{ peso(row.longevity_pay) }}</td>
                                <td class="amount" :class="{ 'individual-tax-highlight-blue': index === currentMonthlyBreakdown.length - 1 }">
                                    {{ peso(row.total) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="amount">{{ peso(currentSummary.annual_basic_salary) }}</th>
                                <th class="amount">{{ peso(currentSummary.annual_hazard_pay) }}</th>
                                <th class="amount">{{ peso(currentSummary.annual_longevity_pay) }}</th>
                                <th class="amount individual-tax-highlight-blue">
                                    {{ peso(currentSummary.gross_taxable_income) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </section>

                <section class="individual-tax-panel">
                    <h2 class="individual-tax-heading">Other Earnings</h2>

                    <div class="individual-tax-list">
                        <div
                            v-for="item in currentOtherComponents.earnings"
                            :key="`earning-${item.name}`"
                            class="individual-tax-list-row"
                        >
                            <span>{{ item.name }}</span>
                            <span>{{ peso(item.amount) }}</span>
                        </div>
                        <div v-if="!currentOtherComponents.earnings.length" class="individual-tax-list-row">
                            <span>No other earnings found.</span>
                            <span>{{ peso(0) }}</span>
                        </div>
                    </div>

                    <h2 class="individual-tax-heading mt-4">Taxes</h2>

                    <div class="individual-tax-list">
                        <div
                            v-for="item in currentOtherComponents.taxes"
                            :key="`tax-${item.name}`"
                            class="individual-tax-list-row"
                        >
                            <span>{{ item.name }}</span>
                            <span>{{ peso(item.amount) }}</span>
                        </div>
                        <div v-if="!currentOtherComponents.taxes.length" class="individual-tax-list-row">
                            <span>No tax components found.</span>
                            <span>{{ peso(0) }}</span>
                        </div>
                    </div>

                    <div class="individual-tax-note">
                        This view uses saved payroll and employee payroll component records for the selected year and
                        automatically shows the first employee that is both <strong>regular</strong> and <strong>active</strong>.
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        baseUrl: { type: String, required: true },
        apiUrl: { type: String, required: true },
        employee: { type: Object, required: true },
        employees: { type: Array, required: true },
        selectedYear: { type: Number, required: true },
        availableYears: { type: Array, required: true },
        monthlyBreakdown: { type: Array, required: true },
        otherComponents: { type: Object, required: true },
        summary: { type: Object, required: true },
    },

    data() {
        return {
            state: {
                employee: this.employee,
                employees: this.employees,
                selectedYear: Number(this.selectedYear || new Date().getFullYear()),
                availableYears: this.availableYears,
                monthlyBreakdown: this.monthlyBreakdown,
                otherComponents: this.otherComponents,
                summary: this.summary,
            },
            selectedEmployeeNo: String(this.employee?.employee_no || ""),
            selectedYearValue: Number(this.selectedYear || new Date().getFullYear()),
            isLoading: false,
            token: localStorage.getItem("auth_token"),
        };
    },

    computed: {
        currentEmployee() {
            return this.state.employee || {};
        },

        currentEmployees() {
            return this.state.employees || [];
        },

        currentMonthlyBreakdown() {
            return this.state.monthlyBreakdown || [];
        },

        currentOtherComponents() {
            return this.state.otherComponents || { earnings: [], taxes: [] };
        },

        currentSummary() {
            return this.state.summary || {};
        },

        employeeName() {
            if (this.currentEmployee?.display_name) {
                return this.currentEmployee.display_name;
            }

            const lastname = this.currentEmployee?.lastname || "";
            const firstname = this.currentEmployee?.firstname ? `, ${this.currentEmployee.firstname}` : "";
            const middlename = this.currentEmployee?.middlename
                ? ` ${String(this.currentEmployee.middlename).charAt(0).toUpperCase()}.`
                : "";
            const suffix = this.currentEmployee?.suffix ? ` ${this.currentEmployee.suffix}` : "";

            return `${lastname}${firstname}${middlename}${suffix}`.trim();
        },

        normalizedYears() {
            return (this.state.availableYears || []).map((year) => Number(year));
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.syncSelectionFromState();
            this.initEmployeeSelect();

            if (!this.currentEmployees.length) {
                this.fetchData();
            }
        });
    },

    beforeUnmount() {
        this.destroyEmployeeSelect();
    },

    watch: {
        currentEmployees: {
            handler() {
                this.$nextTick(() => {
                    this.syncSelectionFromState();
                    this.initEmployeeSelect();
                });
            },
            deep: true,
        },
    },

    methods: {
        peso(amount) {
            return `P ${Number(amount || 0).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        },

        syncSelectionFromState() {
            if (this.currentEmployee?.employee_no != null) {
                this.selectedEmployeeNo = String(this.currentEmployee.employee_no);
            }

            if (this.state?.selectedYear != null) {
                this.selectedYearValue = Number(this.state.selectedYear);
            }
        },

        initEmployeeSelect() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.employeeSelect;
            const dropdownParent = this.$refs.toolbarForm;

            if (!select || !jq || !this.currentEmployees.length) return;

            const $select = jq(select);

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }

            $select.select2({
                width: "resolve",
                dropdownCssClass: "individual-tax-select2-dropdown",
                placeholder: "Search employee name",
                dropdownParent: dropdownParent ? jq(dropdownParent) : undefined,
            });

            if ($select.find(`option[value="${this.selectedEmployeeNo}"]`).length) {
                $select.val(this.selectedEmployeeNo).trigger("change.select2");
            }

            $select.on("change.individual-tax", (event) => {
                this.selectedEmployeeNo = String(event.target.value || "");
                this.fetchData();
            });
        },

        destroyEmployeeSelect() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.employeeSelect;

            if (!select || !jq) return;

            const $select = jq(select);
            $select.off(".individual-tax");

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }
        },

        syncUrl() {
            const url = new URL(this.baseUrl, window.location.origin);

            if (this.selectedEmployeeNo) {
                url.searchParams.set("employee_no", this.selectedEmployeeNo);
            }

            if (this.selectedYearValue) {
                url.searchParams.set("year", String(this.selectedYearValue));
            }

            window.history.replaceState({}, "", url.toString());
        },

        applyPayload(payload = {}) {
            this.state = {
                employee: payload.employee || {},
                employees: payload.employees || [],
                selectedYear: Number(payload.selectedYear || this.selectedYearValue),
                availableYears: payload.availableYears || [],
                monthlyBreakdown: payload.monthlyBreakdown || [],
                otherComponents: payload.otherComponents || { earnings: [], taxes: [] },
                summary: payload.summary || {},
            };

            this.syncSelectionFromState();
            this.$nextTick(() => {
                this.initEmployeeSelect();
            });
            this.syncUrl();
        },

        async fetchData() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const response = await axios.get(this.apiUrl, {
                    params: {
                        employee_no: this.selectedEmployeeNo,
                        year: this.selectedYearValue,
                    },
                    headers: this.token
                        ? { Authorization: `Bearer ${this.token}` }
                        : {},
                });

                this.applyPayload(response.data || {});
            } catch (error) {
                console.error("Failed to load individual tax data:", error);
                window.ErrorToast?.fire?.({
                    title: "Failed to load individual tax data.",
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.individual-tax-page {
    padding: 20px 18px;
    background: linear-gradient(
        180deg,
        rgba(var(--bs-primary-rgb), 0.15) 0%,
        var(--bs-body-bg) 100%
    );
}

.individual-tax-sheet {
    max-width: 1480px;
    margin: 0 auto;
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    position: relative;
}

.individual-tax-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1.125rem 1.375rem;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-secondary-bg);

    &-form {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
}

.individual-tax-title {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--bs-body-color);
}

.individual-tax-loading-bar {
    height: 3px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(var(--bs-primary-rgb), 0.9) 30%,
        rgba(var(--bs-info-rgb), 0.9) 60%,
        transparent 100%
    );
    animation: individual-tax-loading 1.2s linear infinite;
}

.individual-tax-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--bs-secondary-color);
}

.individual-tax-employee-select,
.individual-tax-select {
    min-width: 180px;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-weight: 600;
}

.individual-tax-employee-select {
    min-width: 320px;
}

.individual-tax-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    padding: 1.125rem 1.375rem 0;

    &-card {
        padding: 0.875rem 1rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
    }

    &-label {
        display: block;
        margin-bottom: 0.375rem;
        font-size: 0.6875rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    &-value {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--bs-body-color);
    }
}

.individual-tax-grid {
    display: grid;
    grid-template-columns: 1.1fr 1.5fr 1fr;
    padding: 1.125rem 1.375rem 1.375rem;
}

.individual-tax-panel {
    min-width: 0;

    & + & {
        margin-left: 1.375rem;
        padding-left: 1.375rem;
        border-left: 1px solid var(--bs-border-color);
    }
}

.individual-tax-heading {
    margin: 0 0 0.75rem;
    font-size: 0.9375rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--bs-body-color);
}

.individual-tax-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;

    th,
    td {
        padding: 0.375rem 0.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        vertical-align: middle;
        color: var(--bs-body-color);
    }

    th {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        background: var(--bs-secondary-bg);
    }

    .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    tfoot th {
        background: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
    }
}

.individual-tax-highlight-blue {
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary-text-emphasis);
    font-weight: 800;
}

.individual-tax-highlight-yellow {
    background: var(--bs-warning-bg-subtle);
    color: var(--bs-warning-text-emphasis);
    font-weight: 800;
}

.individual-tax-highlight-pink {
    background: var(--bs-danger-bg-subtle);
    color: var(--bs-danger-text-emphasis);
    font-weight: 800;
}

.individual-tax-highlight-orange {
    background: var(--bs-warning-bg-subtle);
    color: var(--bs-warning-text-emphasis);
    font-weight: 800;
}

.individual-tax-list {
    display: grid;
    gap: 0.5rem;

    &-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.5rem 0.625rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        font-size: 0.8125rem;
        color: var(--bs-body-color);

        span:last-child {
            font-weight: 700;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
    }
}

.individual-tax-note {
    margin-top: 1rem;
    padding: 0.75rem 0.875rem;
    border: 1px dashed var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
}

/* Select2 */
:deep(.select2-container) {
    min-width: 320px !important;
}

:deep(.select2-selection--single) {
    min-height: 44px;
    border: 1px solid var(--bs-border-color) !important;
    border-radius: var(--bs-border-radius) !important;
    background: var(--bs-body-bg) !important;
}

:deep(.select2-selection__rendered) {
    color: var(--bs-body-color) !important;
    line-height: 42px !important;
    font-weight: 600;
}

:deep(.select2-dropdown) {
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}

:deep(.select2-search--dropdown) {
    padding: 0.625rem;
    background: var(--bs-secondary-bg);
}

:deep(.select2-search__field) {
    border: 1px solid var(--bs-border-color) !important;
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
}

:deep(.select2-results__option--highlighted) {
    background: var(--bs-primary-bg-subtle) !important;
    color: var(--bs-primary-text-emphasis) !important;
}

@keyframes individual-tax-loading {
    0% {
        transform: translateX(-35%);
        opacity: 0.45;
    }

    50% {
        opacity: 1;
    }

    100% {
        transform: translateX(35%);
        opacity: 0.45;
    }
}

@media (max-width: 1200px) {
    .individual-tax-grid {
        grid-template-columns: 1fr;
        gap: 1.125rem;
    }

    .individual-tax-panel + .individual-tax-panel {
        margin-left: 0;
        padding-left: 0;
        padding-top: 1.125rem;
        border-left: 0;
        border-top: 1px solid var(--bs-border-color);
    }
}

@media (max-width: 768px) {
    .individual-tax-toolbar,
    .individual-tax-meta {
        display: grid;
        grid-template-columns: 1fr;
    }

    .individual-tax-toolbar-form {
        width: 100%;
    }

    .individual-tax-employee-select,
    .individual-tax-select {
        width: 100%;
        min-width: 0;
    }

    :deep(.select2-container) {
        width: 100% !important;
        min-width: 0 !important;
    }
}
</style>
