<template>
    <div class="mt-4 classic-loader" ref="wrap">
        <!-- Top Summary Banner -->
        <div class="sk-banner mb-4"></div>

        <!-- Tabs -->
        <div class="d-flex gap-2 mb-3">
            <div class="sk-tab active"></div>
            <div class="sk-tab"></div>
        </div>

        <!-- Filters Row -->
        <div class="d-flex gap-3 mb-3">
            <div class="sk-input"></div>
            <div class="sk-select"></div>
            <div class="sk-select"></div>
        </div>

        <!-- Main Content -->
        <div class="row g-3 mb-4">
            <!-- Employee List -->
            <div class="col-md-7">
                <div class="sk-panel large"></div>
            </div>

            <!-- Employee Detail -->
            <div class="col-md-5">
                <div class="sk-panel medium"></div>
            </div>
        </div>

        <!-- Tax Settings -->
        <div class="mt-3">
            <div class="sk-section-title mb-3"></div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="sk-card"></div>
                </div>
                <div class="col-md-4">
                    <div class="sk-card"></div>
                </div>
                <div class="col-md-4">
                    <div class="sk-card"></div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "TaxationSkeleton",
    props: {
        batch_data: {
            type: Object,
            default: () => ({
                total_jobs: 0,
                pending_jobs: 0,
                failed_jobs: 0,
                processed_percentage: 0,
                pending_percentage: 0,
                is_finished: false,
            }),
        },
        defaultOpen: {
            type: Boolean,
            default: false,
        },
    },
};
</script>

<style lang="scss" scoped>
.classic-loader {
    --sk-bg: #e9ecef;
    --sk-light: #f8f9fa;
    --sk-shimmer: rgba(0, 0, 0, 0.06);
}

:root[data-bs-theme="dark"] .classic-loader,
[data-bs-theme="dark"] .classic-loader {
    --sk-bg: #2c323a;
    --sk-light: #3a4048;
    --sk-shimmer: rgba(255, 255, 255, 0.06);
}

.classic-loader {
    position: relative;
    max-height: 90vh;
    
    .sk-banner {
        height: 120px;
        border-radius: 8px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;
    }

    .sk-tab {
        width: 120px;
        height: 36px;
        border-radius: 6px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;

        &.active {
            width: 130px;
        }
    }

    .sk-input {
        flex: 1;
        height: 38px;
        border-radius: 6px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;
    }

    .sk-select {
        width: 200px;
        height: 38px;
        border-radius: 6px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;
    }

    .sk-panel {
        border-radius: 8px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;

        &.large {
            height: 240px;
        }

        &.medium {
            height: 240px;
        }
    }

    .sk-section-title {
        width: 180px;
        height: 18px;
        border-radius: 4px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;
    }

    .sk-card {
        height: 160px;
        border-radius: 8px;
        background: var(--sk-bg);
        position: relative;
        overflow: hidden;
    }

    /* 🔥 Shimmer Animation */
    .sk-banner,
    .sk-tab,
    .sk-input,
    .sk-select,
    .sk-panel,
    .sk-section-title,
    .sk-card {
        &::after {
            content: "";
            position: absolute;
            top: 0;
            left: -150%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                var(--sk-shimmer),
                transparent
            );
            animation: shimmer 1.4s infinite;
        }
    }

    @keyframes shimmer {
        100% {
            left: 150%;
        }
    }
}
</style>
