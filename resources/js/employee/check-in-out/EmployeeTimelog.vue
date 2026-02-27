<template>
    <div class="attendance-container">
        <CorrectionLog ref="correctionModal" />
        <CorrectionList ref="correctionListModal" @clearSearchable="clearSearchable"/>

        <PrintableDtrView ref="printableModal">
            <ViewDtr
                :payload="dtr_all"
                :month="selectedMonth"
                :year="selectedYear"
            />
        </PrintableDtrView>

        <!-- Header -->
        <div class="header d-block d-lg-flex gap-3">
            <h5 class="title text-uppercase">
                <i class="fa-solid fa-calendar-days"></i>
                Employee Attendance
            </h5>
            <div class="filters d-md-flex align-items-center gap-3">
                <button
                    class="btn btn-sm btn-link text-uppercase fw-medium"
                    @click="openCorretionList"
                >
                    View Corrections for this month
                </button>
                <select v-model="selectedMonth" @change="loadTimelogs">
                    <option
                        v-for="(month, index) in months"
                        :key="index"
                        :value="index + 1"
                    >
                        {{ month }}
                    </option>
                </select>
                <select v-model="selectedYear" @change="loadTimelogs">
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
                <button
                    class="btn btn-primary"
                    @click="openPrintables"
                    title="Print View"
                >
                    <i class="fa-solid fa-print"></i>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>In</th>
                        <th>Break</th>
                        <th>Out</th>
                        <th>OT</th>
                        <th>Hours</th>
                        <th>X2</th>
                        <th>UT</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <!-- Loading -->
                <tbody v-if="loading">
                    <tr v-for="n in 15" :key="n">
                        <td v-for="col in 12" :key="col">
                            <div class="skeleton"></div>
                        </td>
                    </tr>
                </tbody>

                <!-- Data -->
                <tbody v-else>
                    <tr
                        v-for="(log, index) in logs"
                        :key="log.id || index"
                        :class="getRowClass(log.remarks)"
                    >
                        <td>{{ index + 1 }}</td>
                        <td>{{ getDayName(index) }}</td>

                        <!-- STATUS ROW -->
                        <template v-if="hasStatus(log.remarks)">
                            <td colspan="8" class="status-cell">
                                <span
                                    class="status"
                                    :class="getStatusClass(log.remarks)"
                                >
                                    <i :class="getStatusIcon(log.remarks)"></i>
                                    {{ getStatusText(log.remarks) }}
                                    <span
                                        v-if="
                                            hasRemark(log.remarks, 'holiday') &&
                                            log.doble
                                        "
                                    >
                                        (X2: {{ log.doble }})
                                    </span>
                                </span>
                            </td>

                            <!-- ACTIONS (ABSENT) -->
                            <td>
                                <button
                                    v-if="hasRemark(log.remarks, 'absent')"
                                    class="btn btn-sm btn-transparent"
                                    title="Request Timelog Correction"
                                    @click="openModal(index + 1)"
                                >
                                    <i
                                        class="fa-solid fa-code-pull-request"
                                    ></i>
                                </button>
                            </td>
                        </template>

                        <!-- REGULAR ROW -->
                        <template v-else>
                            <td>{{ log.time_in || "--:--" }}</td>
                            <td class="small-text">
                                {{ log.break || "--:--" }}
                            </td>
                            <td>{{ log.time_out || "--:--" }}</td>

                            <td>
                                <div v-if="log.overtime" class="small-text">
                                    {{ log.overtime }}
                                </div>
                                <div v-else class="small-text empty">
                                    --:-- to --:--
                                </div>
                                <span
                                    :class="{
                                        highlight:
                                            hasRemark(
                                                log.remarks,
                                                'overtime',
                                            ) ||
                                            hasRemark(
                                                log.remarks,
                                                'pending overtime',
                                            ),
                                    }"
                                >
                                    {{ convertToReadableTime(log.ot_mins) }}
                                </span>
                            </td>

                            <td>
                                <span class="badge">
                                    {{
                                        convertToReadableTime(
                                            log.total_time_work,
                                        )
                                    }}
                                </span>
                            </td>

                            <td class="highlight">{{ log.doble || 0 }}</td>

                            <td>
                                <span class="badge ut">
                                    {{
                                        convertToReadableTime(
                                            log.late_undertime,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                <div class="remarks">
                                    <span
                                        v-for="(
                                            remark, rIndex
                                        ) in getFilteredRemarks(log.remarks)"
                                        :key="rIndex"
                                        class="tag"
                                        :class="getRemarkClass(remark)"
                                    >
                                        {{ formatRemarks(remark) }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <button
                                    class="btn btn-sm btn-transparent"
                                    title="Request Correction"
                                    @click="openModal(index + 1)"
                                >
                                    <i
                                        class="fa-solid fa-code-pull-request"
                                    ></i>
                                </button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import CorrectionLog from "./Corrections/CorrectionLog.vue";
import CorrectionList from "./Corrections/CorrectionList.vue";
import PrintableDtrView from "./printables/PrintableDtrView.vue";
import ViewDtr from "./ViewDtr.vue";

const token = localStorage.getItem("auth_token");

export default {
    components: { CorrectionLog, CorrectionList, PrintableDtrView, ViewDtr },
    props: {
        employeeNumber: { type: String, required: true },
        month: { type: Number, default: null },
        year: { type: Number, default: null },
    },
    data() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();

        return {
            token: token,
            logs: [],
            loading: false,
            summary: [],
            selectedMonth: this.month || currentDate.getMonth() + 1,
            selectedYear: this.year || currentYear,
            searchable: '',
            months: [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December",
            ],
            dtr_all: [],
            years: Array.from({ length: 6 }, (_, i) => currentYear - i),
        };
    },
    emits: ["send-summary"],
    methods: {
        async loadTimelogs() {
            this.loading = true;
            try {
                const response = await axios.get(
                    `/employee/employee-timelogs/${this.employeeNumber}/get`,
                    {
                        params: {
                            month: this.selectedMonth,
                            year: this.selectedYear,
                        },
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: "application/json",
                        },
                    },
                );
                this.logs = response.data.computedData;
                this.summary = response.data.summary;
                this.dtr_all = response.data;
                this.$emit("send-summary", response.data.summary);
            } catch (error) {
                console.error("Error fetching logs:", error);
            }
            this.loading = false;
        },
        formatRemarks(remark) {
            if (!remark) return "";

            const value = String(remark).toLowerCase();
            const isPending = value.includes("pending");

            const formatType = (type, label = null) => {
                const display = label ?? type.toUpperCase();

                const hasType = value.includes(type);

                if (!hasType) return null;

                if (value.includes("morning")) {
                    return isPending
                        ? `PENDING MORNING ${display}`
                        : `MORNING ${display}`;
                }

                if (value.includes("afternoon")) {
                    return isPending
                        ? `PENDING AFTERNOON ${display}`
                        : `AFTERNOON ${display}`;
                }

                if (value.includes("wholeday")) {
                    return isPending ? `PENDING ${display}` : display;
                }

                return isPending ? `PENDING ${display}` : display;
            };

            return (
                formatType("leave") ||
                formatType("offset") ||
                formatType("special order", "SPECIAL ORDER") ||
                formatType("(so)", "SPECIAL ORDER") ||
                String(remark).toUpperCase()
            );
        },
        hasRemark(remarks, keyword) {
            if (!Array.isArray(remarks)) return false;
            return remarks.some(
                (r) =>
                    String(r).trim().toLowerCase() ===
                    keyword.trim().toLowerCase(),
            );
        },
        hasStatus(remarks) {
            return (
                this.hasRemark(remarks, "restday") ||
                this.hasRemark(remarks, "holiday") ||
                this.hasRemark(remarks, "leave") ||
                this.hasRemark(remarks, "ob") ||
                this.hasRemark(remarks, "absent")
            );
        },
        getRowClass(remarks) {
            if (this.hasRemark(remarks, "today")) return "today";
            if (this.hasRemark(remarks, "restday")) return "restday";
            if (this.hasRemark(remarks, "holiday")) return "holiday";
            if (this.hasRemark(remarks, "leave")) return "leave";
            if (this.hasRemark(remarks, "absent")) return "absent";
            return "";
        },
        getStatusClass(remarks) {
            if (this.hasRemark(remarks, "restday")) return "restday";
            if (this.hasRemark(remarks, "holiday")) return "holiday";
            if (this.hasRemark(remarks, "leave")) return "leave";
            if (this.hasRemark(remarks, "ob")) return "ob";
            if (this.hasRemark(remarks, "absent")) return "absent";
            return "";
        },
        getStatusIcon(remarks) {
            if (this.hasRemark(remarks, "restday"))
                return "fa-solid fa-mug-hot";
            if (this.hasRemark(remarks, "holiday"))
                return "fa-solid fa-calendar-star";
            if (this.hasRemark(remarks, "leave"))
                return "fa-solid fa-plane-departure";
            if (this.hasRemark(remarks, "ob")) return "fa-solid fa-briefcase";
            if (this.hasRemark(remarks, "absent"))
                return "fa-solid fa-user-xmark";
            return "";
        },
        getStatusText(remarks) {
            if (this.hasRemark(remarks, "restday")) return "Rest Day";
            if (this.hasRemark(remarks, "holiday")) return "Holiday";
            if (this.hasRemark(remarks, "leave")) return "Leave";
            if (this.hasRemark(remarks, "ob")) return "Official Business";
            if (this.hasRemark(remarks, "absent")) return "Absent";
            return "";
        },
        getFilteredRemarks(remarks) {
            if (!Array.isArray(remarks)) return [];

            const excluded = [
                "restday",
                "holiday",
                "leave",
                "ob",
                "absent",
                "today",
                "overtime",
                "pending overtime",
            ];

            return remarks.filter(
                (r) =>
                    typeof r === "string" &&
                    !excluded.includes(r.toLowerCase().trim()),
            );
        },
        getRemarkClass(remark) {
            const lower = String(remark).toLowerCase();
            if (lower === "incomplete log") return "danger";
            if (lower === "late" || lower === "undertime") return "warning";
            return "";
        },
        convertToReadableTime(minutes) {
            if (!minutes || minutes === 0) return "0h 0m";
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `${hours}h ${mins}m`;
        },
        getDayName(day) {
            const date = new Date(
                this.selectedYear,
                this.selectedMonth - 1,
                day + 1,
            );
            return date.toLocaleDateString("en-US", { weekday: "short" });
        },
        openModal(day) {
            const month = this.selectedMonth ?? new Date().getMonth() + 1;
            const year = this.selectedYear ?? new Date().getFullYear();
            const selectedDay = day ?? new Date().getDate();

            const date = new Date(year, month - 1, selectedDay);
            const formatted =
                date.getFullYear() +
                "-" +
                String(date.getMonth() + 1).padStart(2, "0") +
                "-" +
                String(date.getDate()).padStart(2, "0");

            this.$refs.correctionModal.open(formatted);
        },
        openCorretionList() {
            this.$refs.correctionListModal.open(
                this.selectedMonth,
                this.selectedYear,
                this.searchable
            );
        },
        clearSearchable() {
          this.searchable = '';
        },
        downloadDTR() {
            // Build request parameters
            const params = {
                month: this.selectedMonth,
                year: this.selectedYear,
            };

            axios({
                url: "/api/employee/timelogs/download",
                method: "GET",
                responseType: "blob",
                params: params,
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            })
                .then((response) => {})
                .catch((error) => {
                    console.error("Error downloading DTR:", error);
                    alert("Failed to download DTR. Please try again.");
                });
        },
        openPrintables() {
            this.$refs.printableModal.open();
        },
    },
    watch: {
        month(newVal) {
            if (newVal) this.selectedMonth = newVal;
        },
        year(newVal) {
            if (newVal) this.selectedYear = newVal;
        },
        employeeNumber: "loadTimelogs",
    },
    mounted() {
      this.loadTimelogs().then(() => {
          const params = new URLSearchParams(window.location.search);

          const shouldOpen = params.get("view-corrections") === "true";
          const referenceNo = params.get("reference-no"); 

          this.searchable = referenceNo;
          if (shouldOpen) {
              this.openCorretionList(); 
          }
      });
    },
};
</script>

<style lang="scss" scoped>
.attendance-container {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;

    @media (max-width: 767.98px) {
        .header {
            .title {
                margin-bottom: 15px;
            }
            button {
                width: 100%;
                text-align: center;
            }
            select {
                margin: 6px 0 6px 0;
                width: 100%;
            }
        }

        td {
            padding: 5px;
        }
    }
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--bs-border-color);

    .title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--bs-body-color);

        i {
            color: var(--bs-primary);
            opacity: 0.8; /* less intense icon color */
        }
    }

    .filters select {
        padding: 0.375rem 0.625rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 4px;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);

        &:focus {
            outline: none;
            border-color: var(--bs-primary);
        }
    }
}

