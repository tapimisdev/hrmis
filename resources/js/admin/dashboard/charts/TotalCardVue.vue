<template>
  <div class="chart-card">
    <div class="chart-header">
      <div class="icon-wrapper">
        <i :class="icon"></i>
      </div>
      <div class="chart-info">
        <h1 class="chart-value">{{ value }}</h1>
        <p class="chart-name">{{ name }}</p>

        <!-- Optional extra insights -->
        <p v-if="subValue" class="chart-sub">{{ subValue }}</p>
        <p v-if="trend" class="chart-trend" :class="{ up: trend.startsWith('↑'), down: trend.startsWith('↓') }">
          {{ trend }}
        </p>
      </div>
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
    icon: { type: String, required: true }
  }
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.chart-card {
  display: flex;
  align-items: start;
  justify-content: center;
  min-height: 140px;
  overflow: hidden;
  text-overflow: ellipsis;

  .chart-header {
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;

    .icon-wrapper {
      background: rgba($primary, 0.08);
      border-radius: 12px;
      padding: 12px;
      display: flex;
      align-items: center;
      justify-content: center;

      i {
        font-size: 28px;
        color: $primary;
      }
    }

    .chart-info {
      display: flex;
      flex-direction: column;
      align-items: flex-start;

      .chart-value {
        font-size: 28px;
        font-weight: 800;
        color: $secondary;
        margin: 0;
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
        margin-top: 4px;
      }

      .chart-trend {
        font-size: 13px;
        font-weight: 600;
        margin-top: 2px;

        &.up {
          color: #198754; // Bootstrap green
        }
        &.down {
          color: #dc3545; // Bootstrap red
        }
      }
    }
  }
}
</style>
