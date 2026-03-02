<template>
    <div class="card-simple p-3 mb-2 old-social-card">
        <!-- Identity -->
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="fw-semibold text-body old-name">{{ row.full_name }}</div>
                <div class="small text-muted old-sub">
                    {{ row.position }}
                </div>
                <div class="small text-muted old-sub">
                   {{ row.division }}
                    <span v-if="row.unit && row.unit !== row.division">
                    ( {{ row.unit }} )
                    </span>
                </div>
            </div>

            <div class="text-end">
                <span
                    class="badge old-pill bg-primary-subtle text-primary border border-primary-subtle"
                >
                    ACTIVE
                </span>
                <div class="small text-muted mt-1">Forecast</div>
            </div>
        </div>

        <hr class="my-3 old-divider" />

        <!-- Results headline -->
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <div class="small text-muted">Monthly Tax</div>
                <div class="fs-4 fw-bold text-primary old-money">
                    {{ row.amount_monthly_tax }}
                </div>
                <div class="small text-muted">= Annual Tax ÷ 12</div>
            </div>

            <div class="text-end">
                <div class="small text-muted">Annual Tax</div>
                <div class="fw-semibold fs-5 text-body">{{ row.amount_annual_tax }}</div>
                <div class="small text-muted">TRAIN Law (Bracket)</div>
            </div>
        </div>

        <hr class="my-3 old-divider" />

        <!-- Breakdown overview -->
        <div class="fw-semibold small mb-2 text-body-emphasis">
            Computation Overview
        </div>

        <!-- ACCORDION -->
        <div class="accordion accordion-flush old-accordion" :id="accId">
            <!-- 1) Annual Gross -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + grossId"
                        aria-expanded="false"
                        :aria-controls="grossId"
                    >
                        <div
                            class="w-100 d-flex justify-content-between align-items-center"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i
                                    class="fa-solid fa-sack-dollar text-muted"
                                ></i>
                                <span class="fw-semibold small"
                                    >Annual Gross</span
                                >
                            </div>
                            <span class="fw-bold small">{{ row.amount_gross }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="grossId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted"
                                    >Annual Basic Salary</span
                                >
                                <span class="fw-semibold">{{ row.amount_anual_total_basic_salary }}</span>
                            </div>
                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted"
                                    >Hazard Pay (Annual)</span
                                >
                                <span class="fw-semibold">{{ row.amount_hazard_pay }}</span>
                            </div>
                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted">Mid-Year Bonus</span>
                                <span class="fw-semibold">{{ row.amount_mid_year_bonus }}</span>
                            </div>
                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted">Year-End Bonus</span>
                                <span class="fw-semibold">{{ row.amount_year_end_bonus }}</span>
                            </div>
                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted">Longevity</span>
                                <span class="fw-semibold">{{ row.amount_longevity_pay }}</span>
                            </div>
                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted"
                                    >Other Earnings (Non-taxable)</span
                                >
                                <span class="fw-semibold">{{ row.amount_other_earnings_taxable }}</span>
                            </div>

                            <div
                                class="d-flex justify-content-between small old-row"
                            >
                                <span class="text-muted"
                                    >Other Earnings (Taxable)</span
                                >
                                <span class="fw-semibold">{{ row.amount_other_earnings_taxable }}</span>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold">{{ row.amount_gross  }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-calculator me-2"></i>
                                Annual Gross = Basic + Hazard + Mid-Year +
                                Year-End + Other Earnings
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2) Less (Exemptions & Allowables) -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + lessId"
                        aria-expanded="false"
                        :aria-controls="lessId"
                    >
                        <div
                            class="w-100 d-flex justify-content-between align-items-center"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i
                                    class="fa-solid fa-minus-circle text-muted"
                                ></i>
                                <span class="fw-semibold small"
                                    >Less (Exemptions & Allowables)</span
                                >
                            </div>
                            <span class="fw-bold small text-danger"
                                > {{ row.amount_less }}</span
                            >
                        </div>
                    </button>
                </h2>

                <div
                    :id="lessId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">Non-taxable earnings</span>
                            <span class="fw-semibold text-danger"
                                >- {{ row.amount_other_earnings_non_taxable }}</span
                            >
                        </div>

                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted"
                                >Total Allowables (GSIS + PHIC + PAGIBIG)</span
                            >
                            <span class="fw-semibold text-danger"
                                >- {{ row.amount_annual_total_allowables }}</span
                            >
                        </div>

                        <div
                            v-if="row.less_bir_rr3_2015"
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted"
                                >BIR RR 3-2015 exemption (₱ 90,000 Cap)</span
                            >
                            <span class="fw-semibold text-danger"
                                >- {{ row.amount_bonuses_exempt }}</span
                            >
                        </div>

                        <div class="old-total small mt-2">
                            <span class="text-muted">Annual Taxable</span>
                            <span class="fw-bold text-primary"
                                >{{ row.amount_annual_taxable }}</span
                            >
                        </div>

                        <div class="old-tip mt-2 small text-muted">
                            <i class="fa-solid fa-calculator me-2"></i>
                            Annual Taxable = Annual Gross − (Non-taxable +
                            Allowables + RR 3-2015)
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3) TRAIN bracket -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + trainId"
                        aria-expanded="false"
                        :aria-controls="trainId"
                    >
                        <div
                            class="w-100 d-flex justify-content-between align-items-center"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i
                                    class="fa-solid fa-scale-balanced text-muted"
                                ></i>
                                <span class="fw-semibold small"
                                    >TRAIN Law Breakdown</span
                                >
                            </div>
                            <span class="fw-bold small">{{ row.amount_annual_tax }}</span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="trainId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">Bracket</span>
                            <span class="fw-semibold"
                                >{{ row.tax_computation.bracket_from }} – {{ row.tax_computation.bracket_to }}</span
                            >
                        </div>

                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">Base tax</span>
                            <span class="fw-semibold">{{ row.tax_computation.fixed_tax }}</span>
                        </div>

                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted"
                                >Excess over {{ row.tax_computation.excess_over }}</span
                            >
                            <span class="fw-semibold">{{ row.tax_computation.excess_amount }}</span>
                        </div>

                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">Rate on excess</span>
                            <span class="fw-semibold">{{ row.tax_computation.tax_rate }}%</span>
                        </div>

                        <div
                            class="old-row small d-flex justify-content-between"
                        >
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
                            Monthly tax =
                            <span class="fw-semibold">Annual Tax ÷ 12</span>
                        </div>

                        <div class="old-total small mt-2">
                            <span class="text-muted">Remarks</span>
                            <span class="fw-bold">{{ row.tax_computation.remarks }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4) Monthly tax -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + monthlyId"
                        aria-expanded="false"
                        :aria-controls="monthlyId"
                    >
                        <div
                            class="w-100 d-flex justify-content-between align-items-center"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i
                                    class="fa-solid fa-calendar-day text-muted"
                                ></i>
                                <span class="fw-semibold small"
                                    >Monthly Tax</span
                                >
                            </div>
                            <span class="fw-bold small text-primary"
                                >{{ row.amount_monthly_tax }}</span
                            >
                        </div>
                    </button>
                </h2>

                <div
                    :id="monthlyId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">Annual Tax</span>
                            <span class="fw-semibold">{{ row.amount_annual_tax }}</span>
                        </div>
                        <div
                            class="old-row small d-flex justify-content-between"
                        >
                            <span class="text-muted">÷ months</span>
                            <span class="fw-semibold">{{ row.months_covered }}</span>
                        </div>
                        <div class="old-total small mt-2">
                            <span class="text-muted">Monthly Tax</span>
                            <span class="fw-bold text-primary"
                                >{{ row.amount_monthly_tax }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /accordion -->
    </div>
</template>

<script>
export default {
    name: "TaxForecastCardBreakdownAccordion",
    props: {
        row: { type: Object, default: () => ({}) }
    },
    computed: {
        uid() {
            return "tfc_" + Math.random().toString(36).slice(2, 9);
        },
        accId() {
            return "acc_" + this.uid;
        },
        grossId() {
            return "gross_" + this.uid;
        },
        lessId() {
            return "less_" + this.uid;
        },
        trainId() {
            return "train_" + this.uid;
        },
        monthlyId() {
            return "monthly_" + this.uid;
        },
    },
};
</script>

<style scoped>
/* Old social network vibe: flatter, tighter, slightly gray, bordered */
.old-social-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.25rem;
    background: var(--bs-body-bg);
    box-shadow: none;
}

