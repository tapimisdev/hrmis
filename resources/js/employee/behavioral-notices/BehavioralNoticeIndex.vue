<template>
    <div class="behavioral-notice-page">
        <div class="notice-toolbar">
            <div>
                <h5 class="mb-1 fw-semibold">Behavioral Notice Records</h5>
                <p class="text-muted mb-0 small">Review your behavioral notices and the specific dates or timelog details attached to each record.</p>
            </div>
            <div class="notice-period-filter">
                <div class="notice-filter-field">
                    <label for="behaviorNoticeMonth">Month</label>
                    <select id="behaviorNoticeMonth" v-model.number="filters.month" class="form-control" @change="handlePeriodChange">
                        <option v-for="(month, index) in months" :key="month" :value="index + 1">
                            {{ month }}
                        </option>
                    </select>
                </div>
                <div class="notice-filter-field">
                    <label for="behaviorNoticeYear">Year</label>
                    <select id="behaviorNoticeYear" v-model.number="filters.year" class="form-control" @change="handlePeriodChange">
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <span class="notice-period-label">{{ periodLabel }}</span>
            </div>
        </div>

        <div class="notice-layout">
            <div class="notice-list">
                <div v-if="loading" class="notice-state">
                    <i class="fa fa-spinner fa-spin"></i>
                    <span>Loading behavioral notices...</span>
                </div>

                <div v-else-if="notices.length === 0" class="notice-state">
                    <i class="fa-regular fa-circle-check"></i>
                    <span>{{ emptyMessage }}</span>
                </div>

                <template v-else>
                    <button
                        v-for="notice in notices"
                        :key="notice.id"
                        type="button"
                        class="notice-list-item"
                        :class="{ active: selectedNotice?.id === notice.id, unseen: !notice.is_seen }"
                        @click="selectNotice(notice)"
                    >
                        <span class="notice-list-item__top">
                            <span class="notice-list-item__type">{{ notice.violation_type }}</span>
                            <span v-if="!notice.is_seen" class="notice-unseen-pill">New</span>
                        </span>
                        <span class="notice-list-item__action">{{ notice.action_name }}</span>
                        <span class="notice-list-item__meta">
                            {{ notice.occurrence_count }} occurrence{{ Number(notice.occurrence_count) === 1 ? '' : 's' }}
                        </span>
                    </button>
                </template>
            </div>

            <section class="notice-detail">
                <div v-if="!selectedNotice" class="notice-empty">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <h5>Select a behavioral notice</h5>
                    <p class="text-muted mb-0">Choose a record from the list to view the full details.</p>
                </div>

                <template v-else>
                    <div class="notice-detail__header">
                        <div>
                            <span class="notice-eyebrow">Behavioral Notice</span>
                            <h4 class="mb-1">{{ selectedNotice.violation_type }}</h4>
                            <p class="text-muted mb-0">{{ selectedNotice.action_name }}</p>
                        </div>
                        <span class="notice-count">
                            {{ selectedNotice.occurrence_count }}
                            {{ Number(selectedNotice.occurrence_count) === 1 ? 'Occurrence' : 'Occurrences' }}
                        </span>
                        <span
                            class="notice-status"
                            :class="{ seen: selectedNotice.is_seen }"
                            :title="selectedNotice.is_seen ? 'Seen' : 'Unseen'"
                        ></span>
                    </div>

                    <div class="notice-section">
                        <h6>Description</h6>
                        <p class="mb-0">{{ selectedNotice.description }}</p>
                    </div>

                    <div class="notice-section">
                        <h6>Generated</h6>
                        <p class="mb-0">{{ formatDate(selectedNotice.generated_at) }}</p>
                    </div>

                    <div class="notice-section">
                        <h6>More Info</h6>
                        <div v-if="detailItems.length === 0" class="text-muted small">
                            No details was attached to this record.
                        </div>
                        <div v-else class="detail-table">
                            <div class="detail-row detail-row--head">
                                <span>Date</span>
                                <span>Type</span>
                                <span>Details</span>
                            </div>
                            <div
                                v-for="(item, index) in detailItems"
                                :key="`notice-detail-${selectedNotice.id}-${index}`"
                                class="detail-row"
                            >
                                <span>{{ formatDetailDate(item.date) }}</span>
                                <span>{{ item.type || '-' }}</span>
                                <span>{{ itemDetails(item) }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "BehavioralNoticeIndex",
    data() {
        const currentYear = new Date().getFullYear();

        return {
            loading: false,
            notices: [],
            selectedNotice: null,
            periodLabel: "",
            token: localStorage.getItem("auth_token"),
            unseenCount: 0,
            months: [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December",
            ],
            years: Array.from({ length: 7 }, (_, index) => currentYear - index),
            filters: {
                month: new Date().getMonth() + 1,
                year: currentYear,
            },
            usePeriodFilter: true,
        };
    },
    computed: {
        detailItems() {
            const details = this.selectedNotice?.details || {};

            if (Array.isArray(details.items)) {
                return details.items;
            }

            if (Array.isArray(details.months)) {
                return details.months.flatMap((month) => month.items || []);
            }

            return [];
        },
        emptyMessage() {
            return `No behavioral notices recorded${this.periodLabel ? ` for ${this.periodLabel}` : ""}.`;
        },
    },
    mounted() {
        this.applyFiltersFromUrl();
        window.addEventListener("popstate", this.handlePopState);
        this.fetchNotices();
    },
    beforeUnmount() {
        window.removeEventListener("popstate", this.handlePopState);
    },
    methods: {
        applyFiltersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const month = Number(params.get("month"));
            const year = Number(params.get("year"));
            const hasValidMonth = month >= 1 && month <= 12;
            const hasValidYear = this.years.includes(year);

            if (hasValidMonth) {
                this.filters.month = month;
            }

            if (hasValidYear) {
                this.filters.year = year;
            }

            this.usePeriodFilter = (hasValidMonth && hasValidYear) || !params.get("notice");
        },
        async fetchNotices() {
            this.loading = true;

            try {
                const { data } = await axios.get("/api/employee/behavioral-notices", {
                    headers: { Authorization: `Bearer ${this.token}` },
                    params: this.usePeriodFilter
                        ? { period: `${this.filters.year}-${String(this.filters.month).padStart(2, "0")}` }
                        : {},
                });

                this.notices = data.behavioral_notices || data.violations || [];
                this.periodLabel = data.period || "";
                this.unseenCount = data.unseen_count || 0;
                const linkedNotice = this.noticeFromUrl();

                if (linkedNotice) {
                    if (!this.usePeriodFilter) {
                        this.filters.month = Number(linkedNotice.month);
                        this.filters.year = Number(linkedNotice.year);
                        this.usePeriodFilter = true;
                        this.notices = this.notices.filter((notice) =>
                            Number(notice.month) === this.filters.month
                            && Number(notice.year) === this.filters.year
                        );
                        this.periodLabel = `${this.months[this.filters.month - 1]} ${this.filters.year}`;
                    }

                    this.updateUrl(true, false, linkedNotice.id);
                    await this.selectNotice(linkedNotice, false);
                } else {
                    this.selectedNotice = this.notices[0] || null;
                    this.updateUrl(true, true);
                }
            } finally {
                this.loading = false;
            }
        },
        handlePeriodChange() {
            this.selectedNotice = null;
            this.usePeriodFilter = true;
            this.updateUrl(false, true);
            this.fetchNotices();
        },
        handlePopState() {
            this.applyFiltersFromUrl();
            this.fetchNotices();
        },
        noticeFromUrl() {
            const noticeId = new URLSearchParams(window.location.search).get("notice");

            if (!noticeId) {
                return null;
            }

            return this.notices.find((notice) => Number(notice.id) === Number(noticeId)) || null;
        },
        async selectNotice(notice, updateHistory = true) {
            this.selectedNotice = notice;

            if (updateHistory) {
                this.updateUrl(false, false, notice.id);
            }

            if (notice.is_seen) {
                return;
            }

            try {
                const { data } = await axios.post(
                    `/api/employee/behavioral-notices/${notice.id}/seen`,
                    {},
                    { headers: { Authorization: `Bearer ${this.token}` } },
                );

                notice.is_seen = true;
                notice.status = "seen";
                notice.seen_at = data.seen_at;
                this.unseenCount = data.unseen_count || 0;

                window.dispatchEvent(new CustomEvent("behavioral-notices:seen", {
                    detail: {
                        id: notice.id,
                        unseen_count: this.unseenCount,
                    },
                }));
            } catch (error) {
            }
        },
        updateUrl(replace = false, clearNotice = false, noticeId = null) {
            const url = new URL(window.location.href);

            url.searchParams.set("month", this.filters.month);
            url.searchParams.set("year", this.filters.year);

            if (clearNotice) {
                url.searchParams.delete("notice");
            } else if (noticeId) {
                url.searchParams.set("notice", noticeId);
            }

            const method = replace ? "replaceState" : "pushState";
            window.history[method]({}, "", url.toString());
        },
        formatDate(date) {
            if (!date) return "Not available";

            return new Date(date).toLocaleString();
        },
        formatDetailDate(date) {
            if (!date) return "-";

            return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
                month: "long",
                day: "numeric",
                year: "numeric",
            });
        },
        itemDetails(item) {
            const parts = [
                item.time_in ? `In: ${item.time_in}` : null,
                item.break_out ? `Break out: ${item.break_out}` : null,
                item.break_in ? `Break in: ${item.break_in}` : null,
                item.time_out ? `Out: ${item.time_out}` : null,
                item.time ? `Time: ${item.time}` : null,
                item.minutes ? this.formatMinutes(item.minutes) : null,
                item.reason ? `Reason: ${item.reason}` : null,
                !item.reason && Array.isArray(item.discrepancy_reasons) && item.discrepancy_reasons.length
                    ? `Reason: ${item.discrepancy_reasons.join(", ")}`
                    : null,
                Array.isArray(item.missing_punches) && item.missing_punches.length
                    ? `Missing: ${item.missing_punches.join(", ")}`
                    : null,
                Array.isArray(item.remarks) && item.remarks.length
                    ? `Remarks: ${item.remarks.join(", ")}`
                    : null,
                item.source ? `Source: ${item.source}` : null,
            ].filter(Boolean);

            return parts.length ? parts.join(" | ") : "-";
        },
        formatMinutes(value) {
            const totalMinutes = Number(value) || 0;
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            const parts = [];

            if (hours > 0) {
                parts.push(`${hours} hr${hours === 1 ? "" : "s"}`);
            }

            if (minutes > 0 || parts.length === 0) {
                parts.push(`${minutes} min${minutes === 1 ? "" : "s"}`);
            }

            return parts.join(" ");
        },
    },
};
</script>

