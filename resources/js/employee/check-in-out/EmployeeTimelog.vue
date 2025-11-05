<template>
    <div class="attendance-container">
        <!-- Header Section -->
        <div class="attendance-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fa-solid fa-calendar-days"></i>
                    <h5 class="title">Employee Attendance</h5>
                </div>
                <div class="date-filters">
                    <select v-model="selectedMonth" class="form-select" @change="loadTimelogs">
                        <option v-for="(month, index) in months" :key="index" :value="index + 1">
                            {{ month }}
                        </option>
                    </select>
                    <select v-model="selectedYear" class="form-select" @change="loadTimelogs">
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th class="col-day">Date</th>
                            <th class="col-day">Day</th>
                            <th class="col-time">In</th>
                            <th class="col-time">Break</th>
                            <th class="col-time">Out</th>
                            <th class="col-time">OT</th>
                            <th class="col-hours">Hours</th>
                            <th class="col-double">X2</th>
                            <th class="col-hours">UT</th>
                            <th class="col-remarks">Remarks</th>
                        </tr>
                    </thead>

                    <!-- Skeleton Loader -->
                    <tbody v-if="loading">
                        <tr v-for="n in 15" :key="n">
                            <td v-for="col in 9" :key="col">
                                <div class="skeleton-loader"></div>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Actual Data -->
                    <tbody v-else>
                        <tr 
                            v-for="(log, index) in logs" 
                            :key="log.id || index" 
                            :class="getRowClass(log.remarks)"
                        >
                            <td class="day-cell">
                                <div class="day-number">{{ index + 1 }}</div>
                            </td>

                            <td class="day-cell">
                                <div class="day-number">{{ getDayName(index) }}</div>
                            </td>

                            <!-- Status Badges -->
                            <td v-if="hasStatus(log.remarks)" colspan="8" class="status-cell">
                                <div class="status-badge" :class="getStatusClass(log.remarks)">
                                    <i :class="getStatusIcon(log.remarks)"></i>
                                    <span>{{ getStatusText(log.remarks) }}</span>
                                    <span v-if="hasRemark(log.remarks, 'holiday') && log.doble" class="badge-extra">
                                        (X2: {{ log.doble }})
                                    </span>
                                </div>
                            </td>

                            <!-- Regular Log Details -->
                            <template v-else>
                                <td class="time-cell">
                                    <span :class="log.time_in ? 'time-value' : 'time-empty'">
                                        {{ log.time_in || '--:--' }}
                                    </span>
                                </td>
                                <td class="time-cell">
                                    <span :class="log.break ? 'time-value time-small' : 'time-empty'">
                                        {{ log.break || '--:--' }}
                                    </span>
                                </td>
                                <td class="time-cell">
                                    <span :class="log.time_out ? 'time-value' : 'time-empty'">
                                        {{ log.time_out || '--:--' }}
                                    </span>
                                </td>
                                <td class="time-cell">
                                  <div v-if="log.overtime" class="time-value time-small">
                                      {{ log.overtime }}
                                  </div>
                                  <div v-else class="time-empty">--:-- to --:--</div>  
                                  <span 
                                        :class="{ 
                                            'time-value': log.ot_mins > 0,
                                            'time-empty': log.ot_mins === 0,
                                            'has-overtime': hasRemark(log.remarks, 'overtime') || hasRemark(log.remarks, 'pending overtime')
                                        }"
                                    >
                                        {{ convertToReadableTime(log.ot_mins) }}
                                    </span>
                                </td>
                                <td class="hours-cell">
                                    <span class="hours-badge">
                                        {{ convertToReadableTime(log.total_time_work) }}
                                    </span>
                                </td>
                                <td class="double-cell">
                                    <span class="double-value">{{ log.doble || 0 }}</span>
                                </td>
                                <td class="hours-cell">
                                    <span class="hours-badge ut-badge">
                                        {{ convertToReadableTime(log.late_undertime) }}
                                    </span>
                                </td>
                                
                                <!-- Remarks -->
                                <td class="remarks-cell">
                                    <div class="remarks-container">
                                        <span
                                            v-for="(remark, rIndex) in getFilteredRemarks(log.remarks)"
                                            :key="rIndex"
                                            class="remark-tag"
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
            const kw = keyword.trim().toLowerCase();
            return remarks.some(r => String(r).trim().toLowerCase() === kw);
        },
        hasStatus(remarks) {
            return this.hasRemark(remarks, 'restday') || 
                   this.hasRemark(remarks, 'holiday') || 
                   this.hasRemark(remarks, 'leave') || 
                   this.hasRemark(remarks, 'ob') || 
                   this.hasRemark(remarks, 'absent');
        },
        getRowClass(remarks) {
            return {
                'highlight-today': this.hasRemark(remarks, 'today'),
                'row-restday': this.hasRemark(remarks, 'restday'),
                'row-leave': this.hasRemark(remarks, 'leave'),
                'row-holiday': this.hasRemark(remarks, 'holiday'),
                'row-absent': this.hasRemark(remarks, 'absent')
            };
        },
        getStatusClass(remarks) {
            if (this.hasRemark(remarks, 'restday')) return 'status-restday';
            if (this.hasRemark(remarks, 'holiday')) return 'status-holiday';
            if (this.hasRemark(remarks, 'leave')) return 'status-leave';
            if (this.hasRemark(remarks, 'ob')) return 'status-ob';
            if (this.hasRemark(remarks, 'absent')) return 'status-absent';
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
            const remarkLower = String(remark).toLowerCase();
            return {
                'remark-danger': remarkLower === 'incomplete log',
                'remark-warning': remarkLower === 'late' || remarkLower === 'undertime'
            };
        },
        convertToReadableTime(minutes) {
            if (!minutes || minutes === 0) return '0h 0m';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `${hours}h ${mins}m`;
        },
        getDayName(day) {
          // Month is 1–12 in props, but Date() expects 0–11
          const date = new Date(this.year, this.month - 1, day);
          // Returns short day name like 'Mon', 'Tue', 'Wed'
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
@import '../../../sass/variables';
.attendance-container {
    .attendance-header {
        background: var(--bs-body-bg);
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            
            .header-title {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                
                i {
                    font-size: 1.5rem;
                    color: var(--bs-primary);
                }
                
                .title {
                    font-size: 1.125rem;
                    font-weight: 600;
                    color: var(--bs-body-color);
                    margin: 0;
                }
            }
            
            .date-filters {
                display: flex;
                gap: 0.5rem;
                
                .form-select {
                    padding: 0.5rem 0.75rem;
                    font-size: 0.875rem;
                    border: 1px solid var(--bs-border-color);
                    border-radius: 6px;
                    background: var(--bs-body-bg);
                    color: var(--bs-body-color);
                    cursor: pointer;
                    transition: all 0.2s ease;
                    min-width: 120px;
                    
                    &:focus {
                        border-color: var(--bs-primary);
                        outline: none;
                        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
                    }
                    
                    &:hover {
                        border-color: var(--bs-primary);
                    }
                }
            }
        }
    }
    
    .table-card {
        background: var(--bs-body-bg);
        border-radius: 0 0 12px 12px;
        border: 1px solid var(--bs-border-color);
        border-top: none;
        overflow: hidden;
    }
    
    .table-wrapper {
        max-height: 620px;
        overflow-y: auto;
        overflow-x: auto;
        
        &::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        &::-webkit-scrollbar-track {
            background: var(--bs-secondary-bg);
        }
        
        &::-webkit-scrollbar-thumb {
            background: var(--bs-border-color);
            border-radius: 4px;
            
            &:hover {
                background: var(--bs-secondary-color);
            }
        }
    }
    
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.875rem;
        
        thead {
            th {
                position: sticky;
                top: 0;
                background: var(--bs-primary);
                color: white;
                font-weight: 600;
                font-size: 0.8125rem;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                padding: 0.75rem 0.5rem;
                text-align: center;
                z-index: 10;
                border-bottom: 2px solid var(--bs-border-color);
                
                &.col-day { min-width: 60px; }
                &.col-time { min-width: 90px; }
                &.col-hours { min-width: 80px; }
                &.col-double { min-width: 50px; }
                &.col-remarks { min-width: 150px; }
            }
        }
        
        tbody {
            tr {
                transition: background 0.15s ease;
                background: var(--bs-body-bg);
                
                &:nth-child(even) { 
                    background: var(--bs-secondary-bg);
                }
                
                &:hover { 
                    background: var(--bs-tertiary-bg);
                }
                
                &.highlight-today {
                    background: rgba(var(--bs-primary-rgb), 0.1);
                    border-left: 3px solid var(--bs-primary);
                }
                
                &.row-restday { background: rgba(var(--bs-success-rgb), 0.05); }
                &.row-leave { background: rgba(var(--bs-info-rgb), 0.05); }
                &.row-holiday { background: rgba(var(--bs-warning-rgb), 0.05); }
                &.row-absent { background: rgba(var(--bs-danger-rgb), 0.05); }
            }
            
            td {
                padding: 0.625rem 0.5rem;
                text-align: center;
                border-bottom: 1px solid var(--bs-border-color);
                color: var(--bs-body-color);
            }
        }
    }
    
    .skeleton-loader {
        height: 18px;
        background: linear-gradient(90deg, 
            var(--bs-secondary-bg) 25%, 
            var(--bs-tertiary-bg) 50%, 
            var(--bs-secondary-bg) 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
        margin: 2px auto;
        width: 70%;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .day-cell {
        .day-number {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--bs-body-color);
        }
    }
    
    .status-cell {
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
            
            i { font-size: 0.875rem; }
            
            .badge-extra {
                font-size: 0.75rem;
                opacity: 0.9;
            }
            
            &.status-restday { background: var(--bs-success); color: white; }
            &.status-holiday { background: var(--bs-warning); color: white; }
            &.status-leave { background: var(--bs-info); color: white; }
            &.status-ob { background: var(--bs-purple, #6f42c1); color: white; }
            &.status-absent { background: var(--bs-danger); color: white; }
        }
    }
    
    .time-cell {
        .time-value {
            font-weight: 500;
            color: var(--bs-body-color);
            
            &.time-small { 
                font-size: 0.75rem;
                color: var(--bs-secondary-color);
            }
            
            &.has-overtime {
                color: var(--bs-primary);
                font-weight: 600;
            }
        }
        
        .time-empty {
            color: var(--bs-secondary-color);
            font-weight: 400;
            font-size: 0.8125rem;
        }
    }
    
    .hours-cell {
        .hours-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            background: rgba(var(--bs-info-rgb), 0.1);
            color: var(--bs-info);
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
            border: 1px solid rgba(var(--bs-info-rgb), 0.2);
        }
        
        .ut-badge {
            background: rgba(var(--bs-warning-rgb), 0.1);
            color: var(--bs-warning);
            border-color: rgba(var(--bs-warning-rgb), 0.2);
        }
    }
    
    .double-cell {
        .double-value {
            font-weight: 600;
            color: var(--bs-primary);
            font-size: 0.9375rem;
        }
    }
    
    .remarks-cell {
        .remarks-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            justify-content: center;
        }
        
        .remark-tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: rgba(var(--bs-info-rgb), 0.1);
            color: var(--bs-info);
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 500;
            text-transform: capitalize;
            border: 1px solid rgba(var(--bs-info-rgb), 0.2);
            
            &.remark-danger {
                background: rgba(var(--bs-danger-rgb), 0.1);
                color: var(--bs-danger);
                border-color: rgba(var(--bs-danger-rgb), 0.2);
            }
            
            &.remark-warning {
                background: rgba(var(--bs-warning-rgb), 0.1);
                color: var(--bs-warning);
                border-color: rgba(var(--bs-warning-rgb), 0.2);
            }
        }
    }
}
</style>