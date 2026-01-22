<template>
    <div v-if="showWorkedHours" class="shot-clock-wrapper">
        <div class="shot-clock">
            <h4 :class="{ '': !todayTimeIn }">
                {{ todayTimeIn ? workedHours : "NO TIME IN YET" }}
            </h4>
        </div>
    </div>

    <div class="dropdown position-relative">
        <a
            class="text-decoration-none position-relative d-inline-block"
            href="#"
            id="widgetDropdown"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            style="cursor: pointer"
        >
            <i
                class="fa-solid fa-gear text-light"
                style="font-size: 1.5rem"
            ></i>
        </a>

        <ul
            class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0"
            aria-labelledby="widgetDropdown"
            style="
                min-width: 300px;
                max-width: 380px;
                border-radius: 8px;
                border: 1px solid rgba(0, 0, 0, 0.2);
            "
        >
            <!-- Header -->
            <li class="px-4 py-3 border-bottom bg-body" style="border-top-left-radius: 30px; border-top-right-radius: 30px;">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-uppercase">Widgets</h6>
                </div>
            </li>

            <div class="px-4 py-3">
                <!-- Dark Mode Toggle -->
                <li class="pb-2">
                    <div
                        class="form-check form-switch d-flex align-items-center gap-2"
                    >
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="darkModeSwitch"
                            v-model="isDarkMode"
                            @change="handleThemeToggle"
                            style="
                                cursor: pointer;
                                transform: scale(1.2);
                                margin-right: 0.5rem;
                                margin-bottom: 2px;
                            "
                        />
                        <label
                            class="form-check-label text-uppercase fw-medium"
                            for="darkModeSwitch"
                            style="font-size: 12px; cursor: pointer"
                        >
                            Dark Mode
                        </label>
                    </div>
                </li>

                <!-- Timelog Discrepancy Toggle -->
                <li class="pb-2">
                    <div
                        class="form-check form-switch d-flex align-items-center gap-2"
                    >
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="timelogDiscrepancySwitch"
                            v-model="showTimelogDiscrepancy"
                            @change="handleTimelogToggle"
                            style="
                                cursor: pointer;
                                transform: scale(1.2);
                                margin-right: 0.5rem;
                                margin-bottom: 2px;
                            "
                        />
                        <label
                            class="form-check-label text-uppercase fw-medium"
                            for="timelogDiscrepancySwitch"
                            style="font-size: 12px; cursor: pointer"
                        >
                            Timelogs Discrepancy
                        </label>
                    </div>
                </li>

                <!-- Worked Hours Toggle -->
                <li class="pb-2">
                    <div
                        class="form-check form-switch d-flex align-items-center gap-2"
                    >
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="workedHoursSwitch"
                            v-model="showWorkedHours"
                            @change="handleWorkedHoursToggle"
                            style="
                                cursor: pointer;
                                transform: scale(1.2);
                                margin-right: 0.5rem;
                                margin-bottom: 2px;
                            "
                        />
                        <label
                            class="form-check-label text-uppercase fw-medium"
                            for="workedHoursSwitch"
                            style="font-size: 12px; cursor: pointer"
                        >
                            Today's Worked Hours
                        </label>
                    </div>
                </li>
            </div>
        </ul>
    </div>
</template>

<script>
import axios from "axios";
import { watch } from "vue";

const HIDE_KEY = "hide_timelog_discrepancy";
const HIDE_DATE_KEY = "hide_timelog_discrepancy_date";
const WORKED_HOURS_KEY = "show_worked_hours";

