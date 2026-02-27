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

        <div class="wrapper p-2">
            <DynamicTabs :tabs="tabs" :active-index="0" />
        </div>
    </TaxTemplate>
</template>

<script>
import axios from "axios";
import TaxTemplate from "./../components/TaxTemplate.vue";
import DynamicTabs from "./components/DynamicTabs.vue";
import Overview from "./parts/Overview.vue";

export default {
    name: "ViewBreakDown",
    components: { TaxTemplate, DynamicTabs },
    props: {
        row: { type: Object, required: true },
    },
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            breakdown: null, // store API response here
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
                        row: this.row,
                        breakdown: this.breakdown,
                    },
                },
            ];
        },
    },

    methods: {
        fetchBreakDown() {
            if (!this.row?.id) return;

            axios
                .get(`/api/tax/breakdown/${this.row.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then((response) => {
                    this.breakdown = response.data;
                })
                .catch((err) => {
                    console.error(err);
                    this.breakdown = null;
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