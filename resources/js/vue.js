
import { createApp } from 'vue';

import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';
import DashboardVue from "./admin/dashboard/DashboardVue.vue";
import DtrViewVue from './admin/timekeeping/DtrViewVue.vue';
import ImportEmployeeVue from './admin/hris/ImportEmployeeVue.vue';

const authApp = createApp({
  components: {
    CheckInOutVue,
    DashboardVue,
    DtrViewVue,
    ImportEmployeeVue  
  },
});

authApp.mount('#app');