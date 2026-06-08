<template>
    <div class="mt-4 classic-loader" ref="wrap">
        <div class="panel">
            <!-- Accordion Body (NOW ON TOP) -->
            <transition name="acc">
                <div v-show="isOpen" class="panel-body">
                    <!-- Progress -->
                    <div
                        class="d-flex justify-content-between align-items-center mb-1"
                    >
                        <small class="text-secondary">Progress</small>
                        <small class="text-secondary">
                            <strong class="text-dark"
                                >{{ safe(processed) }}%</strong
                            >
                            <span class="mx-1">•</span>
                            <span>Pending {{ safe(pending) }}%</span>
                        </small>
                    </div>

                    <div
                        class="progress classic-progress"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="progress-bar"
                            :class="progressBarClass"
                            :style="{ width: safe(processed) + '%' }"
                        >
                            <span class="bar-text">{{ safe(processed) }}%</span>
                        </div>
                    </div>

                    <div class="stats mt-3">
                        <div class="stat">
                            <div class="label">Total</div>
                            <div class="value">{{ safe(total_jobs) }}</div>
                        </div>

                        <div class="stat">
                            <div class="label">Processed</div>
                            <div class="value text-primary">
                                {{ processedJobs }}
                            </div>
                        </div>

                        <div class="stat">
                            <div class="label">Pending</div>
                            <div class="value text-warning">
                                {{ safe(pending_jobs) }}
                            </div>
                        </div>

                        <div class="stat">
                            <div class="label">Failed</div>
                            <div
                                class="value"
                                :class="
                                    safe(failed_jobs) > 0
                                        ? 'text-danger'
                                        : 'text-success'
                                "
                            >
                                {{ safe(failed_jobs) }}
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Header (Always Visible, Now At Bottom) -->
            <button type="button" class="panel-head w-100" @click="toggle">
                <div class="d-flex align-items-center gap-2">
                    <span
                        v-if="isProcessing"
                        class="spinner-border spinner-border-sm text-warning sk-spin"
                        role="status"
                        aria-hidden="true"
                    ></span>

                    <div class="lh-sm text-start">
                        <div class="title">Forecasting Status</div>
                        <div class="subtitle">
                            {{ statusText }}
                        </div>
                    </div>
                </div>

                <i
                    class="fa-solid fa-chevron-up chev"
                    :class="{ open: isOpen }"
                ></i>
            </button>
        </div>
    </div>
</template>
<script>
export default {
    emits: ["refresh", "view"],
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
        // optional: start open or closed
        defaultOpen: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            isOpen: this.defaultOpen,
        };
    },
    computed: {
        total_jobs() {
            return this.batch_data?.total_jobs ?? 0;
        },
        pending_jobs() {
            return this.batch_data?.pending_jobs ?? 0;
        },
        failed_jobs() {
            return this.batch_data?.failed_jobs ?? 0;
        },
        processed() {
            return this.batch_data?.processed_percentage ?? 0;
        },
        pending() {
            return this.batch_data?.pending_percentage ?? 0;
        },
        isProcessing() {
            return !this.batch_data?.is_finished;
        },
        processedJobs() {
            const total = Number(this.total_jobs) || 0;
            const pending = Number(this.pending_jobs) || 0;
            const done = total - pending;
            return done < 0 ? 0 : done;
        },
        statusText() {
            if (this.batch_data?.is_finished) return "Completed";
            if (Number(this.failed_jobs) > 0) return "Processing (with errors)";
            return "Processing";
        },
        badgeClass() {
            if (this.batch_data?.is_finished) return "bg-success";
            if (Number(this.failed_jobs) > 0) return "bg-danger";
            return "bg-primary";
        },
        progressBarClass() {
            if (this.batch_data?.is_finished) return "bg-success";
            if (Number(this.failed_jobs) > 0) return "bg-danger";
            return "bg-primary";
        },
        dotClass() {
            if (this.batch_data?.is_finished) return "bg-success";
            if (Number(this.failed_jobs) > 0) return "bg-danger";
            return "bg-primary";
        },
    },
    methods: {
        toggle() {
            this.isOpen = !this.isOpen;

            // when opening -> scroll to top of this component
            if (this.isOpen) {
                this.$nextTick(() => {
                    const el = this.$refs.wrap;
                    if (el && el.scrollIntoView) {
                        el.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                    } else {
                        window.scrollTo({ top: 0, behavior: "smooth" });
                    }
                });
            }
        },
        safe(v) {
            const n = Number(v);
            if (!Number.isFinite(n)) return 0;
            return v === this.processed || v === this.pending
                ? Math.max(0, Math.min(100, Math.round(n)))
                : Math.max(0, Math.round(n));
        },
    },
};
</script>

<style lang="scss" scoped>
.classic-loader {
    max-width: 420px;

    .panel {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 0.125rem 0 rgba(0, 0, 0, 0.05);
    }

    /* header as button */
    .panel-head {
        padding: 0.625rem 0.75rem;
        background: var(--bs-secondary-bg);
        border: 0;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;

        &:hover {
            filter: brightness(0.98);
        }

        .title {
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--bs-body-color);
        }

        .subtitle {
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
        }

        .badge {
            font-weight: 600;
            border: 1px solid var(--bs-border-color-translucent);
        }

        .dot {
            width: 0.625rem;
            height: 0.625rem;
            border-radius: 50%;
            border: 1px solid var(--bs-border-color-translucent);
            display: inline-block;
        }

        .chev {
            transition: transform 180ms ease;
            color: var(--bs-secondary-color);

            &.open {
                transform: rotate(180deg);
            }
        }
    }

    .panel-body {
        padding: 0.75rem;
    }

    .classic-progress {
        height: 1.125rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-secondary-bg);

        .progress-bar {
            position: relative;
            font-weight: 700;
            font-size: 0.75rem;
            line-height: 1.125rem;
        }

        .bar-text {
            position: absolute;
            right: 0.5rem;
            top: 0;
            color: var(--bs-white);
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
        }
    }

    .message {
        padding: 0.5rem 0.625rem;
        background: var(--bs-secondary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;

        .stat {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            background: var(--bs-secondary-bg);
            padding: 0.5rem 0.625rem;

            .label {
                font-size: 0.6875rem;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                color: var(--bs-secondary-color);
            }

            .value {
                margin-top: 0.125rem;
                font-weight: 800;
                font-size: 1rem;
                color: var(--bs-body-color);
            }
        }

        @media (max-width: 768px) {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .footer {
        padding-top: 0.625rem;
        border-top: 1px dashed var(--bs-border-color);
    }

    .panel-foot {
        margin-top: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: var(--bs-secondary-bg);
        border-top: 1px solid var(--bs-border-color);
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
        border-radius: 0.375rem;
    }

    /* accordion transition */
    .acc-enter-active,
    .acc-leave-active {
        transition:
            max-height 220ms ease,
            opacity 180ms ease;
        overflow: hidden;
    }
    .acc-enter,
    .acc-leave-to {
        max-height: 0;
        opacity: 0;
    }
    .acc-enter-to,
    .acc-leave {
        max-height: 1200px;
        opacity: 1;
    }
}
</style>
