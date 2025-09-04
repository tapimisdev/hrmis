<template>
  <div class="chart-card cardiness">
    <div class="chart-header">
      <i :class="icon"></i>
      <h5>{{ name }}</h5>
    </div>
    <div class="chart-body">
      <h1>{{ active }}</h1>
    </div>
  </div>
</template>

<script>
export default {
  name: "DashboardCard",
  props: {
    name: {
      type: String,
      required: true
    },
    active: {
      type: [Number, String],
      required: true
    },
    total: {
      type: [Number, String],
      default: null
    },
    icon: {
      type: String,
      required: true
    }
  }
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.chart-card {
  width: 100%;
  position: relative;
  .chart-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
    i {
      font-size: 38px;
      color: $primary;
      -webkit-text-stroke: 1px $primary; 
      -webkit-text-fill-color: $secondary;
    }
    h5 {
      text-transform: uppercase;
    }
  }
  .chart-body {
    position: absolute;
    top: 20px;
    right: 20px;
    h1 { 
      font-weight: 900;
      color: $secondary;
      -webkit-text-stroke: 1px $primary; 
      -webkit-text-fill-color: $secondary;
    }
  }
}
</style>
