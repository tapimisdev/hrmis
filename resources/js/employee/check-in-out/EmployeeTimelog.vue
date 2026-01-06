<template>
    <div class="attendance-container">
        <!-- Header -->
        <div class="header d-block d-lg-flex gap-3">
            <h5 class="title text-uppercase">
                <i class="fa-solid fa-calendar-days"></i>
                Employee Attendance
            </h5>
            <div class="filters d-flex gap-2">
                <select v-model="selectedMonth" @change="loadTimelogs">
                    <option v-for="(month, index) in months" :key="index" :value="index + 1">
                        {{ month }}
                    </option>
                </select>
                <select v-model="selectedYear" @change="loadTimelogs">
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
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
                    </tr>
                </thead>

                <!-- Loading -->
                <tbody v-if="loading">
                    <tr v-for="n in 15" :key="n">
                        <td v-for="col in 10" :key="col">
                            <div class="skeleton"></div>
                        </td>
                    </tr>
                </tbody>

                <!-- Data -->
                <tbody v-else>
                    <tr v-for="(log, index) in logs" :key="log.id || index" :class="getRowClass(log.remarks)">
                        <td>{{ index + 1 }}</td>
                        <td>{{ getDayName(index) }}</td>

                        <!-- Status Row -->
                        <td v-if="hasStatus(log.remarks)" colspan="8" class="status-cell">
                            <span class="status" :class="getStatusClass(log.remarks)">
                                <i :class="getStatusIcon(log.remarks)"></i>
                                {{ getStatusText(log.remarks) }}
                                <span v-if="hasRemark(log.remarks, 'holiday') && log.doble">
                                    (X2: {{ log.doble }})
                                </span>
                            </span>
                        </td>

                        <!-- Regular Row -->
                        <template v-else>
                            <td>{{ log.time_in || '--:--' }}</td>
                            <td class="small-text">{{ log.break || '--:--' }}</td>
                            <td>{{ log.time_out || '--:--' }}</td>
                            <td>
                                <div v-if="log.overtime" class="small-text">{{ log.overtime }}</div>
                                <div v-else class="small-text empty">--:-- to --:--</div>
                                <span :class="{ 'highlight': hasRemark(log.remarks, 'overtime') || hasRemark(log.remarks, 'pending overtime') }">
                                    {{ convertToReadableTime(log.ot_mins) }}
                                </span>
                            </td>
                            <td><span class="badge">{{ convertToReadableTime(log.total_time_work) }}</span></td>
                            <td class="highlight">{{ log.doble || 0 }}</td>
                            <td><span class="badge ut">{{ convertToReadableTime(log.late_undertime) }}</span></td>
                            <td>
                                <div class="remarks">
                                    <span
                                        v-for="(remark, rIndex) in getFilteredRemarks(log.remarks)"
                                        :key="rIndex"
                                        class="tag"
                                        :class="getRemarkClass(remark)"
                                    >
                                        {{ remark }}
                                    </span>
                                </div>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

const token = localStorage.getItem('auth_token');