.old-divider {
    border-top: 1px solid var(--bs-border-color);
    opacity: 0.9;
}

/* Slightly tighter typography like older UIs */
.old-name {
    font-size: 0.95rem;
    line-height: 1.1;
}
.old-sub {
    line-height: 1.15;
}

.old-pill {
    font-size: 0.72rem;
    letter-spacing: 0.02em;
    padding: 0.28rem 0.5rem;
    border-radius: 999px;
}

/* Make primary value feel like a “headline” but still classic */
.old-money {
    line-height: 1.05;
}

/* Accordion: remove modern chunky look */
.old-accordion .accordion-item {
    border: 0;
    border-top: 1px solid var(--bs-border-color);
    background: transparent;
}
.old-accordion .accordion-item:first-child {
    border-top: 0;
}

.old-acc-btn {
    background: transparent !important;
    box-shadow: none !important;
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
}
.old-accordion .accordion-button::after {
    transform: scale(0.9);
    opacity: 0.75;
}
.old-accordion .accordion-button:not(.collapsed) {
    color: var(--bs-body-color);
    background: transparent;
}

/* Rows inside accordion: subtle separators like classic lists */
.old-mini {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.25rem;
    padding: 0.6rem 0.7rem;
    background: var(--bs-secondary-bg);
}

.old-row {
    padding: 0.25rem 0;
    border-bottom: 1px dashed var(--bs-border-color);
}
.old-row:last-child {
    border-bottom: 0;
}

.old-total {
    display: flex;
    justify-content: space-between;
    padding-top: 0.4rem;
    margin-top: 0.35rem;
    border-top: 1px solid var(--bs-border-color);
}

.old-tip {
    padding: 0.35rem 0.5rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.25rem;
    background: var(--bs-body-bg);
}
</style>
