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
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted">{{ safeText(step.label) }}</span>
                                <span class="fw-semibold">{{ formatValue(step.value) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-calculator me-2"></i>
                                {{ safeText(computation.formula) }}
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
        inputRows() {
            const inputs = this.computation.inputs || {};
            const rows = [];

            if (inputs.eligible !== undefined) {
                rows.push({
                    label: "Eligibility",
                    value: this.formatValue(inputs.eligible),
                });
            }

            if (inputs.as_of_date) {
                rows.push({
                    label: "As of",
                    value: this.formatDate(inputs.as_of_date),
                });
            }

            if (inputs.months_of_service !== undefined && inputs.months_of_service !== null) {
                rows.push({
                    label: "Months of service (as of)",
                    value: this.safeNum(inputs.months_of_service),
                });
            } else if (inputs.months_covered !== undefined && inputs.months_covered !== null) {
                rows.push({
                    label: "Months covered",
                    value: this.safeNum(inputs.months_covered),
                });
            }

            if (inputs.basic_salary_as_of !== undefined && inputs.basic_salary_as_of !== null) {
                rows.push({
                    label: "Basic salary as of",
                    value: this.money(inputs.basic_salary_as_of),
                });
            }

            if (inputs.eligibility_reason) {
                rows.push({
                    label: "Reason",
                    value: this.safeText(inputs.eligibility_reason),
                });
            }

            if (!rows.length) {
                rows.push({ label: "Inputs", value: "-" });
            }

            return rows;
        },
    },
};
</script>
