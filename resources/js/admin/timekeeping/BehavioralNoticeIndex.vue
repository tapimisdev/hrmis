<template>
    <div class="admin-behavioral-page">
        <div class="notice-filter-card">
            <div class="filter-field employee-search-field">
                <label for="behaviorEmployee">Employee</label>
                <input
                    id="behaviorEmployee"
                    v-model="employeeSearch"
                    type="search"
                    class="form-control"
                    placeholder="Search employee name or number"
                    autocomplete="off"
                    @focus="showEmployeeSuggestions = true"
                    @input="handleEmployeeSearchInput"
                    @blur="hideEmployeeSuggestions"
                >
                <div v-if="showEmployeeSuggestions" class="employee-suggestions">
                    <button type="button" class="employee-suggestion" @mousedown.prevent="selectEmployee(null)">
                        All Employees
                    </button>
                    <button
                        v-for="employee in filteredEmployees"
                        :key="`${employee.user_id}-${employee.employee_no}`"
                        type="button"
                        class="employee-suggestion"
                        @mousedown.prevent="selectEmployee(employee)"
                    >
                        <strong>{{ employee.name }}</strong>
                        <span>{{ employee.employee_no }}</span>
                    </button>
                    <span v-if="filteredEmployees.length === 0" class="employee-suggestion-empty">
                        No employees found
                    </span>
                </div>
            </div>

            <div class="filter-field">
                <label for="behaviorViolationType">Behavioral Type</label>
                <select id="behaviorViolationType" v-model="filters.violation_type" class="form-control" @change="handleFilterChange()">
                    <option value="">All Behavioral Types</option>
                    <option v-for="type in violationTypes" :key="type" :value="type">
                        {{ type }}
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="behaviorMonth">Month</label>
                <select id="behaviorMonth" v-model.number="filters.month" class="form-control" @change="handleFilterChange(true)">
                    <option v-for="(month, index) in months" :key="month" :value="index + 1">
                        {{ month }}
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="behaviorYear">Year</label>
                <select id="behaviorYear" v-model.number="filters.year" class="form-control" @change="handleFilterChange(true)">
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>
        </div>

        <div class="notice-summary">
            <strong>{{ notices.length }}</strong>
            <span>behavioral notice{{ notices.length === 1 ? '' : 's' }} for {{ periodLabel }}</span>
        </div>

        <div class="notice-layout">
            <div class="notice-list">
                <div v-if="loading" class="notice-state">
                    <i class="fa fa-spinner fa-spin"></i>
                    <span>Loading behavioral notices...</span>
                </div>

                <div v-else-if="notices.length === 0" class="notice-state">
                    <i class="fa-regular fa-circle-check"></i>
                    <span>No behavioral notices found for the selected filters.</span>
                </div>

                <template v-else>
                    <button
                        v-for="notice in notices"
                        :key="notice.id"
                        :ref="`noticeItem-${notice.id}`"
                        type="button"
                        class="notice-list-item"
                        :class="{ active: selectedNotice?.id === notice.id }"
                        @click="selectNotice(notice)"
                    >
                        <span class="notice-list-item__employee">{{ notice.employee_name }}</span>
                        <span class="notice-list-item__meta">{{ notice.employee_no }}</span>
                        <span class="notice-list-item__type">{{ notice.violation_type }}</span>
                        <span class="notice-list-item__action">{{ notice.action_name }}</span>
                    </button>
                </template>
            </div>

            <section class="notice-detail">
                <div v-if="!selectedNotice" class="notice-empty">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <h5>Select a behavioral notice</h5>
                    <p class="text-muted mb-0">Choose a record from the list to view the full monitoring details.</p>
                </div>

                <template v-else>
                    <div class="notice-detail__header">
                        <div>
                            <span class="notice-eyebrow">Behavioral Notice</span>
                            <h4 class="mb-1">{{ selectedNotice.violation_type }}</h4>
                            <p class="text-muted mb-0">{{ selectedNotice.employee_name }} - {{ selectedNotice.employee_no }}</p>
                        </div>
                        <span class="notice-count">
                            {{ selectedNotice.occurrence_count }}
                            {{ Number(selectedNotice.occurrence_count) === 1 ? 'Occurrence' : 'Occurrences' }}
                        </span>
                    </div>

                    <div class="notice-section">
                        <h6>Action / Notice</h6>
                        <p class="mb-0">{{ selectedNotice.action_name }}</p>
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
                            No details were attached to this record.
                        </div>
                        <div v-else class="detail-table">
                            <div class="detail-row detail-row--head">
                                <span>Date</span>
                                <span>Type</span>
                                <span>Details</span>
                            </div>
                            <div
                                v-for="(item, index) in detailItems"
                                :key="`admin-notice-detail-${selectedNotice.id}-${index}`"
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
    name: "AdminBehavioralNoticeIndex",
    data() {
        const currentYear = new Date().getFullYear();

        return {
            employees: [],
            employeeSearch: "",
            showEmployeeSuggestions: false,
            notices: [],
            selectedNotice: null,
            loading: false,
            periodLabel: "",
            violationTypes: [
                "Tardiness / Late",
                "Habitual Tardiness",
                "Habitual Tardiness - Consecutive",
                "Undertime",
                "Frequent Undertime",
                "Frequent Undertime - Consecutive",
                "Unauthorized Absence",
                "Habitual Absenteeism",
                "Habitual Absenteeism - Consecutive",
                "Discrepancy / Missing Timelog",
                "Missed Break Log",
            ],
            months: [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December",
            ],
            years: Array.from({ length: 7 }, (_, index) => currentYear - index),
            filters: {
                employee_no: "",
                month: new Date().getMonth() + 1,
                year: currentYear,
                violation_type: "",
            },
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
        filteredEmployees() {
            const search = this.employeeSearch.trim().toLowerCase();

            if (!search) {
                return this.employees.slice(0, 20);
            }

            return this.employees
                .filter((employee) =>
                    String(employee.name || "").toLowerCase().includes(search)
                    || String(employee.employee_no || "").toLowerCase().includes(search)
                )
                .slice(0, 20);
        },
    },
    mounted() {
        this.applyFiltersFromUrl();
        window.addEventListener("popstate", this.handlePopState);
        this.fetchEmployees();
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

            this.filters.employee_no = params.get("employee_no") || "";
            this.filters.violation_type = params.get("violation_type") || "";

            if (month >= 1 && month <= 12) {
                this.filters.month = month;
            }

            if (this.years.includes(year)) {
                this.filters.year = year;
            }
        },
        async fetchEmployees() {
            const { data } = await axios.get("/admin/timekeeping/behavioral-notices/employees");
            this.employees = data.employees || [];
            this.syncEmployeeSearch();
        },
        syncEmployeeSearch() {
            const employee = this.employees.find((item) => item.employee_no === this.filters.employee_no);
            this.employeeSearch = employee ? `${employee.name} - ${employee.employee_no}` : "";
        },
        handleEmployeeSearchInput() {
            this.showEmployeeSuggestions = true;

            if (this.employeeSearch.trim() === "" && this.filters.employee_no !== "") {
                this.filters.employee_no = "";
                this.handleFilterChange(true);
            }
        },
        selectEmployee(employee) {
            this.filters.employee_no = employee?.employee_no || "";
            this.employeeSearch = employee ? `${employee.name} - ${employee.employee_no}` : "";
            this.showEmployeeSuggestions = false;
            this.handleFilterChange(true);
        },
        hideEmployeeSuggestions() {
            window.setTimeout(() => {
                this.showEmployeeSuggestions = false;
                this.syncEmployeeSearch();
            }, 100);
        },
        async fetchNotices() {
            this.loading = true;
            const linkedId = new URLSearchParams(window.location.search).get("id");

            try {
                const { data } = await axios.get("/admin/timekeeping/behavioral-notices/data", {
                    params: {
                        ...this.filters,
                        id: linkedId,
                    },
                });

                this.notices = data.behavioral_notices || [];
                this.periodLabel = data.period || "";

                if (data.filters) {
                    this.filters.month = data.filters.month || this.filters.month;
                    this.filters.year = data.filters.year || this.filters.year;
                }

                this.updateFilterUrl(true, false);
                this.selectInitialNotice();
            } finally {
                this.loading = false;
            }
        },
        handleFilterChange(clearViolationType = false) {
            if (clearViolationType) {
                this.filters.violation_type = "";
            }

            this.updateFilterUrl();
            this.fetchNotices();
        },
        handlePopState() {
            this.applyFiltersFromUrl();
            this.syncEmployeeSearch();
            this.fetchNotices();
        },
        selectInitialNotice() {
            const linkedNotice = this.noticeFromUrl();
            this.selectedNotice = linkedNotice || this.notices[0] || null;

            if (this.selectedNotice) {
                this.updateNoticeUrl(this.selectedNotice.id, true);
                this.scrollSelectedNoticeIntoView();
            } else {
                this.clearNoticeUrl();
            }
        },
        noticeFromUrl() {
            const noticeId = new URLSearchParams(window.location.search).get("id");

            if (!noticeId) {
                return null;
            }

            return this.notices.find((notice) => Number(notice.id) === Number(noticeId)) || null;
        },
        selectNotice(notice) {
            this.selectedNotice = notice;
            this.updateNoticeUrl(notice.id);
            this.scrollSelectedNoticeIntoView();
        },
        scrollSelectedNoticeIntoView() {
            this.$nextTick(() => {
                const selectedId = this.selectedNotice?.id;

                if (!selectedId) {
                    return;
                }

                const itemRef = this.$refs[`noticeItem-${selectedId}`];
                const item = Array.isArray(itemRef) ? itemRef[0] : itemRef;

                item?.scrollIntoView({
                    block: "center",
                    behavior: "smooth",
                });
            });
        },
        updateNoticeUrl(id, replace = false) {
            const url = new URL(window.location.href);
            url.searchParams.set("id", id);

            const method = replace ? "replaceState" : "pushState";
            window.history[method]({}, "", url.toString());
        },
        updateFilterUrl(replace = false, clearNotice = true) {
            const url = new URL(window.location.href);
            const optionalFilters = ["employee_no", "violation_type"];

            url.searchParams.set("month", this.filters.month);
            url.searchParams.set("year", this.filters.year);

            if (clearNotice) {
                url.searchParams.delete("id");
            }

            optionalFilters.forEach((key) => {
                if (this.filters[key]) {
                    url.searchParams.set(key, this.filters[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });

            const method = replace ? "replaceState" : "pushState";
            window.history[method]({}, "", url.toString());
        },
        clearNoticeUrl() {
            const url = new URL(window.location.href);
            url.searchParams.delete("id");
            window.history.replaceState({}, "", url.toString());
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
.admin-behavioral-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.notice-filter-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(260px, 1fr) minmax(150px, 220px) minmax(220px, 280px) minmax(140px, 180px);
    padding: 18px;
}

.filter-field label {
    display: block;
    font-weight: 700;
    margin-bottom: 8px;
}

.employee-search-field {
    position: relative;
}

.employee-suggestions {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 6px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
    left: 0;
    max-height: 280px;
    overflow-y: auto;
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    z-index: 20;
}

.employee-suggestion {
    background: transparent;
    border: 0;
    color: var(--bs-body-color);
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 10px 12px;
    text-align: left;
    width: 100%;
}

.employee-suggestion:hover {
    background: rgba(13, 110, 253, 0.08);
}

.employee-suggestion span,
.employee-suggestion-empty {
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
}

.employee-suggestion-empty {
    display: block;
    padding: 12px;
}

.notice-summary {
    align-items: center;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    display: flex;
    gap: 8px;
    padding: 12px 16px;
}

.notice-layout {
    align-items: start;
    display: grid;
    gap: 18px;
    grid-template-columns: minmax(300px, 380px) minmax(0, 1fr);
}

.notice-list,
.notice-detail {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    height: clamp(600px, calc(100vh - 545px), 720px);
    min-height: 600px;
}

.notice-list {
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
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

.notice-list-item__employee,
.notice-list-item__type {
    font-weight: 700;
}

.notice-list-item__meta,
.notice-list-item__action {
    color: var(--bs-secondary-color);
    font-size: 0.88rem;
}

.notice-detail {
    align-self: start;
    height: clamp(600px, calc(100vh - 545px), 720px);
    overflow: auto;
    overscroll-behavior: contain;
    padding: 22px;
    position: sticky;
    top: 86px;
}

.notice-detail__header {
    align-items: flex-start;
    border-bottom: 1px solid var(--bs-border-color);
    display: flex;
    gap: 16px;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 18px;
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
    font-weight: 800;
    padding: 8px 12px;
    white-space: nowrap;
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
    grid-template-columns: 140px 200px minmax(0, 1fr);
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
    .notice-filter-card,
    .notice-layout {
        grid-template-columns: 1fr;
    }

    .notice-list,
    .notice-detail {
        height: auto;
        min-height: auto;
    }

    .notice-detail {
        max-height: none;
        overflow: visible;
        position: static;
    }

    .notice-detail__header {
        align-items: stretch;
        flex-direction: column;
    }

    .detail-row {
        grid-template-columns: 1fr;
    }
}
</style>
