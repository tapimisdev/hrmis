<template>
  <div class="dashboard">
    <!-- Cards -->
    <div class="row g-3 p-3 pt-0">
      <div v-for="card in cards" :key="card.index" class="col-12 col-sm-6 col-md-4 col-lg-4">
        <TotalCardVue 
          :name="card.name"
          :active="card.active"
          :icon="card.icon"
        />
      </div>
    </div>

    <div class="row g-3 p-3 pt-1">
      <div class="col-md-6">
        <ListTableVue
          :people="[
            { name: 'John Doe', birthday: '1990-05-10', image: 'https://imgv3.fotor.com/images/gallery/cartoon-character-generated-by-Fotor-ai-art-creator.jpg' },
            { name: 'Jane Smith', birthday: '1992-08-15', image: 'https://imgv3.fotor.com/images/gallery/cartoon-character-generated-by-Fotor-ai-art-creator.jpg' },
            { name: 'Alice Brown', birthday: '1995-12-03', image: 'https://imgv3.fotor.com/images/gallery/cartoon-character-generated-by-Fotor-ai-art-creator.jpg' }
          ]"
        />
      </div>
      <div class="col-md-6">
        <BarVue/>
      </div>
    </div>

    <div class="row g-3 p-3 pt-1">
      <div class="col-md-5">
          <DonutVue
            :labels="['January', 'February', 'March', 'April']"
            :dataset="[40, 20, 12, 39]"
            title="Sales Overview"
          />
      </div>
      <div class="col-md-7">
         <LineVue
            :labels="['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']"
            :hires="[4, 7, 10, 12, 6, 9, 8]"
            :resignations="[1, 3, 2, 4, 1, 5, 2]"
          />
      </div>
    </div>
  </div>
</template>

<script>
import BarVue from './charts/BarVue.vue';
import DonutVue from './charts/DonutVue.vue';
import LineVue from './charts/LineVue.vue';
import TotalCardVue from './charts/TotalCardVue.vue';
import ListTableVue from './charts/ListTableVue.vue';
export default {
  name: "Dashboard",
  components: { BarVue, TotalCardVue, ListTableVue, DonutVue, LineVue },
  data() {
    return {
      cards: [
        {
          name: 'Total Employees',
          active: 120,
          total: 120,
          icon: 'fa-solid fa-users text-blue-500',
        },
        {
          name: 'Active Employees',
          active: 110,
          total: 120,
          icon: 'fa-solid fa-user-check text-green-500',
        },
        {
          name: 'Inactive Employees',
          active: 10,
          total: 120,
          icon: 'fa-solid fa-user-xmark text-red-500',
        },
        {
          name: 'Departments',
          active: 6,
          total: 6,
          icon: 'fa-solid fa-building text-purple-500',
        },
        {
          name: 'Positions',
          active: 15,
          total: 15,
          icon: 'fa-solid fa-briefcase text-yellow-500',
        },
        {
          name: 'Employment Types',
          active: 4,
          total: 4,
          icon: 'fa-solid fa-id-card text-orange-500',
        },
      ]
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
