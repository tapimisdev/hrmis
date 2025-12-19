<template>
  <div class="chart-card position-relative shadow-sm border border-body-secondary">
    <div v-if="loading" class="position-absolute h-100 w-100 d-flex justify-content-center align-items-center bg-body-secondary z-3" style="left: 0;">
      <div class="spinner-border text-body  text-opacity-25" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div class="icon-wrapper">
      <i :class="icon"></i>
    </div>
    <div class="chart-info">
      <h1 class="chart-value">{{ value }}</h1>
      <p class="chart-name">{{ name }}</p>
      <p v-if="subValue" class="chart-sub">{{ subValue }}</p>
      <p v-if="trend" class="chart-trend" :class="{ up: trend.startsWith('↑'), down: trend.startsWith('↓') }">
        {{ trend }}
      </p>
    </div>
  </div>
</template>

<script>
export default {
  name: "DashboardCard",
  props: {
    name: { type: String, required: true },
    value: { type: [Number, String], required: true },
    subValue: { type: String, default: null },
    trend: { type: String, default: null },
    icon: { type: String, required: true },
    loading: {
      type: Boolean,
      default: true
    }
  }
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.chart-card {
  display: flex;
  align-items: center;
  gap: 16px;
  min-height: 140px;
  overflow: hidden;
  text-overflow: ellipsis;

  .icon-wrapper {
    background: rgba(var(--bs-primary), 0.08);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    i {
      font-size: 28px;
      color: var(--bs-primary);
    }
  }

  .chart-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-width: 0;

    .chart-value {
      font-size: 28px;
      font-weight: 800;
      color: $secondary;
      margin: 0;
      line-height: 1.2;
    }

    .chart-name {
      font-size: clamp(14px, 1.2vw, 14px);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #6c757d;
      margin: 0;
      white-space: nowrap;
    }

    .chart-sub {
      font-size: 13px;
      color: #868e96;
      margin: 4px 0 0;
    }

    .chart-trend {
      font-size: 13px;
      font-weight: 600;
      margin: 2px 0 0;

      &.up { color: #198754; }
      &.down { color: #dc3545; }
    }
  }
}
</style>