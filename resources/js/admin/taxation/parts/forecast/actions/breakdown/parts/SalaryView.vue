<template>
    <div class="card-simple p-3 mb-2 old-social-card">
        <!-- Identity -->
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="fw-semibold text-body old-name">
                    {{ safeText(computation.label) }}
                </div>

                <div class="small text-muted old-sub">
                    Key:
                    <span class="fw-semibold">{{ safeText(computation.key) }}</span>
                </div>

                <div class="small text-muted old-sub">
                    Type:
                    <span class="fw-semibold">
                        {{ safeText(computation.meta && computation.meta.type) }}
                    </span>
                </div>
            </div>

            <div class="text-end">
                <span class="badge old-pill bg-info-subtle text-info border border-info-subtle">
                    {{ safeText(computation.meta && computation.meta.type) || "DETAIL" }}
                </span>
                <div class="small text-muted mt-1">Computation</div>
            </div>
        </div>

        <hr class="my-3 old-divider" />

        <!-- Results headline -->
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <div class="small text-muted">Result</div>
                <div class="fs-4 fw-bold text-info old-money">
                    {{ formatMoney(computation.result_raw, computation.result) }}
                </div>
                <div class="small text-muted">
                    {{ safeText(computation.formula) }}
                </div>
            </div>

            <div class="text-end">
                <div class="small text-muted">Months Covered</div>
                <div class="fw-semibold fs-5 text-body">
                    {{ safeNum(computation.inputs && computation.inputs.months_covered) }}
                </div>
                <div class="small text-muted">
                    Effective Date:
                    {{ formatDate(computation.inputs && computation.inputs.effective_date) }}
                </div>
            </div>
        </div>

        <hr class="my-3 old-divider" />

        <div class="fw-semibold small mb-2 text-body-emphasis">
            Computation Overview
        </div>

        <!-- ACCORDION -->
        <div class="accordion accordion-flush old-accordion" :id="accId">
            <!-- 1) Inputs -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed px-0 old-acc-btn" type="button" data-bs-toggle="collapse"
                        :data-bs-target="'#' + inputsId" aria-expanded="false" :aria-controls="inputsId">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-sliders text-muted"></i>
                                <span class="fw-semibold small">Inputs</span>
                            </div>
                            <span class="fw-bold small">
                                {{ money(safeNum(computation.inputs && computation.inputs.monthly_salary)) }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div :id="inputsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Effective Date</span>
                                <span class="fw-semibold">
                                    {{ formatDate(computation.inputs && computation.inputs.effective_date) }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between small old-row">
                                <span class="text-muted">Monthly Salary</span>
                                <span class="fw-semibold">
                                    {{ money(safeNum(computation.inputs && computation.inputs.monthly_salary)) }}
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

            <!-- 2) Steps -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed px-0 old-acc-btn" type="button" data-bs-toggle="collapse"
                        :data-bs-target="'#' + stepsId" aria-expanded="false" :aria-controls="stepsId">
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

                <div :id="stepsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div v-for="(s, i) in (computation.steps || [])" :key="i"
                                class="d-flex justify-content-between small old-row">
                                <span class="text-muted">{{ safeText(s.label) }}</span>
                                <span class="fw-semibold">{{ safeText(s.value) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-calculator me-2"></i>
                                {{ safeText(computation.formula) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3) Months breakdown -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed px-0 old-acc-btn" type="button" data-bs-toggle="collapse"
                        :data-bs-target="'#' + monthsId" aria-expanded="false" :aria-controls="monthsId">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-muted"></i>
                                <span class="fw-semibold small">Monthly Breakdown</span>
                            </div>
                            <span class="fw-bold small text-info">
                                {{ money(monthsTotal) }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div :id="monthsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div v-for="(m, i) in (computation.months || [])" :key="i"
                                class="d-flex justify-content-between small old-row">
                                <span class="text-muted">{{ safeText(m.month) }}</span>
                                <span class="fw-semibold">{{ money(safeNum(m.amount)) }}</span>
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
        </div>
        <!-- /accordion -->
    </div>
</template>

<script>
export default {
    name: "TaxComputationJsonCard",
    props: {
        // can be raw_computation itself OR a row that contains raw_computation
        data: { type: Object, default: () => ({}) },
    },
    computed: {
        // normalize: always return the raw computation object
        computation() {
            if (this.data && this.data.raw_computation) return this.data.raw_computation;
            return this.data || {};
        },

        uid() {
            // NOTE: this recalculates on every render; ok for small list
            return "tc_" + Math.random().toString(36).slice(2, 9);
        },
        accId() {
            return "acc_" + this.uid;
        },
        inputsId() {
            return "inputs_" + this.uid;
        },
        stepsId() {
            return "steps_" + this.uid;
        },
        monthsId() {
            return "months_" + this.uid;
        },

        monthsTotal() {
            const arr = (this.computation && this.computation.months) || [];
            return arr.reduce((sum, x) => sum + (Number(x.amount) || 0), 0);
        },
    },
    methods: {
        safeText(v) {
            if (v === null || v === undefined) return "";
            return String(v);
        },
        safeNum(v) {
            const n = Number(v);
            return Number.isFinite(n) ? n : 0;
        },
        money(n) {
            const num = this.safeNum(n);
            return num.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
        formatMoney(raw, fallbackStr) {
            const n = Number(raw);
            if (Number.isFinite(n)) return this.money(n);
            return this.safeText(fallbackStr);
        },
        formatDate(yyyyMmDd) {
            const s = this.safeText(yyyyMmDd);
            return s || "-";
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

.old-money {
    line-height: 1.05;
}

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