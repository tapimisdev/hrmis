<template>
  <div class="tax-settings mt-3">
    <h6 class="fw-semibold mb-3">Tax Settings</h6>

    <div class="row g-3">
      <div class="col-lg-6">
        <TrainLaw
          :years="years"
          :selected-year="selectedYear"
          :rows="taxTableRows"
          @update:selectedYear="selectedYear = $event"
          @import="importTrainTaxTable"
        />
      </div>

      <div class="col-lg-3">
        <ComponentMapping :items="componentMapping" />
      </div>

      <div class="col-lg-3">
        <ForecastAssumptions
          :assumptions="forecastAssumptions"
          @update:assumptions="forecastAssumptions = $event"
        />
      </div>
    </div>
  </div>
</template>

<script>
import TrainLaw from "./TaxSettingsComponents/TrainLaw.vue";
import ComponentMapping from "./TaxSettingsComponents/ComponentMapping.vue";
import ForecastAssumptions from "./TaxSettingsComponents/ForecastAssumptions.vue";

export default {
  name: "TaxSettings",
  components: { TrainLaw, ComponentMapping, ForecastAssumptions },

  data() {
    return {
      years: [
        { value: 2023, label: "2023+ (current)" },
        { value: 2022, label: "2022" },
        { value: 2021, label: "2021" },
      ],
      selectedYear: 2023,

      taxTableRows: [
        { income_from: "—", income_to: "—", base_tax: "—", rate: "33%" },
        { income_from: "—", income_to: "—", base_tax: "—", rate: "1.50%" },
        { income_from: "—", income_to: "—", base_tax: "—", rate: "5.50%" },
      ],

      componentMapping: [
        { label: "Basic Pay", note: "Taxable", ok: true },
        { label: "Rice Subsidy", note: "Non-Taxable", ok: true },
        { label: "13th Month", note: "Non-Taxable up to ₱90,000", ok: true },
      ],

      forecastAssumptions: {
        constantSalary: true,
        includeProjected13th: true,
        capDeMinimis: true,
      },
    };
  },

  methods: {
    importTrainTaxTable() {
      // Hook your real import logic here (API upload / file picker / etc.)
      console.log("Import TRAIN tax table for year:", this.selectedYear);
    },
  },
};
</script>

<style scoped>
.tax-settings {

}
</style>
