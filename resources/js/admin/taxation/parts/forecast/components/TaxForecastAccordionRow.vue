<template>
    <tr
        class="own-accordion-row"
        :class="{ 'is-open': open, 'is-selected': selected }"
        :data-tax-row-key="rowKey || null"
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
            <div class="emp-wrap">
                <div class="emp-avatar">
                    <img
                        v-if="row?.avatar"
                        :src="row.avatar"
                        alt="Avatar"
                    />
                    <div v-else class="emp-avatar-fallback">
                        {{ initials }}
                    </div>
                </div>

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
            </div>
        </td>

        <!-- Org -->
        <td class="td-muted">
            <div class="cell-main">{{ row?.division ?? "—" }}</div>
        </td>

        <td class="td-muted">
            <div class="cell-main">{{ row?.unit ?? "—" }}</div>
        </td>

        <!-- Money -->
        <td class="td-money">
            {{ row?.amount_annual_taxable ?? "—" }}
        </td>

        <td class="td-money">
            {{ row?.amount_annual_tax ?? "—" }}
        </td>

        <td class="td-money">
            {{ row?.amount_monthly_tax ?? "—" }}
        </td>

        <td v-if="selectedType === 'nov'" class="td-money">
            {{ row?.amount_return_amount ?? "—" }}
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
        rowKey: { type: String, default: "" },
        selected: { type: Boolean, default: false },
        selectedType: { type: String, default: "forecast" },
    },
    computed: {
        initials() {
            const name = this.row?.full_name || "";
            return name
                .split(" ")
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join("") || "—";
        },
    },
    methods: {
        emitToggle() {
            this.$emit("toggle", { row: this.row, index: this.index });
        },
    },
};
</script>

<style lang="scss" scoped>

/* ===============================
   Accordion Row
================================ */
.own-accordion-row {
    cursor: pointer;
    transition: background-color .15s ease;

    td {
        vertical-align: middle;
        padding: 10px;
    }

    &:hover {
        background-color: var(--bs-light-bg-subtle);
    }

    &.is-open {
        background-color: var(--bs-light-bg-subtle);
        box-shadow: inset 3px 0 0 var(--bs-primary);
    }

    &.is-selected {
        box-shadow: inset 4px 0 0 var(--bs-primary);
    }

    &.is-selected td {
        background-color: var(--bs-primary-bg-subtle);
    }

    /* Money column highlight on hover/open */
    &:hover .td-money,
    &.is-open .td-money {
        color: var(--bs-body-color);
    }
}


/* ===============================
   Chevron
================================ */
.td-chev {
    width: 34px;
    padding: 0 6px;
    color: var(--bs-secondary-color);

    .chev {
        display: inline-block;
        font-size: 12px;
        transition: transform .18s ease;
        transform-origin: center;

        &.rotate {
            transform: rotate(90deg);
        }
    }
}


/* ===============================
   Employee Identity
================================ */
.name-cell {
    padding-left: 8px;

    .emp-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    /* Avatar */
    .emp-avatar {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 8px;
        overflow: hidden;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: center;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        &-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--bs-secondary-color);
            background: var(--bs-light-bg-subtle);
        }
    }

    /* Text Block */
    .emp-block {
        display: flex;
        flex-direction: column;
        gap: 3px;
        flex: 1 1 auto;
        min-width: 0;
    }

    .emp-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-width: 0;
    }

    .emp-name {
        font-weight: 700;
        font-size: 13px;
        color: var(--bs-body-color);
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
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
        letter-spacing: .3px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        color: var(--bs-secondary-color);
        line-height: 1.2;
        flex: 0 0 auto;
    }
}


/* ===============================
   Table Cells
================================ */
.td-muted {
    .cell-main {
        font-size: 12px;
        color: var(--bs-body-color);
    }
}

.td-money {
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--bs-body-color);
}

</style>
