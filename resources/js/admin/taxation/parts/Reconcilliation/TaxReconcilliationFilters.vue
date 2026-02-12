<template>
  <div>
    <div class="d-flex justify-content-between border-bottom mb-3">
      <!-- PERIOD TABS -->
      <div class="fb-tabs mb-3">
        <button
          v-for="p in periods"
          :key="p.key"
          class="fb-tab"
          :class="{ active: p.key === activePeriod }"
          type="button"
          @click="$emit('change-period', p.key)"
        >
          <template v-if="p.sub">
            {{ p.label }} <span>{{ p.sub }}</span>
          </template>
          <template v-else>
            {{ p.label }}
          </template>
        </button>
      </div>

      <div class="action-buttons">
        <button class="fb-btn fb-primary" type="button" @click="$emit('pull-reconcile')">
          <i class="fa-solid fa-arrows-rotate me-1"></i>
          Pull From Payroll & Reconcile
        </button>
      </div>
    </div>

    <!-- FILTER BAR -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      <!-- Search -->
      <div class="input-group input-group-sm" style="max-width: 320px">
        <span class="input-group-text">
          <i class="fa-solid fa-magnifying-glass"></i>
        </span>

        <input
          :value="search"
          @input="$emit('update:search', $event.target.value)"
          type="text"
          class="form-control"
          placeholder="Search employee no..."
        />

        <button
          class="btn btn-outline-secondary"
          type="button"
          @click="$emit('clear')"
          :disabled="!hasActiveFilters"
          title="Clear"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Division Filter -->
      <select
        class="form-select form-select-sm w-auto"
        :value="selectedDivision"
        @change="$emit('update:selectedDivision', $event.target.value)"
      >
        <option value="">All Divisions</option>
        <option v-for="d in divisions" :key="d" :value="d">
          {{ d }}
        </option>
      </select>

      <!-- Unit Filter -->
      <select
        class="form-select form-select-sm w-auto"
        :value="selectedUnit"
        @change="$emit('update:selectedUnit', $event.target.value)"
        :disabled="units.length === 0"
      >
        <option value="">
          {{ units.length ? "All Units" : "No units" }}
        </option>
        <option v-for="u in units" :key="u" :value="u">
          {{ u }}
        </option>
      </select>

      <!-- Result count -->
      <div class="ms-auto small text-muted">
        Showing <b>{{ filteredCount }}</b> of {{ totalCount }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "TaxForecastFilters",
  props: {
    // tabs
    periods: { type: Array, required: true },
    activePeriod: { type: String, required: true },

    // filters
    search: { type: String, default: "" },
    selectedDivision: { type: String, default: "" },
    selectedUnit: { type: String, default: "" },

    // dropdown data
    divisions: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },

    // counters
    filteredCount: { type: Number, default: 0 },
    totalCount: { type: Number, default: 0 },

    // UI flags
    hasActiveFilters: { type: Boolean, default: false },
  },
};
</script>
