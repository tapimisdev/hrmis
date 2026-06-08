<template>
    <div>
        <div class="row g-3">
            <div class="col-md-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                    <div class="fw-bold mb-2">Earnings Included</div>

                    <div
                        v-for="item in earningChecks"
                        :key="item.key"
                        class="form-check mb-2"
                    >
                        <input
                            :id="item.key"
                            v-model="proxy.assumptions[item.key]"
                            class="form-check-input"
                            type="checkbox"
                            disabled
                        />
                        <label class="form-check-label" :for="item.key">
                            {{ item.label }}
                        </label>
                    </div>

                    <hr class="my-3" />

                    <div class="fw-bold mb-2">Less / Exemptions</div>

                    <div class="form-check">
                        <input
                            id="adjustmentLessBirRR32015"
                            v-model="proxy.assumptions.lessBirRR32015"
                            class="form-check-input"
                            type="checkbox"
                            disabled
                        />
                        <label
                            class="form-check-label"
                            for="adjustmentLessBirRR32015"
                        >
                            LESS BIR RR 3-2015
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                    <div class="fw-bold mb-2">Government Bonuses</div>

                    <div v-if="isLoadingBonuses" class="text-muted small">
                        Loading bonuses...
                    </div>

                    <div
                        v-else-if="governmentBonuses.length"
                        class="row g-2"
                    >
                        <div
                            v-for="bonus in governmentBonuses"
                            :key="bonus.id"
                            class="col-12"
                        >
                            <label
                                class="border rounded px-2 py-2 d-flex align-items-start gap-2 w-100"
                            >
                                <input
                                    v-model="proxy.governmentBonuses"
                                    class="form-check-input mt-1"
                                    type="checkbox"
                                    :value="bonus.id"
                                />

                                <span class="d-flex flex-column">
                                    <span class="fw-semibold small">
                                        {{ bonus.name }}
                                    </span>
                                    <span class="text-muted x-small">
                                        {{ bonus.monthLabel }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div v-else class="text-muted small">
                        No government bonuses found for taxation year
                        {{ taxationYear || "-" }}.
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded p-3">
                    <div class="fw-bold mb-2">Allowable Deductions</div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    id="adjustmentGsis"
                                    v-model="proxy.deductions.gsis"
                                    class="form-check-input"
                                    type="checkbox"
                                    disabled
                                />
                                <label
                                    class="form-check-label"
                                    for="adjustmentGsis"
                                >
                                    GSIS
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    id="adjustmentPhilhealth"
                                    v-model="proxy.deductions.philhealth"
                                    class="form-check-input"
                                    type="checkbox"
                                    disabled
                                />
                                <label
                                    class="form-check-label"
                                    for="adjustmentPhilhealth"
                                >
                                    PhilHealth
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check">
                                <input
                                    id="adjustmentPagibig"
                                    v-model="proxy.deductions.pagibig"
                                    class="form-check-input"
                                    type="checkbox"
                                    disabled
                                />
                                <label
                                    class="form-check-label"
                                    for="adjustmentPagibig"
                                >
                                    Pag-IBIG (max ₱200)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ComputeAdjustmentFields",
    props: {
        modelValue: { type: Object, required: true },
        governmentBonuses: { type: Array, default: () => [] },
        isLoadingBonuses: { type: Boolean, default: false },
        taxationYear: { type: [String, Number], default: "" },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            earningChecks: [
                { key: "basicPay", label: "Basic Pay" },
                { key: "longevity", label: "Longevity" },
                { key: "hazardPay", label: "Hazard Pay" },
            ],
        };
    },
    computed: {
        proxy: {
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

<style scoped>
.x-small {
    font-size: 0.75rem;
}
</style>