export default {
    props: {
        employeeNumber: { type: String, required: true },
        month: { type: Number, default: null },
        year: { type: Number, default: null }
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
            months: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ],
            years: Array.from({ length: 6 }, (_, i) => currentYear - i)
        };
    },
    emits: ['send-summary'],
    methods: {
        async loadTimelogs() {
            this.loading = true;
            try {
                const response = await axios.get(
                    `/employee/employee-timelogs/${this.employeeNumber}/get`,
                    {
                        params: { month: this.selectedMonth, year: this.selectedYear },
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: 'application/json'
                        }
                    }
                );
                this.logs = response.data.computedData;
                this.summary = response.data.summary;
                this.$emit('send-summary', response.data.summary);
            } catch (error) {
                console.error("Error fetching logs:", error);
            }
            this.loading = false;
        },
        hasRemark(remarks, keyword) {
            if (!Array.isArray(remarks)) return false;
            return remarks.some(r => String(r).trim().toLowerCase() === keyword.trim().toLowerCase());
        },
        hasStatus(remarks) {
            return this.hasRemark(remarks, 'restday') || 
                   this.hasRemark(remarks, 'holiday') || 
                   this.hasRemark(remarks, 'leave') || 
                   this.hasRemark(remarks, 'ob') || 
                   this.hasRemark(remarks, 'absent');
        },
        getRowClass(remarks) {
            if (this.hasRemark(remarks, 'today')) return 'today';
            if (this.hasRemark(remarks, 'restday')) return 'restday';
            if (this.hasRemark(remarks, 'holiday')) return 'holiday';
            if (this.hasRemark(remarks, 'leave')) return 'leave';
            if (this.hasRemark(remarks, 'absent')) return 'absent';
            return '';
        },
        getStatusClass(remarks) {
            if (this.hasRemark(remarks, 'restday')) return 'restday';
            if (this.hasRemark(remarks, 'holiday')) return 'holiday';
            if (this.hasRemark(remarks, 'leave')) return 'leave';
            if (this.hasRemark(remarks, 'ob')) return 'ob';
            if (this.hasRemark(remarks, 'absent')) return 'absent';
            return '';
        },
        getStatusIcon(remarks) {
            if (this.hasRemark(remarks, 'restday')) return 'fa-solid fa-mug-hot';
            if (this.hasRemark(remarks, 'holiday')) return 'fa-solid fa-calendar-star';
            if (this.hasRemark(remarks, 'leave')) return 'fa-solid fa-plane-departure';
            if (this.hasRemark(remarks, 'ob')) return 'fa-solid fa-briefcase';
            if (this.hasRemark(remarks, 'absent')) return 'fa-solid fa-user-xmark';
            return '';
        },
        getStatusText(remarks) {
            if (this.hasRemark(remarks, 'restday')) return 'Rest Day';
            if (this.hasRemark(remarks, 'holiday')) return 'Holiday';
            if (this.hasRemark(remarks, 'leave')) return 'Leave';
            if (this.hasRemark(remarks, 'ob')) return 'Official Business';
            if (this.hasRemark(remarks, 'absent')) return 'Absent';
            return '';
        },
        getFilteredRemarks(remarks) {
            if (!Array.isArray(remarks)) return [];
            return remarks.filter(r => 
                !['restday', 'holiday', 'leave', 'ob', 'absent', 'today', 'overtime', 'pending overtime'].includes(r.toLowerCase())
            );
        },
        getRemarkClass(remark) {
            const lower = String(remark).toLowerCase();
            if (lower === 'incomplete log') return 'danger';
            if (lower === 'late' || lower === 'undertime') return 'warning';
            return '';
        },
        convertToReadableTime(minutes) {
            if (!minutes || minutes === 0) return '0h 0m';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `${hours}h ${mins}m`;
        },
        getDayName(day) {
            const date = new Date(this.selectedYear, this.selectedMonth - 1, day + 1);
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        }
    },
    watch: {
        month(newVal) {
            if (newVal) this.selectedMonth = newVal;
        },
        year(newVal) {
            if (newVal) this.selectedYear = newVal;
        },
        employeeNumber: 'loadTimelogs'
    },
    mounted() {
        this.loadTimelogs();
    }
};
</script>

<style lang="scss" scoped>
.attendance-container {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;

    @media (max-width: 767.98px) {
        .header {
          .title{
              margin-bottom: 15px;
          }
          select {
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
  // max-height: 720px;
  &::-webkit-scrollbar { width: 6px; height: 6px; }
  &::-webkit-scrollbar-thumb { background: var(--bs-border-color); border-radius: 3px; }
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;

    th, td {
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

        &.restday { background: rgba(var(--bs-success-rgb), 0.03); }
        &.holiday { background: rgba(var(--bs-warning-rgb), 0.03); }
        &.leave { background: rgba(var(--bs-info-rgb), 0.03); }
        &.absent { background: rgba(var(--bs-danger-rgb), 0.03); }
    }
}

/* Loading Skeleton */
.skeleton {
    height: 16px;
    background: linear-gradient(90deg, var(--bs-secondary-bg) 25%, var(--bs-tertiary-bg) 50%, var(--bs-secondary-bg) 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}
@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Text */
.small-text {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    &.empty { opacity: 0.6; }
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

    &.restday { background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success-text-emphasis); }
    &.holiday { background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning-text-emphasis); }
    &.leave { background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info-text-emphasis); }
    &.ob { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
    &.absent { background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger-text-emphasis); }
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