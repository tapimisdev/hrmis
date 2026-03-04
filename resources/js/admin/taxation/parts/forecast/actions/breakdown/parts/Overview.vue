<template>
    <BreakdownViewTemplate
        :section-title="'Computation Overview'"
        :result-class="'text-primary'"
    >
        <template #identity>
            <div>
                <div class="fw-semibold text-body old-name">{{ row.full_name }}</div>
                <div class="small text-muted old-sub">{{ row.position }}</div>
                <div class="small text-muted old-sub">
                    {{ row.division }}
                    <span v-if="row.unit && row.unit !== row.division">
                        ( {{ row.unit }} )
                    </span>
                </div>
            </div>
        </template>

        <template #badge>
            <span class="badge old-pill bg-primary-subtle text-primary border border-primary-subtle">
                ACTIVE
            </span>
            <div class="small text-muted mt-1">Forecast</div>
        </template>

        <template #headline-left>
            <div>
                <div class="small text-muted">Monthly Tax</div>
                <div class="fs-4 fw-bold text-primary old-money">
                    {{ row.amount_monthly_tax }}
                </div>
                <div class="small text-muted">= Annual Tax ÷ 12</div>
            </div>
        </template>

        <template #headline-right>
            <div class="small text-muted">Annual Tax</div>
            <div class="fw-semibold fs-5 text-body">{{ row.amount_annual_tax }}</div>
            <div class="small text-muted">TRAIN Law (Bracket)</div>
        </template>

        <template #default="{ accordionId, panelId }">
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + panelId('gross')"
                        aria-expanded="false"
                        :aria-controls="panelId('gross')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-sack-dollar text-muted"></i>
                                <span class="fw-semibold small">Annual Gross</span>
                            </div>
                            <span class="fw-bold small">{{ row.amount_gross }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('gross')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Annual Basic Salary</span>
                                <span class="fw-semibold">{{ row.amount_anual_total_basic_salary }}</span>
                            </div>
                            <div v-if="row.hazard_pay" class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Hazard Pay (Annual)</span>
                                <span class="fw-semibold">{{ row.amount_hazard_pay }}</span>
                            </div>
                            <div v-if="row.mid_year" class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Mid-Year Bonus</span>
                                <span class="fw-semibold">{{ row.amount_mid_year_bonus }}</span>
                            </div>
                            <div v-if="row.year_end" class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Year-End Bonus</span>
                                <span class="fw-semibold">{{ row.amount_year_end_bonus }}</span>
                            </div>
                            <div v-if="row.longevity" class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Longevity</span>
                                <span class="fw-semibold">{{ row.amount_longevity_pay }}</span>
                            </div>
                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Other Earnings (Non-taxable)</span>
                                <span class="fw-semibold">{{ row.amount_other_earnings_non_taxable }}</span>
                            </div>
                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Other Earnings (Taxable)</span>
                                <span class="fw-semibold">{{ row.amount_other_earnings_taxable }}</span>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold">{{ row.amount_gross }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-calculator me-2"></i>
                                Annual Gross = Basic + Hazard + Mid-Year + Year-End + Other Earnings
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
                        :data-bs-target="'#' + panelId('less')"
                        aria-expanded="false"
                        :aria-controls="panelId('less')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-minus-circle text-muted"></i>
                                <span class="fw-semibold small">Less (Exemptions & Allowables)</span>
                            </div>
                            <span class="fw-bold small text-danger">{{ row.amount_less }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('less')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Non-taxable earnings</span>
                            <span class="fw-semibold text-danger">- {{ row.amount_other_earnings_non_taxable }} asdasd</span>
                        </div>

                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Total Allowables (GSIS + PHIC + PAGIBIG)</span>
                            <span class="fw-semibold text-danger">- {{ row.amount_annual_total_allowables }}</span>
                        </div>

                        <div
                            v-if="row.less_bir_rr3_2015"
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">BIR RR 3-2015 exemption (₱ 90,000 Cap)</span>
                            <span class="fw-semibold text-danger">- {{ row.amount_bonuses_exempt }}</span>
                        </div>

                        <div class="old-total small mt-2">
                            <span class="text-muted">Annual Taxable</span>
                            <span class="fw-bold text-primary">{{ row.amount_annual_taxable }}</span>
                        </div>

                        <div class="old-tip mt-2 small text-muted">
                            <i class="fa-solid fa-calculator me-2"></i>
                            Annual Taxable = Annual Gross − (Non-taxable + Allowables + RR 3-2015)
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
                        :data-bs-target="'#' + panelId('train')"
                        aria-expanded="false"
                        :aria-controls="panelId('train')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-scale-balanced text-muted"></i>
                                <span class="fw-semibold small">TRAIN Law Breakdown</span>
                            </div>
                            <span class="fw-bold small">{{ row.amount_annual_tax }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('train')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Bracket</span>
                            <span class="fw-semibold">
                                {{ row.tax_computation.bracket_from }} – {{ row.tax_computation.bracket_to }}
                            </span>
                        </div>

                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Base tax</span>
                            <span class="fw-semibold">{{ row.tax_computation.fixed_tax }}</span>
                        </div>

                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Excess over {{ row.tax_computation.excess_over }}</span>
                            <span class="fw-semibold">{{ row.tax_computation.excess_amount }}</span>
                        </div>

                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Rate on excess</span>
                            <span class="fw-semibold">{{ row.tax_computation.tax_rate }}%</span>
                        </div>

                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Tax on excess</span>
                            <span class="fw-semibold">{{ row.tax_computation.tax }}</span>
                        </div>

                        <div class="old-total small mt-2">
                            <span class="text-muted">Annual Tax</span>
                            <span class="fw-bold">{{ row.amount_annual_tax }}</span>
                        </div>

                        <div class="old-tip mt-2 small text-muted">
                            <i class="fa-solid fa-calculator me-2"></i>
                            Annual Tax = Base Tax + (Excess × Rate)
                        </div>

                        <div class="old-tip mt-2 small text-muted">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            Monthly tax = <span class="fw-semibold">Annual Tax ÷ 12</span>
                        </div>

                        <div class="old-total small mt-2">
                            <span class="text-muted">Remarks</span>
                            <span class="fw-bold">{{ row.tax_computation.remarks }}</span>
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
                        :data-bs-target="'#' + panelId('monthly')"
                        aria-expanded="false"
                        :aria-controls="panelId('monthly')"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar-day text-muted"></i>
                                <span class="fw-semibold small">Monthly Tax</span>
                            </div>
                            <span class="fw-bold small text-primary">{{ row.amount_monthly_tax }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="panelId('monthly')"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accordionId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">Annual Tax</span>
                            <span class="fw-semibold">{{ row.amount_annual_tax }}</span>
                        </div>
                        <div class="old-row small d-flex justify-content-between">
                            <span class="text-muted">÷ months</span>
                            <span class="fw-semibold">{{ row.months_covered }}</span>
                        </div>
                        <div class="old-total small mt-2">
                            <span class="text-muted">Monthly Tax</span>
                            <span class="fw-bold text-primary">{{ row.amount_monthly_tax }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </BreakdownViewTemplate>
</template>

<script>
import BreakdownViewTemplate from "../components/BreakdownViewTemplate.vue";

export default {
    name: "TaxForecastCardBreakdownAccordion",
    components: { BreakdownViewTemplate },
    props: {
        row: { type: Object, default: () => ({}) },
    },
};
</script>
