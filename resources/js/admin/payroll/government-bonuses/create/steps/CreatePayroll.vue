<template>
    <div>
        <h5 class="mb-3 text-primary text-uppercase">Step 1: Create Payroll</h5>
        <p class="text-muted mb-4">
            Select the government bonus type, then review which employees were matched by its rules.
        </p>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label text-body fw-bold">Label</label>
                <input
                    type="text"
                    class="form-control"
                    v-model="localForm.label"
                    :class="{ 'is-invalid': errors.label }"
                />
                <small v-if="errors.label" class="text-danger">{{ errors.label[0] }}</small>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label text-body fw-bold">Month</label>
                <input
                    type="month"
                    class="form-control"
                    v-model="localForm.month"
                    :class="{ 'is-invalid': errors.month }"
                />
                <small v-if="errors.month" class="text-danger">{{ errors.month[0] }}</small>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label text-body fw-bold">Employment Type</label>
                <select
                    class="form-select"
                    v-model="localForm.employment_type_id"
                    :class="{ 'is-invalid': errors.employment_type_id }"
                >
                    <option :value="1">Regular</option>
                    <option :value="2">COS</option>
                </select>
                <small v-if="errors.employment_type_id" class="text-danger">
                    {{ errors.employment_type_id[0] }}
                </small>
            </div>

            <div class="col-12">
                <label class="form-label text-body fw-bold">Government Bonus Type</label>
                <select
                    class="form-select"
                    v-model="localForm.government_bonus_type_id"
                    :class="{ 'is-invalid': errors.government_bonus_type_id }"
                >
                    <option value="">-- CHOOSE BONUS TYPE --</option>
                    <option
                        v-for="bonusType in modelValue.bonus_types || []"
                        :key="bonusType.id"
                        :value="bonusType.id"
                    >
                        {{ bonusType.name }}
                    </option>
                </select>
                <small v-if="errors.government_bonus_type_id" class="text-danger">
                    {{ errors.government_bonus_type_id[0] }}
                </small>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CreatePayroll",
    props: {
        modelValue: Object,
        errors: Object,
    },
    emits: ["update:modelValue"],
    computed: {
        localForm: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
    },
};
</script>
