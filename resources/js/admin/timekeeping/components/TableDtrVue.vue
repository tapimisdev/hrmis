<template>
    <div class="attendance-container border">
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
                            <th class="col-hours">Time Work (HRS)</th>
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
                                            @click="openModal('view_overtime', index + 1)"
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
import MarkAsAbsentVue from './modal/MarkAsAbsentVue.vue'

export default {
    components: { TableSkeletonVue, ModalVue, RecordLeaveVue, AddTimeVue, AddOvertimeVue, ViewOvertimeVue, MarkAsAbsentVue },
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
                view_overtime: 'ViewOvertimeVue',
                absent: 'MarkAsAbsentVue'
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
            
            if (!this.hasRemark(remarks, 'absent') && !this.hasRemark(remarks, 'restday')) {
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
.attendance-container {
    border-radius: 0.4rem 0.4rem 0 0;
    position: static;
    .attendance-header {
        background: var(--bs-secondary-bg);
        padding:.80rem 1.25rem;
        position: sticky;
        z-index: 1001;
        top: 50px;

        .header-content { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .header-title { 
            display: flex; 
            align-items: center; 
            gap: 0.875rem; 
            
            i { 
                font-size: 1.75rem; 
                color: var(--bs-secondary); 
            }
            
            .title { 
                font-size: 1.125rem; 
                font-weight: 600; 
                color: var(--bs-body-color); 
                margin: 0; 
                line-height: 1.3;
            }
            
            .subtitle { 
                font-size: 0.875rem; 
                color: var(--bs-secondary); 
                font-weight: 500; 
                margin: 0; 
            }
        }
    }
    
    .table-card { 
        background: var(--bs-body-bg); 
        border-radius: 0 0 0.5rem 0.5rem; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.08); 
    }
    
    .table-wrapper {
        
        &::-webkit-scrollbar { 
            width: 8px; 
            height: 8px; 
        }
        
        &::-webkit-scrollbar-track { 
            background: var(--bs-tertiary-bg); 
        }
        
        &::-webkit-scrollbar-thumb { 
            background: var(--bs-border-color); 
            border-radius: 4px; 
            
            &:hover {
                background: var(--bs-secondary);
            }
        }
    }
    
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.8125rem;
        position: static;
        thead th {
            position: sticky; 
            top: 116px; 
            z-index: 1001;
            background: var(--bs-secondary-bg); 
            color: var(--bs-body-color); 
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase; 
            letter-spacing: 0.3px;
            padding: 0.625rem 0.75rem; 
            text-align: center; 
            border-bottom: 2px solid var(--bs-border-color);
            
            &.col-day { min-width: 60px; }
            &.col-time { min-width: 100px; }
            &.col-overtime { min-width: 140px; }
            &.col-hours { min-width: 90px; }
            &.col-double { min-width: 70px; }
            &.col-remarks { min-width: 160px; }
            &.col-action { min-width: 70px; }
        }
        
        tbody {
            tr {
                transition: background-color 0.15s ease;
                background: var(--bs-body-bg);
                
                &:nth-child(even) { 
                    background: var(--bs-tertiary-bg); 
                }
                
                &:hover { 
                    background: var(--bs-secondary-bg); 
                }
                
                &.highlight-today { 
                    background: rgba(108, 117, 125, 0.1); 
                    border-left: 3px solid var(--bs-secondary); 
                }
                
                &.row-restday { background: rgba(108, 117, 125, 0.08); }
                &.row-leave { background: rgba(108, 117, 125, 0.08); }
                &.row-holiday { background: rgba(108, 117, 125, 0.08); }
                &.row-absent { background: rgba(108, 117, 125, 0.08); }
            }
            
            td { 
                padding: 0.5rem 0.5rem; 
                text-align: center; 
                border-bottom: 1px solid var(--bs-border-color); 
                color: var(--bs-body-color); 
                vertical-align: middle;
            }
        }
    }
    
    .day-cell .day-number { 
        width: 32px; 
        height: 32px; 
        margin: 0 auto; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 600; 
        font-size: 0.875rem;
        border-radius: 0.25rem;
    }
    
    .status-cell .status-badge {
        display: inline-flex; 
        align-items: center; 
        gap: 0.5rem; 
        padding: 0.255rem 0.65rem; 
        border-radius: 0.375rem; 
        font-size: 0.75rem;
        font-weight: 500;
        
        i { font-size: 0.875rem; }
        
        .badge-extra { 
            font-size: 0.7rem; 
            opacity: 0.85; 
            margin-left: 0.25rem;
        }
        
        &.status-restday { color: var(--bs-success);  }
        &.status-holiday { color: var(--bs-warning);  }
        &.status-leave { color: var(--bs-info);  }
        &.status-ob { color: var(--bs-primary);  }
        &.status-absent { color: var(--bs-danger);  }
    }
    
    .time-cell {
        .time-value { 
            font-weight: 600; 
            color: var(--bs-emphasis-color); 
            
            &.time-small { 
                font-size: 0.75rem; 
            }
        }
        
        .time-empty { 
            color: var(--bs-secondary-color); 
            font-weight: 400;
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
            color: var(--bs-secondary-color);
            font-size: 0.75rem; 
            border-radius: 0.25rem;
            transition: background-color 0.15s ease;
            
            &:disabled { 
                cursor: not-allowed; 
                opacity: 0.5; 
            }
            
            &:not(:disabled):hover { 
                background: var(--bs-secondary-bg); 
            }
            
            .has-overtime { 
                color: var(--bs-body-color); 
                font-weight: 600; 
                text-decoration: underline; 
            }
        }
    }
    
    .hours-cell {
        .hours-badge { 
            display: inline-block; 
            padding: 0.3rem 0.6rem; 
            background: var(--bs-secondary-bg); 
            color: var(--bs-body-color); 
            border-radius: 0.25rem; 
            font-weight: 600; 
            font-size: 0.75rem;
            border: 1px solid var(--bs-border-color); 
        }
        
        .ut-badge { 
            background: var(--bs-secondary-bg); 
            color: var(--bs-body-color); 
        }
    }
    
    .double-cell .double-value { 
        font-weight: 700; 
        color: var(--bs-body-color); 
        font-size: 0.875rem;
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
            background: var(--bs-secondary-bg); 
            color: var(--bs-body-color);
            border-radius: 0.25rem; 
            font-size: 0.7rem; 
            font-weight: 600; 
            text-transform: capitalize;
            border: 1px solid var(--bs-border-color);
            
            &.remark-danger { 
                background: rgba(220, 53, 69, 0.1); 
                color: #dc3545; 
                border-color: rgba(220, 53, 69, 0.2);
            }
            
            &.remark-warning { 
                background: rgba(255, 193, 7, 0.1); 
                color: #856404; 
                border-color: rgba(255, 193, 7, 0.2);
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
            border-radius: 0.375rem; 
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            color: var(--bs-secondary-color); 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: all 0.15s ease;
            
            &:hover { 
                background: var(--bs-secondary-bg); 
                color: var(--bs-body-color); 
                border-color: var(--bs-border-color);
            }
        }
    }
    
    .dropdown-menu-modern {
        border: 1px solid var(--bs-border-color); 
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.1); 
        border-radius: 0.5rem;
        padding: 0.5rem; 
        min-width: 200px; 
        background: var(--bs-body-bg);
        
        .dropdown-item {
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.625rem 0.875rem; 
            border-radius: 0.375rem; 
            font-size: 0.875rem; 
            color: var(--bs-body-color);
            transition: all 0.15s ease;
            
            i { 
                width: 18px; 
                color: var(--bs-secondary); 
            }
            
            &:hover { 
                background: var(--bs-secondary-bg); 
                color: var(--bs-body-color); 
            }
            
            &.dropdown-item-danger {
                i { color: #dc3545; }
                
                &:hover { 
                    background: rgba(220, 53, 69, 0.1); 
                    color: #dc3545; 
                    
                    i { color: #dc3545; } 
                }
            }
        }
    }
}
</style>