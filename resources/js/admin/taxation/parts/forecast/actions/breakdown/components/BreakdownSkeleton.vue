<template>
    <div class="tax-skeleton">
        <!-- Tabs -->
        <div class="tax-tabs">
            <div class="sk-tab active"></div>
            <div class="sk-tab"></div>
            <div class="sk-tab"></div>
            <div class="sk-tab"></div>
        </div>

        <!-- Card -->
        <div class="tax-card">
            <!-- Employee Header -->
            <div class="tax-header">
                <div class="left">
                    <div class="sk-line title"></div>
                    <div class="sk-line subtitle"></div>
                    <div class="sk-line subtitle small"></div>
                </div>

                <div class="right">
                    <div class="sk-pill"></div>
                    <div class="sk-line subtitle small mt"></div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Tax Summary -->
            <div class="tax-summary">
                <div class="summary-left">
                    <div class="sk-line label"></div>
                    <div class="sk-line value"></div>
                    <div class="sk-line small"></div>
                </div>

                <div class="summary-right">
                    <div class="sk-line label"></div>
                    <div class="sk-line value"></div>
                    <div class="sk-line small"></div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Computation Overview -->
            <div class="computation">
                <div class="sk-line section-title"></div>

                <div class="comp-row" v-for="i in 4" :key="i">
                    <div class="left">
                        <div class="sk-icon"></div>
                        <div class="sk-line text"></div>
                    </div>

                    <div class="right">
                        <div class="sk-line amount"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.tax-skeleton {
    /* skeleton animation */
    .sk-line,
    .sk-pill,
    .sk-icon,
    .sk-tab {
        background: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0.05),
            rgba(255, 255, 255, 0.12),
            rgba(255, 255, 255, 0.05)
        );

        background-size: 200% 100%;
        animation: shimmer 1.4s infinite linear;
        border-radius: 4px;
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }

    /* Tabs */

    .tax-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 14px;
    }

    .sk-tab {
        height: 32px;
        width: 110px;
        border-radius: 6px;

        &.active {
            opacity: 0.9;
        }
    }

    /* Card */

    .tax-card {
        padding: 18px;
        border-radius: 8px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
    }

    /* Header */

    .tax-header {
        display: flex;
        justify-content: space-between;
    }

    .title {
        width: 260px;
        height: 16px;
    }
    .subtitle {
        width: 140px;
        height: 12px;
    }
    .small {
        width: 90px;
        height: 10px;
    }

    .mt {
        margin-top: 6px;
    }

    .sk-pill {
        width: 70px;
        height: 22px;
        border-radius: 50px;
    }

    /* Divider */

    .divider {
        border-top: 1px solid var(--bs-border-color);
        margin: 16px 0;
    }

    /* Summary */

    .tax-summary {
        display: flex;
        justify-content: space-between;
    }

    .label {
        width: 120px;
        height: 12px;
    }

    .value {
        width: 170px;
        height: 18px;
        margin-top: 6px;
    }

    /* Computation */

    .section-title {
        width: 180px;
        height: 14px;
        margin-bottom: 14px;
    }

    .comp-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .comp-row .left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sk-icon {
        width: 16px;
        height: 16px;
        border-radius: 3px;
    }

    .text {
        width: 220px;
        height: 12px;
    }

    .amount {
        width: 120px;
        height: 12px;
    }
}
</style>
