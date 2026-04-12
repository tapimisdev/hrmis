<template>
    <div class="card rounded-4 p-3 chief-surface">
        <div v-if="isAnyLoading" class="chief-page-loader">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="small text-muted">{{ loadingMessage }}</div>
        </div>

        <ul class="nav nav-tabs chief-tabs" role="tablist">
            <li class="nav-item" role="presentation" v-for="tab in tabs" :key="tab.key">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === tab.key }"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.key"
                    :disabled="isAnyLoading"
                    @click="showTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div v-show="activeTab === 'overview'" class="chief-tab-pane">
                <div class="row g-4">
                    <div class="col-12 col-xl-7">
                        <div class="card rounded-4 p-3 h-100 chief-tab-card" :class="{ 'chief-card-loading': isLoading('overview') }">
                            <div v-if="isLoading('overview')" class="chief-tab-loader">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="small text-muted">Loading overview data...</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">Recent Applications Snapshot</h5>
                                    <p class="text-muted mb-0">Latest submissions from your division.</p>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table ref="overviewTable" class="table table-striped align-middle chief-table">
                                    <thead>
                                        <tr class="text-uppercase">
                                            <th>Submitted</th>
                                            <th>Type</th>
                                            <th>Employee</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="application in overview.applications" :key="application.application_no + application.submitted_order">
                                            <td :data-order="application.submitted_order">{{ application.submitted }}</td>
                                            <td>{{ application.type }}</td>
                                            <td>{{ application.employee }}</td>
                                            <td>
                                                <span class="badge" :class="'bg-' + application.status_class">
                                                    {{ application.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-xl-12">
                                <div class="card rounded-4 p-3 h-100">
                                    <div class="chief-kpi-label">Top Late</div>
                                    <div class="fw-bold">{{ overview.highlight_cards.top_late.employee || '-' }}</div>
                                    <div class="text-muted">{{ overview.highlight_cards.top_late.value || 'No data yet.' }}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-12">
                                <div class="card rounded-4 p-3 h-100">
                                    <div class="chief-kpi-label">Top Ontime</div>
                                    <div class="fw-bold">{{ overview.highlight_cards.top_ontime.employee || '-' }}</div>
                                    <div class="text-muted">{{ overview.highlight_cards.top_ontime.value || 'No data yet.' }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card rounded-4 p-3 h-100">
                                    <div class="chief-kpi-label">Current Timelog Period</div>
                                    <div class="fw-bold">{{ overview.highlight_cards.period_label || periodLabel }}</div>
                                    <div class="text-muted">Use the Timelogs tab to jump to a previous or next month.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'applications'" class="chief-tab-pane">
                <div class="card rounded-4 p-3 chief-tab-card" :class="{ 'chief-card-loading': isApplicationsTableLoading }">
                    <div v-if="isApplicationsTableLoading" class="chief-tab-loader">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted">Loading applications...</div>
                    </div>
                    <div class="chief-filter-bar">
                        <div class="chief-filter-copy">
                            <div class="chief-section-eyebrow">Division Activity</div>
                            <h5 class="mb-1">Submitted Applications</h5>
                            <p class="text-muted mb-0">Recent submissions from employees inside your division.</p>
                            <div class="chief-inline-meta">
                                <span class="chief-meta-pill">
                                    <strong>{{ applicationsCount }}</strong>
                                    {{ applicationType ? applicationType + ' records' : 'total records' }}
                                </span>
                            </div>
                        </div>
                        <div class="chief-filter-panel">
                            <select
                                id="applicationTypeFilter"
                                v-model="applicationType"
                                class="form-select chief-filter-select"
                                :disabled="isAnyLoading"
                                @change="reloadApplications"
                            >
                                <option value="">All Application Types</option>
                                <option value="Leave">Leave</option>
                                <option value="Offset">Offset</option>
                                <option value="Overtime">Overtime</option>
                                <option value="Pass Slip">Pass Slip</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table ref="applicationsTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Submitted</th>
                                    <th>Type</th>
                                    <th>Application No.</th>
                                    <th>Employee</th>
                                    <th>Subject</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'timelogs'" class="chief-tab-pane">
                <div class="chief-filter-bar chief-timelog-hero">
                    <div class="chief-filter-copy">
                        <div class="chief-section-eyebrow">Timelog Monitor</div>
                        <h5 class="mb-1">Timelog Monitoring</h5>
                        <p class="text-muted mb-0">Switch between monthly rollups and day-level activity across your managed division.</p>
                        <div class="chief-inline-meta">
                            <span class="chief-meta-pill">
                                <strong>{{ timelogView === 'month' ? timelogs.summary_count : timelogs.daily_count }}</strong>
                                {{ timelogView === 'month' ? 'monthly records' : 'daily entries' }}
                            </span>
                            <span class="chief-meta-pill">
                                {{ timelogs.period_label || periodLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="chief-toolbar-form chief-timelog-toolbar">
                        <input type="hidden" name="tab" value="timelogs">
                        <button type="button" class="btn btn-outline-secondary chief-icon-btn" title="Previous month" :disabled="isAnyLoading" @click="shiftMonth(-1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <input v-model="selectedMonth" type="month" name="month" class="form-control" :disabled="isAnyLoading" @change="applyTimelogMonth">
                        <button type="button" class="btn btn-outline-secondary chief-icon-btn" title="Next month" :disabled="isAnyLoading" @click="shiftMonth(1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="card rounded-4 p-3 mb-4 chief-tab-card" :class="{ 'chief-card-loading': isTimelogStatsLoading }">
                    <div v-if="isTimelogStatsLoading" class="chief-tab-loader">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted">Loading timelog data...</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <div class="chief-section-eyebrow">Timelog Stats</div>
                            <h5 class="mb-1">Top 10 by Category</h5>
                            <p class="text-muted mb-0">{{ timelogs.period_label || periodLabel }}</p>
                        </div>
                        <div class="chief-stat-switch" role="tablist" aria-label="Timelog stat categories">
                            <button
                                v-for="tab in timelogStatTabs"
                                :key="tab.key"
                                type="button"
                                class="chief-stat-switch-btn"
                                :class="{ active: activeTimelogStatTab === tab.key }"
                                :disabled="isAnyLoading"
                                @click="setTimelogStatTab(tab.key)"
                            >
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table ref="timelogStatsTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Employee</th>
                                    <th>Unit</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="card rounded-4 p-3 chief-tab-card" :class="{ 'chief-card-loading': isTimelogDetailLoading }">
                    <div
                        v-if="isTimelogDetailLoading"
                        class="chief-tab-loader"
                    >
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted">
                            {{ timelogView === 'month' ? 'Loading monthly timelog summary...' : 'Loading daily timelog entries...' }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <div class="chief-section-eyebrow">{{ timelogView === 'month' ? 'Per Month View' : 'Per Day View' }}</div>
                            <h5 class="mb-1">{{ timelogView === 'month' ? 'Monthly Timelog Summary' : 'Daily Timelog Entries' }}</h5>
                            <p class="text-muted mb-0">
                                {{ timelogView === 'month'
                                    ? 'One row per employee with monthly totals for logs, late, undertime, overtime, leave, offset, SO, and LTO.'
                                    : 'One row per employee per day with logs, work hours, and remarks.' }}
                            </p>
                        </div>
                        <div class="chief-view-switch" role="tablist" aria-label="Timelog view switch">
                            <button
                                type="button"
                                class="chief-view-switch-btn"
                                :class="{ active: timelogView === 'month' }"
                                :disabled="isAnyLoading"
                                @click="setTimelogView('month')"
                            >
                                Month
                            </button>
                            <button
                                type="button"
                                class="chief-view-switch-btn"
                                :class="{ active: timelogView === 'day' }"
                                :disabled="isAnyLoading"
                                @click="setTimelogView('day')"
                            >
                                Day
                            </button>
                        </div>
                    </div>
                    <div v-show="timelogView === 'month'" class="table-responsive">
                        <table ref="timelogSummaryTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Employee</th>
                                    <th>Unit</th>
                                    <th>Logs</th>
                                    <th>Late</th>
                                    <th>Undertime</th>
                                    <th>OT</th>
                                    <th>Leave</th>
                                    <th>Offset</th>
                                    <th>SO</th>
                                    <th>LTO</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div v-show="timelogView === 'day'" class="table-responsive">
                        <table ref="timelogDailyTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Unit</th>
                                    <th>Clock In</th>
                                    <th>Break Out</th>
                                    <th>Break In</th>
                                    <th>Time Out</th>
                                    <th>OT</th>
                                    <th>Late / UT</th>
                                    <th>Worked Hrs</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'credits'" class="chief-tab-pane">
                <div class="card rounded-4 p-3 mb-4 chief-tab-card" :class="{ 'chief-card-loading': isLeaveCreditsLoading }">
                    <div v-if="isLeaveCreditsLoading" class="chief-tab-loader">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted">Loading credits...</div>
                    </div>
                    <div class="mb-3">
                        <div>
                            <h5 class="mb-1">Credits</h5>
                            <p class="text-muted mb-0">Latest credits per employee, grouped by credit type.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table ref="leaveCreditsTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Employee No</th>
                                    <th>Name</th>
                                    <th v-for="column in leaveCreditColumns" :key="column.key">{{ column.label }}</th>
                                    <th>Offset</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="errorMessage" class="alert alert-danger mt-3 mb-0">
                {{ errorMessage }}
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ChiefCornerIndex",
    props: {
        initialTab: {
            type: String,
            default: "overview",
        },
        selectedMonthProp: {
            type: String,
            required: true,
        },
        periodLabel: {
            type: String,
            required: true,
        },
        tabEndpointTemplate: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            tabs: [
                { key: "overview", label: "Overview" },
                { key: "applications", label: "Applications" },
                { key: "timelogs", label: "Timelogs" },
                { key: "credits", label: "Credits" },
            ],
            timelogStatTabs: [
                { key: "lates", label: "Lates" },
                { key: "undertime", label: "Undertime" },
                { key: "leave", label: "Leave" },
                { key: "offsets", label: "Offsets" },
                { key: "so", label: "SO" },
                { key: "lto", label: "LTO" },
            ],
            activeTab: this.initialTab,
            timelogView: localStorage.getItem("chief-corner-timelog-view") || "month",
            activeTimelogStatTab: localStorage.getItem("chief-corner-timelog-stat-tab") || "lates",
            selectedMonth: this.selectedMonthProp,
            applicationType: localStorage.getItem("chief-corner-application-type") || "",
            applicationsCount: 0,
            tableBusyCount: 0,
            loadingTables: {
                applications: false,
                timelogStats: false,
                timelogSummary: false,
                timelogDaily: false,
                leaveCredits: false,
            },
            loading: {
                overview: false,
                applications: false,
                timelogs: false,
                credits: false,
            },
            loadedTabs: {
                overview: false,
                applications: false,
                timelogs: false,
                credits: false,
            },
            errorMessage: "",
            overview: {
                applications: [],
                highlight_cards: {
                    top_late: {},
                    top_ontime: {},
                    period_label: "",
                },
            },
            timelogs: {
                period_label: "",
                stats: {},
                daily_count: 0,
                summary_count: 0,
            },
            leaveCreditColumns: [],
        };
    },
    computed: {
        isAnyLoading() {
            return Object.values(this.loading).some(Boolean);
        },
        loadingMessage() {
            if (this.loading.applications) {
                return "Refreshing applications...";
            }

            if (this.loading.timelogs) {
                return "Refreshing timelog insights...";
            }

            if (this.loading.credits) {
                return "Refreshing credits...";
            }

            if (this.loading.overview) {
                return "Refreshing overview...";
            }

            return "Loading...";
        },
        isTimelogDetailLoading() {
            return this.timelogView === "month"
                ? this.loadingTables.timelogSummary
                : this.loadingTables.timelogDaily;
        },
        isApplicationsTableLoading() {
            return this.loading.applications || this.loadingTables.applications;
        },
        isTimelogStatsLoading() {
            return this.loading.timelogs || this.loadingTables.timelogStats;
        },
        isLeaveCreditsLoading() {
            return this.loading.credits || this.loadingTables.leaveCredits;
        },
    },
    mounted() {
        const url = new URL(window.location.href);
        const queryTab = url.searchParams.get("tab");
        const savedTab = localStorage.getItem("chief-corner-active-tab");

        if (!queryTab && savedTab && this.tabs.some((tab) => tab.key === savedTab)) {
            this.activeTab = savedTab;
        }

        this.syncUrl();
        this.fetchTab(this.activeTab, true);
    },
    beforeUnmount() {
        this.destroyAllDataTables();
    },
    methods: {
        isLoading(tab) {
            return this.loading[tab];
        },
        tabEndpoint(tab) {
            return this.tabEndpointTemplate.replace("__TAB__", tab);
        },
        async fetchTab(tab, force = false) {
            if (this.loadedTabs[tab] && !force) {
                return;
            }

            this.loading[tab] = true;
            this.errorMessage = "";

            try {
                const params = {};

                if (tab === "applications") {
                    params.application_type = this.applicationType;
                }

                if (tab === "overview" || tab === "timelogs") {
                    params.month = this.selectedMonth;
                }

                const response = await axios.get(this.tabEndpoint(tab), { params });
                const data = response.data;

                this.applyTabData(tab, data);

                this.loadedTabs[tab] = true;
                this.syncUrl();
                await this.$nextTick();
            } catch (error) {
                console.error(`Chief Corner ${tab} load failed`, error);
                this.errorMessage = error.response?.data?.message || "Something went wrong while loading this tab.";
            } finally {
                this.loading[tab] = false;
            }

            try {
                this.initializeDataTables();
                this.adjustVisibleTables();
            } catch (error) {
                console.error(`Chief Corner ${tab} table enhancement failed`, error);
            }
        },
        async showTab(tab) {
            this.activeTab = tab;
            localStorage.setItem("chief-corner-active-tab", tab);
            this.syncUrl();
            await this.fetchTab(tab);
            await this.$nextTick();
            this.adjustVisibleTables();
        },
        async reloadApplications() {
            localStorage.setItem("chief-corner-application-type", this.applicationType);
            this.loadedTabs.applications = false;
            await this.fetchTab("applications", true);
        },
        async applyTimelogMonth() {
            this.loadedTabs.timelogs = false;
            this.loadedTabs.overview = false;
            await this.fetchTab("timelogs", true);

            if (this.activeTab === "timelogs") {
                await this.$nextTick();
                this.reloadDataTable("timelogStats");
                this.reloadDataTable("timelogSummary");
                this.reloadDataTable("timelogDaily");
                this.adjustVisibleTables();
            }

            if (this.activeTab === "overview") {
                await this.fetchTab("overview", true);
            }
        },
        async shiftMonth(delta) {
            const [year, month] = this.selectedMonth.split("-").map(Number);
            const nextDate = new Date(year, month - 1 + delta, 1);
            const nextYear = nextDate.getFullYear();
            const nextMonth = String(nextDate.getMonth() + 1).padStart(2, "0");
            this.selectedMonth = `${nextYear}-${nextMonth}`;
            await this.applyTimelogMonth();
        },
        syncUrl() {
            const url = new URL(window.location.href);
            url.searchParams.set("tab", this.activeTab);
            url.searchParams.set("month", this.selectedMonth);
            window.history.replaceState({}, "", url);
        },
        applyTabData(tab, data) {
            if (tab === "overview") {
                this.overview = data;
            }

            if (tab === "applications") {
                this.applicationsCount = data.applications_count || 0;
            }

            if (tab === "timelogs") {
                this.timelogs = data;
                this.selectedMonth = data.selected_month || this.selectedMonth;
                if (!this.timelogStatTabs.some((item) => item.key === this.activeTimelogStatTab)) {
                    this.activeTimelogStatTab = "lates";
                }
            }

            if (tab === "credits") {
                this.leaveCreditColumns = data.leave_credit_columns || [];
            }
        },
        initializeDataTables() {
            this.rebuildDataTable("overview", this.$refs.overviewTable, {
                pageLength: 10,
                order: [[0, "desc"]],
                columnDefs: [{ targets: [3], orderable: false }],
            });

            this.rebuildDataTable("applications", this.$refs.applicationsTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[0, "desc"]],
                ajax: this.buildAjaxHandler("applications", "applications"),
                columns: [
                    {
                        data: "submitted",
                        name: "submitted_order",
                        render: (data, type, row) => type === "sort" || type === "type"
                            ? row.submitted_order
                            : data,
                    },
                    { data: "type" },
                    { data: "application_no" },
                    { data: "employee" },
                    { data: "subject" },
                    { data: "schedule" },
                    {
                        data: "status",
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => type === "display"
                            ? `<span class="badge bg-${row.status_class}">${data}</span>`
                            : data,
                    },
                ],
            });

            this.buildTimelogStatsTable();

            this.rebuildDataTable("timelogSummary", this.$refs.timelogSummaryTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                order: [[3, "desc"]],
                ajax: this.buildAjaxHandler("timelogs", "timelogSummary"),
                columns: [
                    {
                        data: "employee",
                        name: "employee_order",
                        render: (data, type, row) => type === "display"
                            ? `<div class="fw-bold">${data}</div><div class="small text-muted">${row.position}</div>`
                            : row.employee_order || `${data} ${row.position}`,
                    },
                    {
                        data: "unit",
                        name: "unit_order",
                        render: (data, type, row) => type === "sort" || type === "type"
                            ? row.unit_order
                            : data,
                    },
                    this.numericColumn("logs", "logs_order"),
                    this.numericColumn("late", "late_order"),
                    this.numericColumn("undertime", "undertime_order"),
                    this.numericColumn("overtime", "overtime_order"),
                    this.numericColumn("leave", "leave_order"),
                    this.numericColumn("offset", "offset_order"),
                    this.numericColumn("so", "so_order"),
                    this.numericColumn("lto", "lto_order"),
                ],
            });

            this.rebuildDataTable("timelogDaily", this.$refs.timelogDailyTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                order: [[0, "desc"]],
                ajax: this.buildAjaxHandler("timelogs", "timelogDaily"),
                columns: [
                    {
                        data: "date",
                        name: "date_order",
                        render: (data, type, row) => type === "display"
                            ? `<div class="fw-semibold">${data}</div><div class="small text-muted">${row.day_name}</div>`
                            : row.date_order,
                    },
                    {
                        data: "employee",
                        name: "employee_order",
                        render: (data, type, row) => type === "display"
                            ? `<div class="fw-bold">${data}</div><div class="small text-muted">${row.position}</div>`
                            : row.employee_order || `${data} ${row.position}`,
                    },
                    {
                        data: "unit",
                        name: "unit_order",
                        render: (data, type, row) => type === "sort" || type === "type"
                            ? row.unit_order
                            : data,
                    },
                    this.numericColumn("time_in", "time_in_order"),
                    this.numericColumn("break_out", "break_out_order"),
                    this.numericColumn("break_in", "break_in_order"),
                    this.numericColumn("time_out", "time_out_order"),
                    this.numericColumn("ot", "overtime_order"),
                    this.numericColumn("late_undertime", "late_undertime_order"),
                    this.numericColumn("worked_hours", "worked_hours_order"),
                    { data: "remarks" },
                ],
            });

            this.buildLeaveCreditsTable();
        },
        buildTimelogStatsTable() {
            this.rebuildDataTable("timelogStats", this.$refs.timelogStatsTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                order: [],
                searching: false,
                ajax: this.buildAjaxHandler("timelogs", "timelogStats"),
                columns: [
                    {
                        data: "employee",
                        name: "employee_order",
                        render: (data, type, row) => type === "display"
                            ? `<div class="fw-bold">${data}</div><div class="small text-muted">${row.position}</div>`
                            : `${data} ${row.position}`,
                    },
                    { data: "unit", name: "unit" },
                    { data: "value", name: "value" },
                ],
            });
        },
        buildLeaveCreditsTable() {
            this.rebuildDataTable("leaveCredits", this.$refs.leaveCreditsTable, {
                processing: true,
                serverSide: true,
                pageLength: 20,
                order: [[0, "asc"]],
                ajax: this.buildAjaxHandler("credits", "leaveCredits"),
                columns: [
                    {
                        data: "employee_no",
                        name: "employee_no_order",
                    },
                    {
                        data: "employee",
                        name: "employee_order",
                        render: (data, type, row) => type === "sort" || type === "type"
                            ? row.employee_order
                            : data,
                    },
                    ...this.leaveCreditColumns.map((column) => this.numericColumn(column.key, `${column.key}_order`)),
                    this.numericColumn("offset", "offset_order"),
                ],
            });
        },
        numericColumn(dataKey, orderKey) {
            return {
                data: dataKey,
                name: orderKey,
                render: (data, type, row) => type === "sort" || type === "type"
                    ? row[orderKey]
                    : data,
            };
        },
        buildAjaxHandler(tab, table) {
            return async (data, callback) => {
                this.tableBusyCount += 1;
                if (Object.prototype.hasOwnProperty.call(this.loadingTables, table)) {
                    this.loadingTables[table] = true;
                }

                try {
                    const params = {
                        datatable: 1,
                        table,
                        draw: data.draw,
                        start: data.start,
                        length: data.length,
                        search: {
                            value: data.search?.value || "",
                            regex: data.search?.regex || false,
                        },
                        order: Array.isArray(data.order) ? data.order : [],
                        columns: Array.isArray(data.columns) ? data.columns.map((column) => ({
                            data: column.data,
                            name: column.name,
                            searchable: column.searchable,
                            orderable: column.orderable,
                            search: {
                                value: column.search?.value || "",
                                regex: column.search?.regex || false,
                            },
                        })) : [],
                    };

                    if (tab === "applications") {
                        params.application_type = this.applicationType;
                    }

                    if (tab === "timelogs") {
                        params.month = this.selectedMonth;
                        params.stat = this.activeTimelogStatTab;
                    }

                    const response = await axios.get(this.tabEndpoint(tab), { params });
                    this.applyDataTableMeta(table, response.data);
                    callback(response.data);
                } catch (error) {
                    console.error(`Chief Corner ${table} table load failed`, error);
                    this.errorMessage = error.response?.data?.message || "Something went wrong while loading table data.";
                    callback({
                        draw: data.draw,
                        recordsTotal: 0,
                        recordsFiltered: 0,
                        data: [],
                    });
                } finally {
                    if (Object.prototype.hasOwnProperty.call(this.loadingTables, table)) {
                        this.loadingTables[table] = false;
                    }
                    this.tableBusyCount = Math.max(0, this.tableBusyCount - 1);
                }
            };
        },
        rebuildDataTable(key, element, options = {}) {
            if (!element || typeof window.$ === "undefined" || !window.$.fn?.DataTable) {
                return;
            }

            const $table = window.$(element);
            const isAjaxTable = Boolean(options.ajax);
            const userInitComplete = options.initComplete;

            if (window.$.fn.DataTable.isDataTable(element)) {
                $table.DataTable().destroy();
            }

            if (isAjaxTable) {
                $table.find("tbody").empty();
            }

            $table.DataTable({
                autoWidth: false,
                scrollX: true,
                searchDelay: 400,
                language: {
                    search: "",
                    searchPlaceholder: "Search records",
                    lengthMenu: "_MENU_",
                    emptyTable: "No records found.",
                    processing: "Loading records...",
                },
                initComplete(...args) {
                    if (typeof userInitComplete === "function") {
                        userInitComplete.apply(this, args);
                    }

                    const api = this.api();
                    const $container = window.$(api.table().container());
                    const $searchInput = $container
                        .find("input[type='search'], .dataTables_filter input, .dt-search input")
                        .first();

                    if (!$searchInput.length) {
                        return;
                    }

                    let searchTimer = null;

                    $searchInput.off(".DT");
                    $searchInput.off(".chiefSearch");
                    $searchInput.on("input.chiefSearch keyup.chiefSearch search.chiefSearch change.chiefSearch", function () {
                        const value = this.value;

                        window.clearTimeout(searchTimer);
                        searchTimer = window.setTimeout(() => {
                            if (api.search() !== value) {
                                api.search(value).draw();
                            }
                        }, 400);
                    });
                },
                ...options,
            });
        },
        applyDataTableMeta(table, payload) {
            if (typeof payload?.recordsTotal !== "number") {
                return;
            }

            if (table === "applications") {
                this.applicationsCount = payload.recordsTotal;
            }

            if (table === "timelogSummary") {
                this.timelogs.summary_count = payload.recordsTotal;
            }

            if (table === "timelogDaily") {
                this.timelogs.daily_count = payload.recordsTotal;
            }
        },
        reloadDataTable(key) {
            const element = this.$refs[key];

            if (!element || typeof window.$ === "undefined" || !window.$.fn?.DataTable || !window.$.fn.DataTable.isDataTable(element)) {
                return;
            }

            window.$(element).DataTable().ajax.reload(null, true);
        },
        destroyAllDataTables() {
            if (typeof window.$ === "undefined" || !window.$.fn?.DataTable) {
                return;
            }

            [
                this.$refs.overviewTable,
                this.$refs.applicationsTable,
                this.$refs.timelogStatsTable,
                this.$refs.timelogSummaryTable,
                this.$refs.timelogDailyTable,
                this.$refs.leaveCreditsTable,
            ].forEach((element) => {
                if (element && window.$.fn.DataTable.isDataTable(element)) {
                    window.$(element).DataTable().destroy();
                }
            });
        },
        adjustVisibleTables() {
            if (typeof window.$ === "undefined" || !window.$.fn?.dataTable?.tables) {
                return;
            }

            window.$.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        },
        setTimelogView(view) {
            this.timelogView = view;
            localStorage.setItem("chief-corner-timelog-view", view);
            this.$nextTick(() => {
                this.adjustVisibleTables();
            });
        },
        setTimelogStatTab(tab) {
            this.activeTimelogStatTab = tab;
            localStorage.setItem("chief-corner-timelog-stat-tab", tab);
            this.$nextTick(() => {
                this.buildTimelogStatsTable();
                this.adjustVisibleTables();
            });
        },
    },
};
</script>
