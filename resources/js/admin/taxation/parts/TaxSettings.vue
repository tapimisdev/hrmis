<template>
    <div class="tax-settings mt-3">
        <h6 class="fw-semibold mb-3">Tax Settings</h6>
        <div class="row g-3">
            <div class="col-lg-6">
                <TrainLaw :train-law-year="settings.train_law_year" :rows="settings.train_law_table" />
            </div>

            <div class="col-lg-3">
                <ComponentMapping :items="settings.mapping_components" />
            </div>

            <div class="col-lg-3">
                <ForecastAllocation :allocation="settings.allocation" />
            </div>
        </div>
    </div>
</template>

<script>
import TrainLaw from "./TaxSettingsComponents/TrainLaw.vue";
import ComponentMapping from "./TaxSettingsComponents/ComponentMapping.vue";
import ForecastAllocation from "./TaxSettingsComponents/ForecastAllocation.vue";

export default {
    name: "TaxSettings",
    components: { TrainLaw, ComponentMapping, ForecastAllocation },

    props: {
        settings: { type: Object, default: () => ({}) },
    },
};
</script>
