<template>
    <div class="container-fluid px-2">
        <div class="card border-0 shadow-sm">
            <!-- Header -->
            <div
                class="card-header py-2 bg-body-secondary px-3 d-flex justify-content-between align-items-center"
            >
                <div class="fw-semibold small">TRAIN Law Items</div>

                <button
                    class="btn btn-primary btn-sm"
                    type="button"
                    @click="addRow"
                    title="Add Row"
                >
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="small text-muted">
                            <tr>
                                <th class="text-center" style="width: 45px">
                                    #
                                </th>
                                <th style="min-width: 170px">Income From</th>
                                <th style="min-width: 170px">
                                    Income To
                                    <span class="text-secondary">(blank = Above)</span>
                                </th>
                                <th style="min-width: 150px">Fixed Tax</th>
                                <th style="min-width: 120px">Rate %</th>
                                <th style="min-width: 160px">Excess Over</th>
                                <th class="text-center" style="width: 70px">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(row, i) in rows" :key="row._key">
                                <td class="text-center small fw-semibold">
                                    {{ i + 1 }}
                                </td>

                                <!-- income_from -->
                                <td class="py-1">
                                    <input
                                        v-model="row.income_from"
                                        type="number"
                                        step="0.01"
                                        :class="inputClass(i, 'income_from')"
                                    />
                                    <div v-if="fieldError(i, 'income_from')" class="small text-danger mt-1">
                                        {{ fieldError(i, "income_from") }}
                                    </div>
                                </td>

                                <!-- income_to -->
                                <td class="py-1">
                                    <input
                                        v-model="row.income_to"
                                        type="number"
                                        step="0.01"
                                        placeholder="Optional"
                                        :class="inputClass(i, 'income_to')"
                                    />
                                    <div v-if="fieldError(i, 'income_to')" class="small text-danger mt-1">
                                        {{ fieldError(i, "income_to") }}
                                    </div>
                                </td>

                                <!-- fixed_tax -->
                                <td class="py-1">
                                    <input
                                        v-model="row.fixed_tax"
                                        type="number"
                                        step="0.01"
                                        :class="inputClass(i, 'fixed_tax')"
                                    />
                                    <div v-if="fieldError(i, 'fixed_tax')" class="small text-danger mt-1">
                                        {{ fieldError(i, "fixed_tax") }}
                                    </div>
                                </td>

                                <!-- tax_rate -->
                                <td class="py-1">
                                    <input
                                        v-model="row.tax_rate"
                                        type="number"
                                        step="0.01"
                                        :class="inputClass(i, 'tax_rate')"
                                    />
                                    <div v-if="fieldError(i, 'tax_rate')" class="small text-danger mt-1">
                                        {{ fieldError(i, "tax_rate") }}
                                    </div>
                                </td>

                                <!-- excess_over -->
                                <td class="py-1">
                                    <input
                                        v-model="row.excess_over"
                                        type="number"
                                        step="0.01"
                                        :class="inputClass(i, 'excess_over')"
                                    />
                                    <div v-if="fieldError(i, 'excess_over')" class="small text-danger mt-1">
                                        {{ fieldError(i, "excess_over") }}
                                    </div>
                                </td>

                                <!-- remove only -->
                                <td class="text-center py-1">
                                    <button
                                        class="btn btn-danger btn-sm"
                                        type="button"
                                        @click="removeRow(i)"
                                        title="Remove"
                                        :disabled="rows.length <= 1"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td colspan="7" class="text-center text-muted py-4 small">
                                    No items yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Save All -->
                <div class="d-flex justify-content-end mt-3">
                    <button
                        class="btn btn-success text-light btn-sm px-4"
                        type="button"
                        :disabled="isSubmitting || rows.length === 0"
                        @click="saveAll"
                        title="Save All"
                    >
                        <i
                            :class="
                                isSubmitting
                                    ? 'fa-solid fa-spinner fa-spin me-1'
                                    : 'fa-solid fa-check me-1'
                            "
                        ></i>
                        {{ isSubmitting ? "Saving..." : "Save All" }}
                    </button>
                </div>

                <div class="small text-muted mt-2 px-1">
                    Tip: Leave <strong>Income To</strong> blank for the last bracket (Above). 
                    Each next bracket should start at <strong>.01 higher</strong> than the previous 
                    <strong>Income To</strong> value (e.g., 250,000.00 → 250,000.01) to avoid overlapping ranges.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "TrainLawItemIndex",
    props: {
        trainLawId: { type: Number, required: true },
        items: { type: Array, default: () => [] },
    },

    data() {
        return {
            rows: [],
            isSubmitting: false,
            errors: {}, // expects keys: rows.0.income_from, rows.1.tax_rate, etc.
        };
    },

    mounted() {
        this.setRowsFromItems(this.items);
    },

    watch: {
        items: {
            deep: true,
            handler(newItems) {
                this.setRowsFromItems(newItems);
            },
        },
    },

    methods: {
        makeKey() {
            return crypto?.randomUUID
                ? crypto.randomUUID()
                : String(Date.now() + Math.random());
        },

        setRowsFromItems(items) {
            const data = Array.isArray(items) ? items : [];
            this.rows = data.map((r) => ({
                _key: this.makeKey(),
                ...r,
            }));
            this.errors = {};
        },

        blankRow() {
            return {
                _key: this.makeKey(),
                id: null,
                income_from: "",
                income_to: "",
                fixed_tax: 0,
                tax_rate: 0,
                excess_over: 0,
            };
        },

        addRow() {
            this.rows.unshift(this.blankRow());
            this.errors = {};
        },

        removeRow(index) {
            this.rows.splice(index, 1);
            this.errors = {}; // reset errors so indexes don't mismatch
        },

        normalizeRow(row) {
            return {
                id: row.id ?? null,

                // send numeric or null
                income_from: row.income_from === "" ? null : Number(row.income_from),
                income_to:
                    row.income_to === "" || row.income_to === null
                        ? null
                        : Number(row.income_to),

                fixed_tax: row.fixed_tax === "" ? 0 : Number(row.fixed_tax),
                tax_rate: row.tax_rate === "" ? 0 : Number(row.tax_rate),
                excess_over: row.excess_over === "" ? 0 : Number(row.excess_over),
            };
        },

        saveAll() {
            this.isSubmitting = true;
            this.errors = {};

            const payload = {
                rows: this.rows.map((r) => this.normalizeRow(r)),
            };

            axios
                .post(`/admin/taxation/train-law/${this.trainLawId}/items`, payload)
                .then((res) => {
                    const returned = res.data?.data || [];
                    if (Array.isArray(returned)) {
                        this.setRowsFromItems(returned);
                    }
                })
                .catch((err) => {
                    if (err.response?.status === 422) {
                        this.errors = err.response.data.errors || {};
                    }
                })
                .finally(() => {
                    this.isSubmitting = false;
                });
        },

        fieldError(i, field) {
            return this.errors?.[`rows.${i}.${field}`]?.[0] || "";
        },

        inputClass(i, field) {
            return [
                "form-control",
                "form-control-sm",
                { "is-invalid": !!this.fieldError(i, field) },
            ];
        },
    },
};
</script>