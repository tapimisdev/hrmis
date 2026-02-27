<template>
    <div class="classic-card">
        <!-- TOP BAR: actions only -->
        <div class="classic-head">
            <div class="head-left">
                <div class="title">
                    Details
                    <span class="sub">Portions • Remarks</span>
                </div>
            </div>

            <div class="classic-actions" @click.stop>
                <button class="classic-btn" @click="$emit('view', row)">
                    <i class="fa-solid fa-chart-column me-1"></i>
                    Breakdown
                </button>

                <button class="classic-btn" @click="$emit('edit', row)">
                    <i class="fa-solid fa-pen-to-square me-1"></i>
                    Edit
                </button>

                <button class="classic-btn" @click="$emit('recompute', row)">
                    <i class="fa-solid fa-rotate me-1"></i>
                    Recompute
                </button>
            </div>
        </div>

        <!-- BODY -->
        <div class="classic-body">
            <div class="classic-grid">
                <!-- LEFT -->
                <div class="classic-box">
                    <div class="box-title">Portion Breakdown</div>

                    <div class="rows">
                        <div class="rowx">
                            <div class="key">
                                Basic Pay
                                <span class="hint">{{ pct(row?.portion_basic_pay) }}</span>
                            </div>
                            <div class="val">
                                {{ money(row?.amount_portion_basic_pay) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">
                                Hazard Pay
                                <span class="hint">{{ pct(row?.portion_hazard_pay) }}</span>
                            </div>
                            <div class="val">
                                {{ money(row?.amount_portion_hazard_pay) }}
                            </div>
                        </div>

                        <div class="rowx">
                            <div class="key">
                                Longevity
                                <span class="hint">{{ pct(row?.portion_longevity_pay) }}</span>
                            </div>
                            <div class="val">
                                {{ money(row?.amount_portion_longevity_pay) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="classic-box">
                    <div class="box-title">Remarks</div>

                    <div class="rows">
                        <ul v-if="row?.remarks?.length" class="remark-list">
                            <li v-for="(r, idx) in row.remarks" :key="idx">{{ r }}</li>
                        </ul>

                        <div v-else class="empty-note">No remarks.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: { row: { type: Object, required: true } },
    methods: {
        money(val) {
            if (val === null || val === undefined || val === "") return "—";
            const n = Number(val);
            if (Number.isNaN(n)) return String(val);
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(n);
        },
        pct(val) {
            if (val === null || val === undefined || val === "") return "—";
            const n = Number(val);
            if (Number.isNaN(n)) return String(val);
            return `${n}%`;
        },
    },
};
</script>

<style lang="scss" scoped>
/* Card shell */
.classic-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 4px;
    background: var(--bs-body-bg);
}

/* Top bar */
.classic-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;

    padding: 10px 12px;
    background: linear-gradient(var(--bs-tertiary-bg), var(--bs-body-bg));
    border-bottom: 1px solid var(--bs-border-color);
}

.title {
    font-weight: 700;
    font-size: 13px;
    color: var(--bs-body-color);
}
.sub {
    margin-left: 8px;
    font-weight: 400;
    font-size: 11px;
    color: var(--bs-secondary-color);
}

/* Buttons */
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

/* Body layout */
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

/* Reusable rows container */
.rows {
    padding: 0;
}

.rowx {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    padding: 6px 9px;
    border-bottom: 1px solid var(--bs-border-color);
}
.rowx:last-child {
    border-bottom: none;
}

.key {
    font-size: 12px;
    color: var(--bs-body-color);
}
.val {
    font-size: 12px;
    font-weight: 600;
    color: var(--bs-body-color);
}

.hint {
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
}
</style>