<template>
  <section class="mb-4" v-if="showSection">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="text-muted fw-semibold mb-0">{{ title }}</h6>
    </div>

    <div class="row g-3">
      <template v-if="payrolls.length">
        <div v-for="item in payrolls" :key="item.id" :class="columnClass">
          <slot name="card" :payroll="item" :url="url">
            <PayrollCard
              :url="url"
              :payroll="item"
              @change-status="forwardChangeStatus"
              @cancel="$emit('cancel', $event)"
            />
          </slot>
        </div>
      </template>

      <template v-else-if="showEmptyState">
        <div class="col-12">
          <div class="alert alert-info mb-0" role="alert" style="max-width: 420px">
            {{ emptyMessage }}
          </div>
        </div>
      </template>
    </div>
  </section>
</template>

<script>
import PayrollCard from "./PayrollCard.vue";

export default {
  name: "PayrollSection",
  components: { PayrollCard },
  emits: ["change-status", "cancel"],
  props: {
    title: { type: String, required: true },
    payrolls: { type: Array, required: true },
    url: { type: String, required: true },
    columnClass: { type: String, default: "col-12 col-md-6" },
    emptyMessage: { type: String, default: "" },
    showEmptyState: { type: Boolean, default: false },
    showSection: { type: Boolean, default: true },
  },
  methods: {
    forwardChangeStatus(id, nextStatus) {
      this.$emit("change-status", id, nextStatus);
    },
  },
};
</script>
