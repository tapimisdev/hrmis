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
        background: white;
        border-radius: 18px 18px 0 0;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        
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
                    background: linear-gradient(135deg, $primary 0%, $secondary 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                
                .title {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #1e293b;
                    margin: 0;
                }
            }
            
            .date-filters {
                display: flex;
                gap: 0.5rem;
                
                .form-select {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    background: white;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    min-width: 120px;
                    
                    &:focus {
                        border-color: $primary;
                        outline: none;
                        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                    }
                    
                    &:hover {
                        border-color: $primary;
                    }
                }
            }
        }
    }
    
    .table-card {
        background: white;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .table-wrapper {
        max-height: 620px;
        overflow-y: auto;
        overflow-x: auto;
        
        &::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        &::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        &::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, $primary 0%, $secondary 100%);
            border-radius: 3px;
        }
    }
    
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.8125rem;
        
        thead {
            th {
                position: sticky;
                top: 0;
                background: linear-gradient(135deg, $primary 0%, $secondary 100%);
                color: $light;
                font-weight: 600;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 0.5rem 0.5rem;
                text-align: center;
                z-index: 10;

                tr {
                  border: 1px solid;
                }
                
                &.col-day { min-width: 50px; }
                &.col-time { min-width: 80px; }
                &.col-hours { min-width: 80px; }
                &.col-double { min-width: 50px; }
                &.col-remarks { min-width: 150px; }
            }
        }
        
        tbody {
            tr {
                transition: background 0.2s ease;
                background: white;
                
                &:nth-child(even) { background: #f8fafc; }
                &:hover { background: #f1f5f9; }
                
                &.highlight-today {
                    background: linear-gradient(90deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
                    border-left: 3px solid $primary;
                }
                
                &.row-restday { background: rgba(16, 185, 129, 0.04); }
                &.row-leave { background: rgba(59, 130, 246, 0.04); }
                &.row-holiday { background: rgba(245, 158, 11, 0.04); }
                &.row-absent { background: rgba(239, 68, 68, 0.04); }
            }
            
            td {
                padding: 0.4rem 0.3rem;
                text-align: center;
                border-bottom: 1px solid #e2e8f0;
                color: #475569;
            }
        }
    }
    
    .skeleton-loader {
        height: 16px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
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
            width: 26px;
            height: 26px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.8125rem;
        }
    }
    
    .status-cell {
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.5rem;
            border-radius: 16px;
            font-weight: 500;
            font-size: 0.75rem;
            
            i { font-size: 0.75rem; }
            
            .badge-extra {
                font-size: 0.7rem;
                opacity: 0.85;
            }
            
            &.status-restday { background: #10b981; color: white; }
            &.status-holiday { background: #f59e0b; color: white; }
            &.status-leave { background: #3b82f6; color: white; }
            &.status-ob { background: #8b5cf6; color: white; }
            &.status-absent { background: #ef4444; color: white; }
        }
    }
    
    .time-cell {
        .time-value {
            font-weight: 600;
            color: #1e293b;
            
            &.time-small { font-size: 0.7rem; }
            &.has-overtime {
                color: $primary;
                font-weight: 700;
            }
        }
        
        .time-empty {
            color: #cbd5e1;
            font-weight: 400;
        }
    }
    
    .hours-cell {
        .hours-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e0f2fe;
            color: #0284c7;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.7rem;
        }
        
        .ut-badge {
            background: #fde68a;
            color: #d97706;
        }
    }
    
    .double-cell {
        .double-value {
            font-weight: 700;
            color: $primary;
            font-size: 0.875rem;
        }
    }
    
    .remarks-cell {
        .remarks-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            justify-content: center;
        }
        
        .remark-tag {
            display: inline-block;
            padding: 0.2rem 0.4rem;
            background: #bfdbfe;
            color: #1e40af;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: capitalize;
            
            &.remark-danger {
                background: #fecaca;
                color: #991b1b;
            }
            
            &.remark-warning {
                background: #fde68a;
                color: #92400e;
            }
        }
    }
}
</style>