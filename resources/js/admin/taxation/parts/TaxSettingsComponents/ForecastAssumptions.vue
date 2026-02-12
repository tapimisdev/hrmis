<template>
  <div class="card shadow-sm h-100">
    <div class="card-header d-flex align-items-center gap-2">
      <i class="fa-solid fa-sliders text-muted"></i>
      <span class="fw-semibold">Forecast Assumptions</span>
    </div>

    <div class="card-body">
      <div class="form-check mb-2">
        <input
          class="form-check-input"
          type="checkbox"
          id="assumeConstantSalary"
          :checked="local.constantSalary"
          @change="update('constantSalary', $event.target.checked)"
        />
        <label class="form-check-label small" for="assumeConstantSalary">
          Assume constant salary
        </label>
      </div>

      <div class="form-check mb-2">
        <input
          class="form-check-input"
          type="checkbox"
          id="includeProjected13th"
          :checked="local.includeProjected13th"
          @change="update('includeProjected13th', $event.target.checked)"
        />
        <label class="form-check-label small" for="includeProjected13th">
          Include projected 13th month
        </label>
      </div>

      <div class="form-check">
        <input
          class="form-check-input"
          type="checkbox"
          id="capDeMinimis"
          :checked="local.capDeMinimis"
          @change="update('capDeMinimis', $event.target.checked)"
        />
        <label class="form-check-label small" for="capDeMinimis">
          Cap de minimis automatically
        </label>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ForecastAssumptions",
  props: {
    assumptions: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      local: { ...this.assumptions },
    };
  },

  watch: {
    assumptions: {
      deep: true,
      handler(val) {
        this.local = { ...val };
      },
    },
  },

  methods: {
    update(key, value) {
      const next = { ...this.local, [key]: value };
      this.local = next;
      this.$emit("update:assumptions", next);
    },
  },
};
</script>
