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
            :actions="[
                { label: 'Close', class: 'btn py-3 px-4 btn-outline-danger', icon: 'fas fa-times', type: 'close' },
                { label: 'Save', class: 'btn py-3 px-4 btn-primary', icon: 'fas fa-save', type: 'save' }
            ]"
            @action="handleAction"
            >
            <div v-if="modalType === 'adjustment'">
                <p>Fill out adjustment form here...</p>
            </div>
            <div v-else-if="modalType === 'leave'">
                <RecordLeaveVue
                    ref="recordLeave"
                    :employee_id="employee_id"
                    :month="month"
                    :year="year"
                    :index="dateIndex"
                    @leave-added="loadTimelogs"
                />
            </div>
            <div v-else-if="modalType === 'absent'">
                <p>Are you sure you want to mark this employee absent?</p>
            </div>
            <div v-else-if="modalType === 'restday'">
                <p>Set this day as rest day?</p>
            </div>
            <div v-else-if="modalType === 'ob'">
                <p>Record official business details here...</p>
            </div>
            <div v-else-if="modalType === 'obs'">
                <p>Recorasdadasd.</p>
            </div>
        </ModalVue>

        <div class="table-wrapper">
            <table class="table table-sm table-border">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Break</th>
                        <th>OT</th>
                        <th>ATRO</th>
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
                    <tr v-for="(log, index) in logs" :key="log.id || index">
                        <td>{{ index + 1 }}</td>

                        <!-- If Absent -->
                        <td v-if="hasRemark(log.remarks, 'absent')" colspan="9" class="text-center bg-danger bg-opacity-10 fw-bold">
                            Absent
                        </td>

                        <!-- If Leave -->
                        <td v-else-if="hasRemark(log.remarks, 'leave')" colspan="9" class="text-center bg-info bg-opacity-10 fw-bold">
                            Leave
                        </td>

                        <!-- If OB -->
                        <td v-else-if="hasRemark(log.remarks, 'ob')" colspan="9" class="text-center bg-warning bg-opacity-10 fw-bold">
                            OB
                        </td>

                        <!-- Otherwise show log details -->
                        <template v-else>
                            <td>{{ log.time_in ?? '-- : --' }}</td>
                            <td>{{ log.time_out ?? '-- : --' }}</td>
                            <td>{{ log.break ?? '-- : -- to -- : --' }}</td>
                            <td>{{ log.overtime }}</td>
                            <td>
                                <input 
                                    type="checkbox" 
                                    :checked="Boolean(log.apply_overtime)" 
                                    @change="toggleOvertime(log)"
                                >
                            </td>
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
                                    class="btn btn-sm p-0 bg-transparent border-0"
                                    type="button"
                                    id="correctionsDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="correctionsDropdown">
                                    <li><button class="dropdown-item" @click="openModal('adjustment', index)">Add or Adjustment</button></li>
                                    <li><button class="dropdown-item" @click="openModal('leave', index)">Record Leave</button></li>
                                    <li>
                                        <button class="dropdown-item" 
                                                v-if="!hasRemark(log.remarks, 'absent')" 
                                                @click="openModal('absent', index)">
                                            Mark Absent
                                        </button>
                                    </li>
                                    <li><button class="dropdown-item" @click="openModal('restday', index)">Set as Restday</button></li>
                                    <li><button class="dropdown-item" @click="openModal('ob', index)">Record OB</button></li>
                                    <li><button class="dropdown-item" @click="openModal('obs', index)">Record OB</button></li>
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

export default {
    components: { TableSkeletonVue, ModalVue, RecordLeaveVue },
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
        handleAction(actionType) {
            if (actionType === "close") this.$refs.modal.close();
            if (actionType === "save") {
                console.log("Saving data for", this.modalType);

                switch (this.modalType) {
                    case 'leave':
                        this.$refs.recordLeave.submitLeave();
                        break;

                    case 'overtime':
                        this.$refs.recordOvertime.submitOvertime();
                        break;

                    case 'adjustment':
                        this.$refs.recordAdjustment.submitAdjustment();
                        break;

                    default:
                        console.warn(`Unknown modal type: ${this.modalType}`);
                }

                // this.$refs.modal.close();
            }
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
</style>
