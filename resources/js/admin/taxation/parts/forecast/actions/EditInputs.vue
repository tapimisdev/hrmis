<template>
  <TaxTemplate :is_open="true">
    <template #header>Edit Inputs</template>

    <div class="text-center d-flex flex-column align-items-center justify-content-center py-5">
      Edit inputs!
    </div>
  </TaxTemplate>
</template>

<script>
import TaxTemplate from "./components/TaxTemplate.vue";

export default {
  name: "EditInputs",
  components: { TaxTemplate },
};
</script>