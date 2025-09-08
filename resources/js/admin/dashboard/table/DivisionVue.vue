<template>
  <div class="cardiness">
    <h5 class="text-uppercase fw-bold pb-2 mb-3 border-bottom">Division One</h5>

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
        <!-- <component :is="tab.component" /> -->
        <ViewLogsVue/>
      </div>
    </div>


  </div>
  
</template>

<script>
import ViewLogsVue from './ViewLogsVue.vue';
export default {
  name: "division",
  components: { ViewLogsVue },
  data() {
   return {
      activeTab: "unit-one", // Default selected tab
      tabs: [
        { name: "unit-one", label: "UNIT 1" },
        { name: "unit-two", label: "UNIT 2" },
        { name: "unit-three", label: "UNIT 3" },
      ]
    };
  },
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';
.cardiness {
    border: 2px solid rgba($color: $primary, $alpha: 1.0);
    border-radius: 12px;
    padding: 24px;
}
.nav-tabs .nav-link.active {
  font-weight: 600;
  background-color: $secondary;
  color: $primary;
}
</style>
