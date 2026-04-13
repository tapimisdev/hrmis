<template>
    <div class="mt-3 border-bottom pb-3">
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
                <button class="fb-btn fb-secondary">
                    <i class="fa-solid fa-file-pdf me-1"></i>
                    Export
                </button>
                <button class="fb-btn fb-primary">
                    <i class="fa-solid fa-file-pdf me-1"></i>
                    Pull from Payroll
                </button>
                <button
                    class="fb-btn bg-success"
                    @click="confirmApplyToPayroll"
                    :disabled="isApplying"
                >
                    <i class="fa-solid fa-check me-1"></i>
                    {{ isApplying ? "Applying..." : "Apply to Payroll" }}
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
import IndexForecast from "./forecast/IndexForecast.vue";

export default {
    components: {
        IndexForecast,
    },
    props: {
        body: {
            type: Array,
            required: false,
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
        };
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
        async confirmApplyToPayroll() {
            if (this.isApplying) return;

            const result = await Swal.fire({
                title: "Apply Forecast to Payroll?",
                html: `
                    <div class="text-start">
                        <div>This will update the Payroll tax tables used for employee tax computation.</div>
                        <div class="mt-2">This action will apply the selected taxation setup from <b class="text-primary">January to December</b>.</div>
                        <div class="mt-2">If Payroll tax data already exists for those months, it will be <b class="text-danger">overridden</b> by this action.</div>
                        <div class="mt-2">To continue, type <b>Apply</b> below.</div>
                    </div>
                `,
                icon: "warning",
                input: "text",
                inputPlaceholder: "Type Apply to confirm",
                inputAttributes: { autocapitalize: "off" },
                showCancelButton: true,
                confirmButtonText: "Apply to Payroll",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#198754",
                reverseButtons: true,
                preConfirm: (value) => {
                    if (String(value || "").trim() !== "APPLY") {
                        Swal.showValidationMessage(
                            'Confirmation text must be "APPLY".',
                        );
                        return false;
                    }

                    return true;
                },
            });

            if (!result.isConfirmed) return;

            this.$emit("apply-to-tax");
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