<style scoped>
.behavioral-notice-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.notice-toolbar {
    align-items: center;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    display: flex;
    gap: 16px;
    justify-content: space-between;
    padding: 18px;
}

.notice-period-label {
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    color: var(--bs-body-color);
    font-size: 0.85rem;
    font-weight: 700;
    padding: 8px 12px;
    white-space: nowrap;
}

.notice-period-filter {
    align-items: end;
    display: flex;
    gap: 10px;
}

.notice-filter-field label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 4px;
    text-transform: uppercase;
}

.notice-filter-field select {
    min-width: 130px;
}

.notice-layout {
    display: grid;
    gap: 18px;
    grid-template-areas: "list detail";
    grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
    width: 100%;
}

.notice-list,
.notice-detail {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    min-height: 520px;
}

.notice-list {
    grid-area: list;
    max-height: calc(100vh - 220px);
    overflow: hidden auto;
}

.notice-list-item {
    background: transparent;
    border: 0;
    border-bottom: 1px solid var(--bs-border-color);
    color: var(--bs-body-color);
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 15px 16px;
    text-align: left;
    width: 100%;
}

.notice-list-item:hover,
.notice-list-item.active {
    background: rgba(13, 110, 253, 0.08);
}

.notice-list-item.unseen {
    border-left: 4px solid #0d6efd;
}

