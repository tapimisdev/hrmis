<template>
    <div class="dropdown position-relative">
        <button
            type="button"
            class="violation-trigger text-decoration-none d-inline-flex align-items-center justify-content-center"
            title="Behavioral Notices"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            @click="openDropdown"
        >
            <i class="fa-solid fa-triangle-exclamation text-light"></i>
            <span v-if="unseenCount > 0" class="notice-badge">{{ badgeCount }}</span>
        </button>

        <div
            ref="violationMenu"
            class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0 violation-menu"
            @scroll.passive="handleScroll"
        >
            <div class="px-4 py-3 border-bottom bg-body">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="mb-0 fw-semibold text-uppercase">Behavioral Notices</h6>
                    </div>
                </div>
            </div>

            <div v-if="loading && violations.length === 0" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div v-else-if="violations.length === 0" class="text-center py-5 px-4 text-muted">
                <i class="fa-regular fa-circle-check mb-2" style="font-size: 2rem"></i>
                <p class="mb-0">No behavioral notices recorded.</p>
            </div>

            <div v-else class="violation-list">
                <a
                    v-for="violation in violations"
                    :key="violation.id"
                    class="dropdown-item py-3 px-4 violation-item text-decoration-none"
                    :class="{ 'violation-item--unseen': !violation.is_seen }"
                    :href="noticeUrl(violation)"
                >
                    <div class="d-flex gap-3">
                        <div class="violation-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <span v-if="!violation.is_seen" class="unseen-dot"></span>
                                <strong class="small">{{ violation.violation_type }}</strong>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Generated {{ formatDate(violation.generated_at) }}
                            </small>
                        </div>
                    </div>
                </a>

                <div v-if="loadingMore" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading more...</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ViolationsComponent",
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            loading: false,
            loadingMore: false,
            violations: [],
            periodLabel: "",
            unseenCount: 0,
            page: 1,
            perPage: 10,
            hasMore: true,
        };
    },
    computed: {
        badgeCount() {
            return this.unseenCount > 99 ? "99+" : this.unseenCount;
        },
    },
    mounted() {
        this.fetchNoticeSummary();
        window.addEventListener("behavioral-notices:seen", this.handleSeenUpdate);
    },
    beforeUnmount() {
        window.removeEventListener("behavioral-notices:seen", this.handleSeenUpdate);
    },
    methods: {
        openDropdown() {
            this.fetchViolations(true);
        },
        async fetchViolations(reset = false) {
            if ((this.loading || this.loadingMore) || (!reset && !this.hasMore)) {
                return;
            }

            if (reset) {
                this.page = 1;
                this.hasMore = true;
                this.violations = [];
                this.$nextTick(() => {
                    if (this.$refs.violationMenu) {
                        this.$refs.violationMenu.scrollTop = 0;
                    }
                });
            }

            this.loading = reset;
            this.loadingMore = !reset;

            try {
                const { data } = await axios.get("/api/employee/behavioral-notices", {
                    params: {
                        page: this.page,
                        per_page: this.perPage,
                    },
                    headers: { Authorization: `Bearer ${this.token}` },
                });

                const notices = data.behavioral_notices || data.violations || [];

                this.violations = reset
                    ? notices
                    : [...this.violations, ...notices];
                this.periodLabel = data.period || "";
                this.unseenCount = data.unseen_count || 0;
                this.hasMore = Boolean(data.pagination?.has_more);
                this.page = (data.pagination?.current_page || this.page) + 1;
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },
        async fetchNoticeSummary() {
            const { data } = await axios.get("/api/employee/behavioral-notices", {
                params: {
                    per_page: 1,
                },
                headers: { Authorization: `Bearer ${this.token}` },
            });

            this.unseenCount = data.unseen_count || 0;
        },
        handleSeenUpdate(event) {
            this.unseenCount = event.detail?.unseen_count ?? Math.max(0, this.unseenCount - 1);
        },
        handleScroll(event) {
            const menu = event.target;
            const distanceFromBottom = menu.scrollHeight - menu.scrollTop - menu.clientHeight;

            if (distanceFromBottom <= 80) {
                this.fetchViolations();
            }
        },
        noticeUrl(violation) {
            return `/employee/behavioral-notices?notice=${violation.id}`;
        },
        formatDate(date) {
            if (!date) return "just now";

            return new Date(date).toLocaleString();
        },
    },
};
</script>

<style scoped>
.violation-trigger {
    width: 1.75rem;
    height: 1.75rem;
    background: transparent;
    border: 0;
    padding: 0;
    cursor: pointer;
    position: relative;
}

.violation-trigger i {
    font-size: 1.45rem;
}

.violation-menu {
    min-width: 420px;
    max-width: 420px;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    max-height: 500px;
    overflow-y: auto;
}

.violation-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 193, 7, 0.2);
    color: #9a6700;
    flex-shrink: 0;
}

.violation-item {
    color: var(--bs-body-color);
    white-space: normal;
}

.violation-item:hover {
    background: rgba(13, 110, 253, 0.08);
}

.violation-item--unseen {
    background: rgba(13, 110, 253, 0.06);
}

.notice-badge {
    align-items: center;
    background: #dc3545;
    border: 2px solid var(--bs-body-bg);
    border-radius: 999px;
    color: #fff;
    display: inline-flex;
    font-size: 0.68rem;
    font-weight: 800;
    justify-content: center;
    min-height: 18px;
    min-width: 18px;
    padding: 0 5px;
    position: absolute;
    right: -10px;
    top: -7px;
}

.unseen-dot {
    background: #0d6efd;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
    height: 8px;
    width: 8px;
}

@media (max-width: 767.98px) {
    .violation-menu {
        min-width: 320px;
        max-width: 320px;
    }
}
</style>
