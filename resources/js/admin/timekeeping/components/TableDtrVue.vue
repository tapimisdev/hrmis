<template>
    <p class="fw-bold">Employee Attendance Table</p>
    <div class="card p-4">
        <p class="fs-5 text-uppercase fw-bolder">
            <span class="text-danger">{{ formattedMonth }} {{ year }}</span>
        </p>

        <div class="table-wrapper">
            <table class="table table-sm table-border">
                <thead> 
                    <tr>
                        <th>#</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Break</th>
                        <th>Overtime</th>
                        <th>Total Hrs</th>
                        <th>Doble</th>
                        <th>Late/Undertime</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <!-- Show skeleton while loading -->
                <TableSkeletonVue v-if="loading" :rows="5" :columns="9" />

                <!-- Show data once loaded -->
                <tbody v-else>
                    <tr v-for="(log, index) in logs" :key="index">
                        <td>{{ index + 1 }}</td>
                        <td>{{ log.time_in }}</td>
                        <td>{{ log.time_out }}</td>
                        <td>{{ log.break }}</td>
                        <td>{{ log.overtime }}</td>
                        <td>{{ log.total_paid_hrs }}</td>
                        <td>{{ log.doble }}</td>
                        <td>{{ log.late_undertime }}</td>
                        <td>
                            <div class="mb-0 ps-3 d-flex flex-wrap">
                                <span
                                    class="me-2 mb-1"
                                    v-for="(remark, rIndex) in log.remarks"
                                    :key="rIndex"
                                    >
                                    {{ remark }}
                                    <span v-if="rIndex < log.remarks.length - 1">|</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <!-- Dropdown for Corrections -->
                            <div class="dropdown w-100">
                                <button 
                                    class="btn btn-outline-primary btn-sm w-100 dropdown-toggle" 
                                    type="button" 
                                    id="correctionsDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false"
                                >
                                    <i class="fas fa-pencil-alt me-1"></i>
                                </button>
                                
                                <ul class="dropdown-menu w-100" aria-labelledby="correctionsDropdown">
                                    <li><button class="dropdown-item">Time Adjustment</button></li>
                                    <li><button class="dropdown-item">Record Leave</button></li>
                                    <li><button class="dropdown-item">Mark Absent</button></li>
                                    <li><button class="dropdown-item">Set as Restday</button></li>
                                    <li><button class="dropdown-item">Record OB</button></li>
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

export default {
    components: { TableSkeletonVue },
    props: {
        employee_id: {
            type: Number,
            required: true
        },
        month: Number,
        year: Number
    },
    data() {
        return {
            logs: [],
            showRemarks: null,
            loading: false
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
                    {
                        params: {
                            month: this.month,
                            year: this.year
                        }
                    }
                );
                console.log(response.data);
                this.logs = response.data;
            } catch (error) {
                console.error("Error fetching logs:", error);
            }

            this.loading = false
        },
    },
    watch: {
        month: 'loadTimelogs',
        year: 'loadTimelogs'
    },
    async mounted() {
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
        margin-top: 1rem;
        thead {
            tr {
                th {
                    font-weight: 600;
                    font-size: 14px !important;
                    position: sticky;
                    top: 0;
                    background-color: $primary;
                    color: $light;
                    z-index: 9999;
                }
            }
        }
        tbody {
            tr {
                td {
                    height: 60px !important;
                    font-size: 10;
                }
            }
        }
    }
}
</style>
