<template>
    <div class="border rounded p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            
            <div class="fw-bold mb-2 d-flex align-items-center">
                <slot name="title">
                    {{ title }}
                </slot>
            </div>

            <button type="button" class="fb-btn fb-primary" @click="add">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <div v-if="rows.length === 0" class="text-muted small">
            No items added.
        </div>

        <div v-for="(row, idx) in rows" :key="idx" class="row g-2 mb-3">
            <!-- NAME -->
            <div :class="enableTaxType ? 'col-10' : 'col-7'">
                <input
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': hasFieldError(idx, 'name') }"
                    placeholder="Name"
                    v-model.trim="row.name"
                />
                <div v-if="hasFieldError(idx, 'name')" class="invalid-feedback">
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
                <div v-if="hasFieldError(idx, 'amount')" class="invalid-feedback">
                    {{ fieldError(idx, "amount") }}
                </div>
            </div>

            <!-- TAX TYPE (ONLY FOR EARNINGS) -->
            <div v-if="enableTaxType" class="col-6">
                <select
                    class="form-select form-select-sm"
                    :class="{ 'is-invalid': hasFieldError(idx, 'tax_type') }"
                    v-model="row.tax_type"
                >
                    <option disabled value="">Tax type</option>
                    <option value="taxable">Taxable</option>
                    <option value="non_taxable">Non-taxable</option>
                    <option value="exempt">Exempt</option>
                </select>

                <div v-if="hasFieldError(idx, 'tax_type')" class="invalid-feedback">
                    {{ fieldError(idx, "tax_type") }}
                </div>
            </div>

            <!-- REMOVE -->
            <div class="col-1 d-flex align-items-center justify-content-start">
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

        <!-- OPTIONAL: base-level errors -->
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
        errors: { type: Object, default: () => ({}) },
        errorKey: { type: String, required: true },

        // sendYearToParentNEW: enable tax_type select ONLY for earnings
        enableTaxType: { type: Boolean, default: false },

        defaultRow: {
            type: Object,
            default: () => ({ name: "", amount: 0, tax_type: "" }),
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

        baseError() {
            const val = this.errors?.[this.errorKey];
            return Array.isArray(val) ? val[0] : val || null;
        },
    },

    methods: {
        add() {
            // sendYearToParentonly include tax_type when enabled
            const row = { ...this.defaultRow };

            if (!this.enableTaxType) {
                delete row.tax_type;
            } else {
                // default value if you want it pre-selected:
                // row.tax_type = row.tax_type || 'taxable'
                row.tax_type = row.tax_type ?? "";
            }

            this.rows = [...this.rows, row];
        },

        remove(idx) {
            const copy = [...this.rows];
            copy.splice(idx, 1);
            this.rows = copy;
        },

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
.invalid-feedback {
    display: block;
    font-size: 11px;
    line-height: 1.2;
}
</style>