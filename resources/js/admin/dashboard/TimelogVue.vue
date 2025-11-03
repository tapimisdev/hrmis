<template>
  <div class="dashboard">

    <!-- Row: Divisions -->
    <div class="row g-3 p-3 pt-1">
      <div class="col-md-6">
          <DivisionVue/>
      </div>
      <div class="col-md-6">
          <DivisionVue/>
      </div>
      <div class="col-md-6">
          <DivisionVue/>
      </div>
      <div class="col-md-6">
          <DivisionVue/>
      </div>
    </div>

    <!-- Row: Timelogs Table -->
    <div class="g-3 p-3 pt-1">
      <TimelogsTableVue/>
    </div>

  </div>
</template>

<script>
import TimelogsTableVue from './table/TimelogsTableVue.vue';
import DivisionVue from './table/DivisionVue.vue';
export default {
  name: "timelog",
  components: { TimelogsTableVue, DivisionVue },
  data() {
    return {
      
    };
  },
};
</script>

<style lang="scss" scoped>
@import './../../../sass/variables';

.dashboard {
  background: rgba($color: var(--bs-primary), $alpha: 0.1);
  padding-bottom: 36px;
  min-height: 100vh;
}
.chart-card {
    background: var(--bs-secondary-bg);
    height: 100%;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    &:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
}
</style>
