<template>
    <p class="fw-bold">Employee Attendance Table</p>

    <div class="card p-4">
        <p class="fs-5 text-uppercase fw-bolder">
            <span class="text-danger">{{ formattedMonth }} {{ year }}</span>
        </p>
        <!-- Modal -->
        <ModalVue
            ref="modal"
            :type="modalType"
            >
            <div v-if="modalType === 'adjustment'">
                <AddTimeVue
                    ref="recordAdjustment"
                    :employee_id="employee_id"
                    :month="month"
                    :year="year"
                    :index="dateIndex"
                    @success="loadTimelogs"
                />
            </div>
            <div v-else-if="modalType === 'leave'">
                <RecordLeaveVue
                    ref="recordLeave"
                    :employee_id="employee_id"
                    :month="month"
                    :year="year"
                    :index="dateIndex"
                    @success="loadTimelogs"
                />
            </div>
            <div v-else-if="modalType === 'overtime'">
                <AddOvertimeVue
                    ref="addOvertime"
                    :employee_id="employee_id"
                    :month="month"
                    :year="year"
                    :index="dateIndex"
                    @success="loadTimelogs"
                />
            </div>
            <div v-else-if="modalType === 'absent'">
                <p>Are you sure you want to mark this employee absent?</p>
            </div>
            <div v-else-if="modalType === 'ob'">
                <p>Record official business details here...</p>
            </div>
        </ModalVue>

        <div class="table-wrapper">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>In</th>
                        <th>Break</th>
                        <th>Out</th>
                        <th>Overtime</th>
                        <th>OT (HRS)</th>
                        <th>Hrs</th>
                        <th>2x</th>
                        <th>Tar/UT</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <!-- Skeleton Loader -->
                <TableSkeletonVue v-if="loading" :rows="31" :columns="10" />

                <!-- Actual Data -->
                <tbody v-else>
                    <tr 
                        v-for="(log, index) in logs" 
                        :key="log.id || index" 
                        :class="{ 'highlight-today': hasRemark(log.remarks, 'today') }"
                        >

                        <td>{{ index + 1 }}</td>

                        <!-- If Absent -->
                        <td v-if="hasRemark(log.remarks, 'restday')" colspan="9" class="text-center text-success fw-bold">
                            Restday 
                        </td>

                        <!-- If Leave -->
                        <td v-else-if="hasRemark(log.remarks, 'leave')" colspan="9" class="text-center text-info fw-bold">
                            Leave 
                        </td>

                        <!-- If OB -->
                        <td v-else-if="hasRemark(log.remarks, 'ob')" colspan="9" class="text-center text-warning fw-bold">
                            OB 
                        </td>

                        <!-- If Absent -->
                        <td v-else-if="hasRemark(log.remarks, 'absent')" colspan="9" class="text-center text-danger fw-bold">
                            Absent 
                        </td>


                        <!-- Otherwise show log details -->
                        <template v-else>
                            <td>{{ log.time_in ?? '-- : --' }}</td>
                            <td>{{ log.break ?? '-- : -- to -- : --' }}</td>
                            <td>{{ log.time_out ?? '-- : --' }}</td>
                            <td>{{ log.overtime ?? '-- : -- to -- : --' }}</td>
                            <td>{{ log.ot_hrs }}</td>
                            <td>{{ log.total_paid_hrs }}</td>
                            <td>{{ log.doble }}</td>
                            <td>{{ log.late_undertime }}</td>
                            <!-- Remarks Column -->
                            <td>
                                <div class="mb-0 ps-3 d-flex flex-wrap">
                                    <span
                                        v-for="(remark, rIndex) in log.remarks || []"
                                        :key="rIndex"
                                        class="me-2 mb-1"
                                    >
                                        {{ remark }}
                                        <span v-if="rIndex < (log.remarks || []).length - 1">|</span>
                                    </span>
                                </div>
                            </td>
                        </template>

                        <!-- Actions Column -->
                        <td>
                            <div class="dropdown w-100 d-flex justify-content-center">
                                <button
                                    class="btn btn-sm p-0 bg-transparent w-100 border-0"
                                    type="button"
                                    id="correctionsDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="correctionsDropdown">
                                    <li><button class="dropdown-item" @click="openModal('adjustment', index)">Add or Adjustment</button></li>
                                    <li><button class="dropdown-item" @click="openModal('overtime', index)">Add Overtime</button></li>
                                    <li><button class="dropdown-item" @click="openModal('leave', index)">Record Leave</button></li>
                                    <li>
                                        <button class="dropdown-item" 
                                                v-if="!hasRemark(log.remarks, 'absent')" 
                                                @click="openModal('absent', index)">
                                            Mark Absent
                                        </button>
                                    </li>
                                    <li><button class="dropdown-item" @click="openModal('ob', index)">Record OB</button></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>
    </div>
</template>

<script>
import TableSkeletonVue from '../../../components/TableSkeletonVue.vue';
import axios from 'axios';
import ModalVue from './modal/ModalVue.vue';

// Modals
import RecordLeaveVue from './modal/RecordLeaveVue.vue';
import AddTimeVue from './modal/AddTimeVue.vue';
import AddOvertimeVue from './modal/AddOvertimeVue.vue';

export default {
    components: { TableSkeletonVue, ModalVue, RecordLeaveVue, AddTimeVue, AddOvertimeVue },
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
        };
    },
    computed: {
        formattedMonth() {
            return new Date(this.year, this.month - 1).toLocaleString("default", {
                month: "long"
            });
        }
    },
    methods: {
        async loadTimelogs() {
            this.loading = true;
            try {
                const response = await axios.get(
                    `/admin/timekeeping/daily-time-record/${this.employee_id}/show`,
                    { params: { month: this.month, year: this.year } }
                );
                this.logs = response.data;
                console.log(response.data);
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
        openModal(type, index) {
            this.modalType = type;
            this.dateIndex = index + 1;
            this.$refs.modal.open();
        },
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

.table-wrapper {
    max-height: 520px;
    overflow-y: auto;
    table {
        position: relative;
        max-height: 620px;
        overflow-x: scroll;
        
        thead th {
            font-weight: 600;
            font-size: 14px !important;
            position: sticky;
            top: 0;
            background-color: $primary;
            color: $light;
            z-index: 1000;
        }

        tbody td {
            height: 60px !important;
            font-size: 12px;
        }
    }
}
.table-wrapper::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
  background: #f1f1f1; /* track color */
  border-radius: 8px;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background: rgba($color: $primary, $alpha: 0.6);
  border-radius: 8px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: $primary;
}

.highlight-today td:not(:first-child):not(:last-child) {
    
}
</style>
