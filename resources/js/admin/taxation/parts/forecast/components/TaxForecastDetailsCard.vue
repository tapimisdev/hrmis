<template>
    <div class="classic-card">
        <!-- HEADER -->
        <div class="classic-head">
            <div class="who">
                <div class="pic">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="meta">
                    <div class="line1">
                        <a href="javascript:void(0)" class="name-link">
                            {{ row?.full_name || "—" }}
                        </a>

                        <span v-if="row?.employee_no" class="tiny-pill">
                            ID: {{ row.employee_no }}
                        </span>
                    </div>

                    <div class="line2">
                        <span>{{ row?.position || "—" }}</span>
                        <span class="sep">·</span>
                        <span>{{ row?.division || "—" }}</span>
                        <span v-if="row?.unit" class="sep">·</span>
                        <span v-if="row?.unit">{{ row.unit }}</span>
                    </div>
                </div>
            </div>

            <div class="classic-actions" @click.stop>
                <button class="classic-btn" @click="$emit('view', row)">
                    <i class="fa-solid fa-chart-column me-1"></i>
                    View Breakdown
                </button>

                <button class="classic-btn" @click="$emit('edit', row)">
                    <i class="fa-solid fa-pen-to-square me-1"></i>
                    Edit Inputs
                </button>

                <button class="classic-btn" @click="$emit('recompute', row)">
                    <i class="fa-solid fa-rotate me-1"></i>
                    Recompute
                </button>
            </div>
        </div>

        <!-- STATS STRIP -->
        <div class="classic-strip">
            <div class="strip-item">
                <div class="k">Gross</div>
                <div class="v">{{ money(row?.amount_gross) }}</div>
            </div>

            <div class="strip-item">
                <div class="k">Annual Taxable</div>
                <div class="v">{{ money(row?.amount_annual_taxable) }}</div>
            </div>

            <div class="strip-item">
                <div class="k">Annual Tax</div>
                <div class="v">{{ money(row?.amount_annual_tax) }}</div>
            </div>

            <div class="strip-item">
                <div class="k">Monthly Tax</div>
                <div class="v">{{ money(row?.amount_monthly_tax) }}</div>
            </div>
        </div>

        <!-- BODY -->
        <div class="classic-body">
            <div class="classic-grid">
                <!-- LEFT -->
                <div class="classic-box">
                    <div class="box-title">Earnings / Deductions</div>

                    <div class="kv">
                        <div class="rowx">
                            <div class="key">Other Earnings (Taxable)</div>
                            <div class="val">
                                {{ money(row?.amount_other_earnings_taxable) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">Other Earnings (Non Taxable)</div>
                            <div class="val">
                                {{ money(row?.amount_other_earnings_non_taxable) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">Other Deductions</div>
                            <div class="val">
                                {{ money(row?.amount_other_deductions) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">Annual Allowables</div>
                            <div class="val strong">
                                {{ money(row?.amount_annual_total_allowables) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">Annual Taxable Income</div>
                            <div class="val strong">
                                {{ money(row?.amount_annual_taxable) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">Gross Income</div>
                            <div class="val strong">
                                {{ money(row?.amount_gross) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="classic-box">
                    <div class="box-title">Portion Breakdown</div>

                    <div class="portion">
                        <div class="prow">
                            <div class="pname">
                                Hazard Pay
                                <span class="pct">{{
                                    pct(row?.portion_hazard_pay)
                                }}</span>
                            </div>
                            <div class="pamt">
                                {{ money(row?.amount_portion_hazard_pay) }}
                            </div>
                        </div>

                        <div class="prow">
                            <div class="pname">
                                Basic Pay
                                <span class="pct">{{
                                    pct(row?.portion_basic_pay)
                                }}</span>
                            </div>
                            <div class="pamt">
                                {{ money(row?.amount_portion_basic_pay) }}
                            </div>
                        </div>

                        <div class="prow">
                            <div class="pname">
                                Longevity
                                <span class="pct">{{
                                    pct(row?.portion_longevity_pay)
                                }}</span>
                            </div>
                            <div class="pamt">
                                {{ money(row?.amount_portion_longevity_pay) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REMARKS -->
            <div class="classic-box remarks">
                <div class="box-title">Remarks</div>

                <ul
                    v-if="row?.remarks && row.remarks.length"
                    class="remark-list"
                >
                    <li v-for="(r, idx) in row.remarks" :key="idx">{{ r }}</li>
                </ul>

                <div v-else class="empty-note">No remarks.</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: { row: { type: Object, required: true } },
    methods: {
        money(val) {
            if (!val) return "—";
            const n = Number(val);
            if (isNaN(n)) return val;
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(n);
        },
        pct(val) {
            if (!val) return "—";
            const n = Number(val);
            if (isNaN(n)) return val;
            return `${n}%`;
        },
    },
};
</script>

<style lang="scss" scoped>
.classic-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 4px;
    background: var(--bs-body-bg);
}

/* HEADER */
.classic-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    background: linear-gradient(var(--bs-tertiary-bg), var(--bs-body-bg));
    border-bottom: 1px solid var(--bs-border-color);
}

.who {
    display: flex;
    gap: 10px;
    align-items: center;
}

.pic {
    width: 34px;
    height: 34px;
    border-radius: 4px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    display: grid;
    place-items: center;
    color: var(--bs-secondary-color);
}

.name-link {
    color: var(--bs-primary);
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
}
.name-link:hover {
    text-decoration: underline;
}

.tiny-pill {
    font-size: 11px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    padding: 1px 6px;
    border-radius: 12px;
}

.line2 {
    font-size: 12px;
    color: var(--bs-secondary-color);
}

.sep {
    margin: 0 6px;
}

/* BUTTONS */
.classic-btn {
    border: 1px solid var(--bs-border-color);
    background: linear-gradient(var(--bs-body-bg), var(--bs-tertiary-bg));
    color: var(--bs-body-color);
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 3px;
}
.classic-btn:hover {
    background: var(--bs-tertiary-bg);
}

/* STATS STRIP */
.classic-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
}

.strip-item {
    padding: 8px 10px;
    border-right: 1px solid var(--bs-border-color);
}
.strip-item:last-child {
    border-right: none;
}
.strip-item .k {
    font-size: 11px;
    color: var(--bs-secondary-color);
}
.strip-item .v {
    font-size: 13px;
    font-weight: 700;
    color: var(--bs-body-color);
}

/* BODY */
.classic-body {
    padding: 10px 12px 12px;
}

.classic-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.classic-box {
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    border-radius: 3px;
}

.box-title {
    padding: 7px 9px;
    font-size: 12px;
    font-weight: 700;
    background: linear-gradient(var(--bs-body-bg), var(--bs-tertiary-bg));
    border-bottom: 1px solid var(--bs-border-color);
}

/* Rows */
.rowx,
.prow {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    padding: 6px 9px;
    border-bottom: 1px solid var(--bs-border-color);
}
.rowx:last-child,
.prow:last-child {
    border-bottom: none;
}

.key,
.pname {
    font-size: 12px;
    color: var(--bs-body-color);
}

.val,
.pamt {
    font-size: 12px;
    font-weight: 600;
    color: var(--bs-body-color);
}

.pct {
    margin-left: 6px;
    font-size: 11px;
    color: var(--bs-secondary-color);
}

/* Remarks */
.remark-list {
    margin: 0;
    padding: 8px 20px 10px;
    font-size: 12px;
}

.empty-note {
    padding: 10px;
    font-size: 12px;
    color: var(--bs-secondary-color);
}

@media (max-width: 768px) {
    .classic-grid {
        grid-template-columns: 1fr;
    }
    .classic-strip {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
