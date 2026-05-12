<template>
    <div class="mt-3 border-bottom pb-3">
        <ComputeCumulativeChoiceModal
            ref="computeCumulativeChoiceModal"
            :is-submitting="isComputingCumulative"
            :active-action="currentCumulativeAction"
            @select="handleComputeCumulativeSelection"
        />
        <ComputeCumulativeModal
            ref="computeCumulativeModal"
            :taxation="taxation"
            :is-submitting="isComputingCumulative"
            @submit="submitOverrideCumulative"
        />
        <ComputeAdjustmentModal
            ref="computeAdjustmentModal"
            :taxation="taxation"
            :is-submitting="isComputingAdjustment"
            @submit="submitAdjustment"
        />
        <ApplyTaxComputationModal
            ref="applyTaxComputationModal"
            :is-submitting="isApplying"
            :type="activeTab"
            @confirm="submitApplyToPayroll"
        />

        <div
            class="border-bottom mb-3 d-flex justify-content-between align-items-center"
        >
            <!-- MAIN MODE TABS -->
            <div class="fb-tabs mb-3" style="max-width: 360px">
                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'forecast' }"
                    @click="setActiveTab('forecast')"
                >
                    Q1
                    <span class="ms-1 text-muted"> Forecast </span>
                </button>

                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'q2' }"
                    @click="setActiveTab('q2')"
                >
                    Q2
                    <span class="ms-1 text-muted"> Actual (Apr–Jun) </span>
                </button>

                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'q3' }"
                    @click="setActiveTab('q3')"
                >
                    Q3
                    <span class="ms-1 text-muted"> Actual (Jul–Sep) </span>
                </button>

                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'q4' }"
                    @click="setActiveTab('q4')"
                >
                    Q4
                    <span class="ms-1 text-muted"> Actual (Oct-Dec) </span>
                </button>

                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'nov' }"
                    @click="setActiveTab('nov')"
                >
                    Adjustment
                    <span class="ms-1 text-muted"> (Nov) </span>
                </button>

                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'final' }"
                    @click="setActiveTab('final')"
                >
                    Finalization
                    <span class="ms-1 text-muted"> (Dec) </span>
                </button>
            </div>

            <div class="d-flex mb-3 gap-2">
                <button
                    v-if="showComputeAdjustmentButton"
                    class="fb-btn fb-secondary"
                    @click="openComputeAdjustmentModal"
                    :disabled="isComputingAdjustment"
                >
                    <i class="fa-solid fa-calculator me-1"></i>
                    {{
                        isComputingAdjustment
                            ? "Computing Adjustment..."
                            : "Compute Adjustment"
                    }}
                </button>

                <button
                    v-else
                    class="fb-btn fb-secondary"
                    @click="handleComputeCumulative"
                    :disabled="isComputingCumulative"
                >
                    <i class="fa-solid fa-calculator me-1"></i>
                    {{
                        isComputingCumulative
                            ? "Computing Cumulative..."
                            : "Compute Cumulative"
                    }}
                </button>

                <button
                    class="fb-btn fb-secondary"
                    @click="confirmApplyToPayroll"
                    :disabled="isApplying"
                >
                    <i class="fa-solid fa-check me-1"></i>
                    {{
                        isApplying
                            ? "Applying Taxes..."
                            : "Apply Tax Computation"
                    }}
                </button>
            </div>
        </div>

        <!-- CONTENT -->
        <IndexForecast
            :body="body"
            :is-applying-to-payroll="isApplyingToPayroll"
            :selected-type="selectedType"
            @refresh-forecast="$emit('refresh-forecast', $event)"
        />
    </div>
</template>

<script>
import axios from "axios";
import IndexForecast from "./forecast/IndexForecast.vue";
import ComputeCumulativeModal from "../modal/run-forecast/ComputeCumulativeModal.vue";
import ComputeAdjustmentModal from "../modal/run-forecast/ComputeAdjustmentModal.vue";
import ComputeCumulativeChoiceModal from "../modal/run-forecast/ComputeCumulativeChoiceModal.vue";
import ApplyTaxComputationModal from "../modal/ApplyTaxComputationModal.vue";

