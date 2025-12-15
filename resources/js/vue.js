
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

import TabModule from './admin/modules/TabModule.vue';
import TaxSettings from './admin/settings/TaxIndex.vue';

// Payroll Settings
import PayrollComponentIndex from './admin/payroll-component-settings/Index.vue';
import PayrollComponentForm from './admin/payroll-component-settings/Form.vue';
import PayrollEmployeeComponentIndex from './admin/payroll-component/Index.vue';
import PayrollEmployeeComponentForm from './admin/payroll-component/Form.vue';
import PayrollSettings from './admin/payroll-settings/Index.vue';

// Salary Pay Payroll
import SalaryPayIndex from './admin/payroll/salary-pay/Index.vue';
import PayrollStepper from './admin/payroll/salary-pay/create/PayrollStepper.vue';
import ShowPayroll from './admin/payroll/salary-pay/show/ShowPayroll.vue';

// Hazard Pay Payroll
import HazardPayIndex from './admin/payroll/hazard-pay/Index.vue';
import HazardPayStepper from './admin/payroll/hazard-pay/create/PayrollStepper.vue';
import HazardPayView from './admin/payroll/hazard-pay/show/ShowPayroll.vue';

// SLA Pay Payroll
import SlaPayIndex from './admin/payroll/sla-pay/Index.vue';
import SlaPayStepper from './admin/payroll/sla-pay/create/PayrollStepper.vue';
import SlaPayView from './admin/payroll/sla-pay/show/ShowPayroll.vue';

// PERA RATA Payroll
import PeraRataIndex from './admin/payroll/pera-rata/Index.vue';
import PeraRataStepper from './admin/payroll/pera-rata/create/PayrollStepper.vue';
import PeraRataView from './admin/payroll/pera-rata/show/ShowPayroll.vue';

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
    
    PayrollEmployeeComponentIndex,
    PayrollEmployeeComponentForm, 
    TabModule,
    TaxSettings,
    PayrollComponentIndex,
    PayrollComponentForm,
    PayrollSettings,
    
    SalaryPayIndex,
    PayrollStepper,
    ShowPayroll,

    HazardPayIndex,
    HazardPayStepper,
    HazardPayView,

    SlaPayIndex,
    SlaPayStepper,
    SlaPayView,
    
    PeraRataIndex,
    PeraRataStepper,
    PeraRataView
  },
});

authApp.mount('#app');