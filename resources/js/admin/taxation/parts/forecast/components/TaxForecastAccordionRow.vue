<template>
    <!-- MAIN ROW -->
    <tr
        class="own-accordion-row"
        :class="{ 'is-open': open }"
        role="button"
        tabindex="0"
        @click="emitToggle"
        @keydown.enter.prevent="emitToggle"
        @keydown.space.prevent="emitToggle"
    >
        <td class="text-center">
            <span class="chev" :class="{ rotate: open }">▸</span>
        </td>

        <td class="fw-semibold name-cell" style="width: 30%">
            <div class="emp-name">
                {{ row?.full_name ?? "—" }}
            </div>

            <div class="emp-no">
                {{ row?.employee_no ?? "—" }}
            </div>
        </td>

        <td>{{ row?.division ?? "—" }}</td>
        <td>{{ row?.unit ?? "—" }}</td>

        <td>{{ row?.amount_annual_taxable ?? "—" }}</td>
        <td>{{ row?.amount_annual_tax ?? "—" }}</td>
        <td>{{ row?.amount_monthly_tax ?? "—" }}</td>
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
            this.$emit("toggle", { row: this.row, index: this.index })
        },
    },
}
</script>

<style lang="scss" scoped>
/* =========================================
   ACCORDION ROW
========================================= */
.own-accordion-row {
    &:hover,
    &.is-open {
        background-color: var(--bs-light-bg-subtle);
    }
}

/* =========================================
   CHEVRON
========================================= */
.chev {
    display: inline-block;
    font-size: 12px;
    color: var(--bs-secondary-color);
    transition: transform 0.15s ease;

    &.rotate {
        transform: rotate(90deg);
    }
}

/* =========================================
   DETAILS SECTION
========================================= */
.own-accordion-details {
    td {
        padding: 10px 12px;
        background: var(--bs-body-bg);
    }
}

/* =========================================
   NAME CELL
========================================= */
.name-cell {
    position: relative;
    padding-right: 70px;
}


/* =========================================
   EMPLOYEE NAME
========================================= */
.emp-name {
    font-weight: 600;
    font-size: 14px;
    line-height: 1.3;
    color: var(--bs-body-color);
    padding-left: 26px;
}

/* =========================================
   FLOATING EMPLOYEE NUMBER (REFINED)
========================================= */
.emp-no {
    position: absolute;
    top: 12px;
    left: 2px;

    font-size: 8px;
    font-weight: 600;
    letter-spacing: 0.5px;

    padding: 2px 6px;
    border-radius: 6px;

    // background: var(--bs-primasecondaryry-bg-subtle);
    background-color: var(--bs-primary);
    color: var(--bs-light);

    line-height: 1;
}
</style>