export default {
    components: {
        IndexForecast,
        ComputeCumulativeChoiceModal,
        ComputeCumulativeModal,
        ComputeAdjustmentModal,
        ApplyTaxComputationModal,
    },
    props: {
        body: {
            type: Array,
            required: false,
        },
        taxation: {
            type: Object,
            default: () => ({}),
        },
        disable_recon: {
            type: Boolean,
            default: true,
        },
        isApplyingToPayroll: {
            type: Boolean,
            default: false,
        },
        selectedType: {
            type: String,
            default: "forecast",
        },
    },
    data() {
        return {
            activeTab: this.selectedType || "forecast",
            isComputingCumulative: false,
            isComputingAdjustment: false,
            currentCumulativeAction: "",
        };
    },
    computed: {
        isApplying() {
            return this.isApplyingToPayroll;
        },
        hasTaxationRecord() {
            return Boolean(this.taxation?.id);
        },
        showComputeAdjustmentButton() {
            return this.activeTab === "nov";
        },
    },
    watch: {
        selectedType(newType) {
            if (newType && newType !== this.activeTab) {
                this.activeTab = newType;
            }
        },
    },
    methods: {
        setActiveTab(type) {
            if (!type || type === this.activeTab) return;

            this.activeTab = type;
            this.$emit("type-change", type);
        },
        async handleComputeCumulative() {
            if (this.isComputingCumulative) return;
            this.currentCumulativeAction = "";

            if (!this.hasTaxationRecord) {
                await Swal.fire({
                    title: "No Taxation Selected",
                    text: "There is no saved taxation record to use for cumulative computation.",
                    icon: "info",
                });
                return;
            }

            if (this.activeTab === "forecast") {
                await Swal.fire({
                    title: "Select a Cumulative Tab",
                    text: "Compute Cumulative is only available for Q2, Q3, Q4, Adjustment, or Finalization.",
                    icon: "info",
                });
                return;
            }

            this.$refs.computeCumulativeChoiceModal?.open?.();
        },
        async handleComputeCumulativeSelection(mode) {
            this.currentCumulativeAction = mode;

            if (mode === "override") {
                this.$refs.computeCumulativeChoiceModal?.close?.();
                this.$refs.computeCumulativeModal?.open?.();
                return;
            }

            if (mode === "same_configuration") {
                const result = await Swal.fire({
                    title: "Compute with Same Configuration?",
                    text: "This will use the currently saved setup and run the cumulative computation immediately.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Compute",
                    cancelButtonText: "Cancel",
                });

                if (!result.isConfirmed) {
                    this.currentCumulativeAction = "";
                    return;
                }

                await this.computeCumulative({
                    mode: "same_configuration",
                });
            }
        },
        async submitOverrideCumulative(form) {
            await this.computeCumulative({
                mode: "override",
                ...form,
            });
        },
        async openComputeAdjustmentModal() {
            if (this.isComputingAdjustment) return;

            if (!this.hasTaxationRecord) {
                await Swal.fire({
                    title: "No Taxation Selected",
                    text: "There is no saved taxation record to use for adjustment computation.",
                    icon: "info",
                });
                return;
            }

            this.$refs.computeAdjustmentModal?.open?.();
        },
        async submitAdjustment(form) {
            await this.computeAdjustment({
                mode: "override",
                ...form,
            });
        },
        async computeCumulative(payload = {}) {
            if (this.isComputingCumulative) return;

            this.isComputingCumulative = true;
            this.$refs.computeCumulativeModal?.setErrors?.({});

            try {
                const response = await axios.post(
                    "/admin/taxation/compute-cumulative",
                    {
                        taxation_id: this.taxation.id,
                        type: this.activeTab,
                        ...payload,
                    },
                );

                this.$refs.computeCumulativeModal?.close?.();
                this.$refs.computeCumulativeChoiceModal?.close?.();

                await Swal.fire({
                    title: "Cumulative Computation Started",
                    text:
                        response?.data?.message ||
                        "The cumulative computation has been queued.",
                    icon: "success",
                });

                this.$emit("refresh-forecast", {
                    source: "compute-cumulative",
                    type: this.activeTab,
                });
            } catch (error) {
                if (error.response?.status === 422) {
                    this.$refs.computeCumulativeModal?.setErrors?.(
                        error.response?.data?.errors || {},
                    );
                    return;
                }

                await Swal.fire({
                    title: "Error",
                    text:
                        error?.response?.data?.message ||
                        "Failed to compute cumulative tax.",
                    icon: "error",
                });
            } finally {
                this.isComputingCumulative = false;
                this.currentCumulativeAction = "";
            }
        },
        async computeAdjustment(payload = {}) {
            if (this.isComputingAdjustment) return;

            this.isComputingAdjustment = true;
            this.$refs.computeAdjustmentModal?.setErrors?.({});

            try {
                const response = await axios.post(
                    "/admin/taxation/compute-cumulative",
                    {
                        taxation_id: this.taxation.id,
                        type: this.activeTab,
                        ...payload,
                    },
                );

                this.$refs.computeAdjustmentModal?.close?.();

                await Swal.fire({
                    title: "Adjustment Computation Started",
                    text:
                        response?.data?.message ||
                        "The adjustment computation has been queued.",
                    icon: "success",
                });

                this.$emit("refresh-forecast", {
                    source: "compute-adjustment",
                    type: this.activeTab,
                });
            } catch (error) {
                if (error.response?.status === 422) {
                    this.$refs.computeAdjustmentModal?.setErrors?.(
                        error.response?.data?.errors || {},
                    );
                    return;
                }

                await Swal.fire({
                    title: "Error",
                    text:
                        error?.response?.data?.message ||
                        "Failed to compute adjustment tax.",
                    icon: "error",
                });
            } finally {
                this.isComputingAdjustment = false;
            }
        },
        async confirmApplyToPayroll() {
            if (this.isApplying) return;
            this.$refs.applyTaxComputationModal?.open?.();
        },
        submitApplyToPayroll() {
            this.$refs.applyTaxComputationModal?.close?.();
            this.$emit("apply-to-tax", { type: this.activeTab });
        },
    },
};
</script>

<style lang="scss" scoped>
.fb-tabs {
    flex-wrap: wrap;
    max-width: 920px !important;
    width: 100%;
}
.fb-tab {
    &:disabled {
        background-color: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
        opacity: 0.7;
        pointer-events: none;
    }
}
</style>
