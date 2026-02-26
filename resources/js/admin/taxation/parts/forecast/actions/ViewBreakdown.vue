<template>
  <TaxTemplate :is_open="true">
    <template #header>Breakdown</template>

    <div class="text-center d-flex flex-column align-items-center justify-content-center py-5">
      breakdown to yow!
    </div>
  </TaxTemplate>
</template>

<script>
import TaxTemplate from "./components/TaxTemplate.vue";

export default {
  name: "ViewBreakDown",
  components: { TaxTemplate },
};
</script>