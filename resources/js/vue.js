
import { createApp } from 'vue';

import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';
import DashboardVue from "./admin/dashboard/DashboardVue.vue";
import DtrViewVue from './admin/timekeeping/DtrViewVue.vue';
import ImportEmployeeVue from './admin/hris/ImportEmployeeVue.vue';
import UploadTimelogVue from './admin/timekeeping/UploadTimelogVue.vue';

// payroll
import PayrollIndex from './admin/payroll/IndexVue.vue';

const authApp = createApp({
  components: {
    CheckInOutVue,
    DashboardVue,
    DtrViewVue,
    ImportEmployeeVue,
    UploadTimelogVue,
    PayrollIndex
  },
});

authApp.mount('#app');