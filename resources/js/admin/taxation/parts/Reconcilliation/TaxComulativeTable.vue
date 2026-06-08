<template>
    <div class="own-table-wrapper">
        <table class="table own-table mb-0 align-middle">
            <thead>
                <!-- TOP HEADER (row 1) -->
                <tr class="thead-row-1">
                    <th
                        rowspan="2"
                        class="sticky th-1 th-icon"
                        style="width: 34px"
                    ></th>
                    <th rowspan="2" class="sticky th-1">Name</th>
                    <th rowspan="2" class="sticky th-1">Division</th>
                    <th rowspan="2" class="sticky th-1">Unit</th>

                    <!-- GROUPED HEADER -->
                    <th colspan="3" class="sticky th-1 text-center">
                        Forecasted
                    </th>
                </tr>

                <!-- SECOND HEADER (row 2) -->
                <tr class="thead-row-2">
                    <th class="sticky th-2">Annual Taxable</th>
                    <th class="sticky th-2">Annual Tax</th>
                    <th class="sticky th-2">Monthly Tax</th>
                </tr>
            </thead>

            <tbody>
                <!-- EMPTY STATE -->
                <tr v-if="!rows || rows.length === 0">
                    <td colspan="7" class="text-center text-muted py-4">
                        No employees found.
                    </td>
                </tr>

                <!-- ACCORDION ROWS -->
                <template
                    v-else
                    v-for="(row, i) in rows"
                    :key="getRowKey(row, i)"
                >
                    <!-- MAIN ROW -->
                    <tr
                        class="own-accordion-row"
                        :class="{ 'is-open': isOpen(row, i) }"
                        role="button"
                        tabindex="0"
                        @click="toggleRow(row, i)"
                        @keydown.enter.prevent="toggleRow(row, i)"
                        @keydown.space.prevent="toggleRow(row, i)"
                    >
                        <td class="text-center">
                            <span
                                class="chev"
                                :class="{ rotate: isOpen(row, i) }"
                                >▸</span
                            >
                        </td>

                        <td class="fw-semibold">
                            {{ row.name }} <br/>
                            {{ row.employee_no }}
                        </td>
                        <td>{{ row.division ?? "—" }}</td>
                        <td>{{ row.unit ?? "—" }}</td>

                        <td>{{ row.forecasted_annual_taxable }}</td>
                        <td>{{ row.forecasted_annual_tax }}</td>
                        <td>{{ row.forecasted_monthly_tax }}</td>
                    </tr>

                    <!-- EXPANDED ROW -->
                    <tr v-show="isOpen(row, i)" class="own-accordion-details">
                        <td colspan="7">
                            <div class="details-card">
                                <div class="details-head">
                                    <div class="details-title">
                                        <div class="badge-id mb-2">
                                            Name: 
                                            <span class="fw-semibold">{{
                                                row.name
                                            }}</span>
                                        </div>
                                        <div class="text-muted small">
                                            Full details preview
                                        </div>
                                    </div>

                                    <!-- ACTION BUTTONS -->
                                    <div class="action-buttons" @click.stop>
                                        <button
                                            type="button"
                                            class="fb-btn fb-secondary text-primary"
                                            @click="$emit('view', row)"
                                        >
                                            <i
                                                class="fa-solid fa-chart-column me-1"
                                            ></i>
                                            View Breakdown
                                        </button>

                                        <button
                                            type="button"
                                            class="fb-btn fb-secondary text-primary"
                                            @click="$emit('edit', row)"
                                        >
                                            <i
                                                class="fa-solid fa-pen-to-square me-1"
                                            ></i>
                                            Edit Inputs
                                        </button>

                                        <button
                                            type="button"
                                            class="fb-btn fb-secondary text-primary"
                                            @click="$emit('recompute', row)"
                                        >
                                            <i
                                                class="fa-solid fa-rotate me-1"
                                            ></i>
                                            Recompute
                                        </button>
                                    </div>
                                </div>

                                <div class="details-grid">
                                    <!-- LEFT -->
                                    <div class="detail-col">
                                        <div class="detail-item">
                                            <div class="label">Division</div>
                                            <div class="value">
                                                {{ row.division ?? "—" }}
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <div class="label">Unit</div>
                                            <div class="value">
                                                {{ row.unit ?? "—" }}
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <div class="label">
                                                Forecasted Annual Taxable
                                            </div>
                                            <div class="value">
                                                {{
                                                    row.forecasted_annual_taxable ??
                                                    "—"
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT -->
                                    <div class="detail-col">
                                        <div class="detail-item">
                                            <div class="label">
                                                Forecasted Annual Tax
                                            </div>
                                            <div class="value">
                                                {{
                                                    row.forecasted_annual_tax ??
                                                    "—"
                                                }}
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <div class="label">
                                                Forecasted Monthly Tax
                                            </div>
                                            <div class="value">
                                                {{
                                                    row.forecasted_monthly_tax ??
                                                    "—"
                                                }}
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <div class="label">
                                                Notes / Extra
                                            </div>
                                            <div class="value">
                                                {{ row.notes ?? "—" }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: "TaxForecastTable",
    props: {
        rows: { type: Array, required: true },
    },
    data() {
        return {
            openKey: null, // only one open at a time
        };
    },
    methods: {
        // (if employee_no can repeat, fallback to index to make it unique)
        getRowKey(row, i) {
            const emp = (row?.employee_no ?? "").toString().trim();
            return emp ? `emp-${emp}` : `idx-${i}`;
        },
        isOpen(row, i) {
            return this.openKey === this.getRowKey(row, i);
        },
        toggleRow(row, i) {
            const key = this.getRowKey(row, i);
            this.openKey = this.openKey === key ? null : key;
        },
    },
};
</script>

<style lang="scss" scoped>
/* SCROLL CONTAINER */
.own-table-wrapper {
    border-radius: 8px;
    background: var(--bs-body-bg);
}

/* TABLE BASE */
.own-table {
    width: 100%;
    border-collapse: separate; /* sticky + borders stable */
    border-spacing: 0;
}

/* CELLS */
.own-table th {
    border: 1px solid var(--bs-border-color);
}

.own-table td {
    padding: 6px 12px;
}

/* STICKY HEADER (2 rows) */
.sticky {
    position: sticky;
    background: var(--bs-secondary-bg);
}

.th-1 {
    top: 0; /* first header row sticks at top */
    z-index: 10; /* above everything */
}

.th-2 {
    top: 34px; /* second header row sticks under the first */
    z-index: 9;
}

/* Tweak this if your header height differs */
.own-table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.72rem;
    padding: 8px 12px;
}

/* Optional: slight separation line for header */
.own-table thead th {
    box-shadow: 0 1px 0 var(--bs-border-color);
}

/* ROW INTERACTION */
.own-accordion-row:hover {
    background-color: var(--bs-light-bg-subtle);
}
.own-accordion-row.is-open {
    background-color: var(--bs-light-bg-subtle);
}

/* CHEVRON */
.chev {
    display: inline-block;
    font-size: 12px;
    color: var(--bs-secondary-color);
    transition: transform 0.15s ease;
}
.chev.rotate {
    transform: rotate(90deg);
}

/* DETAILS ROW */
.own-accordion-details td {
    padding: 10px 12px;
    background: var(--bs-body-bg);
}

.details-card {
    background: var(--bs-body-bg);
    border-radius: 10px;
    padding: 12px 0px 12px 32px;
}

.details-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--bs-border-color);
    margin-bottom: 10px;
}

.badge-id {
    display: inline-block;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 12px;
}

/* ACTION BUTTONS */
.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* DETAILS GRID */
.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 14px;
}

.detail-col {
    display: grid;
    gap: 10px;
}

.detail-item .label {
    font-size: 12px;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.detail-item .value {
    font-size: 14px;
    color: var(--bs-body-color);
    font-weight: 500;
    margin-top: 2px;
}

/* MOBILE */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>
