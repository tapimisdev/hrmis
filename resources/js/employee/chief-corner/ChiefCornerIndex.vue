<template>
    <div class="card rounded-4 p-3 chief-surface">
        <div v-if="isAnyLoading" class="chief-page-loader">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="small text-muted">{{ loadingMessage }}</div>
        </div>

        <div class="chief-filter-bar mb-3">
            <div class="chief-filter-copy">
                <div class="chief-section-eyebrow">Page Filters</div>
                <h5 class="mb-1">Month Context</h5>
                <p class="text-muted mb-0">Changing the month updates every tab using the same selected period.</p>
            </div>
            <div class="chief-toolbar-form chief-timelog-toolbar">
                <button type="button" class="btn btn-outline-secondary chief-icon-btn" title="Previous month" :disabled="isAnyLoading" @click="shiftMonth(-1)">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <input v-model="selectedMonth" type="month" class="form-control" :disabled="isAnyLoading" @change="applyGlobalMonthFilter">
                <button type="button" class="btn btn-outline-secondary chief-icon-btn" title="Next month" :disabled="isAnyLoading" @click="shiftMonth(1)">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
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

        <div class="chief-subtab-bar">
            <div>
                <div class="chief-section-eyebrow">Employee Type</div>
                <div class="text-muted small">Separate division employees by employment category.</div>
            </div>
            <div class="chief-stat-switch" role="tablist" aria-label="Employee type tabs">
                <button
                    v-for="tab in employeeTypeTabs"
                    :key="tab.key"
                    type="button"
                    class="chief-stat-switch-btn"
                    :class="{ active: activeEmployeeType === tab.key }"
                    :disabled="isAnyLoading"
                    @click="setEmployeeTypeTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

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
                                            <td>{{ uppercaseName(application.employee) }}</td>
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
                                <div class="card rounded-4 p-3 h-100 chief-tab-card" :class="{ 'chief-card-loading': isLoading('overview') }">
                                    <div v-if="isLoading('overview')" class="chief-tab-loader">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="small text-muted">Loading overview data...</div>
                                    </div>
                                    <div class="chief-kpi-label">Current Timelog Period</div>
                                    <div class="fw-bold">{{ overview.highlight_cards.period_label || periodLabel }}</div>
                                    <div class="text-muted">Use the filter above to view different data through out the period.</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-12">
                                <div class="card rounded-4 p-3 h-100 chief-tab-card" :class="{ 'chief-card-loading': isLoading('overview') }">
                                    <div v-if="isLoading('overview')" class="chief-tab-loader">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="small text-muted">Loading overview data...</div>
                                    </div>
                                    <div class="chief-kpi-label">Top Late</div>
                                    <div v-if="overview.highlight_cards.top_late.length" class="chief-kpi-list">
                                        <div v-for="(employee, index) in overview.highlight_cards.top_late" :key="`top-late-${employee.employee}-${index}`" class="chief-kpi-item">
                                            <div class="fw-bold">{{ index + 1 }}. {{ uppercaseName(employee.employee) }}</div>
                                            <div class="text-muted">{{ employee.value }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-muted">No data yet.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card rounded-4 p-3 h-100 chief-tab-card" :class="{ 'chief-card-loading': isLoading('overview') }">
                                    <div v-if="isLoading('overview')" class="chief-tab-loader">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="small text-muted">Loading overview data...</div>
                                    </div>
                                    <div class="chief-kpi-label">Top Ontime</div>
                                    <div v-if="overview.highlight_cards.top_ontime.length" class="chief-kpi-list">
                                        <div v-for="(employee, index) in overview.highlight_cards.top_ontime" :key="`top-ontime-${employee.employee}-${index}`" class="chief-kpi-item">
                                            <div class="fw-bold">{{ index + 1 }}. {{ uppercaseName(employee.employee) }}</div>
                                            <div class="text-muted">{{ employee.value }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-muted">No data yet.</div>
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
                        <span class="chief-meta-pill">{{ selectedMonth }}</span>
                    </div>
                </div>

                <div class="card rounded-4 p-3 chief-tab-card mb-4" :class="{ 'chief-card-loading': isTimelogDetailLoading }">
                    <div
                        v-if="isTimelogDetailLoading"
                        class="chief-tab-loader"
                    >
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted">
                            {{ timelogView === 'month' ? 'Loading monthly timelog summary...' : 'Loading daily timelog entries...' }}
                        </div>
                    </div>
                    <div class="chief-timelog-detail-header mb-3">
                        <div>
                            <div class="chief-section-eyebrow">{{ timelogView === 'month' ? 'Per Month View' : 'Per Day View' }}</div>
                            <h5 class="mb-1">{{ timelogView === 'month' ? 'Monthly Timelog Summary' : 'Daily Timelog Entries' }}</h5>
                            <p class="text-muted mb-0">
                                {{ timelogView === 'month'
                                    ? 'One row per employee with monthly totals for worked hours, late, undertime, overtime, leave, offset, SO, and LTO.'
                                    : 'One row per employee per day with logs, work hours, and remarks.' }}
                            </p>
                        </div>
                        <div class="chief-timelog-detail-controls">
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
                    </div>
                    <div v-show="timelogView === 'month'" class="table-responsive">
                        <table ref="timelogSummaryTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Employee</th>
                                    <th>Unit</th>
                                    <th>Worked Hrs</th>
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
                        <div
                            v-if="timelogView === 'day'"
                            class="chief-day-filter-card"
                        >
                            <label class="chief-day-filter-label" for="chief-timelog-day-filter">
                                Specific date
                            </label>
                            <div class="chief-day-filter-inline">
                                <input
                                    id="chief-timelog-day-filter"
                                    v-model="selectedTimelogDate"
                                    type="date"
                                    class="form-control chief-day-filter-input"
                                    :min="timelogDayMin"
                                    :max="timelogDayMax"
                                    :disabled="isAnyLoading"
                                    @change="applyTimelogDateFilter"
                                >
                                <button
                                    v-if="selectedTimelogDate"
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    :disabled="isAnyLoading"
                                    @click="clearTimelogDateFilter"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
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

                <div class="card rounded-4 p-3 chief-tab-card" :class="{ 'chief-card-loading': isTimelogStatsLoading }">
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
                                    <th>Mins Consumed</th>
                                    <th>Tally</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="card rounded-4 p-3 chief-tab-card mt-4" :class="{ 'chief-card-loading': isQuarterlyTimelogStatsLoading }">
                    <div v-if="isQuarterlyTimelogStatsLoading" class="chief-tab-loader">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted">Loading quarterly timelog data...</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <div class="chief-section-eyebrow">Quarterly Stats</div>
                            <h5 class="mb-1">Top 10 by Category</h5>
                            <p class="text-muted mb-0">{{ selectedYear }} totals by quarter</p>
                        </div>
                        <div class="chief-stat-switch" role="tablist" aria-label="Quarter tabs">
                            <button
                                v-for="quarter in quarterTabs"
                                :key="quarter.key"
                                type="button"
                                class="chief-stat-switch-btn"
                                :class="{ active: activeQuarterTab === quarter.key }"
                                :disabled="isAnyLoading"
                                @click="setQuarterTab(quarter.key)"
                            >
                                {{ quarter.label }}
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <div class="chief-stat-switch" role="tablist" aria-label="Quarterly timelog stat categories">
                            <button
                                v-for="tab in timelogStatTabs"
                                :key="`quarterly-${tab.key}`"
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
                        <table ref="timelogQuarterlyStatsTable" class="table table-striped align-middle chief-table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th>Employee</th>
                                    <th>Unit</th>
                                    <th>Mins Consumed</th>
                                    <th>Tally</th>
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
                            <h5 class="mb-1">Leave Credits and Offset</h5>
                            <p class="text-muted mb-0">One row per employee showing all leave credit balances plus offset.</p>
                        </div>
                    </div>
                    <div class="alert alert-info mb-3" role="alert">
                        The leave credits shown below are based on the latest records.
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

        <div
            ref="timelogBreakdownModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="chief-breakdown-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="chief-section-eyebrow">Timelog Breakdown</div>
                                <h5 id="chief-breakdown-title" class="mb-1">
                                    {{ activeTimelogBreakdown?.title || 'Timelog Breakdown' }}
                                </h5>
                                <p class="text-muted mb-0">
                                    {{ uppercaseName(activeTimelogBreakdown?.employee || '-') }} • {{ activeTimelogBreakdown?.value || '-' }}
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div v-if="activeTimelogBreakdown?.items?.length" class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-uppercase">
                                        <th>Date</th>
                                        <th>Value</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in activeTimelogBreakdown.items" :key="`${item.date}-${item.value}-${item.details}`">
                                        <td class="fw-semibold">{{ item.date }}</td>
                                        <td>{{ item.value }}</td>
                                        <td class="text-muted">{{ item.details || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-muted">
                            {{ activeTimelogBreakdown?.empty_message || 'No breakdown available.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { Modal } from "bootstrap";

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
                { key: "ut", label: "UT" },
                { key: "leave", label: "Leave" },
                { key: "offsets", label: "Offsets" },
                { key: "so", label: "SO" },
                { key: "lto", label: "LTO" },
            ],
            quarterTabs: [
                { key: "1", label: "Q1 (Jan - Mar)" },
                { key: "2", label: "Q2 (Apr - Jun)" },
                { key: "3", label: "Q3 (Jul - Sep)" },
                { key: "4", label: "Q4 (Oct - Dec)" },
            ],
            employeeTypeTabs: [
                { key: "all", label: "All" },
                { key: "regular", label: "Regular" },
                { key: "cos", label: "COS" },
            ],
            activeTab: this.initialTab,
            timelogView: localStorage.getItem("chief-corner-timelog-view") || "month",
            activeTimelogStatTab: localStorage.getItem("chief-corner-timelog-stat-tab") || "lates",
            activeQuarterTab: localStorage.getItem("chief-corner-quarter-tab") || String(Math.ceil((Number(this.selectedMonthProp.slice(5, 7)) || 1) / 3)),
            activeEmployeeType: localStorage.getItem("chief-corner-employee-type") || "all",
            selectedMonth: this.selectedMonthProp,
            selectedTimelogDate: "",
            applicationType: localStorage.getItem("chief-corner-application-type") || "",
            applicationsCount: 0,
            timelogBreakdowns: {},
            activeTimelogBreakdown: null,
            tableBusyCount: 0,
            loadingTables: {
                applications: false,
                timelogStats: false,
                timelogQuarterlyStats: false,
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
                    top_late: [],
                    top_ontime: [],
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
        isQuarterlyTimelogStatsLoading() {
            return this.loading.timelogs || this.loadingTables.timelogQuarterlyStats;
        },
        isLeaveCreditsLoading() {
            return this.loading.credits || this.loadingTables.leaveCredits;
        },
        selectedYear() {
            return this.selectedMonth.slice(0, 4);
        },
        timelogDayMin() {
            return `${this.selectedMonth}-01`;
        },
        timelogDayMax() {
            const [year, month] = this.selectedMonth.split("-").map(Number);
            const endOfMonth = new Date(year, month, 0);
            const monthMax = `${year}-${String(month).padStart(2, "0")}-${String(endOfMonth.getDate()).padStart(2, "0")}`;
            const today = new Date().toISOString().slice(0, 10);

            return this.selectedMonth === today.slice(0, 7) && today < monthMax ? today : monthMax;
        },
    },
    mounted() {
        const url = new URL(window.location.href);
        const queryTab = url.searchParams.get("tab");
        const queryEmployeeType = url.searchParams.get("employee_type");
        const queryDate = queryTab === "timelogs" ? url.searchParams.get("selected_date") : null;
        const savedTab = localStorage.getItem("chief-corner-active-tab");

        if (!queryTab && savedTab && this.tabs.some((tab) => tab.key === savedTab)) {
            this.activeTab = savedTab;
        }

        if (queryEmployeeType && this.employeeTypeTabs.some((tab) => tab.key === queryEmployeeType)) {
            this.activeEmployeeType = queryEmployeeType;
        } else if (!this.employeeTypeTabs.some((tab) => tab.key === this.activeEmployeeType)) {
            this.activeEmployeeType = "all";
        }

        if (!this.quarterTabs.some((tab) => tab.key === this.activeQuarterTab)) {
            this.activeQuarterTab = String(Math.ceil((Number(this.selectedMonth.slice(5, 7)) || 1) / 3));
        }

        if (queryDate) {
            this.selectedTimelogDate = queryDate;
        }

        if (this.activeTab === "timelogs" && this.timelogView === "day") {
            this.selectedTimelogDate = this.resolveTimelogDateSelection(this.selectedTimelogDate);
        }

        this.syncUrl();
        this.fetchTab(this.activeTab, true);
    },
    beforeUnmount() {
        this.detachTimelogStatsClickHandler();
        this.disposeTimelogBreakdownModal();
        this.destroyAllDataTables();
    },
    methods: {
        isLoading(tab) {
            return this.loading[tab];
        },
        uppercaseName(value) {
            return String(value || "").toUpperCase();
        },
        tabEndpoint(tab) {
            return this.tabEndpointTemplate.replace("__TAB__", tab);
        },
        async fetchTab(tab, force = false) {
            if (this.loadedTabs[tab] && !force) {
                return false;
            }

            this.loading[tab] = true;
            this.errorMessage = "";

            try {
                const params = {
                    month: this.selectedMonth,
                };

                const selectedDate = this.selectedDateParam(tab);
                if (selectedDate) {
                    params.selected_date = selectedDate;
                }

                if (tab === "applications") {
                    params.application_type = this.applicationType;
                }

                params.employee_type = this.activeEmployeeType;

                const response = await axios.get(this.tabEndpoint(tab), { params });
                const data = response.data;

                this.applyTabData(tab, data);

                this.loadedTabs[tab] = true;
                this.syncUrl();
                await this.$nextTick();
            } catch (error) {
                console.error(`Chief Corner ${tab} load failed`, error);
                this.errorMessage = error.response?.data?.message || "Something went wrong while loading this tab.";
                return false;
            } finally {
                this.loading[tab] = false;
            }

            try {
                this.initializeTabTables(tab);
                this.adjustVisibleTables();
            } catch (error) {
                console.error(`Chief Corner ${tab} table enhancement failed`, error);
            }

            return true;
        },
        async showTab(tab) {
            this.activeTab = tab;
            localStorage.setItem("chief-corner-active-tab", tab);
            this.syncUrl();
            const didFetch = await this.fetchTab(tab);
            await this.$nextTick();
            if (!didFetch) {
                this.reloadTabTables(tab);
            }
            this.adjustVisibleTables();
        },
        async reloadApplications() {
            localStorage.setItem("chief-corner-application-type", this.applicationType);
            this.loadedTabs.applications = false;
            await this.fetchTab("applications", true);
        },
        async refreshLoadedTabs() {
            this.loadedTabs = {
                overview: false,
                applications: false,
                timelogs: false,
                credits: false,
            };

            await this.fetchTab(this.activeTab, true);
            await this.$nextTick();
            this.adjustVisibleTables();
        },
        async applyGlobalMonthFilter() {
            this.selectedTimelogDate = this.timelogView === "day"
                ? this.resolveTimelogDateSelection(this.selectedTimelogDate)
                : this.normalizeSelectedTimelogDate(this.selectedTimelogDate);
            await this.refreshLoadedTabs();
        },
        async shiftMonth(delta) {
            const [year, month] = this.selectedMonth.split("-").map(Number);
            const nextDate = new Date(year, month - 1 + delta, 1);
            const nextYear = nextDate.getFullYear();
            const nextMonth = String(nextDate.getMonth() + 1).padStart(2, "0");
            this.selectedMonth = `${nextYear}-${nextMonth}`;
            await this.applyGlobalMonthFilter();
        },
        syncUrl() {
            const url = new URL(window.location.href);
            url.searchParams.set("tab", this.activeTab);
            url.searchParams.set("month", this.selectedMonth);
            url.searchParams.set("employee_type", this.activeEmployeeType);
            const selectedDate = this.selectedDateParam();
            if (this.activeTab === "timelogs" && selectedDate) {
                url.searchParams.set("selected_date", selectedDate);
            } else {
                url.searchParams.delete("selected_date");
            }
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
                const nextSelectedDate = data.selected_date || this.selectedTimelogDate;
                this.selectedTimelogDate = this.timelogView === "day"
                    ? this.resolveTimelogDateSelection(nextSelectedDate)
                    : this.normalizeSelectedTimelogDate(nextSelectedDate);
                if (!this.timelogStatTabs.some((item) => item.key === this.activeTimelogStatTab)) {
                    this.activeTimelogStatTab = "lates";
                }
            }

            if (tab === "credits") {
                this.leaveCreditColumns = data.leave_credit_columns || [];
            }
        },
        initializeApplicationsTable() {
            this.rebuildDataTable("applications", this.$refs.applicationsTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[3, "asc"]],
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
                    {
                        data: "employee",
                        render: (data, type) => type === "display"
                            ? this.uppercaseName(data)
                            : data,
                    },
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
        },
        initializeTimelogTables() {
            this.buildTimelogStatsTable();
            this.buildTimelogQuarterlyStatsTable();
            this.buildActiveTimelogDetailTable();
        },
        buildActiveTimelogDetailTable() {
            if (this.timelogView === "month") {
                this.buildTimelogSummaryTable();
                return;
            }

            this.buildTimelogDailyTable();
        },
        buildTimelogSummaryTable() {
            this.rebuildDataTable("timelogSummary", this.$refs.timelogSummaryTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                dom: "<'chief-datatable-toolbar'<'chief-datatable-search'f>>rt<'chief-datatable-footer'ip>",
                order: [[0, "asc"]],
                ajax: this.buildAjaxHandler("timelogs", "timelogSummary"),
                columns: [
                    {
                        data: "employee",
                        name: "employee_order",
                        render: (data, type, row) => type === "display"
                            ? `<div class="fw-bold">${this.uppercaseName(data)}</div><div class="small text-muted">${this.uppercaseName(row.position)}</div>`
                            : row.employee_order || `${data} ${row.position}`,
                    },
                    {
                        data: "unit",
                        name: "unit_order",
                        render: (data, type, row) => type === "sort" || type === "type"
                            ? row.unit_order
                            : data,
                    },
                    this.numericColumn("worked_hours", "worked_hours_order"),
                    this.numericColumn("late", "late_order"),
                    this.numericColumn("undertime", "undertime_order"),
                    this.numericColumn("overtime", "overtime_order"),
                    this.numericColumn("leave", "leave_order"),
                    this.numericColumn("offset", "offset_order"),
                    this.numericColumn("so", "so_order"),
                    this.numericColumn("lto", "lto_order"),
                ],
            });
        },
        buildTimelogDailyTable() {
            this.rebuildDataTable("timelogDaily", this.$refs.timelogDailyTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                dom: "<'chief-datatable-toolbar'<'chief-datatable-search'f>>rt<'chief-datatable-footer'ip>",
                order: [[1, "asc"]],
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
                            ? `<div class="fw-bold">${this.uppercaseName(data)}</div><div class="small text-muted">${this.uppercaseName(row.position)}</div>`
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
        },
        initializeTabTables(tab) {
            if (tab === "overview") {
                this.refreshOverviewTable();
                return;
            }

            if (tab === "applications") {
                this.initializeApplicationsTable();
                return;
            }

            if (tab === "timelogs") {
                this.initializeTimelogTables();
                return;
            }

            if (tab === "credits") {
                this.buildLeaveCreditsTable();
            }
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
                columns: this.timelogStatsColumns(),
            });
            this.attachTimelogStatsClickHandler();
        },
        buildTimelogQuarterlyStatsTable() {
            this.rebuildDataTable("timelogQuarterlyStats", this.$refs.timelogQuarterlyStatsTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                order: [],
                searching: false,
                ajax: this.buildAjaxHandler("timelogs", "timelogQuarterlyStats"),
                columns: this.timelogStatsColumns(),
            });
            this.attachTimelogStatsClickHandler();
        },
        timelogStatsColumns() {
            return [
                {
                    data: "employee",
                    name: "employee_order",
                    render: (data, type, row) => type === "display"
                        ? `<div class="fw-bold">${this.uppercaseName(data)}</div><div class="small text-muted">${this.uppercaseName(row.position)}</div>`
                        : `${data} ${row.position}`,
                },
                { data: "unit", name: "unit" },
                {
                    data: "value",
                    name: "value_order",
                    render: (data, type, row) => {
                        if (type === "sort" || type === "type") {
                            return row.value_order;
                        }

                        if (type === "display" && row.breakdown_id) {
                            return `<button type="button" class="btn btn-link p-0 chief-breakdown-trigger" data-breakdown-id="${row.breakdown_id}">${data}</button>`;
                        }

                        return data;
                    },
                },
                this.numericColumn("tally", "tally_order"),
            ];
        },
        buildLeaveCreditsTable() {
            this.rebuildDataTable("leaveCredits", this.$refs.leaveCreditsTable, {
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: true,
                order: [[1, "asc"]],
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
                            : this.uppercaseName(data),
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

                    params.month = this.selectedMonth;
                    params.employee_type = this.activeEmployeeType;
                    const selectedDate = this.selectedDateParam(tab, table);
                    if (selectedDate) {
                        params.selected_date = selectedDate;
                    }

                    if (tab === "timelogs") {
                        params.stat = this.activeTimelogStatTab;
                        if (table === "timelogQuarterlyStats") {
                            params.quarter = this.activeQuarterTab;
                        }
                    }

                    const response = await axios.get(this.tabEndpoint(tab), { params });
                    if (table === "timelogStats" || table === "timelogQuarterlyStats") {
                        this.registerTimelogBreakdowns(response.data?.data || []);
                    }
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
        registerTimelogBreakdowns(rows) {
            rows.forEach((row) => {
                if (row?.breakdown_id && row?.breakdown) {
                    this.timelogBreakdowns[row.breakdown_id] = row.breakdown;
                }
            });
        },
        attachTimelogStatsClickHandler() {
            if (typeof window.$ === "undefined") {
                return;
            }

            [
                this.$refs.timelogStatsTable,
                this.$refs.timelogQuarterlyStatsTable,
            ].forEach((table) => {
                if (!table) {
                    return;
                }

                window.$(table)
                    .off("click.chiefBreakdown")
                    .on("click.chiefBreakdown", ".chief-breakdown-trigger", (event) => {
                        const breakdownId = event.currentTarget.dataset.breakdownId;
                        this.openTimelogBreakdown(breakdownId);
                    });
            });
        },
        detachTimelogStatsClickHandler() {
            if (typeof window.$ === "undefined") {
                return;
            }

            [
                this.$refs.timelogStatsTable,
                this.$refs.timelogQuarterlyStatsTable,
            ].forEach((table) => {
                if (table) {
                    window.$(table).off("click.chiefBreakdown");
                }
            });
        },
        openTimelogBreakdown(breakdownId) {
            this.activeTimelogBreakdown = this.timelogBreakdowns[breakdownId] || null;
            this.$nextTick(() => {
                this.getTimelogBreakdownModal()?.show();
            });
        },
        closeTimelogBreakdown() {
            this.getTimelogBreakdownModal()?.hide();
        },
        getTimelogBreakdownModal() {
            const modalElement = this.$refs.timelogBreakdownModal;

            if (!modalElement) {
                return null;
            }

            const modal = Modal.getOrCreateInstance(modalElement);

            if (!this._timelogBreakdownModalBound) {
                modalElement.addEventListener("hidden.bs.modal", this.handleTimelogBreakdownHidden);
                this._timelogBreakdownModalBound = true;
            }

            return modal;
        },
        handleTimelogBreakdownHidden() {
            this.activeTimelogBreakdown = null;
        },
        disposeTimelogBreakdownModal() {
            const modalElement = this.$refs.timelogBreakdownModal;

            if (!modalElement) {
                return;
            }

            if (this._timelogBreakdownModalBound) {
                modalElement.removeEventListener("hidden.bs.modal", this.handleTimelogBreakdownHidden);
                this._timelogBreakdownModalBound = false;
            }

            Modal.getInstance(modalElement)?.dispose();
        },
        normalizeSelectedTimelogDate(dateValue) {
            if (!dateValue || !dateValue.startsWith(`${this.selectedMonth}-`)) {
                return "";
            }

            if (dateValue < this.timelogDayMin || dateValue > this.timelogDayMax) {
                return "";
            }

            return dateValue;
        },
        resolveTimelogDateSelection(dateValue) {
            const normalizedDate = this.normalizeSelectedTimelogDate(dateValue);

            return normalizedDate || this.timelogDayMax;
        },
        selectedDateParam(tab = null, table = null) {
            if (this.timelogView !== "day") {
                return "";
            }

            const allowsSelectedDate = table === "timelogDaily"
                || tab === "applications"
                || tab === "timelogs";

            if (!allowsSelectedDate) {
                return "";
            }

            return this.normalizeSelectedTimelogDate(this.selectedTimelogDate);
        },
        invalidateLoadedTabs(tabs = this.tabs.map((tab) => tab.key)) {
            tabs.forEach((tab) => {
                if (Object.prototype.hasOwnProperty.call(this.loadedTabs, tab)) {
                    this.loadedTabs[tab] = false;
                }
            });
        },
        async applyTimelogDateFilter() {
            this.selectedTimelogDate = this.resolveTimelogDateSelection(this.selectedTimelogDate);
            this.invalidateLoadedTabs();
            this.syncUrl();
            this.reloadTabTables("timelogs");
        },
        async clearTimelogDateFilter() {
            this.selectedTimelogDate = "";
            this.invalidateLoadedTabs();
            this.syncUrl();
            this.reloadTabTables("timelogs");
        },
        reloadTabTables(tab) {
            const tableMap = {
                applications: ["applications"],
                timelogs: [
                    "timelogStats",
                    "timelogQuarterlyStats",
                    this.timelogView === "month" ? "timelogSummary" : "timelogDaily",
                ],
                credits: ["leaveCredits"],
            };

            if (tab === "overview") {
                this.refreshOverviewTable();
                return;
            }

            (tableMap[tab] || []).forEach((key) => this.reloadDataTable(key));
        },
        refreshOverviewTable() {
            this.rebuildDataTable("overview", this.$refs.overviewTable, {
                pageLength: 10,
                order: [[0, "desc"]],
                columnDefs: [{ targets: [3], orderable: false }],
            });
        },
        reloadDataTable(key) {
            const element = this.$refs[`${key}Table`] || this.$refs[key];

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
                this.$refs.timelogQuarterlyStatsTable,
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
            if (view === "day") {
                this.selectedTimelogDate = this.resolveTimelogDateSelection(this.selectedTimelogDate);
            }
            this.invalidateLoadedTabs();
            this.syncUrl();
            this.$nextTick(() => {
                this.buildActiveTimelogDetailTable();
                this.reloadDataTable("timelogStats");
                this.reloadDataTable("timelogQuarterlyStats");
                this.adjustVisibleTables();
            });
        },
        setTimelogStatTab(tab) {
            this.activeTimelogStatTab = tab;
            localStorage.setItem("chief-corner-timelog-stat-tab", tab);
            this.$nextTick(() => {
                this.reloadDataTable("timelogStats");
                this.reloadDataTable("timelogQuarterlyStats");
                this.adjustVisibleTables();
            });
        },
        setQuarterTab(tab) {
            this.activeQuarterTab = tab;
            localStorage.setItem("chief-corner-quarter-tab", tab);
            this.$nextTick(() => {
                this.reloadDataTable("timelogQuarterlyStats");
                this.adjustVisibleTables();
            });
        },
        async setEmployeeTypeTab(tab) {
            if (this.activeEmployeeType === tab) {
                return;
            }

            this.activeEmployeeType = tab;
            localStorage.setItem("chief-corner-employee-type", tab);
            this.invalidateLoadedTabs();
            this.syncUrl();
            await this.fetchTab(this.activeTab, true);
            await this.$nextTick();
            this.adjustVisibleTables();
        },
    },
};
</script>

<style scoped>
.chief-subtab-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 1rem 0 1.25rem;
}

.chief-breakdown-trigger {
    font-weight: 600;
    text-decoration: none;
}

.chief-kpi-list {
    display: grid;
    gap: 0.85rem;
}

.chief-kpi-item + .chief-kpi-item {
    padding-top: 0.85rem;
    border-top: 1px solid var(--bs-border-color);
}

.chief-timelog-detail-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.chief-timelog-detail-controls {
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
    margin-left: auto;
}

.chief-day-filter-card {
    min-width: 260px;
    max-width: 340px;
    padding: 0.85rem 1rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 1rem;
    background: rgba(var(--bs-primary-rgb), 0.04);
}

.chief-day-filter-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
}

.chief-day-filter-inline {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.chief-day-filter-input {
    flex: 1 1 180px;
    min-width: 0;
}

:deep(.chief-datatable-toolbar) {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 1rem;
}

:deep(.chief-datatable-search) {
    width: min(100%, 230px);
}

:deep(.chief-datatable-search label) {
    display: block;
    margin: 0;
    width: 100%;
    font-size: 0;
}

:deep(.chief-datatable-search input) {
    width: 100% !important;
    margin-left: 0 !important;
}

:deep(.chief-datatable-footer) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

@media (max-width: 991.98px) {
    .chief-timelog-detail-controls {
        width: 100%;
        margin-left: 0;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .chief-timelog-detail-header,
    .chief-timelog-detail-controls,
    .chief-day-filter-inline {
        align-items: stretch;
    }

    .chief-timelog-detail-controls,
    .chief-day-filter-card {
        width: 100%;
        max-width: none;
    }

    :deep(.chief-datatable-toolbar),
    :deep(.chief-datatable-footer) {
        justify-content: stretch;
    }

    :deep(.chief-datatable-search) {
        width: 100%;
    }
}
</style>
