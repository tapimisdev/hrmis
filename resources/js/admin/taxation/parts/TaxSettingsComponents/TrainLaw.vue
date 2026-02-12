<template>
  <div class="card shadow-sm h-100">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-table text-muted"></i>
        <span class="fw-semibold">TRAIN Tax Table</span>
      </div>

      <select
        class="form-select form-select-sm"
        style="max-width: 160px"
        :value="selectedYear"
        @change="$emit('update:selectedYear', toNumber($event.target.value))"
      >
        <option v-for="y in years" :key="y.value" :value="y.value">
          {{ y.label }}
        </option>
      </select>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table">
            <tr>
              <th class="ps-3">Income From</th>
              <th>Income To</th>
              <th>Base Tax</th>
              <th class="text-end pe-3">Tax</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, idx) in rows" :key="idx">
              <td class="ps-3">{{ r.income_from }}</td>
              <td>{{ r.income_to }}</td>
              <td>{{ r.base_tax }}</td>
              <td class="text-end pe-3">{{ r.rate }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer text-end">
      <button class="fb-btn fb-primary" type="button" @click="$emit('import')">
        <i class="fa-solid fa-upload me-1"></i>
        Import TRAIN Table
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: "TrainLaw",
  props: {
    years: { type: Array, default: () => [] },
    selectedYear: { type: Number, required: true },
    rows: { type: Array, default: () => [] },
  },
  methods: {
    toNumber(v) {
      const n = Number(v);
      return Number.isFinite(n) ? n : this.selectedYear;
    },
  },
};
</script>
