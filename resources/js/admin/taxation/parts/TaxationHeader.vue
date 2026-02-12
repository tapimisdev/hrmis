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
                <RunForecastModal ref="foreCastModal" :selectedYear="selectedYear" />
                <button class="fb-btn fb-primary" @click="$refs.foreCastModal.handleOpenaddModal()">
                    <i class="fa-solid fa-calculator me-1"></i>
                    Run Forecast
                </button>

                <button class="fb-btn fb-secondary">
                    <i class="fa-solid fa-file-lines me-1"></i>
                    BIR 2316
                </button>

                <button class="fb-btn fb-secondary">
                    <i class="fa-solid fa-file-pdf me-1"></i>
                    Alpha List
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import RunForecastModal from '../modal/run-forecast/RunForecastModal.vue';
export default {
    name: 'TaxationHeader',
    components: { RunForecastModal },
    data() {
        const currentYear = new Date().getFullYear();

        return {
            selectedYear: currentYear,
            years: [
                currentYear - 2,
                currentYear - 1,
                currentYear,
                currentYear + 1,
            ],
        };
    },
};
</script>

<style scoped>
/* Container */
.fb-style {
    margin-top: 12px;
    background: var(--bs-body);
    border-radius: 4px;
}

/* Toolbar */
.fb-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

/* Left group */
.fb-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

/* Breadcrumb / title wrapper */
.fb-breadcrumb {
    display: flex;
    align-items: center;
}

/* Title */
.fb-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--bs-body-color);
    margin: 0;              /* key alignment fix */
    line-height: 1.2;
}

/* Year select */
.fb-select {
    height: 30px;           /* visually matches buttons */
    border: 1px solid var(--bs-border-color);
    padding: 4px 10px;
    font-size: 13px;
    background: var(--bs-body-bg);
    border-radius: 2px;
}

/* Right buttons */
.fb-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
</style>
