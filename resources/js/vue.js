
import { createApp } from 'vue';
// employee
import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';
import DashboardIndex from './employee/dashboard/DashboardIndex.vue'
import HeaderVue from './employee/components/Header.vue';
import EmployeeTimelog from './employee/check-in-out/EmployeeTimelog.vue';
import ProfileIndex from './employee/profile/ProfileIndex.vue';

// Admin
import DashboardVue from "./admin/dashboard/DashboardVue.vue";
import DtrViewVue from './admin/timekeeping/DtrViewVue.vue';
import ImportEmployeeVue from './admin/hris/ImportEmployeeVue.vue';
import UploadTimelogVue from './admin/timekeeping/UploadTimelogVue.vue';
import PayrollStepper from './admin/payroll/create/PayrollStepper.vue';
import ShowPayroll from './admin/payroll/show/ShowPayroll.vue';

// payroll
import PayrollIndex from './admin/payroll/IndexVue.vue';

const authApp = createApp({
  components: {
    CheckInOutVue,
    DashboardIndex,
    HeaderVue,
    EmployeeTimelog,
    ProfileIndex,

    DashboardVue,
    DtrViewVue,
    ImportEmployeeVue,
    UploadTimelogVue,
    PayrollIndex,
    PayrollStepper,
    ShowPayroll
  },
});

console.log('Vue is Working');
authApp.mount('#app');