export default {
    name: "WidgetComponent",
    data() {
        const token = localStorage.getItem("auth_token");

        return {
            token,
            showTimelogDiscrepancy: true,
            showWorkedHours: true,
            isDarkMode: false,
            todayTimeIn: null,
            todayTimeOut: null,
            now: Date.now(),
            clockInterval: null,
            loading: false,
        };
    },
    computed: {
        workedHours() {
            if (!this.todayTimeIn) return "NO TIME IN";

            const timeInDate = this.parseTimeIn(this.todayTimeIn);
            if (!timeInDate) return "NO TIME IN";

            const endTime = this.todayTimeOut
                ? this.parseTimeIn(this.todayTimeOut)?.getTime()
                : this.now;

            const diffMs = endTime - timeInDate.getTime();
            if (diffMs <= 0) return "0 HRS 0 MINS";

            const totalMinutes = Math.floor(diffMs / 60000);
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;

            const hourLabel = hours === 1 ? "HR" : "HRS";
            const minuteLabel = minutes === 1 ? "MIN" : "MINS";

            return `${hours} ${hourLabel} & ${minutes} ${minuteLabel}`;
        },
    },
    mounted() {
        this.initializeTheme();
        this.syncTimelogToggleState();

        const saved = localStorage.getItem(WORKED_HOURS_KEY);
        if (saved !== null) this.showWorkedHours = saved === "true";

        if (this.showWorkedHours) this.fetchLatestTimeLog();

        this.$watch(
            () => window.clockTriggers,
            ({ stopped, start }) => {
                if (stopped) {
                    this.stopClock();
                    window.clockTriggers.stopped = false;
                }
                if (start) {
                    this.startClock();
                    window.clockTriggers.start = false;
                }
            },
            { deep: true },
        );
    },
    beforeUnmount() {
        this.stopClock();
        window.removeEventListener(
            "timelog-toggle",
            this.syncTimelogToggleState,
        );
        window.removeEventListener("stop-clock-ticking", this.stopClock);
    },
    methods: {
        initializeTheme() {
            const saved = localStorage.getItem("theme-preference");
            this.isDarkMode = saved === "dark";
            this.applyTheme();
        },
        handleThemeToggle() {
            localStorage.setItem(
                "theme-preference",
                this.isDarkMode ? "dark" : "light",
            );
            this.applyTheme();
        },
        applyTheme() {
            document.documentElement.setAttribute(
                "data-bs-theme",
                this.isDarkMode ? "dark" : "light",
            );
        },
        parseTimeIn(timeIn) {
            const direct = new Date(timeIn);
            if (!isNaN(direct.getTime())) return direct;

            const match = timeIn?.match(
                /(\d{1,2}):(\d{2})(?::(\d{2}))?\s?(AM|PM)/i,
            );
            if (!match) return null;

            let hours = Number(match[1]);
            const minutes = Number(match[2]);
            const seconds = Number(match[3] || 0);
            const meridiem = match[4].toUpperCase();

            if (meridiem === "PM" && hours < 12) hours += 12;
            if (meridiem === "AM" && hours === 12) hours = 0;

            const date = new Date();
            date.setHours(hours, minutes, seconds, 0);

            return date;
        },
        startClock() {
            if (this.clockInterval) return;
            this.now = Date.now();
            this.fetchLatestTimeLog();

            this.clockInterval = setInterval(() => {
                this.now = Date.now();
                console.log(123);
            }, 1000);
        },
        stopClock() {
            if (this.clockInterval) clearInterval(this.clockInterval);
            this.clockInterval = null;
        },
        async fetchLatestTimeLog() {
            if (this.loading) return;
            this.loading = true;

            try {
                const res = await axios.get("/api/employee/current-logs", {
                    headers: { Authorization: `Bearer ${this.token}` },
                });

                const logs = res.data || [];

                this.todayTimeIn = logs?.time_in || null;
                this.todayTimeOut = logs?.time_out || null;

                if (this.todayTimeIn && !this.todayTimeOut) {
                    this.startClock();
                } else {
                    this.stopClock();
                }
            } catch (err) {
                console.error("Failed to fetch time logs:", err);
            } finally {
                this.loading = false;
            }
        },
        handleWorkedHoursToggle() {
            localStorage.setItem(
                WORKED_HOURS_KEY,
                this.showWorkedHours ? "true" : "false",
            );
            if (this.showWorkedHours) this.fetchLatestTimeLog();
            else {
                this.todayTimeIn = null;
                this.todayTimeOut = null;
                this.stopClock();
            }
        },
        syncTimelogToggleState() {
            const today = new Date().toDateString();
            const hidden = localStorage.getItem(HIDE_KEY);
            const hideDate = localStorage.getItem(HIDE_DATE_KEY);
            this.showTimelogDiscrepancy = !(
                hidden === "true" && hideDate === today
            );
        },
        handleTimelogToggle() {
            if (!this.showTimelogDiscrepancy) {
                localStorage.setItem(HIDE_KEY, "true");
                localStorage.setItem(HIDE_DATE_KEY, new Date().toDateString());
            } else {
                localStorage.removeItem(HIDE_KEY);
                localStorage.removeItem(HIDE_DATE_KEY);
            }
            window.dispatchEvent(new Event("timelog-toggle"));
        },
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../../sass/variables";

@media (max-width: 767.98px) {
    .dropdown-menu {
        min-width: 300px !important;

        label {
            font-size: 10px !important;
        }
    }

    .shot-clock-wrapper {
        position: fixed !important;
        width: 80%;
        top: auto !important;
        bottom: -40px !important;
        left: 50%;
        transform: translate(-50%, -25%) !important;
        padding: 20px 0 30px 0 !important;
        border-radius: 16px 16px 0 0 !important;

        h4 {
            font-size: 1rem;
            text-align: center;
        }
    }
}

.shot-clock-wrapper {
    position: fixed;
    top: 25px;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: $primary;
    color: $light;
    padding: 40px 50px 10px;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 9999;

    h4 {
        font-weight: 600;
        text-align: center;
    }
}
</style>
