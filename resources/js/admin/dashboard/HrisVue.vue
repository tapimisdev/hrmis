<template>
    <div class="dashboard container-fluid">
        <div class="d-flex mb-3 align-items-center gap-2">
            <div class="ms-2">{{ currentDateTime }}</div>
            <button class="btn btn-transparent btn-sm" @click="fetchCards">
                <i class="fa-solid fa-arrow-rotate-left"></i>
            </button>
        </div>

        <!-- Cards Section -->
        <div class="row g-3 pt-0 mb-4">
            <div
                v-for="(card, i) in cards"
                :key="i"
                class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3"
            >
                <TotalCardVue v-bind="card" :loading="isLoading" />
            </div>
        </div>

        <!-- Row: Birthdays + Attendance -->
        <div class="row g-3 pt-0 mb-4">
            <div class="col-md-6">
                <div class="chart-card shadow-sm border border-body-secondary">
                    <ListTableVue
                        title="🎂 Upcoming Birthdays"
                        :people="birthdays"
                        :loading="isLoading"
                    />
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card shadow-sm border border-body-secondary">
                    <BarVue
                        :labels="attendances.labels"
                        :ontime="attendances.datasets[0].data"
                        :lates="attendances.datasets[1].data"
                        :total_employees="attendances.total_employees"
                        :loading="isLoading"
                    />
                </div>
            </div>
        </div>

        <!-- Row: Workforce Charts -->
        <div class="row g-3 pt-1 mb-4">
            <div class="col-md-5">
                <div class="chart-card shadow-sm border border-body-secondary">
                    <DonutVue
                        :labels="employment_types_pie.labels"
                        :dataset="employment_types_pie.datasets"
                        title="Employment Type Distribution"
                        :loading="isLoading"
                    />
                </div>
            </div>
            <div class="col-md-7">
                <div class="chart-card shadow-sm border border-body-secondary">
                    <LineVue
                        :labels="employee_movement.labels"
                        :hires="employee_movement.hires"
                        :resignations="employee_movement.resignations"
                        title="📈 Hiring vs Resignations"
                        :loading="isLoading"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import BarVue from "./charts/BarVue.vue";
import DonutVue from "./charts/DonutVue.vue";
import LineVue from "./charts/LineVue.vue";
import TotalCardVue from "./charts/TotalCardVue.vue";
import ListTableVue from "./charts/ListTableVue.vue";

const token = localStorage.getItem("auth_token");

export default {
    name: "Dashboard",
    components: { BarVue, TotalCardVue, ListTableVue, DonutVue, LineVue },
    data: () => ({
        isLoading: true,
        cards: [],
        birthdays: [],
        attendances: {
            labels: [], // will hold ['Mon','Tue',...]
            datasets: [
                {
                    label: "On-Time",
                    backgroundColor: "#4CAF50", // green
                    data: [], // on-time counts
                },
                {
                    label: "Late",
                    backgroundColor: "#F44336", // red
                    data: [], // late counts
                },
            ],
        },
        employment_types_pie: [],
        employee_movement: [],
    }),
    computed: {
        currentDateTime() {
            const now = new Date();
            const date = now.toLocaleDateString("en-PH", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
            });
            const time = now.toLocaleTimeString("en-PH", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
                hour12: false, // set true for 12-hour format with AM/PM
            });
            return `${date} ${time}`;
        },
    },
    mounted() {
        let vm = this;
        setTimeout(function() {
            vm.fetchCards();
        }, 1000);
    },
    methods: {
        fetchCards() {
            this.isLoading = true;
            axios
                .get("/api/admin/metrics", {
                    headers: { Authorization: `Bearer ${token}` },
                })
                .then((response) => {
                    this.cards = response.data.cards;
                    this.birthdays = response.data.birthdays;
                    this.attendances = response.data.attendances;
                    this.employment_types_pie = response.data.employment_types;
                    this.employee_movement = response.data.employee_movement;
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../sass/variables";

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
