import { createApp } from "vue";
import axios from "axios";

// employee
import IndexVue from "./employee/check-in-out/IndexVue.vue";
import DashboardIndex from "./employee/dashboard/DashboardIndex.vue";
import HeaderVue from "./employee/components/Header.vue";
import ProfileIndex from "./employee/profile/ProfileIndex.vue";
import Announcements from "./employee/announcements/Announcements.vue";
import PayslipIndex from "./employee/payslip/PayslipIndex.vue";
import Show from "./employee/announcements/Show.vue";
import ChangePassword from "./employee/profile/ChangePassword.vue"; // <-- import

// Admin
import DashboardVue from "./admin/dashboard/DashboardVue.vue";
import DtrViewVue from "./admin/timekeeping/DtrViewVue.vue";
import HrisIndex from "./admin/hris/HrisIndex.vue";
import ImportEmployeeVue from "./admin/hris/ImportEmployeeVue.vue";
import UploadTimelogVue from "./admin/timekeeping/UploadTimelogVue.vue";

import TabModule from "./admin/modules/TabModule.vue";
import TaxSettings from "./admin/settings/TaxIndex.vue";

// Payroll Settings
import PayrollComponentIndex from "./admin/payroll-component-settings/Index.vue";
import PayrollComponentForm from "./admin/payroll-component-settings/Form.vue";
import PayrollEmployeeComponentIndex from "./admin/payroll-component/Index.vue";
import PayrollEmployeeComponentForm from "./admin/payroll-component/Form.vue";
import PayrollSettings from "./admin/payroll-settings/Index.vue";

// Salary Pay Payroll
import SalaryPayIndex from "./admin/payroll/salary-pay/Index.vue";
import PayrollStepper from "./admin/payroll/salary-pay/create/PayrollStepper.vue";
import ShowPayroll from "./admin/payroll/salary-pay/show/ShowPayroll.vue";

// Hazard Pay Payroll
import HazardPayIndex from "./admin/payroll/hazard-pay/Index.vue";
import HazardPayStepper from "./admin/payroll/hazard-pay/create/PayrollStepper.vue";
import HazardPayView from "./admin/payroll/hazard-pay/show/ShowPayroll.vue";

// SLA Pay Payroll
import SlaPayIndex from "./admin/payroll/sla-pay/Index.vue";
import SlaPayStepper from "./admin/payroll/sla-pay/create/PayrollStepper.vue";
import SlaPayView from "./admin/payroll/sla-pay/show/ShowPayroll.vue";

// PERA RATA Payroll
import PeraRataIndex from "./admin/payroll/pera-rata/Index.vue";
import PeraRataStepper from "./admin/payroll/pera-rata/create/PayrollStepper.vue";
import PeraRataView from "./admin/payroll/pera-rata/show/ShowPayroll.vue";

import BirthdayComponent from "./birthday/BirthdayComponent.vue";

const authApp = createApp({
    components: {
        DashboardIndex,
        IndexVue,
        HeaderVue,
        ProfileIndex,
        Announcements,
        Show,
        PayslipIndex,
        BirthdayComponent,
        ChangePassword, // <-- register component

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
        PeraRataView,
    },

    data() {
        return {
            showChangePasswordModal: false, 
        };
    },

    mounted() {
        const token = localStorage.getItem('auth_token');

        if (token) {
            axios.get('/api/force-update-password', {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(response => {
                const res = response.data;
                // response.data contains the API payload
                if (res.isForcedUpdate) {
                    this.showChangePasswordModal = true;
                   
                    setTimeout(() => {
                        const modalEl = document.getElementById('forceChangePasswordModal');
                        if (!modalEl) return;

                        // Show the modal
                        modalEl.classList.add('show');
                        modalEl.style.display = 'block';
                        modalEl.removeAttribute('aria-hidden');
                        modalEl.setAttribute('aria-modal', 'true');
                        modalEl.setAttribute('role', 'dialog');

                        // Add backdrop
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade';
                        document.body.appendChild(backdrop);

                        // Trigger fade-in effect
                        requestAnimationFrame(() => {
                            backdrop.classList.add('show');
                        });

                        // Optional: prevent closing by clicking outside
                        modalEl.addEventListener('click', (e) => {
                            if (e.target === modalEl) e.stopPropagation();
                        });

                        // Optional: close button
                        document.getElementById('closeBtn').addEventListener('click', () => {
                            modalEl.classList.remove('show');
                            modalEl.style.display = 'none';
                            backdrop.classList.remove('show');
                            setTimeout(() => backdrop.remove(), 300); // remove after fade-out
                        });
                    }, 100);

                }
            })
            .catch(error => {
                console.error('API error:', error);
            });
        }
    }
});

authApp.mount("#app");
