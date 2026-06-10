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
                                <template v-if="isRowsArray(step.value)">
                                    <div class="d-flex justify-content-between small old-row old-row-head">
                                        <span class="text-muted">{{ safeText(step.label) }}</span>
                                        <span class="fw-semibold">{{ safeText(step.value.length) }} item(s)</span>
                                    </div>

                                    <div class="old-nested">
                                        <div
                                            v-for="(row, rowIndex) in step.value"
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
                                <i class="fa-solid fa-layer-group text-muted"></i>
                                <span class="fw-semibold small">Breakdown</span>
                            </div>
                            <span class="fw-bold small text-info">{{ money(computationsTotal) }}</span>
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
                                v-for="computationItem in computationsList"
                                :key="computationItem._k"
                                class="old-module"
                            >
                                <div class="d-flex justify-content-between small old-row old-row-head">
                                    <span class="text-muted">
                                        {{ safeText(computationItem.label) }}
                                        <span class="ms-2 badge old-pill bg-info-subtle text-info border border-info-subtle">
                                            {{ safeText(computationItem.type) }}
                                        </span>
                                    </span>
                                    <span class="fw-semibold">{{ money(computationItem.total) }}</span>
                                </div>

                                <div v-if="computationItem.monthly && computationItem.monthly.length" class="old-nested">
                                    <div
                                        v-for="(month, index) in computationItem.monthly"
                                        :key="index"
                                        class="d-flex justify-content-between small old-row old-row-nested"
                                    >
                                        <span class="text-muted">{{ safeText(month.label) }}</span>
                                        <span class="fw-semibold">{{ money(safeNum(month.amount)) }}</span>
                                    </div>
                                </div>

                                <div v-else class="small text-muted pt-1">
                                    {{ safeText(computationItem.formula) }}
                                </div>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold text-info">{{ money(computationsTotal) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                For <strong>Allowable Deductions</strong>, this expands GSIS / PAG-IBIG / PHILHEALTH plus any other deductions.
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
        computationsList() {
            const rows = (this.computation && this.computation.computations) || [];

            return rows.map((item, index) => {
                const monthly = Array.isArray(item.inputs && item.inputs.monthly)
                    ? item.inputs.monthly.map((month) => ({
                          label: month.label || month.month || "",
                          amount: Number(month.amount) || 0,
                      }))
                    : [];

                return {
                    _k: `${item.key || "comp"}_${index}`,
                    label: item.label || item.key || "Item",
                    type: (item.meta && item.meta.type) || "",
                    total:
                        Number(item.result_raw) ||
                        Number(item.inputs && item.inputs.total) ||
                        Number(item.inputs && item.inputs.amount) ||
                        0,
                    monthly,
                    formula: item.formula || "",
                };
            });
        },
        computationsTotal() {
            return this.computationsList.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
        },
        inputRows() {
            const inputs = this.computation.inputs || {};
            const rows = [];

            if (inputs.employee_no) rows.push({ label: "Employee No", value: this.safeText(inputs.employee_no) });
            if (inputs.year) rows.push({ label: "Year", value: this.safeNum(inputs.year) });
            if (inputs.items_count !== undefined && inputs.items_count !== null) {
                rows.push({ label: "Items count", value: this.safeNum(inputs.items_count) });
            }
            if (inputs.other_deductions !== undefined && inputs.other_deductions !== null) {
                rows.push({ label: "Other deductions", value: this.money(inputs.other_deductions) });
            }
            if (inputs.gsis !== undefined && inputs.gsis !== null) {
                rows.push({ label: "GSIS", value: this.money(inputs.gsis) });
            }
            if (inputs.pagibig !== undefined && inputs.pagibig !== null) {
                rows.push({ label: "PAG-IBIG", value: this.money(inputs.pagibig) });
            }
            if (inputs.philhealth !== undefined && inputs.philhealth !== null) {
                rows.push({ label: "PHILHEALTH", value: this.money(inputs.philhealth) });
            }
            if (inputs.grand_total !== undefined && inputs.grand_total !== null) {
                rows.push({ label: "Grand total", value: this.money(inputs.grand_total) });
            }
            if (inputs.eligible !== undefined) {
                rows.push({ label: "Eligibility", value: this.formatValue(inputs.eligible) });
            }
            if (inputs.as_of_date) {
                rows.push({ label: "As of", value: this.formatDate(inputs.as_of_date) });
            }

            if (!rows.length) rows.push({ label: "Inputs", value: "-" });
            return rows;
        },
    },
};
</script>
