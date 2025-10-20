<template>
    <div class="attendance-container">
        <!-- Header Section -->
        <div class="attendance-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fa-solid fa-calendar-days"></i>
                    <div>
                        <h5 class="title">Employee Attendance</h5>
                        <p class="subtitle">{{ formattedMonth }} {{ year }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <ModalVue ref="modal" :type="modalType">
            <component 
                :is="modalComponent" 
                v-if="modalComponent"
                v-bind="modalProps"
                @success="loadTimelogs"
            />
            <div v-else-if="modalType === 'absent'" class="modal-confirm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>Are you sure you want to mark this employee absent?</p>
            </div>
        </ModalVue>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th class="col-day">Day</th>
                            <th class="col-time">Time In</th>
                            <th class="col-time">Break</th>
                            <th class="col-time">Time Out</th>
                            <th class="col-overtime">Overtime</th>
                            <th class="col-hours">Paid HRS</th>
                            <th class="col-double">Double</th>
                            <th class="col-hours">UT</th>
                            <th class="col-remarks">Remarks</th>
                            <th class="col-action">Action</th>
                        </tr>
                    </thead>

                    <!-- Skeleton Loader -->
                    <TableSkeletonVue v-if="loading" :rows="31" :columns="10" />

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

                            <!-- Status Badges (Rest Day, Holiday, Leave, etc.) -->
                            <template v-if="getStatusBadge(log)">
                                <td colspan="8" class="status-cell">
                                    <div :class="['status-badge', getStatusBadge(log).class]">
                                        <i :class="getStatusBadge(log).icon"></i>
                                        <span>{{ getStatusBadge(log).text }}</span>
                                        <span v-if="getStatusBadge(log).extra" class="badge-extra">
                                            {{ getStatusBadge(log).extra }}
                                        </span>
                                    </div>
                                </td>
                            </template>

                            <!-- Regular Log Details -->
                            <template v-else>
                                <td class="time-cell">
                                    <span :class="log.time_in ? 'time-value' : 'time-empty'">
                                        {{ log.time_in || '--:--' }}
                                    </span>
                                </td>
                                <td class="time-cell">
                                    <span :class="log.break ? 'time-value time-small' : 'time-empty'">
                                        {{ log.break || '--:-- to --:--' }}
                                    </span>
                                </td>
                                <td class="time-cell">
                                    <span :class="log.time_out ? 'time-value' : 'time-empty'">
                                        {{ log.time_out || '--:--' }}
                                    </span>
                                </td>
                                <td class="overtime-cell">
                                    <div class="overtime-content">
                                        <div :class="log.overtime ? 'time-value time-small' : 'time-empty'">
                                            {{ log.overtime || '--:-- to --:--' }}
                                        </div>
                                        
                                        <button
                                            class="overtime-link"
                                            :disabled="!hasOvertimeRemark(log.remarks)"
                                            @click="openModal('view_overtime', index)"
                                        >
                                            <span :class="{ 'has-overtime': hasOvertimeRemark(log.remarks) }">
                                                {{ convertToReadableTime(log.ot_mins) }}
                                            </span>
                                        </button>
                                    </div>
                                </td>
                                <td class="hours-cell">
                                    <span class="hours-badge">
                                        {{ convertToReadableTime(log.total_time_work) }}
                                    </span>
                                </td>
                                <td class="double-cell">
                                    <span class="double-value">{{ log.doble }}</span>
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
                                            v-for="(remark, rIndex) in log.remarks || []"
                                            :key="rIndex"
                                            :class="['remark-tag', getRemarkClass(remark)]"
                                        >
                                            {{ remark }}
                                        </span>
                                    </div>
                                </td>
                            </template>

                            <!-- Actions -->
                            <td class="action-cell">
                                <div class="action-dropdown">
                                    <button
                                        class="action-btn"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li v-for="action in getActions(log.remarks)" :key="action.type">
                                            <button 
                                                :class="['dropdown-item', action.danger ? 'dropdown-item-danger' : '']"
                                                @click="openModal(action.type, index)"
                                            >
                                                <i :class="action.icon"></i>
                                                <span>{{ action.text }}</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import TableSkeletonVue from '../../../components/TableSkeletonVue.vue';
import axios from 'axios';
import ModalVue from './modal/ModalVue.vue';
import RecordLeaveVue from './modal/RecordLeaveVue.vue';
import AddTimeVue from './modal/AddTimeVue.vue';
import AddOvertimeVue from './modal/AddOvertimeVue.vue';
import ViewOvertimeVue from './modal/ViewOvertimeVue.vue';

export default {
    components: { TableSkeletonVue, ModalVue, RecordLeaveVue, AddTimeVue, AddOvertimeVue, ViewOvertimeVue },
    props: {
        employee_id: { type: String, required: true },
        month: Number,
        year: Number
    },
    data() {
        return {
            logs: [],
            loading: false,
            modalType: null,
            dateIndex: null,
            summary: []
        };
    },
    computed: {
        formattedMonth() {
            return new Date(this.year, this.month - 1).toLocaleString("default", {
                month: "long"
            });
        },
        modalComponent() {
            const components = {
                adjustment: 'AddTimeVue',
                leave: 'RecordLeaveVue',
                overtime: 'AddOvertimeVue',
                view_overtime: 'ViewOvertimeVue'
            };
            return components[this.modalType] || null;
        },
        modalProps() {
            return {
                employee_id: this.employee_id,
                month: this.month,
                year: this.year,
                index: this.dateIndex
            };
        }
    },
    emits: ['send-summary'],
    methods: {
        async loadTimelogs() {
            this.loading = true;
            try {
                const { data } = await axios.get(
                    `/admin/timekeeping/daily-time-record/${this.employee_id}/show`,
                    { params: { month: this.month, year: this.year } }
                );
                this.logs = data.computedData;
                this.summary = data.summary;
                this.$emit('send-summary', data.summary);
            } catch (error) {
                console.error("Error fetching logs:", error);
            } finally {
                this.loading = false;
            }
        },
        hasRemark(remarks, keyword) {
            if (!Array.isArray(remarks)) return false;
            const kw = keyword.trim().toLowerCase();
            return remarks.some(r => String(r).trim().toLowerCase() === kw);
        },
        hasOvertimeRemark(remarks) {
            return this.hasRemark(remarks, 'overtime') || this.hasRemark(remarks, 'pending overtime');
        },
        getRowClass(remarks) {
            if (this.hasRemark(remarks, 'today')) return 'highlight-today';
            if (this.hasRemark(remarks, 'restday')) return 'row-restday';
            if (this.hasRemark(remarks, 'suspension')) return 'row-restday';
            if (this.hasRemark(remarks, 'leave')) return 'row-leave';
            if (this.hasRemark(remarks, 'holiday')) return 'row-holiday';
            if (this.hasRemark(remarks, 'absent')) return 'row-absent';
            return '';
        },
       getStatusBadge(log) {
            const { remarks } = log;

            switch (true) {
                case this.hasRemark(remarks, 'restday'):
                    return {
                        class: 'status-restday',
                        icon: 'fa-solid fa-mug-hot',
                        text: 'Rest Day',
                    };

                 case this.hasRemark(remarks, 'suspension Whole day'):
                    return {
                        class: 'status-restday',
                        icon: 'fa-solid fa-mug-hot',
                        text: 'Suspended',
                    };

                case this.hasRemark(remarks, 'holiday') && !log.time_in:
                    return {
                        class: 'status-holiday',
                        icon: 'fa-solid fa-calendar-star',
                        text: 'Holiday',
                        extra: `(Double = ${log.doble})`,
                    };

                case this.hasRemark(remarks, 'leave'):
                    return {
                        class: 'status-leave',
                        icon: 'fa-solid fa-plane-departure',
                        text: 'Leave',
                    };

                case this.hasRemark(remarks, 'ob'):
                    return {
                        class: 'status-ob',
                        icon: 'fa-solid fa-briefcase',
                        text: 'Official Business',
                    };

                case this.hasRemark(remarks, 'absent'):
                    return {
                        class: 'status-absent',
                        icon: 'fa-solid fa-user-xmark',
                        text: 'Absent',
                    };

                default:
                    return null;
            }
        },
        getRemarkClass(remark) {
            const remarkLower = String(remark).trim().toLowerCase();
            if (remarkLower === 'incomplete log') return 'remark-danger';
            if (remarkLower === 'late' || remarkLower === 'undertime') return 'remark-warning';
            return '';
        },
        getActions(remarks) {
            const actions = [
                { type: 'adjustment', icon: 'fa-solid fa-clock-rotate-left', text: 'Add/Adjust Time' },
                { type: 'overtime', icon: 'fa-solid fa-hourglass-half', text: 'Add Overtime' },
                { type: 'leave', icon: 'fa-solid fa-plane-departure', text: 'Record Leave' }
            ];
            
            if (!this.hasRemark(remarks, 'absent')) {
                actions.push({ 
                    type: 'absent', 
                    icon: 'fa-solid fa-user-xmark', 
                    text: 'Mark Absent',
                    danger: true
                });
            }
            
            return actions;
        },
        openModal(type, index) {
            this.modalType = type;
            this.dateIndex = index + 1;
            this.$refs.modal.open();
        },
        convertToReadableTime(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `${hours}h ${mins}m`;
        }
    },
    watch: {
        month: 'loadTimelogs',
        year: 'loadTimelogs'
    },
    mounted() {
        this.loadTimelogs();
    }
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.attendance-container {
    .attendance-header {
        background: white;
        border-radius: 16px 16px 0 0;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            
            .header-title {
                display: flex;
                align-items: center;
                gap: 1rem;
                
                i {
                    font-size: 2rem;
                    color: $primary;
                    background: linear-gradient(135deg, $primary 0%, $secondary 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                
                .title {
                    font-size: 1.25rem;
                    font-weight: 700;
                    color: #1e293b;
                    margin: 0;
                }
                
                .subtitle {
                    font-size: 0.9375rem;
                    color: $primary;
                    font-weight: 600;
                    margin: 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
            }
        }
    }
    
    .table-card {
        background: white;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .table-wrapper {
        max-height: 520px;
        overflow-y: auto;
        overflow-x: auto;
        
        &::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        &::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }
        
        &::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, $primary 0%, $secondary 100%);
            border-radius: 8px;
            
            &:hover {
                background: linear-gradient(135deg, $secondary 0%, $primary 100%);
            }
        }
    }
    
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        
        thead {
            th {
                position: sticky;
                top: 0;
                background: linear-gradient(135deg, $primary 0%, $secondary 100%);
                color: white;
                font-weight: 600;
                font-size: 0.8125rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: .4rem 0.75rem;
                text-align: center;
                z-index: 10;
                border-bottom: 2px solid $secondary;
                
                &.col-day { min-width: 60px; }
                &.col-time { min-width: 100px; }
                &.col-overtime { min-width: 140px; }
                &.col-hours { min-width: 100px; }
                &.col-double { min-width: 80px; }
                &.col-remarks { min-width: 180px; }
                &.col-action { min-width: 80px; }
            }
        }
        
        tbody {
            tr {
                transition: all 0.2s ease;
                background: white;
                
                &:nth-child(even) {
                    background: #f8fafc;
                }
                
                &:hover {
                    background: #f1f5f9;
                }
                
                &.highlight-today {
                    background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
                    border-left: 4px solid $primary;
                }
                
                &.row-restday { background: rgba(16, 185, 129, 0.05); }
                &.row-leave { background: rgba(59, 130, 246, 0.05); }
                &.row-holiday { background: rgba(245, 158, 11, 0.05); }
                &.row-absent { background: rgba(239, 68, 68, 0.05); }
            }
            
            td {
                padding: 0.4rem 0.3rem;
                font-size: 0.8125rem;
                text-align: center;
                border-bottom: 1px solid #e2e8f0;
                color: #475569;
            }
        }
    }
    
    .day-cell {
        .day-number {
            width: 28px;
            height: 28px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8125rem;
        }
    }
    
    .status-cell {
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem .8rem;
            border-radius: 20px;
            font-weight: 400;
            font-size: 0.7125rem;
            margin: 8px 0px;
            
            i { font-size: .8rem; }
            
            .badge-extra {
                font-size: 0.75rem;
                opacity: 0.8;
            }
            
            &.status-restday {
                background: #10b981;
                color: white;
            }
            
            &.status-holiday {
                background: #f59e0b;
                color: white;
            }
            
            &.status-leave {
                background: #3b82f6;
                color: white;
            }
            
            &.status-ob {
                background: #8b5cf6;
                color: white;
            }
            
            &.status-absent {
                background: #ef4444;
                color: white;
            }
        }
    }
    
    .time-cell {
        .time-value {
            font-weight: 600;
            color: #1e293b;
            
            &.time-small {
                font-size: 0.75rem;
            }
        }
        
        .time-empty {
            color: #cbd5e1;
            font-weight: 500;
        }
    }
    
    .overtime-cell {
        .overtime-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            align-items: center;
        }
        
        .overtime-link {
            background: none;
            border: none;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            color: #94a3b8;
            font-size: 0.8125rem;
            transition: all 0.2s ease;
            border-radius: 6px;
            
            &:disabled {
                cursor: not-allowed;
                opacity: 0.5;
            }
            
            &:not(:disabled):hover {
                background: #f1f5f9;
            }
            
            .has-overtime {
                color: $primary;
                font-weight: 600;
                text-decoration: underline;
            }
        }
    }
    
    .hours-cell {
        .hours-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            background: #e0f2fe;
            color: #0284c7;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
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
            padding: 0.2rem 0.5rem;
            background: #bfdbfe;
            color: #1e40af;
            border-radius: 6px;
            font-size: 0.7rem;
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
    
    .action-cell {
        .action-dropdown {
            display: flex;
            justify-content: center;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            
            &:hover {
                background: linear-gradient(135deg, $primary 0%, $secondary 100%);
                color: white;
                transform: rotate(90deg);
            }
        }
    }
    
    .dropdown-menu-modern {
        border: none;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border-radius: 12px;
        padding: 0.5rem;
        min-width: 200px;
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            
            i {
                width: 18px;
                color: $primary;
            }
            
            &:hover {
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                color: $primary;
            }
            
            &.dropdown-item-danger {
                i { color: #ef4444; }
                
                &:hover {
                    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                    color: #dc2626;
                    
                    i { color: #dc2626; }
                }
            }
        }
    }
    
    .modal-confirm {
        text-align: center;
        padding: 2rem;
        
        i {
            font-size: 4rem;
            color: #f59e0b;
            margin-bottom: 1rem;
        }
        
        p {
            font-size: 1.125rem;
            color: #475569;
            margin: 0;
        }
    }
}
</style>