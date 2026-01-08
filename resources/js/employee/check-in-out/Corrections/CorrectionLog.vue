<template>
    <div
        class="modal fade"
        id="myModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div
            class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"
        >
            <div class="modal-content modern-modal">
                <!-- Header -->
                <div class="modal-header modern-header border-bottom">
                    <div class="header-content mb-3">
                        <div class="icon-wrapper">
                            <i class="text-light fas fa-clock"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">Request for Correction</h5>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>


                <!-- Content -->
                <div class="modal-body">
                    <form @submit.prevent="submitLogs">
                        <div class="row">
                            <!-- Date -->
                            <div class="col-md-12">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Date <span class="text-danger">( required )</span></label>
                                    <input type="date"
                                        v-model="form.date"
                                        class="form-control"
                                        disabled
                                        :class="{ 'is-invalid': errors.date }"
                                        required />
                                    <span class="text-danger" v-if="errors.date">{{ errors.date[0] }}</span>
                                </div>
                            </div>

                            <!-- Time In -->
                            <div class="col-md-3">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Time In <span class="text-danger">( required )</span></label>
                                    <input type="time"
                                        step="1"
                                        v-model="form.time_in"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.time_in }"
                                        required />
                                    <span class="text-danger" v-if="errors.time_in">{{ errors.time_in[0] }}</span>
                                </div>
                            </div>

                            <!-- Break Out -->
                            <div class="col-md-3">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Break Out</label>
                                    <input type="time"
                                        step="1"
                                        v-model="form.break_out"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.break_out }" />
                                    <span class="text-danger" v-if="errors.break_out">{{ errors.break_out[0] }}</span>
                                </div>
                            </div>

                            <!-- Break In -->
                            <div class="col-md-3">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Break In</label>
                                    <input type="time"
                                        step="1"
                                        v-model="form.break_in"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.break_in }" />
                                    <span class="text-danger" v-if="errors.break_in">{{ errors.break_in[0] }}</span>
                                </div>
                            </div>

                            <!-- Time Out -->
                            <div class="col-md-3">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Time Out <span class="text-danger">( required )</span></label>
                                    <input type="time"
                                        step="1"
                                        v-model="form.time_out"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.time_out }"
                                        required />
                                    <span class="text-danger" v-if="errors.time_out">{{ errors.time_out[0] }}</span>
                                </div>
                            </div>

                            <!-- Overtime In -->
                            <div class="col-md-6">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Overtime In</label>
                                    <input type="time"
                                        step="1"
                                        v-model="form.overtime_in"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.overtime_in }"
                                        required />
                                    <span class="text-danger" v-if="errors.overtime_in">{{ errors.overtime_in[0] }}</span>
                                </div>
                            </div>

                            <!-- Overtime Out -->
                            <div class="col-md-6">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Overtime Out</label>
                                    <input type="time"
                                        step="1"
                                        v-model="form.overtime_out"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.overtime_out }"
                                        required />
                                    <span class="text-danger" v-if="errors.overtime_out">{{ errors.overtime_out[0] }}</span>
                                </div>
                            </div>

                            <!-- Attachment -->
                            <div class="col-md-12">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Attachment <span class="text-danger">( required )</span></label>
                                    <input 
                                        type="file" 
                                        @change="handleFileUpload"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.attachment }"
                                    />
                                    <span class="text-danger" v-if="errors.attachment">{{ errors.attachment[0] }}</span>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="col-md-12">
                                <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1"/>
                                <div v-else class="mb-3">
                                    <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                    <textarea
                                        v-model="form.remarks"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Enter your remarks"
                                        :class="{ 'is-invalid': errors.remarks }"
                                        required
                                    ></textarea>
                                    <span class="text-danger" v-if="errors.remarks">{{ errors.remarks[0] }}</span>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- Actions -->
                <div class="modal-footer">
                    <button @click="close" class="btn py-3 px-4 btn-danger">
                        <i class="me-2 fas fa-times"></i>
                        Close
                    </button>

                    <button
                        :disabled="loading"
                        @click="submitLogs"
                        class="btn py-3 px-4 btn-primary">
                        
                        <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
                        <i v-else class="me-2 fas fa-save"></i>

                        {{ loading ? 'Requesting...' : 'Request' }}
                    </button>

                </div>
            </div>
        </div>
    </div>
