<template>
    <div class="welcome-header">
        <div class="row align-items-center g-3">
            <div class="col-md-5 order-md-2">
                <div class="image-container">
                    <img
                        src="../../img/engineering.svg"
                        alt="Engineering"
                        class="illustration"
                    />
                </div>
            </div>
            <div class="col-md-7 order-md-1">
                <div class="welcome-content">
                    <div
                        class="d-flex justify-content-between align-items-start mb-2"
                    >
                        <div>
                            <div class="greeting mb-1">
                                <span class="wave">👋</span>
                                <span class="greeting-text"
                                    >Good {{ timeOfDay }},</span
                                >
                            </div>
                            <h4 class="user-name mb-0">{{ name }}</h4>
                        </div>
                        <div class="info-badge">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span>{{ currentDate }}</span>
                        </div>
                    </div>

                    <p class="welcome-message mb-3">
                        Welcome back! Here's an overview of your activity.
                    </p>

                    <div class="action-buttons d-flex gap-2 mb-3">
                        <a
                            href="/employee/check-in-out"
                            class="btn btn-warning px-4 py-2 d-flex align-items-center gap-2"
                        >
                            <i class="fa-regular fa-calendar"></i>
                            <span>View Schedule</span>
                        </a>
                        <button
                            :class="isRegular ? 'd-block' : 'd-none'"
                            @click="viewLeaveCredits"
                            class="btn btn-outline-light px-4 py-2 d-flex align-items-center gap-2 leave-creds"
                        >
                            <i class="fa-solid fa-plane-departure"></i>
                            <span>Leave Credits</span>
                        </button>

                        <button
                            @click="viewOffsetCredits"
                            class="btn btn-outline-light px-4 py-2 d-flex align-items-center gap-2"
                        >
                            <i class="fa-solid fa-ghost"></i>
                            <span>Offset Credits</span>
                        </button>
                    </div>

                    <div class="quick-stats row g-4 mt-3 mb-0 pt-0">
                        <div class="col-md-6">
                            <div class="stat-item d-flex gap-3">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <div class="stat-value">
                                        {{ stats.totalHours }}
                                    </div>
                                    <div
                                        class="stat-label text-uppercase text-muted"
                                        style="font-size: 12px; margin-top: 4px"
                                    >
                                        Total Hours
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stat-item d-flex gap-3">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-calendar-xmark"></i>
                                </div>
                                <div>
                                    <div class="stat-value">
                                        {{ stats.pendingLeaves }}
                                    </div>
                                    <div
                                        class="stat-label text-uppercase text-muted"
                                        style="font-size: 12px; margin-top: 4px"
                                    >
                                        Pending Leaves
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stat-item d-flex gap-3">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-business-time"></i>
                                </div>
                                <div>
                                    <div class="stat-value">
                                        {{ stats.overtime }}
                                    </div>
                                    <div
                                        class="stat-label text-uppercase text-muted"
                                        style="font-size: 12px; margin-top: 4px"
                                    >
                                        Overtime | Offsets
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stat-item d-flex gap-3">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-user-xmark"></i>
                                </div>
                                <div>
                                    <div class="stat-value">
                                        {{ stats.absent }}
                                    </div>
                                    <div
                                        class="stat-label text-uppercase text-muted"
                                        style="font-size: 12px; margin-top: 4px"
                                    >
                                        Absences
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "WelcomeHeader",
    props: {
     name: {
        type: String,
        required: true
      },
      isRegular: {
        type: Boolean, 
        required: true
      }
    },
    data() {
        return {
            stats: {
                totalHours: "0 HRS",
                pendingLeaves: 0,
                overtime: "0 MINS",
                absent: "0 Days",
            },
        };
    },
    computed: {
        timeOfDay() {
            const hour = new Date().getHours();
            if (hour < 12) return "morning";
            if (hour < 18) return "afternoon";
            return "evening";
        },
        currentDate() {
            const options = { month: "short", day: "numeric", year: "numeric" };
            return new Date().toLocaleDateString("en-US", options);
        },
    },
    mounted() {
        this.fetchStats();
    },
    methods: {
        async fetchStats() {
            try {
                const response = await axios.get("/employee/get-stats", {
                    headers: { Authorization: `Bearer ${this.token}` },
                });

                const statsData = response.data.data || [];

                const totalHrs = statsData.find((s) => s.label === "Total HRS");
                const pendingLeaves = statsData.find(
                    (s) => s.label === "Pending Leaves"
                );
                const overtime = statsData.find((s) => s.label === "Overtime");
                const absent = statsData.find((s) => s.label === "Absent");

                this.stats = {
                    totalHours: totalHrs?.value || "0 HRS",
                    pendingLeaves: pendingLeaves?.value || 0,
                    overtime: overtime?.value || "0 MINS",
                    absent: absent?.value || "0 Days",
                };
            } catch (error) {
                console.error("Error fetching stats:", error);
            }
        },
        viewSchedule() {
            this.$router.push("/schedule");
        },
        viewLeaveCredits() {
            window.location.href = "/employee/credits/leave";
        },
        viewOffsetCredits() {
            window.location.href = "/employee/credits/offset";
        },
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../../sass/variables";

[data-bs-theme="dark"] {
    .welcome-header {
        background: linear-gradient(
            135deg,
            var(--bs-secondary-bg) 0%,
            var(--bs-body-bg) 100%
        );
        border: 1px solid var(--bs-border-color);
        box-shadow: none;
    }

    .btn-warning {
        border: none;
        background-color: var(--bs-primary);
        color: var(--bs-body-color);
        &:hover {
            transform: translateY(-2px);
        }
    }
}

.welcome-header {
    background: linear-gradient(
        135deg,
        var(--bs-secondary) 0%,
        var(--bs-primary) 100%
    );
    border-radius: 10px;
    padding: 1.75rem 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);

    &::before {
        content: "";
        position: absolute;
        top: -30%;
        right: -15%;
        width: 700px;
        height: 700px;
        background: rgba(0, 0, 0, 0.08);
        border-radius: 50%;
        z-index: 0;
    }

    .row {
        position: relative;
        z-index: 1;
    }
}

