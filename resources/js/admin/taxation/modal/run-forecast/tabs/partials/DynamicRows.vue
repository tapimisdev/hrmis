<template>
    <div class="border rounded p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">{{ title }}</div>

            <button type="button" class="fb-btn fb-primary" @click="add">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <div v-if="rows.length === 0" class="text-muted small">
            No items added.
        </div>

        <div v-for="(row, idx) in rows" :key="idx" class="row g-2 mb-2">
            <!-- NAME -->
            <div class="col-7">
                <input
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': hasFieldError(idx, 'name') }"
                    placeholder="Name"
                    v-model.trim="row.name"
                />
                <div
                    v-if="hasFieldError(idx, 'name')"
                    class="invalid-feedback"
                >
                    {{ fieldError(idx, "name") }}
                </div>
            </div>

            <!-- AMOUNT -->
            <div class="col-4">
                <input
                    type="number"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': hasFieldError(idx, 'amount') }"
                    placeholder="Amount"
                    min="0"
                    step="0.01"
                    v-model.number="row.amount"
                />
                <div
                    v-if="hasFieldError(idx, 'amount')"
                    class="invalid-feedback"
                >
                    {{ fieldError(idx, "amount") }}
                </div>
            </div>

            <!-- REMOVE -->
            <div class="col-1 d-flex align-items-center justify-content-end">
                <button
                    type="button"
                    class="btn btn-sm btn-link text-danger p-0"
                    @click="remove(idx)"
                    title="Remove"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- OPTIONAL: show base-level errors like "others_deductions must be array" -->
        <small v-if="baseError" class="text-danger d-block mt-2">
            {{ baseError }}
        </small>
    </div>
</template>

<script>
export default {
    name: "DynamicRows",
    props: {
        modelValue: { type: Array, required: true },
        title: { type: String, required: true },

        /**
         * Laravel 422 errors object:
         * {
         *   "others_deductions.0.name": ["The name field is required."]
         * }
         */
        errors: { type: Object, default: () => ({}) },

        /**
         * Base key for this rows group (match backend keys)
         * Examples:
         *  - "others_deductions"
         *  - "others_earnings"
         *  - "earnings.others"
         *  - "deductions.others"
         */
        errorKey: { type: String, required: true },

        /**
         * Default row structure (optional)
         */
        defaultRow: {
            type: Object,
            default: () => ({ name: "", amount: 0 }),
        },
    },
    emits: ["update:modelValue"],

    computed: {
        rows: {
            get() {
                return this.modelValue || [];
            },
            set(v) {
                this.$emit("update:modelValue", Array.isArray(v) ? v : []);
            },
        },

        // Base-level errors like: "others_deductions": ["Must be an array"]
        baseError() {
            const val = this.errors?.[this.errorKey];
            return Array.isArray(val) ? val[0] : val || null;
        },
    },

    methods: {
        add() {
            this.rows = [...this.rows, { ...this.defaultRow }];
        },

        remove(idx) {
            const copy = [...this.rows];
            copy.splice(idx, 1);
            this.rows = copy;
        },

        // "others_deductions.0.amount" -> first message
        fieldError(idx, field) {
            const key = `${this.errorKey}.${idx}.${field}`;
            const val = this.errors?.[key];

            if (Array.isArray(val)) return val[0] || null;
            if (typeof val === "string") return val;
            return null;
        },

        hasFieldError(idx, field) {
            return !!this.fieldError(idx, field);
        },
    },
};
</script>

<style scoped>
/* Keep bootstrap invalid feedback visible under small inputs */
.invalid-feedback {
    display: block;
    font-size: 11px;
    line-height: 1.2;
}
</style>
