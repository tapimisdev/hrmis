<template>
    <TaxTemplate :is_open="true">
        <template #header>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-receipt" style="color: var(--bs-primary)"></i>
                <div class="d-flex flex-column lh-sm">
                    <span class="fw-semibold">Breakdown</span>
                    <span class="small">Tax forecast • Year 2026</span>
                </div>
            </div>
        </template>

        <div class="wrapper py-3">
            <BasicInformation :row="row" />
            <BreakdownSkeleton v-if="is_loading" />
            <DynamicTabs v-else :tabs="tabs" :active-index="0" />
        </div>
    </TaxTemplate>
</template>

<script>
import axios from "axios";
import TaxTemplate from "./../components/TaxTemplate.vue";
import BasicInformation from "../components/BasicInformation.vue";
import DynamicTabs from "./components/DynamicTabs.vue";
import BreakdownSkeleton from "./components/BreakdownSkeleton.vue";
import Overview from "./parts/Overview.vue";
import SalaryView from "./parts/SalaryView.vue";
import LongevityView from "./parts/LongevityView.vue";
import BonusView from "./parts/BonusView.vue";
import AllowablesView from "./parts/AllowablesView.vue";
import OtherEarningsView from "./parts/OtherEarningsView.vue";

export default {
    name: "ViewBreakDown",
    components: { 
        TaxTemplate, 
        BasicInformation,
        DynamicTabs,
        SalaryView,
        BreakdownSkeleton,
        OtherEarningsView
    },
    props: {
        row: { type: Object, required: true },
    },
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            breakdown: null, // store API response here
            is_loading: false
        };
    },

    computed: {
        tabs() {
            return [
                {
                    id: "overview",
                    name: "Overview",
                    component: Overview,
                    props: {
                        row: this.row
                    },
                },
                {
                    id: "salary",
                    name: "Basic salary",
                    component: SalaryView,
                    props: {
                        data: this.breakdown.basic_salary,
                    },
                },
                {
                    id: "longevity",
                    name: "Longevity",
                    is_show: this.row.longevity,
                    component: LongevityView,
                    props: {
                        data: this.breakdown.longetivity_pay,
                    },
                },
                {
                    id: "hazard",
                    name: "Hazard pay",
                    is_show: this.row.hazard_pay,
                    component: SalaryView,
                    props: {
                        data: this.breakdown.hazard_pay,
                    },
                },
                {
                    id: "mid_year",
                    name: "Mid year",
                    is_show: this.row.mid_year,
                    component: BonusView,
                    props: {
                        data: this.breakdown.mid_year,
                    },
                },
                {
                    id: "year_end",
                    name: "Year end",
                    is_show: this.row.year_end,
                    component: BonusView,
                    props: {
                        data: this.breakdown.year_end,
                    },
                },
                {
                    id: "allowables",
                    name: "Allowables",
                    component: AllowablesView,
                    props: {
                        data: this.breakdown.allowables_deductions,
                    },
                },
                {
                    id: "other_earnings",
                    name: "Other Earnings",
                    component: OtherEarningsView,
                    props: {
                        data: this.breakdown.other_earnings,
                    },
                },
                
            ];
        },
    },

    methods: {
        fetchBreakDown() {
            if (!this.row?.id) return;
            this.breakdown = null;
            this.is_loading = true;
            axios
                .get(`/admin/taxation/breakdowns/${this.row.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then((response) => {
                    this.breakdown = response.data;
                })
                .catch((err) => {
                    console.error(err);
                    this.breakdown = null;
                }).finally(() => {
                    this.is_loading = false;
                });
        },
    },

    watch: {
        "row.id": {
            immediate: true,
            handler() {
                this.fetchBreakDown();
            },
        },
    },
};
</script>

<style scoped>
.wrapper {
    background: var(--bs-body-bg);
}

.breakdown-identity-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.25rem;
    background: var(--bs-body-bg);
    box-shadow: none;
}

.breakdown-name {
    font-size: 0.95rem;
    line-height: 1.1;
}

.breakdown-sub {
    line-height: 1.15;
}

.breakdown-pill {
    font-size: 0.72rem;
    letter-spacing: 0.02em;
    padding: 0.28rem 0.5rem;
    border-radius: 999px;
}

/* old FB style tabs */
.custom-tabs .nav-link {
    border: 1px solid transparent;
    color: var(--bs-secondary-color);
}
.custom-tabs .nav-link.active {
    background: var(--bs-white);
    border: 1px solid var(--bs-border-color);
    border-bottom-color: var(--bs-white);
    color: var(--bs-primary);
    font-weight: 600;
}
</style>
