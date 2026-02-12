<template>
    <div>
        <h6 class="mb-3">Tab B — Forecasting Assumptions</h6>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3">
                    <div class="fw-bold mb-2">Earnings Included</div>

                    <div
                        class="form-check"
                        v-for="item in earningChecks"
                        :key="item.key"
                    >
                        <input
                            class="form-check-input"
                            type="checkbox"
                            :id="item.key"
                            v-model="proxy.assumptions[item.key]"
                            :disabled="item.disabled"
                        />
                        <label class="form-check-label" :for="item.key">{{
                            item.label
                        }}</label>
                    </div>

                    <hr class="my-2" />

                    <div class="fw-bold mb-2">Less / Exemptions</div>
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="lessBirRR32015"
                            v-model="proxy.assumptions.lessBirRR32015"
                        />
                        <label class="form-check-label" for="lessBirRR32015"
                            >Less BIR RR3-2015</label
                        >
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <DynamicRows
                    title="Others (Earnings)"
                    v-model="proxy.othersEarnings"
                    :errors="errors"
                    error-key="othersEarnings"
                />
            </div>

            <div class="col-12">
                <div class="border rounded p-3">
                    <div class="fw-bold mb-2">Allowable Deductions</div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="gsis"
                                    v-model="proxy.deductions.gsis"
                                    disabled
                                />
                                <label class="form-check-label" for="gsis"
                                    >GSIS</label
                                >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="philhealth"
                                    v-model="proxy.deductions.philhealth"
                                    disabled
                                />
                                <label class="form-check-label" for="philhealth"
                                    >PhilHealth</label
                                >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="pagibig"
                                    v-model="proxy.deductions.pagibig"
                                    disabled
                                />
                                <label class="form-check-label" for="pagibig"
                                    >Pag-IBIG (max ₱200)</label
                                >
                            </div>
                        </div>
                    </div>

                    <hr class="my-3" />

                    <DynamicRows
                        title="Others (Allowable Deductions)"
                        v-model="proxy.othersDeductions"
                        :errors="errors"
                        error-key="othersDeductions"
                    />

                    <small v-if="errors?.othersDeductions" class="text-danger">{{
                        errors.othersDeductions
                    }}</small>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DynamicRows from "./partials/DynamicRows.vue";

export default {
    name: "TabBAssumptions",
    components: { DynamicRows },
    props: {
        modelValue: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
    },
    emits: ["update:modelValue"],

    data() {
        return {
            earningChecks: [
                { key: "basicPay", label: "Basic Pay", disabled: true },
                { key: "midYear", label: "Mid Year (same as monthly salary)", disabled: false },
                { key: "yearEnd", label: "Year End (same as monthly salary)", disabled: false },
                { key: "longevity", label: "Longevity", disabled: false },
                { key: "hazardPay", label: "Hazard Pay", disabled: false },
            ],
        };
    },

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
