<template>
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
  <div class="g-3 p-3 pt-1">
    <TimelogsTableVue/>
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
.cardiness {
    border: 2px solid rgba($color: $primary, $alpha: 1.0);
    border-radius: 12px;
    padding: 24px;
}
</style>