.notice-list-item__top {
    align-items: center;
    display: flex;
    gap: 8px;
    justify-content: space-between;
}

.notice-list-item__type {
    font-weight: 700;
}

.notice-list-item__action,
.notice-list-item__meta {
    color: var(--bs-secondary-color);
    font-size: 0.88rem;
}

.notice-unseen-pill {
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 4px 8px;
    text-transform: uppercase;
}

.notice-unseen-pill {
    background: #0d6efd;
    color: #fff;
}

.notice-detail {
    align-self: start;
    grid-area: detail;
    max-height: calc(100vh - 220px);
    overflow: auto;
    padding: 22px;
    position: sticky;
    top: 96px;
}

.notice-detail__header {
    align-items: center;
    border-bottom: 1px solid var(--bs-border-color);
    display: flex;
    gap: 16px;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 0 48px 18px 0;
    position: relative;
}

.notice-eyebrow {
    color: var(--bs-primary);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.notice-count {
    background: #ffc107;
    border-radius: 999px;
    color: #1f2937;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 8px 12px;
    margin-left: auto;
    white-space: nowrap;
}

.notice-status {
    background: #dc3545;
    border-radius: 50%;
    height: 10px;
    position: absolute;
    right: 0;
    top: 0;
    width: 10px;
}

.notice-status.seen {
    background: #198754;
}

.notice-section + .notice-section {
    margin-top: 20px;
}

.notice-section h6 {
    font-weight: 700;
    margin-bottom: 8px;
}

.notice-state,
.notice-empty {
    align-items: center;
    color: var(--bs-secondary-color);
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: center;
    min-height: 260px;
    padding: 24px;
    text-align: center;
}

.notice-empty i {
    color: var(--bs-primary);
    font-size: 2.2rem;
}

.detail-table {
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    overflow: hidden;
}

.detail-row {
    display: grid;
    gap: 12px;
    grid-template-columns: 130px 190px minmax(0, 1fr);
    padding: 12px 14px;
}

.detail-row + .detail-row {
    border-top: 1px solid var(--bs-border-color);
}

.detail-row--head {
    background: var(--bs-secondary-bg);
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
}

@media (max-width: 991.98px) {
    .notice-toolbar,
    .notice-detail__header {
        align-items: stretch;
        flex-direction: column;
    }

    .notice-period-filter {
        align-items: stretch;
        flex-wrap: wrap;
    }

    .notice-filter-field {
        flex: 1 1 140px;
    }

    .notice-filter-field select {
        width: 100%;
    }

    .notice-layout {
        grid-template-areas:
            "list"
            "detail";
        grid-template-columns: 1fr;
    }

    .notice-list,
    .notice-detail {
        max-height: none;
        min-height: auto;
        overflow: visible;
    }

    .notice-detail {
        position: static;
    }

    .detail-row {
        grid-template-columns: 1fr;
    }
}
</style>
