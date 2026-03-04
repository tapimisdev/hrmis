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
                <span
                    class="badge old-pill bg-info-subtle text-info border border-info-subtle"
                >
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

            <!-- Right summary: adaptable -->
            <div class="text-end">
                <template v-if="summaryCountLabel">
                    <div class="small text-muted">{{ summaryCountLabel }}</div>
                    <div class="fw-semibold fs-5 text-body">
                        {{ summaryCountValue }}
                    </div>
                </template>

                <div v-if="effectiveDateUsed" class="small text-muted">
                    Effective Date: {{ formatDate(effectiveDateUsed) }}
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
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + inputsId"
                        aria-expanded="false"
                        :aria-controls="inputsId"
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
                    :id="inputsId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
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

            <!-- 2) Steps -->
            <div class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + stepsId"
                        aria-expanded="false"
                        :aria-controls="stepsId"
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
                    :id="stepsId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(s, i) in (computation.steps || [])"
                                :key="i"
                                class="old-step"
                            >
                                <!-- Rows array (like "Items") -->
                                <template v-if="isRowsArray(s.value)">
                                    <div class="d-flex justify-content-between small old-row old-row-head">
                                        <span class="text-muted">{{ safeText(s.label) }}</span>
                                        <span class="fw-semibold">{{ safeText(s.value.length) }} item(s)</span>
                                    </div>

                                    <div class="old-nested">
                                        <div
                                            v-for="(r, ri) in s.value"
                                            :key="ri"
                                            class="d-flex justify-content-between small old-row old-row-nested"
                                        >
                                            <span class="text-muted">{{ safeText(r.label) }}</span>
                                            <span class="fw-semibold">{{ safeText(r.value) }}</span>
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

            <!-- 3) Computations breakdown (nested computations[]) -->
            <div v-if="hasComputations" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + compsId"
                        aria-expanded="false"
                        :aria-controls="compsId"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-layer-group text-muted"></i>
                                <span class="fw-semibold small">Breakdown</span>
                            </div>
                            <span class="fw-bold small text-info">
                                {{ money(computationsTotal) }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div
                    :id="compsId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="c in computationsList"
                                :key="c._k"
                                class="old-module"
                            >
                                <div class="d-flex justify-content-between small old-row old-row-head">
                                    <span class="text-muted">
                                        {{ safeText(c.label) }}
                                        <span class="ms-2 badge old-pill bg-info-subtle text-info border border-info-subtle">
                                            {{ safeText(c.type) }}
                                        </span>
                                    </span>
                                    <span class="fw-semibold">{{ money(c.total) }}</span>
                                </div>

                                <div v-if="c.monthly && c.monthly.length" class="old-nested">
                                    <div
                                        v-for="(x, xi) in c.monthly"
                                        :key="xi"
                                        class="d-flex justify-content-between small old-row old-row-nested"
                                    >
                                        <span class="text-muted">{{ safeText(x.label) }}</span>
                                        <span class="fw-semibold">{{ money(safeNum(x.amount)) }}</span>
                                    </div>
                                </div>

                                <div v-else class="small text-muted pt-1">
                                    {{ safeText(c.formula) }}
                                </div>
                            </div>

                            <div class="old-total small mt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold text-info">{{ money(computationsTotal) }}</span>
                            </div>

                            <div class="old-tip mt-2 small text-muted">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                For <strong>Allowable Deductions</strong>, this expands GSIS / PAG-IBIG / PHILHEALTH plus any “Other deductions”.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4) Months breakdown (only if present) -->
            <div v-if="hasMonths" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + monthsId"
                        aria-expanded="false"
                        :aria-controls="monthsId"
                    >
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

                <div
                    :id="monthsId"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#' + accId"
                >
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(m, i) in (computation.months || [])"
                                :key="i"
                                class="d-flex justify-content-between small old-row"
                            >
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
        data: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            // IMPORTANT: keep stable IDs (computed + Math.random() will change on re-render)
            _uid: "tc_" + Math.random().toString(36).slice(2, 9),
        };
    },
    computed: {
        computation() {
            if (this.data && this.data.raw_computation) return this.data.raw_computation;
            return this.data || {};
        },

        accId() {
            return "acc_" + this._uid;
        },
        inputsId() {
            return "inputs_" + this._uid;
        },
        stepsId() {
            return "steps_" + this._uid;
        },
        monthsId() {
            return "months_" + this._uid;
        },
        compsId() {
            return "comps_" + this._uid;
        },

        hasMonths() {
            const arr = (this.computation && this.computation.months) || [];
            return Array.isArray(arr) && arr.length > 0;
        },
        monthsTotal() {
            const arr = (this.computation && this.computation.months) || [];
            return arr.reduce((sum, x) => sum + (Number(x.amount) || 0), 0);
        },

        effectiveDateUsed() {
            return (
                (this.computation.meta && this.computation.meta.salary_effective_date_used) ||
                (this.computation.inputs && this.computation.inputs.effective_date) ||
                ""
            );
        },

        // Right-side "count" (Allowables: use items_count)
        summaryCountLabel() {
            const i = this.computation.inputs || {};
            if (i.items_count !== undefined && i.items_count !== null) return "Items";
            if (i.months_covered !== undefined && i.months_covered !== null) return "Months Covered";
            if (i.months_of_service !== undefined && i.months_of_service !== null) return "Months of Service";
            return "";
        },
        summaryCountValue() {
            const i = this.computation.inputs || {};
            if (i.items_count !== undefined && i.items_count !== null) return this.safeNum(i.items_count);
            if (i.months_covered !== undefined && i.months_covered !== null) return this.safeNum(i.months_covered);
            if (i.months_of_service !== undefined && i.months_of_service !== null) return this.safeNum(i.months_of_service);
            return "";
        },

        // Nested computations (Allowables: GSIS/PAGIBIG/PHILHEALTH + other item)
        hasComputations() {
            const arr = (this.computation && this.computation.computations) || [];
            return Array.isArray(arr) && arr.length > 0;
        },

        computationsList() {
            const arr = (this.computation && this.computation.computations) || [];
            return arr.map((c, idx) => {
                const total =
                    Number(c.result_raw) ||
                    Number(c.inputs && c.inputs.total) ||
                    Number(c.inputs && c.inputs.amount) ||
                    0;

                const monthly = Array.isArray(c.inputs && c.inputs.monthly)
                    ? (c.inputs.monthly || []).map((m) => ({
                          label: m.label || m.month || "",
                          amount: Number(m.amount) || 0,
                      }))
                    : [];

                return {
                    _k: `${c.key || "comp"}_${idx}`,
                    key: c.key,
                    type: (c.meta && c.meta.type) || "",
                    label: c.label || c.key || "Item",
                    total,
                    monthly,
                    formula: c.formula || "",
                };
            });
        },

        computationsTotal() {
            // NOTE: For allowables, your parent result is the true total.
            // But showing a computed sum is still fine; we’ll keep it consistent.
            return this.computationsList.reduce((sum, x) => sum + (Number(x.total) || 0), 0);
        },

        // Inputs table (explicit support for allowables inputs)
        inputRows() {
            const i = this.computation.inputs || {};
            const rows = [];

            if (i.employee_no) rows.push({ label: "Employee No", value: this.safeText(i.employee_no) });
            if (i.year) rows.push({ label: "Year", value: this.safeNum(i.year) });

            // Allowables (from your JSON)
            if (i.items_count !== undefined && i.items_count !== null) {
                rows.push({ label: "Items count", value: this.safeNum(i.items_count) });
            }
            if (i.other_deductions !== undefined && i.other_deductions !== null) {
                rows.push({ label: "Other deductions", value: this.money(i.other_deductions) });
            }
            if (i.gsis !== undefined && i.gsis !== null) rows.push({ label: "GSIS", value: this.money(i.gsis) });
            if (i.pagibig !== undefined && i.pagibig !== null) rows.push({ label: "PAG-IBIG", value: this.money(i.pagibig) });
            if (i.philhealth !== undefined && i.philhealth !== null) rows.push({ label: "PHILHEALTH", value: this.money(i.philhealth) });

            if (i.grand_total !== undefined && i.grand_total !== null) {
                rows.push({ label: "Grand total", value: this.money(i.grand_total) });
            }

            // generic fallbacks
            if (i.eligible !== undefined) rows.push({ label: "Eligibility", value: this.formatValue(i.eligible) });
            if (i.as_of_date) rows.push({ label: "As of", value: this.formatDate(i.as_of_date) });

            if (!rows.length) rows.push({ label: "Inputs", value: "-" });
            return rows;
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
        formatValue(v) {
            if (typeof v === "boolean") return v ? "Yes" : "No";
            if (typeof v === "number") return this.safeNum(v);
            if (this.isRowsArray(v)) return `${v.length} item(s)`;
            return this.safeText(v) || "-";
        },
        isRowsArray(v) {
            return (
                Array.isArray(v) &&
                (v.length === 0 ||
                    (v[0] && typeof v[0] === "object" && ("label" in v[0] || "value" in v[0])))
            );
        },
    },
};
</script>

<style scoped>
/* Old social network vibe */
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

.old-row-head {
    border-bottom-style: solid;
    opacity: 0.9;
}

.old-nested {
    padding: 0.15rem 0 0.4rem;
}

.old-row-nested {
    padding-left: 0.25rem;
    opacity: 0.95;
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