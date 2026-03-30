<template>
    <div class="tax-forecast-header fb-style">
        <div class="fb-toolbar border-bottom pb-3 pt-2">
            <!-- LEFT -->
            <div class="fb-left">
                <div class="fb-breadcrumb">
                    <h3 class="fb-title">Yearly Employee Tax Forecast</h3>
                </div>

                <select v-model="selectedYear" class="fb-select">
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>

            <!-- RIGHT -->
            <div class="fb-buttons">
                <RunForecastModal ref="foreCastModal" :selectedYear="selectedYear"
                    @forecast-ran="emitYearToParent" />
                <button class="fb-btn bg-danger" v-if="showDeleteButton" @click="$emit('delete')">
                    <i class="fa-solid fa-trash me-1"></i>
                    Delete Permanently
                </button>

                <button class="fb-btn fb-primary" v-if="showRunButton" @click="$refs.foreCastModal.handleOpenaddModal()">
                    <i class="fa-solid fa-calculator me-1"></i>
                    Run Forecast
                </button>

                <template v-else-if="showDeleteButton">
                    <button class="fb-btn fb-secondary">
                        <i class="fa-solid fa-file-lines me-1"></i>
                        BIR 2316
                    </button>

                    <button class="fb-btn fb-secondary">
                        <i class="fa-solid fa-file-pdf me-1"></i>
                        Alpha List
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import RunForecastModal from "../modal/run-forecast/RunForecastModal.vue";

export default {
    name: "TaxationHeader",
    components: { RunForecastModal },
    props: {
        has_taxation_record: {
            default: null,
        },
        is_busy: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        const currentYear = new Date().getFullYear();
        const token = localStorage.getItem("auth_token");
        return {
            token,
            selectedYear: currentYear,
            years: [
                currentYear + 2,
                currentYear + 1,
                currentYear,
                currentYear - 1,
                currentYear - 2,
                currentYear - 3,
                currentYear - 4,
                currentYear - 5,
                currentYear - 6,
                currentYear - 7,
            ],
        };
    },
    computed: {
        showRunButton() {
            return this.has_taxation_record === false && !this.is_busy;
        },
        showDeleteButton() {
            return this.has_taxation_record === true && !this.is_busy;
        },
    },

    created() {
        this.initYearFromUrl();
        this.sendYearToParent();

        window.addEventListener("popstate", this.onPopState);
    },

    beforeUnmount() {
        window.removeEventListener("popstate", this.onPopState);
    },

    methods: {
        emitYearToParent() {
            this.$emit("taxation-data-updated", this.selectedYear);
        },
        initYearFromUrl() {
            const currentYear = new Date().getFullYear();
            const params = new URLSearchParams(window.location.search);
            const yearParam = params.get("year");

            const parsed = parseInt(yearParam, 10);
            const yearFromUrl = Number.isFinite(parsed) ? parsed : currentYear;

            this.selectedYear = yearFromUrl;

            if (!yearParam || parsed !== yearFromUrl) {
                this.setUrlYear(yearFromUrl, true);
            }
        },

        setUrlYear(year, replace = false) {
            const url = new URL(window.location.href);
            url.searchParams.set("year", year);

            if (replace) {
                window.history.replaceState({}, "", url);
            } else {
                window.history.pushState({}, "", url);
            }
        },

        sendYearToParent() {
            this.setUrlYear(this.selectedYear);
            this.emitYearToParent();
        },

        onPopState() {
            const params = new URLSearchParams(window.location.search);
            const parsed = parseInt(params.get("year"), 10);

            if (Number.isFinite(parsed) && parsed !== this.selectedYear) {
                this.selectedYear = parsed;
            }
        },
    },

    watch: {
        selectedYear(newVal, oldVal) {
            if (newVal === oldVal) return;
            this.sendYearToParent();
        },
    },
};
</script>

<style lang="scss" scoped>
.fb-style {
    margin-top: 12px;
    background: var(--bs-body);
    border-radius: 4px;

    /* Toolbar */
    .fb-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;

        /* Left group */
        .fb-left {
            display: flex;
            align-items: center;
            gap: 14px;

            /* Breadcrumb / title wrapper */
            .fb-breadcrumb {
                display: flex;
                align-items: center;

                /* Title */
                .fb-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: var(--bs-body-color);
                    margin: 0;
                    line-height: 1.2;
                }
            }

            /* Year select */
            .fb-select {
                height: 30px;
                border: 1px solid var(--bs-border-color);
                padding: 4px 10px;
                font-size: 13px;
                background: var(--bs-body-bg);
                border-radius: 2px;
            }
        }

        /* Right buttons */
        .fb-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
    }
}
</style>
