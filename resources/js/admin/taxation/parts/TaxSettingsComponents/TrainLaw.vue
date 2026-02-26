<template>
    <div class="card shadow-sm h-100">
        <div
            class="card-header d-flex align-items-center justify-content-between"
        >
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-table text-muted"></i>
                <span class="fw-semibold">TRAIN Tax Table</span>
            </div>

            <div class="small text-muted">
                {{ trainLawYear }}
            </div>
        </div>

        <div class="card-body p-0">
            <!-- HAS DATA -->
            <div v-if="rows && rows.length" class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Income From</th>
                            <th>Income To</th>
                            <th>Fixed Tax</th>
                            <th>Rate %</th>
                            <th>Excess</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, idx) in rows" :key="idx">
                            <td class="ps-3">{{ r.income_from }}</td>
                            <td>{{ r.income_to ?? '-' }}</td>
                            <td>{{ r.fixed_tax }}</td>
                            <td>{{ r.rate }}</td>
                            <td>{{ r.excess }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- EMPTY STATE -->
            <div
                v-else
                class="d-flex flex-column align-items-center justify-content-center text-center py-5 text-muted"
            >
                <i class="fa-solid fa-file-circle-xmark fs-3 mb-2 opacity-50"></i>

                <div class="fw-semibold small">
                    No TRAIN Law Table Found
                </div>

                <div class="small">
                    No tax brackets are configured for this year.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TrainLaw",
    props: {
        trainLawYear: { type: String, default: "No Train Law" },
        rows: { type: Array, default: () => [] },
    },
};
</script>