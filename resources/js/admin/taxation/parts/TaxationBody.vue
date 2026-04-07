<template>
    <div class="mt-3 border-bottom pb-3">

        <div class="border-bottom mb-3 d-flex justify-content-between align-items-center">
            <!-- MAIN MODE TABS -->
            <div class="fb-tabs mb-3" style="max-width: 360px">
                <button
                    class="fb-tab"
                    :class="{ active: activeTab === 'forecast' }"
                    @click="activeTab = 'forecast'"
                >
                    Forecasting
                </button>

                <button
                    class="fb-tab"
                    :disabled="disable_recon"
                    :class="{ active: activeTab === 'cumulative' }"
                    @click="activeTab = 'cumulative'"
                >
                    Cumulative
                </button>
            </div>

            <button class="fb-btn fb-secondary">
                <i class="fa-solid fa-file-pdf me-1"></i>
                Export
            </button>
        </div>

        <!-- CONTENT -->
        <IndexForecast 
            v-if="activeTab === 'forecast'" 
            :body="body"
            :is-applying-to-payroll="isApplyingToPayroll"
            @apply-to-tax="$emit('apply-to-tax')"
            @refresh-forecast="$emit('refresh-forecast', $event)"
            />
            
        <IndexCumulative v-if="activeTab === 'cumulative'" />

    </div>
</template>

<script>
import IndexForecast from "./forecast/IndexForecast.vue";
import IndexCumulative from "./Reconcilliation/IndexComulative.vue";

export default {
    components: {
        IndexForecast,
        IndexCumulative
    },
    props: {
        body: {
            type: Array,
            required: true
        },
        disable_recon: {
            type: Boolean,
            default: true
        },
        isApplyingToPayroll: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            activeTab: "forecast" // default tab
        };
    }
};
</script>

<style lang="scss" scoped>
.fb-tab {
    &:disabled {
        background-color: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
        opacity: 0.7;
        pointer-events: none;
    }
}
</style>