.image-container {
    display: flex;
    justify-content: center;
    align-items: center;

    .illustration {
        max-width: 100%;
        height: auto;
        max-height: 240px;
        animation: float 3s ease-in-out infinite reverse;
    }
}

.welcome-content {
    color: $light;

    .greeting {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;

        .wave {
            font-size: 1.2rem;
        }

        .greeting-text {
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.9;
        }
    }

    .user-name {
        font-size: 1.75rem;
        font-weight: 700;
        background: linear-gradient(to right, #ffffff, #f5f5f5);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .info-badge {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.9rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.2);
        color: white;

        i {
            font-size: 0.9rem;
        }
    }

    .welcome-message {
        font-size: 0.9rem;
        line-height: 1.5;
        opacity: 0.9;
    }

    .action-buttons {
        .btn {
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.9rem;

            &.btn-warning {
                border: none;

                &:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(255, 193, 7, 0.4);
                }
            }

            &.btn-outline-light {
                border-color: rgba(255, 255, 255, 0.6);
                color: white !important;
                background: rgba(255, 255, 255, 0.1);

                &:hover {
                    background: rgba(255, 255, 255, 0.2);
                    border-color: white;
                    transform: translateY(-2px);
                }
            }
        }
    }

    .quick-stats {
        white-space: nowrap;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;

            .stat-icon {
                width: 36px;
                height: 36px;
                background: rgba(255, 255, 255, 0.15);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;

                i {
                    font-size: 1rem;
                }
            }

            .stat-value {
                font-size: 1.4rem;
                font-weight: 700;
                line-height: 1;
            }

            .stat-label text-uppercase text-muted {
                font-size: 0.75rem;
                opacity: 0.8;
                line-height: 1.2;
            }
        }
    }
}

// Responsive styles
@media (max-width: 768px) {
    .welcome-header {
        padding: 1.5rem 1.25rem;
    }

    .welcome-content {
        .info-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
        }

        .user-name {
            font-size: 1.5rem;
        }

        .action-buttons {
            .btn {
                flex: 1;
                min-width: 120px;
                font-size: 0.85rem;
            }
        }

        .quick-stats {
            flex-wrap: wrap;

            .stat-item {
                .stat-icon {
                    width: 32px;
                    height: 32px;
                }

                .stat-value {
                    font-size: 1.2rem;
                }
            }
        }
    }

    .image-container {
        .illustration {
            max-height: 180px;
        }
    }
}

@media (max-width: 576px) {
    .welcome-content {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-buttons {
            flex-direction: column;

            .btn {
                width: 100%;
            }
        }
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0) translateX(0%);
    }
    50% {
        transform: translateY(-10px) translateX(0%);
    }
}
</style>
