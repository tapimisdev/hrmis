<template>
    <BreakdownViewTemplate
        :computation="computation"
        :result-value="formatMoney(computation.result_raw, computation.result)"
    >
        <template #headline-right>
            <div class="small text-muted">Months Covered</div>
            <div class="fw-semibold fs-5 text-body">
                {{ safeNum(computation.inputs && computation.inputs.months_covered) }}
            </div>
            <div class="small text-muted">
                Year: {{ safeNum(computation.inputs && computation.inputs.year) || "-" }}
            </div>
        </template>

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
                            <span class="fw-bold small">
                                {{ safeText(computation.inputs && computation.inputs.component_type) || "-" }}
                            </span>
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
                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Employee No</span>
                                <span class="fw-semibold">
                                    {{ safeText(computation.inputs && computation.inputs.employee_no) || "-" }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Year</span>
                                <span class="fw-semibold">
                                    {{ safeNum(computation.inputs && computation.inputs.year) || "-" }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Component Type</span>
                                <span class="fw-semibold">
                                    {{ safeText(computation.inputs && computation.inputs.component_type) || "-" }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Payroll Component ID</span>
                                <span class="fw-semibold">
                                    {{ safeNum(computation.inputs && computation.inputs.payroll_component_id) || "-" }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Component Year ID</span>
                                <span class="fw-semibold">
                                    {{ safeNum(computation.inputs && computation.inputs.component_year_id) || "-" }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Months Covered</span>
                                <span class="fw-semibold">
                                    {{ safeNum(computation.inputs && computation.inputs.months_covered) }}
                                </span>
                            </div>

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
                                class="old-row"
                            >
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">{{ safeText(step.label) }}</span>
                                    <span class="fw-semibold">
                                        <template v-if="isComplex(step.value)">
                                            {{ complexHint(step.value) }}
                                        </template>
                                        <template v-else>
                                            {{ safeText(step.value) }}
                                        </template>
                                    </span>
                                </div>

                                <div v-if="isComplex(step.value)" class="mt-2">
                                    <div class="old-mini inner">
                                        <pre class="json-pre">{{ pretty(step.value) }}</pre>
                                    </div>
                                </div>
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
                                v-for="(month, index) in monthsList"
                                :key="index"
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted">{{ safeText(month.label || month.month) }}</span>
                                <span class="fw-semibold">{{ money(safeNum(month.amount)) }}</span>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold text-info">{{ money(monthsTotal) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Month-by-month values (from <code>inputs.monthly</code>).
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
        monthsList() {
            const rows = (this.computation.inputs && this.computation.inputs.monthly) || [];
            return Array.isArray(rows) ? rows : [];
        },
        hasMonths() {
            return this.monthsList.length > 0;
        },
        monthsTotal() {
            return this.monthsList.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
        },
    },
};
</script>

<style scoped>
.inner {
    background: var(--bs-body-bg);
}

.json-pre {
    margin: 0;
    font-size: 0.8rem;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
