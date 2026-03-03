<template>
    <div>
        <h6 class="mb-3">Tab A — Reference Lists</h6>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label d-flex align-items-center gap-2">
                    <span>
                        Hazard Tax List
                        <span class="text-danger">(Required)</span>
                    </span>

                    <AppTooltip
                        text="Computed hazard tax will be saved to this selected table." />
                </label>
                <select class="form-select form-select-sm" v-model="proxy.hazardTaxId" :disabled="loading.hazard">
                    <option value="">-- Select --</option>
                    <option v-for="i in options.hazardTax" :key="i.id" :value="i.id">
                        {{ i.year }}
                    </option>
                </select>
                <small v-if="errors?.hazardTaxId" class="text-danger">{{
                    errors.hazardTaxId[0]
                }}</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Salary Tax List
                    <span class="text-danger me-2">(Reuired)</span>
                    <AppTooltip
                        text="Computed salary tax will be saved to this selected table." />
                </label>
                <select class="form-select form-select-sm" v-model="proxy.salaryTaxId" :disabled="loading.salary">
                    <option value="">-- Select --</option>
                    <option v-for="i in options.salaryTax" :key="i.id" :value="i.id">
                        {{ i.year }}
                    </option>
                </select>
                <small v-if="errors?.salaryTaxId" class="text-danger">{{
                    errors.salaryTaxId[0]
                }}</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Longevity Tax List
                    <span class="text-danger me-2">(Reuired)</span>
                    <AppTooltip
                        text="Computed longevity tax will be saved to this selected table." />
                </label>
                <select class="form-select form-select-sm" v-model="proxy.longevityTaxId" :disabled="loading.longevity">
                    <option value="">-- Select --</option>
                    <option v-for="i in options.longevityTax" :key="i.id" :value="i.id">
                        {{ i.year }}
                    </option>
                </select>
                <small v-if="errors?.longevityTaxId" class="text-danger">{{
                    errors.longevityTaxId[0]
                }}</small>
            </div>

            <div class="col-md-6">
                <label class="form-label d-flex align-items-center gap-2">
                    <span>
                        TRAIN Law List
                        <span class="text-danger">(Required)</span>
                    </span>

                    <AppTooltip
                        text="This TRAIN Law table will be used for tax computation." />
                </label>
                <select class="form-select form-select-sm" v-model="proxy.trainLawId" :disabled="loading.train">
                    <option value="">-- Select --</option>
                    <option v-for="i in options.trainLaw" :key="i.id" :value="i.id">
                        {{ i.year }}
                    </option>
                </select>
                <small v-if="errors?.trainLawId" class="text-danger">{{
                    errors.trainLawId[0]
                }}</small>
            </div>
        </div>
    </div>
</template>

<script>
import AppTooltip from "../../../../../components/AppTooltip.vue";
export default {
    name: "TabALists",
    components: { AppTooltip },
    props: {
        modelValue: { type: Object, required: true },
        options: { type: Object, required: true },
        loading: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
    },
    emits: ["update:modelValue"],

    computed: {
        proxy: {
            get() {
                return this.modelValue;
            },
            set(v) {
                this.$emit("update:modelValue", v);
            },
        },
    },
};
</script>
