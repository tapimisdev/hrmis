<template>
    <div>
        <h6 class="mb-3">Tab B — Forecasting Assumptions</h6>

        <div class="row g-3">
            <div class="col-md-12 col-lg-6">
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
                        <label
                            class="form-check-label d-flex justify-content-between align-items-center"
                            for="lessBirRR32015"
                        >
                            <span>Less BIR RR 3-2015</span>

                            <AppTooltip
                                text="Applies the ₱90,000 tax exemption for bonuses and benefits under BIR RR 3-2015. This reduces the taxable portion of bonuses."
                            />
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-lg-6">
                <DynamicRows
                    v-model="proxy.othersEarnings"
                    :errors="errors"
                    error-key="othersEarnings"
                    :enableTaxType="true"
                >
                    <template #title>
                        <span>Others (Earnings)</span>

                        <AppTooltip
                            class="ms-2"
                            text="Includes additional taxable or non-taxable earnings such as bonuses, allowances, or other compensation not listed above."
                        />
                    </template>
                </DynamicRows>
            </div>

            <div class="col-12">
                <div class="border rounded p-3">
                    <div class="fw-bold mb-2 d-flex align-items-center">
                        <span>Allowable Deductions</span>
                    </div>

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
                        v-model="proxy.othersDeductions"
                        :errors="errors"
                        error-key="othersDeductions">

                        <template #title>
                            <span>Others (Allowable Deductions)</span>

                            <AppTooltip
                                class="ms-2"
                                text="Includes other allowable deductions not listed above, such as approved employee deductions or BIR-recognized adjustments that reduce taxable income."
                            />
                        </template>
                
                    </DynamicRows>

                    <small
                        v-if="errors?.othersDeductions"
                        class="text-danger"
                        >{{ errors.othersDeductions }}</small
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DynamicRows from "./partials/DynamicRows.vue";
import AppTooltip from "../../../../../components/AppTooltip.vue";

export default {
    name: "TabBAssumptions",
    components: { DynamicRows, AppTooltip },
    props: {
        modelValue: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
    },
    emits: ["update:modelValue"],

    data() {
        return {
            earningChecks: [
                { key: "basicPay", label: "Basic Pay", disabled: true },
                {
                    key: "midYear",
                    label: "Mid Year (same as monthly salary)",
                    disabled: false,
                },
                {
                    key: "yearEnd",
                    label: "Year End (same as monthly salary)",
                    disabled: false,
                },
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
