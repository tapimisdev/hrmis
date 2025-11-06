<template>
  <div class="dashboard container-fluid">
    <!-- Cards Section -->
    <div class="row g-3 p-3 pt-0">
      <div v-for="(card, i) in cards" :key="i" class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
        <TotalCardVue v-bind="card" />
      </div>
    </div>

    <!-- Row: Birthdays + Attendance -->
    <div class="row g-3 p-3 pt-1">
      <div class="col-md-6">
        <div class="chart-card shadow-sm border border-body-secondary">
          <ListTableVue title="🎂 Upcoming Birthdays" :people="birthdays" />
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card shadow-sm border border-body-secondary">
          <BarVue />
        </div>
      </div>
    </div>

    <!-- Row: Workforce Charts -->
    <div class="row g-3 p-3 pt-1">
      <div class="col-md-5">
        <div class="chart-card shadow-sm border border-body-secondary">
          <DonutVue
            :labels="['Regular', 'Contractual', 'Intern']"
            :dataset="[85, 15, 8]"
            title="Employment Type Distribution"
          />
        </div>
      </div>
      <div class="col-md-7">
        <div class="chart-card shadow-sm border border-body-secondary">
          <LineVue
            :labels="['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']"
            :hires="[4, 7, 10, 12, 6, 9, 8]"
            :resignations="[1, 3, 2, 4, 1, 5, 2]"
            title="📈 Hiring vs Resignations"
          />
        </div>
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
  data: () => ({
    cards: [
      { name: 'Total Employees', value: 120, subValue: '↑ 5 this month', icon: 'fa-solid fa-users text-blue-500' },
      { name: 'Active Employees', value: 110, subValue: '91.6% workforce', icon: 'fa-solid fa-user-check text-green-500' },
      { name: 'On Leave Today', value: 8, subValue: '6 Vacation • 2 Sick', icon: 'fa-solid fa-plane-departure text-orange-500' },
      { name: 'Upcoming Birthdays', value: 3, subValue: 'This Week 🎂', icon: 'fa-solid fa-cake-candles text-pink-500' },
      { name: 'Attrition Rate', value: '2.5%', trend: '↓ 1% vs last month', icon: 'fa-solid fa-chart-line text-red-500' },
      { name: 'New Hires', value: 4, subValue: 'This Month', icon: 'fa-solid fa-user-plus text-green-600' },
      { name: 'Average Tenure', value: '3.2 yrs', subValue: 'Company-wide', icon: 'fa-solid fa-hourglass-half text-indigo-500' },
      { name: 'Training Completion', value: '78%', subValue: 'Ongoing courses', icon: 'fa-solid fa-graduation-cap text-teal-500' },
    ],
    birthdays: [
      { name: 'John Doe', birthday: '1990-05-10', image: 'https://imgv3.fotor.com/images/gallery/cartoon-character-generated-by-Fotor-ai-art-creator.jpg' },
      { name: 'Jane Smith', birthday: '1992-08-15', image: 'https://imgv3.fotor.com/images/gallery/cartoon-character-generated-by-Fotor-ai-art-creator.jpg' },
      { name: 'Alice Brown', birthday: '1995-12-03', image: 'https://imgv3.fotor.com/images/gallery/cartoon-character-generated-by-Fotor-ai-art-creator.jpg' }
    ]
  })
};
</script>

<style lang="scss" scoped>
@import './../../../sass/variables';

.dashboard {
  padding-bottom: 36px;
  min-height: 100vh;
}

.chart-card {
  background: var(--bs-secondary-bg);
  height: 100%;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  }
}
</style>