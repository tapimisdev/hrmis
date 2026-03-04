<template>
    <BreakdownViewTemplate
        :computation="computation"
        :result-value="formatMoney(computation.result_raw, computation.result)"
        :summary-label="summaryCountLabel"
        :summary-value="summaryCountValue"
        :effective-date="effectiveDateText"
    >
        <template #default="{ accordionId, panelId }">
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + panelId('inputs')"
                        aria-expanded="false"
                        :aria-controls="panelId('inputs')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-sliders text-muted"></i>
                                <span class="fw-semibold small">Inputs</span>
                            </div>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('inputs')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <template v-for="row in inputRows" :key="row.label">
                                <div class="d-flex justify-content-between small old-row">
                                    <span class="text-muted">{{ row.label }}</span>
                                    <span class="fw-semibold">{{ row.value }}</span>
                                </div>
                            </template>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                These are the raw values used to compute the result.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + panelId('steps')"
                        aria-expanded="false"
                        :aria-controls="panelId('steps')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-list-check text-muted"></i>
                                <span class="fw-semibold small">Steps</span>
                            </div>
                            <span class="fw-bold small">
                                {{ (computation.steps || []).length }} item(s)
                            </span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('steps')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(step, index) in (computation.steps || [])"
                                :key="index"
                                class="old-step"
                            >
                                <template v-if="!isRowsArray(step.value)">
                                    <div class="d-flex justify-content-between small old-row">
                                        <span class="text-muted">{{ safeText(step.label) }}</span>
                                        <span class="fw-semibold">{{ formatValue(step.value) }}</span>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="d-flex justify-content-between small old-row old-row-head">
                                        <span class="text-muted">{{ safeText(step.label) }}</span>
                                        <span class="fw-semibold">{{ (step.value || []).length }} item(s)</span>
                                    </div>

                                    <div class="old-nested">
                                        <div
                                            v-for="(row, rowIndex) in (step.value || [])"
                                            :key="rowIndex"
                                            class="d-flex justify-content-between small old-row old-row-nested"
                                        >
                                            <span class="text-muted">{{ safeText(row.label) }}</span>
                                            <span class="fw-semibold">{{ safeText(row.value) }}</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-calculator me-2"></i>
                                {{ safeText(computation.formula) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="hasComputations" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + panelId('computations')"
                        aria-expanded="false"
                        :aria-controls="panelId('computations')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-code-branch text-muted"></i>
                                <span class="fw-semibold small">Computations</span>
                            </div>
                            <span class="fw-bold small">
                                {{ (computation.computations || []).length }} item(s)
                            </span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('computations')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(item, index) in (computation.computations || [])"
                                :key="index"
                                class="old-module"
                            >
                                <div class="d-flex justify-content-between small old-row old-row-head">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-muted">{{ safeText(item.label) }}</span>

                                        <span class="badge old-pill bg-info-subtle text-info border border-info-subtle">
                                            {{ safeText(item.meta && item.meta.type) }}
                                        </span>

                                        <span
                                            v-if="item.meta && item.meta.is_non_taxable === true"
                                            class="badge old-pill bg-success-subtle text-success border border-success-subtle"
                                        >
                                            NON-TAXABLE
                                        </span>
                                        <span
                                            v-else-if="item.meta && item.meta.is_non_taxable === false"
                                            class="badge old-pill bg-warning-subtle text-warning border border-warning-subtle"
                                        >
                                            TAXABLE
                                        </span>
                                    </div>

                                    <span class="fw-semibold">
                                        {{ formatMoney(item.result_raw, item.result) }}
                                    </span>
                                </div>

                                <div class="old-nested">
                                    <div
                                        v-for="(step, stepIndex) in (item.steps || [])"
                                        :key="stepIndex"
                                        class="d-flex justify-content-between small old-row old-row-nested"
                                    >
                                        <span class="text-muted">{{ safeText(step.label) }}</span>
                                        <span class="fw-semibold">{{ formatValue(step.value) }}</span>
                                    </div>
                                </div>

                                <div v-if="hasItemMonthly(item)" class="old-months mt-1">
                                    <div class="d-flex justify-content-between small old-row old-row-head">
                                        <span class="text-muted">Monthly breakdown</span>
                                        <span class="fw-semibold">{{ money(itemMonthlyTotal(item)) }}</span>
                                    </div>

                                    <div class="old-nested">
                                        <div
                                            v-for="(month, monthIndex) in itemMonthly(item)"
                                            :key="monthIndex"
                                            class="d-flex justify-content-between small old-row old-row-nested"
                                        >
                                            <span class="text-muted">{{ safeText(month.label || month.month) }}</span>
                                            <span class="fw-semibold">{{ money(safeNum(month.amount)) }}</span>
                                        </div>
                                    </div>

                                    <div class="old-total small">
                                        <span class="text-muted">Total</span>
                                        <span class="fw-bold text-info">{{ money(itemMonthlyTotal(item)) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Detailed per-item computations used to build the grand total.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="hasModules" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + panelId('modules')"
                        aria-expanded="false"
                        :aria-controls="panelId('modules')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-layer-group text-muted"></i>
                                <span class="fw-semibold small">Modules Breakdown</span>
                            </div>
                            <span class="fw-bold small text-info">{{ money(modulesTotal) }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('modules')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div v-for="module in modulesList" :key="module.key" class="old-module">
                                <div class="d-flex justify-content-between small old-row old-row-head">
                                    <span class="text-muted">{{ module.title }}</span>
                                    <span class="fw-semibold">{{ money(module.total) }}</span>
                                </div>

                                <div class="old-nested">
                                    <div
                                        v-for="(item, index) in module.monthly"
                                        :key="index"
                                        class="d-flex justify-content-between small old-row old-row-nested"
                                    >
                                        <span class="text-muted">{{ safeText(item.label) }}</span>
                                        <span class="fw-semibold">{{ money(item.amount) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold text-info">{{ money(modulesTotal) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Breakdown per module (GSIS / Pag-IBIG / PhilHealth) and monthly values.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="hasMonths" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + panelId('months')"
                        aria-expanded="false"
                        :aria-controls="panelId('months')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-muted"></i>
                                <span class="fw-semibold small">Monthly Breakdown</span>
                            </div>
                            <span class="fw-bold small text-info">{{ money(monthsTotal) }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('months')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(month, index) in (computation.months || [])"
                                :key="index"
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted">{{ safeText(month.month) }}</span>
                                <span class="fw-semibold">{{ money(safeNum(month.amount)) }}</span>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold text-info">{{ money(monthsTotal) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                This section lists the month-by-month values used for the annualized result.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </BreakdownViewTemplate>
</template>

<script>
import BreakdownViewTemplate from "../components/BreakdownViewTemplate.vue";
import computationView from "../mixins/computationView";

export default {
    name: "TaxComputationJsonCard",
    components: { BreakdownViewTemplate },
    mixins: [computationView],
    computed: {
        effectiveDateText() {
            return this.effectiveDateUsed === "-" ? "" : this.effectiveDateUsed;
        },
        hasComputations() {
            const rows = (this.computation && this.computation.computations) || [];
            return Array.isArray(rows) && rows.length > 0;
        },
        hasModules() {
            const modules = this.computation.inputs && this.computation.inputs.modules;
            return !!modules && typeof modules === "object" && Object.keys(modules).length > 0;
        },
        modulesList() {
            const modules = (this.computation.inputs && this.computation.inputs.modules) || {};
            const map = [
                { key: "gsis", title: "GSIS" },
                { key: "pagibig", title: "Pag-IBIG" },
                { key: "philhealth", title: "PhilHealth" },
            ];

            return map
                .filter((item) => modules[item.key])
                .map((item) => ({
                    key: item.key,
                    title: item.title,
                    total: Number(modules[item.key].total) || 0,
                    monthly: Array.isArray(modules[item.key].monthly) ? modules[item.key].monthly : [],
                }));
        },
        modulesTotal() {
            return this.modulesList.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
        },
        inputRows() {
            const inputs = this.computation.inputs || {};
            const rows = [];

            if (inputs.employee_no) rows.push({ label: "Employee No", value: this.safeText(inputs.employee_no) });
            if (inputs.year) rows.push({ label: "Year", value: this.safeNum(inputs.year) });
            if (inputs.eligible !== undefined) rows.push({ label: "Eligibility", value: this.formatValue(inputs.eligible) });
            if (inputs.as_of_date) rows.push({ label: "As of", value: this.formatDate(inputs.as_of_date) });
            if (inputs.months_of_service !== undefined && inputs.months_of_service !== null) {
                rows.push({ label: "Months of service (as of)", value: this.safeNum(inputs.months_of_service) });
            }
            if (inputs.months_covered !== undefined && inputs.months_covered !== null) {
                rows.push({ label: "Months covered", value: this.safeNum(inputs.months_covered) });
            }
            if (inputs.basic_salary_as_of !== undefined && inputs.basic_salary_as_of !== null) {
                rows.push({ label: "Basic salary as of", value: this.money(inputs.basic_salary_as_of) });
            }
            if (inputs.monthly_salary !== undefined && inputs.monthly_salary !== null) {
                rows.push({ label: "Monthly salary", value: this.money(inputs.monthly_salary) });
            }
            if (inputs.items_count !== undefined && inputs.items_count !== null) {
                rows.push({ label: "Items count", value: this.safeNum(inputs.items_count) });
            }
            if (inputs.taxable_total !== undefined && inputs.taxable_total !== null) {
                rows.push({ label: "Taxable total", value: this.money(inputs.taxable_total) });
            }
            if (inputs.non_taxable_total !== undefined && inputs.non_taxable_total !== null) {
                rows.push({ label: "Non-taxable total", value: this.money(inputs.non_taxable_total) });
            }
            if (inputs.grand_total !== undefined && inputs.grand_total !== null) {
                rows.push({ label: "Grand total", value: this.money(inputs.grand_total) });
            }
            if (inputs.modules && typeof inputs.modules === "object") {
                rows.push({ label: "Modules", value: Object.keys(inputs.modules).join(", ").toUpperCase() });
            }
            if (inputs.eligibility_reason) {
                rows.push({ label: "Reason", value: this.safeText(inputs.eligibility_reason) });
            }

            if (!rows.length) rows.push({ label: "Inputs", value: "-" });
            return rows;
        },
    },
    methods: {
        itemMonthly(item) {
            const rows = item && item.inputs && item.inputs.monthly;
            return Array.isArray(rows) ? rows : [];
        },
        hasItemMonthly(item) {
            return this.itemMonthly(item).length > 0;
        },
        itemMonthlyTotal(item) {
            return this.itemMonthly(item).reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
        },
    },
};
</script>