/* Table */
.table-wrapper {
    max-height: 600px;
    &::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    &::-webkit-scrollbar-thumb {
        background: var(--bs-border-color);
        border-radius: 3px;
    }
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;

    th,
    td {
        padding: 0.625rem 0.5rem;
        text-align: center;
        border-bottom: 1px solid var(--bs-border-color);
    }
    thead {
        position: sticky;
        top: 0;
    }

    thead th {
        background-color: var(--bs-primary);
        color: var(--bs-light);
        font-weight: 600;
        font-size: 0.8125rem;
        text-transform: uppercase;
        z-index: 10;
    }

    tbody tr {
        background: var(--bs-body-bg);

        &:nth-child(even) {
            background: var(--bs-secondary-bg);
        }

        &:hover {
            background: rgba(var(--bs-primary-rgb), 0.05);
        }

        &.today {
            background: rgba(var(--bs-primary-rgb), 0.08);
            border-left: 3px solid var(--bs-primary);
        }

        &.restday {
            background: rgba(var(--bs-success-rgb), 0.03);
        }
        &.holiday {
            background: rgba(var(--bs-warning-rgb), 0.03);
        }
        &.leave {
            background: rgba(var(--bs-info-rgb), 0.03);
        }
        &.absent {
            background: rgba(var(--bs-danger-rgb), 0.03);
        }
    }
}