</template>
<script>
const token = localStorage.getItem('auth_token');
import axios from "axios";
import FormSkeletonVue from "../../../components/FormSkeletonVue.vue";

export default {
    name: 'correction modal',
    components: { FormSkeletonVue },
    data() {
        return {
            employee_id: '',
            date: null,
            loading: false,
            initial_loading: false,
            errors: {},
            shifts: [],
            weeklySchedules: [],
            form: {
                date: "",
                time_in: "",
                break_out: "",
                break_in: "",
                time_out: "",
                overtime_in: "",
                overtime_out: "",
                shift: "",
                weeklyschedule: "",
                attachment: null,
                remarks: "",
            }
        };
    },
    async mounted() {
        this.loadShifts();
        this.loadSchedules();
        this.setClickedDate();
    },
    methods: {
        open(date) {
            this.form.date = date;

            this.loadTimelog();

            $("#myModal").modal("show");
        },
        async loadTimelog() {
            this.initial_loading = true;
            this.errors = {};
            try {
                const res = await axios.get("/api/request-correction", {
                    headers: {
                        Authorization: `Bearer ${token}`
                    },
                    params: {
                        date: this.form.date // or start_date / end_date
                    }
                });

                const logs = res.data.data; // assuming the JSON you provided
                if (logs.length > 0) {
                    const log = logs[0]; // take the first record

                    this.form = {
                        user_id: this.employee_id,
                        date: log.date || "",
                        time_in: log.time_in ? log.time_in.split(' ')[1] : "",
                        break_out: log.break_out ? log.break_out.split(' ')[1] : "",
                        break_in: log.break_in ? log.break_in.split(' ')[1] : "",
                        time_out: log.time_out ? log.time_out.split(' ')[1] : "",
                        overtime_in: log.overtime_in ? log.overtime_in.split(' ')[1] : "",
                        overtime_out: log.overtime_out ? log.overtime_out.split(' ')[1] : "",
                        shift: log.shift_id || "",
                        weeklyschedule: log.work_schedule_id || ""
                    };
                } else {
                    // No logs found, reset form
                    this.form = {
                        user_id: this.employee_id,
                        date: this.form.date, // keep current selected date
                        time_in: "",
                        break_out: "",
                        break_in: "",
                        time_out: "",
                        overtime_in: "",
                        overtime_out: "",
                        shift: "",
                        weeklyschedule: ""
                    };
                }

            } catch (error) {
                console.error("Failed to load timelogs:", error);
            } finally {
                this.initial_loading = false;
            }
        },
        async submitLogs() {
            this.loading = true;
            this.errors = {};

            try {
                const formData = new FormData();

                // Append all form fields
                for (let key in this.form) {
                    if (this.form[key] !== null) {
                        formData.append(key, this.form[key]);
                    }
                }

                const response = await axios.post('/api/request-correction', formData, {
                    headers: { 
                        'Accept': 'application/json', 
                        Authorization: `Bearer ${token}`,
                        'Content-Type': 'multipart/form-data'
                    }
                });

                Swal.fire({
                    title: "Success!",
                    text: "Logs saved successfully.",
                    icon: "success"
                }).then(() => {
                    this.$emit("success", response.data);
                    this.close();
                    this.resetForm();
                });

            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    Swal.fire("Error", error.response?.data?.message || "Something went wrong.", "error");
                }
            } finally {
                this.loading = false;
            }
        },
        resetForm() {
            this.form = {
                user_id: this.employee_id,
                date: "",
                time_in: "",
                break_out: "",
                break_in: "",
                time_out: "",
                overtime_in: "",
                overtime_out: "",
                shift: "",
                weeklyschedule: ""
            };
            this.setClickedDate();
        },
        handleFileUpload(event) {
            this.form.attachment = event.target.files[0]; // store the selected file
        },
        close() {
            $('#myModal').modal('hide');
        }
    },
    watch: {
        month: 'setClickedDate',
        year: 'setClickedDate',
        index: 'setClickedDate',
    }
};
</script>

<style scoped>
span.text-danger {
    font-size: 0.875rem;
}
</style>