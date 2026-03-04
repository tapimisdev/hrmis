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

                <!-- Hide if empty -->
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

                <div :id="inputsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
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

                <div :id="stepsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(s, i) in (computation.steps || [])"
                                :key="i"
                                class="old-step"
                            >
                                <!-- Normal value -->
                                <template v-if="!isRowsArray(s.value)">
                                    <div class="d-flex justify-content-between small old-row">
                                        <span class="text-muted">{{ safeText(s.label) }}</span>
                                        <span class="fw-semibold">{{ formatValue(s.value) }}</span>
                                    </div>
                                </template>

                                <!-- Rows array (like Items) -->
                                <template v-else>
                                    <div class="d-flex justify-content-between small old-row old-row-head">
                                        <span class="text-muted">{{ safeText(s.label) }}</span>
                                        <span class="fw-semibold">{{ (s.value || []).length }} item(s)</span>
                                    </div>

                                    <div class="old-nested">
                                        <div
                                            v-for="(r, ri) in (s.value || [])"
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

            <!-- 3) Computations (detailed breakdown + per-month) -->
            <div v-if="hasComputations" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + computationsId"
                        aria-expanded="false"
                        :aria-controls="computationsId"
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

                <div :id="computationsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div
                                v-for="(c, ci) in (computation.computations || [])"
                                :key="ci"
                                class="old-module"
                            >
                                <!-- Header row -->
                                <div class="d-flex justify-content-between small old-row old-row-head">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-muted">{{ safeText(c.label) }}</span>

                                        <span
                                            class="badge old-pill bg-info-subtle text-info border border-info-subtle"
                                        >
                                            {{ safeText(c.meta && c.meta.type) }}
                                        </span>

                                        <span
                                            v-if="c.meta && c.meta.is_non_taxable === true"
                                            class="badge old-pill bg-success-subtle text-success border border-success-subtle"
                                        >
                                            NON-TAXABLE
                                        </span>
                                        <span
                                            v-else-if="c.meta && c.meta.is_non_taxable === false"
                                            class="badge old-pill bg-warning-subtle text-warning border border-warning-subtle"
                                        >
                                            TAXABLE
                                        </span>
                                    </div>

                                    <span class="fw-semibold">
                                        {{ formatMoney(c.result_raw, c.result) }}
                                    </span>
                                </div>

                                <!-- Inner steps -->
                                <div class="old-nested">
                                    <div
                                        v-for="(ss, si) in (c.steps || [])"
                                        :key="si"
                                        class="d-flex justify-content-between small old-row old-row-nested"
                                    >
                                        <span class="text-muted">{{ safeText(ss.label) }}</span>
                                        <span class="fw-semibold">{{ formatValue(ss.value) }}</span>
                                    </div>
                                </div>

                                <!-- Monthly breakdown per computation item -->
                                <div v-if="hasItemMonthly(c)" class="old-months mt-1">
                                    <div class="d-flex justify-content-between small old-row old-row-head">
                                        <span class="text-muted">Monthly breakdown</span>
                                        <span class="fw-semibold">{{ money(itemMonthlyTotal(c)) }}</span>
                                    </div>

                                    <div class="old-nested">
                                        <div
                                            v-for="(mm, mi) in itemMonthly(c)"
                                            :key="mi"
                                            class="d-flex justify-content-between small old-row old-row-nested"
                                        >
                                            <span class="text-muted">{{ safeText(mm.label || mm.month) }}</span>
                                            <span class="fw-semibold">{{ money(safeNum(mm.amount)) }}</span>
                                        </div>
                                    </div>

                                    <div class="old-total small">
                                        <span class="text-muted">Total</span>
                                        <span class="fw-bold text-info">{{ money(itemMonthlyTotal(c)) }}</span>
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

            <!-- 4) Modules breakdown (allowables_deductions) -->
            <div v-if="hasModules" class="accordion-item old-acc-item">
                <h2 class="accordion-header">
                    <button
                        class="accordion-button collapsed px-0 old-acc-btn"
                        type="button"
                        data-bs-toggle="collapse"
                        :data-bs-target="'#' + modulesId"
                        aria-expanded="false"
                        :aria-controls="modulesId"
                    >
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-layer-group text-muted"></i>
                                <span class="fw-semibold small">Modules Breakdown</span>
                            </div>
                            <span class="fw-bold small text-info">
                                {{ money(modulesTotal) }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div :id="modulesId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
                    <div class="accordion-body px-0 pt-2">
                        <div class="mini old-mini">
                            <div v-for="m in modulesList" :key="m.key" class="old-module">
                                <div class="d-flex justify-content-between small old-row old-row-head">
                                    <span class="text-muted">{{ m.title }}</span>
                                    <span class="fw-semibold">{{ money(m.total) }}</span>
                                </div>

                                <div class="old-nested">
                                    <div
                                        v-for="(x, xi) in m.monthly"
                                        :key="xi"
                                        class="d-flex justify-content-between small old-row old-row-nested"
                                    >
                                        <span class="text-muted">{{ safeText(x.label) }}</span>
                                        <span class="fw-semibold">{{ money(x.amount) }}</span>
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

            <!-- 5) Months breakdown (only if parent has computation.months) -->
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

                <div :id="monthsId" class="accordion-collapse collapse" :data-bs-parent="'#' + accId">
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
    computed: {
        computation() {
            if (this.data && this.data.raw_computation) return this.data.raw_computation;
            return this.data || {};
        },

        uid() {
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
        modulesId() {
            return "modules_" + this.uid;
        },
        computationsId() {
            return "computations_" + this.uid;
        },

        hasMonths() {
            const arr = (this.computation && this.computation.months) || [];
            return Array.isArray(arr) && arr.length > 0;
        },
        monthsTotal() {
            const arr = (this.computation && this.computation.months) || [];
            return arr.reduce((sum, x) => sum + (Number(x.amount) || 0), 0);
        },

        hasComputations() {
            const arr = (this.computation && this.computation.computations) || [];
            return Array.isArray(arr) && arr.length > 0;
        },

        effectiveDateUsed() {
            return (
                (this.computation.meta && this.computation.meta.salary_effective_date_used) ||
                (this.computation.inputs && this.computation.inputs.effective_date) ||
                ""
            );
        },

        summaryCountLabel() {
            const i = this.computation.inputs || {};
            if (i.months_covered !== undefined && i.months_covered !== null) return "Months Covered";
            if (i.months_of_service !== undefined && i.months_of_service !== null) return "Months of Service";
            if (i.items_count !== undefined && i.items_count !== null) return "Items";
            return "";
        },
        summaryCountValue() {
            const i = this.computation.inputs || {};
            if (i.months_covered !== undefined && i.months_covered !== null) return this.safeNum(i.months_covered);
            if (i.months_of_service !== undefined && i.months_of_service !== null) return this.safeNum(i.months_of_service);
            if (i.items_count !== undefined && i.items_count !== null) return this.safeNum(i.items_count);
            return "";
        },

        hasModules() {
            const mods = this.computation.inputs && this.computation.inputs.modules;
            return !!mods && typeof mods === "object" && Object.keys(mods).length > 0;
        },

        modulesList() {
            const mods = (this.computation.inputs && this.computation.inputs.modules) || {};
            const map = [
                { key: "gsis", title: "GSIS" },
                { key: "pagibig", title: "Pag-IBIG" },
                { key: "philhealth", title: "PhilHealth" },
            ];

            return map
                .filter((x) => mods[x.key])
                .map((x) => ({
                    key: x.key,
                    title: x.title,
                    total: Number(mods[x.key].total) || 0,
                    monthly: Array.isArray(mods[x.key].monthly) ? mods[x.key].monthly : [],
                }));
        },

        modulesTotal() {
            return this.modulesList.reduce((sum, m) => sum + (Number(m.total) || 0), 0);
        },

        inputRows() {
            const i = this.computation.inputs || {};
            const rows = [];

            if (i.employee_no) rows.push({ label: "Employee No", value: this.safeText(i.employee_no) });
            if (i.year) rows.push({ label: "Year", value: this.safeNum(i.year) });

            if (i.eligible !== undefined) rows.push({ label: "Eligibility", value: this.formatValue(i.eligible) });
            if (i.as_of_date) rows.push({ label: "As of", value: this.formatDate(i.as_of_date) });

            if (i.months_of_service !== undefined && i.months_of_service !== null) {
                rows.push({ label: "Months of service (as of)", value: this.safeNum(i.months_of_service) });
            }
            if (i.months_covered !== undefined && i.months_covered !== null) {
                rows.push({ label: "Months covered", value: this.safeNum(i.months_covered) });
            }

            if (i.basic_salary_as_of !== undefined && i.basic_salary_as_of !== null) {
                rows.push({ label: "Basic salary as of", value: this.money(i.basic_salary_as_of) });
            }
            if (i.monthly_salary !== undefined && i.monthly_salary !== null) {
                rows.push({ label: "Monthly salary", value: this.money(i.monthly_salary) });
            }

            // other_earnings inputs
            if (i.items_count !== undefined && i.items_count !== null) {
                rows.push({ label: "Items count", value: this.safeNum(i.items_count) });
            }
            if (i.taxable_total !== undefined && i.taxable_total !== null) {
                rows.push({ label: "Taxable total", value: this.money(i.taxable_total) });
            }
            if (i.non_taxable_total !== undefined && i.non_taxable_total !== null) {
                rows.push({ label: "Non-taxable total", value: this.money(i.non_taxable_total) });
            }
            if (i.grand_total !== undefined && i.grand_total !== null) {
                rows.push({ label: "Grand total", value: this.money(i.grand_total) });
            }

            if (i.modules && typeof i.modules === "object") {
                rows.push({ label: "Modules", value: Object.keys(i.modules).join(", ").toUpperCase() });
            }

            if (i.eligibility_reason) rows.push({ label: "Reason", value: this.safeText(i.eligibility_reason) });

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
                v.length >= 0 &&
                (v.length === 0 ||
                    (v[0] &&
                        typeof v[0] === "object" &&
                        ("label" in v[0] || "value" in v[0])))
            );
        },

        // per-item monthly helpers
        itemMonthly(c) {
            const m = c && c.inputs && c.inputs.monthly;
            return Array.isArray(m) ? m : [];
        },
        hasItemMonthly(c) {
            return this.itemMonthly(c).length > 0;
        },
        itemMonthlyTotal(c) {
            return this.itemMonthly(c).reduce((sum, x) => sum + (Number(x.amount) || 0), 0);
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