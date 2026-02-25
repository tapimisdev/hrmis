<template>
    <!-- LOADING STATE -->
    <div
        v-if="loading"
        class="text-center d-flex align-items-center justify-content-center gap-2 py-4"
    >
        <div
            class="spinner-border text-body text-opacity-25"
            role="status"
            style="height: 12px; width: 12px"
        >
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 fw-semibold text-body text-opacity-25">
            Loading ...
        </div>
    </div>
    <Line v-else class="cardiness" :data="chartData" :options="chartOptions" />
</template>

<script lang="ts">
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ChartOptions,
} from "chart.js";
import { Line } from "vue-chartjs";

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
);

export default {
    name: "HrisLineChart",
    components: { Line },
    props: {
        labels: {
            type: Array,
            default: () => ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"],
        },
        hires: {
            type: Array,
            default: () => [5, 10, 7, 12, 8, 15, 10],
        },
        resignations: {
            type: Array,
            default: () => [2, 3, 1, 4, 3, 5, 2],
        },
        loading: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            theme:
                document.documentElement.getAttribute("data-bs-theme") ||
                "light",
        };
    },
    computed: {
        chartData() {
            const hireColor = this.theme === "dark" ? "#6ea8fe" : "#032985";
            const resignColor = this.theme === "dark" ? "#f67280" : "#000000";

            return {
                labels: this.labels,
                datasets: [
                    {
                        label: "New Hires",
                        data: this.hires,
                        borderColor: hireColor,
                        backgroundColor: hireColor + "33",
                        tension: 0.3,
                        fill: true,
                        pointRadius: 5,
                    },
                    {
                        label: "Resignations",
                        data: this.resignations,
                        borderColor: resignColor,
                        backgroundColor: resignColor + "33",
                        tension: 0.3,
                        fill: true,
                        pointRadius: 5,
                    },
                ],
            };
        },
        chartOptions() {
            const isDark = this.theme === "dark";
            const textColor = isDark ? "#e9ecef" : "#212529";
            const gridColor = isDark ? "#495057" : "#dee2e6";

            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "top",
                        labels: {
                            color: textColor,
                        },
                    },
                    title: {
                        display: true,
                        text: "Employee Movement (Monthly)",
                        color: textColor,
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColor,
                        },
                        grid: {
                            color: gridColor,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            precision: 0,
                        },
                        grid: {
                            color: gridColor,
                        },
                    },
                },
            } as ChartOptions<"line">;
        },
    },
    mounted() {
        // Observe changes in theme dynamically
        const observer = new MutationObserver(() => {
            const newTheme =
                document.documentElement.getAttribute("data-bs-theme") ||
                "light";
            if (newTheme !== this.theme) {
                this.theme = newTheme;
            }
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["data-bs-theme"],
        });
    },
};
</script>

<style scoped>
.cardiness {
    height: 350px;
}
</style>
