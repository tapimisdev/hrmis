<template>
    <tr
        class="own-accordion-row"
        :class="{ 'is-open': open }"
        role="button"
        tabindex="0"
        @click="emitToggle"
        @keydown.enter.prevent="emitToggle"
        @keydown.space.prevent="emitToggle"
    >
        <!-- Chevron rail -->
        <td class="td-chev text-center">
            <span class="chev" :class="{ rotate: open }">▸</span>
        </td>

        <!-- Identity -->
        <td class="name-cell" style="width: 30%">
            <div class="emp-block">
                <div class="emp-top">
                    <span class="emp-name">{{ row?.full_name ?? "—" }}</span>

                    <span v-if="row?.employee_no" class="emp-no">
                        {{ row.employee_no }}
                    </span>
                </div>

                <div class="emp-sub">
                    <span class="emp-pos">{{ row?.position ?? "—" }}</span>
                </div>
            </div>
        </td>

        <!-- Org -->
        <td class="td-muted">
            <div class="cell-main">{{ row?.division ?? "—" }}</div>
        </td>

        <td class="td-muted">
            <div class="cell-main">{{ row?.unit ?? "—" }}</div>
        </td>

        <!-- Money (right aligned) -->
        <td class="td-money">
            {{ row?.amount_annual_taxable ?? "—" }}
        </td>

        <td class="td-money">
            {{ row?.amount_annual_tax ?? "—" }}
        </td>

        <td class="td-money">
            {{ row?.amount_monthly_tax ?? "—" }}
        </td>
    </tr>
</template>

<script>
export default {
    name: "TaxForecastAccordionRow",
    props: {
        row: { type: Object, required: true },
        index: { type: Number, required: true },
        open: { type: Boolean, default: false },
    },
    methods: {
        emitToggle() {
            this.$emit("toggle", { row: this.row, index: this.index });
        },
    },
};
</script>

<style lang="scss" scoped>
/* Row */
.own-accordion-row {
    cursor: pointer;
    transition: background-color 0.15s ease;

    &:hover {
        background-color: var(--bs-light-bg-subtle);
    }

    &.is-open {
        background-color: var(--bs-light-bg-subtle);
        box-shadow: inset 3px 0 0 var(--bs-primary);
    }

    td {
        vertical-align: middle;
        padding: 10px 10px;
    }
}

/* Chevron rail */
.td-chev {
    width: 34px;
    padding-left: 6px;
    padding-right: 6px;
    color: var(--bs-secondary-color);
}

.chev {
    display: inline-block;
    font-size: 12px;
    transition: transform 0.18s ease;
    transform-origin: 50% 50%;

    &.rotate {
        transform: rotate(90deg);
    }
}

/* Identity */
.name-cell {
    padding-left: 8px;
}

.emp-block {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.emp-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.emp-name {
    font-weight: 700;
    font-size: 12px;
    color: var(--bs-body-color);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.emp-sub {
    display: flex;
    gap: 6px;
    min-width: 0;
}

.emp-pos {
    font-size: 11px;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Employee number pill */
.emp-no {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
    padding: 2px 8px;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    line-height: 1.2;
    flex: 0 0 auto;
}

/* Muted org cells */
.td-muted .cell-main {
    font-size: 12px;
    color: var(--bs-body-color);
}

/* Money cells */
.td-money {
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--bs-body-color);
}

/* Optional: make money columns slightly tinted on hover/open */
.own-accordion-row:hover .td-money,
.own-accordion-row.is-open .td-money {
    color: var(--bs-body-color);
}
</style>