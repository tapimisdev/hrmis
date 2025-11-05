<template>
  <div>
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs">
      <li class="nav-item" v-for="tab in tabs" :key="tab.name">
        <button
          class="nav-link"
          :class="{ active: activeTab === tab.name }"
          @click="activeTab = tab.name"
        >
          {{ tab.label }}
        </button>
      </li>
    </ul>
    <!-- Tabs Content -->
    <div class="tab-content mt-3">
      <div
        v-for="tab in tabs"
        :key="tab.name"
        class="tab-pane fade"
        :class="{ 'show active': activeTab === tab.name }"
      >
        <component :is="tab.component" />
      </div>
    </div>
  </div>
</template>

<script>
import HrisVue from './HrisVue.vue';
import TimelogVue from './TimelogVue.vue';

export default {
  name: "Dashboard",
  components: { HrisVue, TimelogVue },
  data() {
    return {
      activeTab: "hris",
      tabs: [
        { name: "hris", label: "HRIS", component: "HrisVue" },
        { name: "timelog", label: "Timelogs", component: "TimelogVue" },
      ]
    };
  }
};
</script>

<style lang="scss" scoped>
@import './../../../sass/variables';
.nav-tabs .nav-link {
  border: 2px solid rgba($color: var(--bs-primary), $alpha: 0.2);
}
.nav-tabs .nav-link.active {
  font-weight: 600;
  background-color: var(--bs-primary);
  color: $light;
}
</style>