/* Loading Skeleton */
.skeleton {
    height: 16px;
    background: linear-gradient(
        90deg,
        var(--bs-secondary-bg) 25%,
        var(--bs-tertiary-bg) 50%,
        var(--bs-secondary-bg) 75%
    );
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}
@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Text */
.small-text {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    &.empty {
        opacity: 0.6;
    }
}

.highlight {
    color: var(--bs-primary);
    opacity: 0.8;
    font-weight: 600;
}

/* Status Cells */
.status-cell .status {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.8125rem;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-body-color);

    &.restday {
        background: rgba(var(--bs-success-rgb), 0.15);
        color: var(--bs-success-text-emphasis);
    }
    &.holiday {
        background: rgba(var(--bs-warning-rgb), 0.15);
        color: var(--bs-warning-text-emphasis);
    }
    &.leave {
        background: rgba(var(--bs-info-rgb), 0.15);
        color: var(--bs-info-text-emphasis);
    }
    &.ob {
        background: rgba(111, 66, 193, 0.15);
        color: #6f42c1;
    }
    &.absent {
        background: rgba(var(--bs-danger-rgb), 0.15);
        color: var(--bs-danger-text-emphasis);
    }
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: rgba(var(--bs-info-rgb), 0.08);
    color: var(--bs-info);
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.75rem;

    &.ut {
        background: rgba(var(--bs-warning-rgb), 0.08);
        color: var(--bs-warning);
    }
}

/* Remarks */
.remarks {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    justify-content: center;
}

.tag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: rgba(var(--bs-info-rgb), 0.08);
    color: var(--bs-info);
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: capitalize;

    &.danger {
        background: rgba(var(--bs-danger-rgb), 0.08);
        color: var(--bs-danger);
    }

    &.warning {
        background: rgba(var(--bs-warning-rgb), 0.08);
        color: var(--bs-warning);
    }
}

/* Dark Mode Adjustments */
[data-bs-theme="dark"] {
    thead th {
        background: var(--bs-primary);
    }
}
</style>
