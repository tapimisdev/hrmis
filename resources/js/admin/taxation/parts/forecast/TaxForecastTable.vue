<template>
    <div class="own-table-wrapper table-responsive">
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

                <!-- ACCORDION ROWS (PAGINATED) -->
                <template
                    v-else
                    v-for="(row, i) in pagedRows"
                    :key="getRowKey(row, rowIndexOffset + i)"
                >
                    <TaxForecastAccordionRow
                        :row="row"
                        :index="rowIndexOffset + i"
                        :open="isOpen(row, rowIndexOffset + i)"
                        @toggle="({ row, index }) => toggleRow(row, index)"
                    />

                    <!-- EXPANDED ROW -->
                    <tr
                        v-show="isOpen(row, rowIndexOffset + i)"
                        class="own-accordion-details"
                    >
                        <td colspan="7">
                            <TaxForecastDetailsCard
                                :row="row"
                                @view="viewRow(row)"
                                @edit="editRow(row)"
                                @recompute="recomputeRow(row)"
                            />
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <OwnPagination
            :total-pages="totalPages"
            :start-item="startItem"
            :end-item="endItem"
            :rows="rows"
            :current-page="currentPage"
            :visible-pages="visiblePages"
            :go-to-page="goToPage"
        />
    </div>
</template>

<script>
import TaxForecastDetailsCard from "./components/TaxForecastDetailsCard.vue";
import TaxForecastAccordionRow from "./components/TaxForecastAccordionRow.vue";
import OwnPagination from "./components/OwnPagination.vue";
export default {
    name: "TaxForecastTable",
    components: {
        TaxForecastDetailsCard,
        TaxForecastAccordionRow,
        OwnPagination
    },
    props: {
        rows: { type: Array, required: true },
    },
    data() {
        return {
            openKey: null,
            currentPage: 1,
            perPage: 20,
        };
    },
    computed: {
        totalPages() {
            return Math.max(
                1,
                Math.ceil((this.rows?.length ?? 0) / this.perPage),
            );
        },
        rowIndexOffset() {
            return (this.currentPage - 1) * this.perPage;
        },
        pagedRows() {
            const start = this.rowIndexOffset;
            return (this.rows ?? []).slice(start, start + this.perPage);
        },
        startItem() {
            if (!this.rows?.length) return 0;
            return this.rowIndexOffset + 1;
        },
        endItem() {
            if (!this.rows?.length) return 0;
            return Math.min(
                this.rowIndexOffset + this.perPage,
                this.rows.length,
            );
        },
        visiblePages() {
            // simple: show up to 5 pages around current
            const total = this.totalPages;
            const cur = this.currentPage;

            const start = Math.max(1, cur - 2);
            const end = Math.min(total, start + 4);

            const pages = [];
            for (let p = start; p <= end; p++) pages.push(p);
            return pages;
        },
    },
    watch: {
        // If rows change (filter/search), keep page valid and close open accordion
        rows: {
            handler() {
                if (this.currentPage > this.totalPages) this.currentPage = 1;
                this.openKey = null;
            },
            deep: false,
        },
    },
    methods: {
        goToPage(page) {
            const safe = Math.min(this.totalPages, Math.max(1, page));
            if (safe === this.currentPage) return;

            this.currentPage = safe;
            this.openKey = null; // close expanded row on page change
        },

        getRowKey(row, i) {
            const emp = (row?.employee_no ?? "").toString().trim();
            return emp ? `emp-${emp}-${i}` : `idx-${i}`;
        },
        isOpen(row, i) {
            return this.openKey === this.getRowKey(row, i);
        },
        toggleRow(row, i) {
            const key = this.getRowKey(row, i);
            this.openKey = this.openKey === key ? null : key;
        },
        recomputeRow(row) {
            this.$emit('recompute', row)
        },
        viewRow(row) {
            console.log("VIEW", row);
            this.$emit('view', row)

        },
        editRow(row) {
            this.$emit('edit', row)
        }
    },
};
</script>

<style lang="scss" scoped>
/* =========================================
   TABLE WRAPPER
========================================= */
.own-table-wrapper {
    border-radius: 8px;
    background: var(--bs-body-bg);

    .own-table {
        width: 100%;
        border-collapse: separate; // sticky + borders stable
        border-spacing: 0;

        /* ===== HEADER ===== */
        thead {
            th {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.72rem;
                padding: 8px 12px;
                border: 1px solid var(--bs-border-color);
                box-shadow: 0 1px 0 var(--bs-border-color);
            }
        }

        /* ===== BODY CELLS ===== */
        td {
            padding: 6px 12px;
        }
    }
}

/* =========================================
   STICKY HEADERS
========================================= */
.sticky {
    position: sticky;
    background: var(--bs-secondary-bg);
}

.th-1 {
    top: 0;
    z-index: 10;
}

.th-2 {
    top: 34px;
    z-index: 9;
}

</style>
