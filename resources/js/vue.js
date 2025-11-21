
import { createApp } from 'vue';
// employee
import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';
import DashboardIndex from './employee/dashboard/DashboardIndex.vue'
import HeaderVue from './employee/components/Header.vue';
import EmployeeTimelog from './employee/check-in-out/EmployeeTimelog.vue';
import ProfileIndex from './employee/profile/ProfileIndex.vue';
import Announcements from './employee/announcements/Announcements.vue';
import Show from './employee/announcements/Show.vue';

// Admin
import DashboardVue from "./admin/dashboard/DashboardVue.vue";
import DtrViewVue from './admin/timekeeping/DtrViewVue.vue';
import HrisIndex from './admin/hris/HrisIndex.vue';
import ImportEmployeeVue from './admin/hris/ImportEmployeeVue.vue';
import UploadTimelogVue from './admin/timekeeping/UploadTimelogVue.vue';
import PayrollStepper from './admin/payroll/create/PayrollStepper.vue';
import ShowPayroll from './admin/payroll/show/ShowPayroll.vue';
import TaxIndex from './admin/tax/TaxIndex.vue';
import TaxTable from './admin/tax/TaxTable.vue';
import TabModule from './admin/modules/TabModule.vue';

// payroll
import PayrollIndex from './admin/payroll/IndexVue.vue';

const authApp = createApp({
  components: {
    CheckInOutVue,
    DashboardIndex,
    HeaderVue,
    EmployeeTimelog,
    ProfileIndex,
    Announcements,
    Show,

    DashboardVue,
    DtrViewVue,
    HrisIndex,
    ImportEmployeeVue,
    UploadTimelogVue,
    PayrollIndex,
    PayrollStepper,
    ShowPayroll,
    TaxIndex,
    TaxTable, 
    TabModule
  },
});

authApp.mount('#app');