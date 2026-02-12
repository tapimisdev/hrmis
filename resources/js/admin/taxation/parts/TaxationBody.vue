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
                    :class="{ active: activeTab === 'reconcile' }"
                    @click="activeTab = 'reconcile'"
                >
                    Reconciliation
                </button>
            </div>

            <button class="fb-btn fb-secondary">
                <i class="fa-solid fa-file-pdf me-1"></i>
                Export
            </button>
        </div>

        <!-- CONTENT -->
        <IndexForecast v-if="activeTab === 'forecast'" />
        <IndexReconciliation v-if="activeTab === 'reconcile'" />

    </div>
</template>

<script>
import IndexForecast from "./forecast/IndexForecast.vue";
import IndexReconciliation from "./Reconcilliation/IndexReconcilliation.vue";

export default {
    components: {
        IndexForecast,
        IndexReconciliation
    },
    data() {
        return {
            activeTab: "forecast" // default tab
        };
    }
};
</script>
