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
import ChangePassword from "./employee/profile/ChangePassword.vue";
import BirthdayComponent from "./birthday/BirthdayComponent.vue";
import PushNotification from "./employee/profile/PushNotification.vue";
import ViewDtr from "./employee/check-in-out/ViewDtr.vue";
import MessagesPage from "./employee/messages/MessagesPage.vue";

// Admin
import AdminHeader from "./admin/components/Header.vue";
import DashboardVue from "./admin/dashboard/DashboardVue.vue";
import DtrViewVue from "./admin/timekeeping/DtrViewVue.vue";
import HrisIndex from "./admin/hris/HrisIndex.vue";
import ImportCredits from "./admin/credits/Import.vue";
import ImportEmployeeVue from "./admin/hris/employee/ImportEmployeeVue.vue";
import UploadTimelogVue from "./admin/timekeeping/UploadTimelogVue.vue";
import PatchNotesPage from "./admin/pages/patch-notes/PatchNotesPage.vue";

import WebtimeIndex from "./admin/web-time-access/WebtimeIndex.vue";

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
import LongevityPayIndex from "./admin/payroll/longevity-pay/Index.vue";
import LongevityPayStepper from "./admin/payroll/longevity-pay/create/PayrollStepper.vue";
import LongevityPayView from "./admin/payroll/longevity-pay/show/ShowPayroll.vue";
import GovernmentBonusIndex from "./admin/payroll/government-bonuses/Index.vue";
import GovernmentBonusStepper from "./admin/payroll/government-bonuses/create/PayrollStepper.vue";
import GovernmentBonusView from "./admin/payroll/government-bonuses/show/ShowPayroll.vue";
import GovernmentBonusTypeIndex from "./admin/payroll/government-bonus-types/Index.vue";

const authApp = createApp({
    components: {
        AdminHeader,
        DashboardIndex,
        IndexVue,
        HeaderVue,
        ProfileIndex,
        Announcements,
        Show,
        PayslipIndex,
        BirthdayComponent,
        ChangePassword,
        PushNotification,
        WebtimeIndex,
        ViewDtr,

        DashboardVue,
        DtrViewVue,
        HrisIndex,
        ImportCredits,
        ImportEmployeeVue,
        UploadTimelogVue,
        PatchNotesPage,

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

        LongevityPayIndex,
        LongevityPayStepper,
        LongevityPayView,

        GovernmentBonusIndex,
        GovernmentBonusStepper,
        GovernmentBonusView,
        GovernmentBonusTypeIndex,
        MessagesPage,
    },

    data() {
        return {
            showChangePasswordModal: false,
            presenceOnlineListener: null,
            presenceOfflineListener: null,
            presencePageHideListener: null,
            presenceBeforeUnloadListener: null,
        };
    },
    mounted() {
        const token = localStorage.getItem("auth_token");

        if (!token) return;

        if (window.Echo?.connector?.options?.auth) {
            window.Echo.connector.options.auth.headers = {
                ...(window.Echo.connector.options.auth.headers || {}),
                Authorization: `Bearer ${token}`,
            };
        }

        const syncOnlineUsersState = (users = []) => {
            const onlineUserIds = users.map((user) => Number(user.id));

            window.__onlineUsersPresence = {
                onlineUserIds,
                updatedAt: Date.now(),
            };

            window.dispatchEvent(
                new CustomEvent("online-users:updated", {
                    detail: {
                        onlineUserIds,
                    },
                }),
            );
        };

        window.__onlineUsersPresence = window.__onlineUsersPresence || {
            onlineUserIds: [],
            updatedAt: null,
        };

        window.Echo.join("online-users")
            .here((users) => {
                syncOnlineUsersState(users);
            })
            .joining((user) => {
                const currentIds = window.__onlineUsersPresence?.onlineUserIds || [];
                const userId = Number(user.id);

                if (!currentIds.includes(userId)) {
                    syncOnlineUsersState([
                        ...currentIds.map((id) => ({ id })),
                        { id: userId },
                    ]);
                    return;
                }

                syncOnlineUsersState(currentIds.map((id) => ({ id })));
            })
            .leaving((user) => {
                const userId = Number(user.id);
                const currentIds = window.__onlineUsersPresence?.onlineUserIds || [];
                const nextIds = currentIds.filter((id) => id !== userId);

                syncOnlineUsersState(nextIds.map((id) => ({ id })));
            });

        this.presenceOnlineListener = () => {
            this.announcePresence("online");
        };

        this.presenceOfflineListener = () => {
            this.announcePresence("offline", true);
        };

        this.presencePageHideListener = () => {
            this.announcePresence("offline", true);
        };

        this.presenceBeforeUnloadListener = () => {
            this.announcePresence("offline", true);
        };

        window.addEventListener("online", this.presenceOnlineListener);
        window.addEventListener("offline", this.presenceOfflineListener);
        window.addEventListener("pagehide", this.presencePageHideListener);
        window.addEventListener("beforeunload", this.presenceBeforeUnloadListener);
        this.announcePresence("online");

        axios
            .get("/api/force-update-password", {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            })
            .then((response) => {
                if (response.data.isForcedUpdate) {
                    this.showChangePasswordModal = true;

                    this.$nextTick(() => {
                        $("#forceChangePasswordModal").modal({
                            backdrop: "static",
                            keyboard: false,
                        });

                        $("#forceChangePasswordModal").modal("show");
                    });
                }
            })
            .catch((error) => {
            });
    },
    methods: {
        async announcePresence(status = "online", useKeepAlive = false) {
            try {
                const token = localStorage.getItem("auth_token");
                const headers = {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                };

                if (token) {
                    headers.Authorization = `Bearer ${token}`;
                }

                await fetch("/api/presence", {
                    method: "POST",
                    headers,
                    credentials: "same-origin",
                    keepalive: useKeepAlive,
                    body: JSON.stringify({ status }),
                });
            } catch (error) {
                console.error("Failed to announce presence:", error);
            }
        },
        handlePasswordChanged() {
            $("#forceChangePasswordModal").modal("hide");
        },
    },
    beforeUnmount() {
        if (this.presenceOnlineListener) {
            window.removeEventListener("online", this.presenceOnlineListener);
            this.presenceOnlineListener = null;
        }

        if (this.presenceOfflineListener) {
            window.removeEventListener("offline", this.presenceOfflineListener);
            this.presenceOfflineListener = null;
        }

        if (this.presencePageHideListener) {
            window.removeEventListener("pagehide", this.presencePageHideListener);
            this.presencePageHideListener = null;
        }

        if (this.presenceBeforeUnloadListener) {
            window.removeEventListener("beforeunload", this.presenceBeforeUnloadListener);
            this.presenceBeforeUnloadListener = null;
        }
    },
});

authApp.mount("#app");
