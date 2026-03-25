<template>
    <!-- Content -->
    <div class="modal-body">
        <form @submit.prevent="submitLogs">
            <div class="row">
                <!-- Date -->
                <div class="col-md-4">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label"
                            >Date <span class="text-danger">*</span></label
                        >
                        <input
                            type="date"
                            v-model="form.date"
                            class="form-control"
                            disabled
                            :class="{ 'is-invalid': errors.date }"
                            required
                        />
                        <span class="text-danger" v-if="errors.date">{{
                            errors.date[0]
                        }}</span>
                    </div>
                </div>

                <!-- Shift -->
                <div class="col-md-4">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label"
                            >Shift <span class="text-danger">*</span></label
                        >
                        <select
                            v-model="form.shift"
                            class="form-select"
                            :class="{ 'is-invalid': errors.shift }"
                            required
                        >
                            <option value="">Select Shift</option>
                            <option
                                v-for="shift in shifts"
                                :key="shift.id"
                                :value="shift.id"
                            >
                                {{ shift.name }}
                            </option>
                        </select>
                        <span class="text-danger" v-if="errors.shift">{{
                            errors.shift[0]
                        }}</span>
                    </div>
                </div>

                <!-- Weekly Schedule -->
                <div class="col-md-4">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label"
                            >Weekly Schedule
                            <span class="text-danger">*</span></label
                        >
                        <select
                            v-model="form.weeklyschedule"
                            class="form-select"
                            :class="{ 'is-invalid': errors.weeklyschedule }"
                            required
                        >
                            <option value="">Select Schedule</option>
                            <option
                                v-for="schedule in weeklySchedules"
                                :key="schedule.id"
                                :value="schedule.id"
                            >
                                {{ schedule.name }}
                            </option>
                        </select>
                        <span
                            class="text-danger"
                            v-if="errors.weeklyschedule"
                            >{{ errors.weeklyschedule[0] }}</span
                        >
                    </div>
                </div>

                <!-- Time In -->
                <div class="col-md-3">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label"
                            >Time In <span class="text-danger">*</span></label
                        >
                        <input
                            type="time"
                            step="1"
                            v-model="form.time_in"
                            class="form-control"
                            :class="{ 'is-invalid': errors.time_in }"
                            required
                        />
                        <span class="text-danger" v-if="errors.time_in">{{
                            errors.time_in[0]
                        }}</span>
                    </div>
                </div>

                <!-- Break Out -->
                <div class="col-md-3">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label">Break Out</label>
                        <input
                            type="time"
                            step="1"
                            v-model="form.break_out"
                            class="form-control"
                            :class="{ 'is-invalid': errors.break_out }"
                        />
                        <span class="text-danger" v-if="errors.break_out">{{
                            errors.break_out[0]
                        }}</span>
                    </div>
                </div>

                <!-- Break In -->
                <div class="col-md-3">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label">Break In</label>
                        <input
                            type="time"
                            step="1"
                            v-model="form.break_in"
                            class="form-control"
                            :class="{ 'is-invalid': errors.break_in }"
                        />
                        <span class="text-danger" v-if="errors.break_in">{{
                            errors.break_in[0]
                        }}</span>
                    </div>
                </div>

                <!-- Time Out -->
                <div class="col-md-3">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label"
                            >Time Out <span class="text-danger">*</span></label
                        >
                        <input
                            type="time"
                            step="1"
                            v-model="form.time_out"
                            class="form-control"
                            :class="{ 'is-invalid': errors.time_out }"
                            required
                        />
                        <span class="text-danger" v-if="errors.time_out">{{
                            errors.time_out[0]
                        }}</span>
                    </div>
                </div>

                <!-- Overtime In -->
                <div class="col-md-6">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label">Overtime In</label>
                        <input
                            type="time"
                            step="1"
                            v-model="form.overtime_in"
                            class="form-control"
                            :class="{ 'is-invalid': errors.overtime_in }"
                            required
                        />
                        <span class="text-danger" v-if="errors.overtime_in">{{
                            errors.overtime_in[0]
                        }}</span>
                    </div>
                </div>

                <!-- Overtime Out -->
                <div class="col-md-6">
                    <FormSkeletonVue
                        v-if="initial_loading"
                        :rows="1"
                        :columns="1"
                    />
                    <div v-else class="mb-3">
                        <label class="form-label">Overtime Out</label>
                        <input
                            type="time"
                            step="1"
                            v-model="form.overtime_out"
                            class="form-control"
                            :class="{ 'is-invalid': errors.overtime_out }"
                            required
                        />
                        <span class="text-danger" v-if="errors.overtime_out">{{
                            errors.overtime_out[0]
                        }}</span>
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
            class="btn py-3 px-4 btn-primary"
        >
            <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
            <i v-else class="me-2 fas fa-save"></i>

            {{ loading ? "Saving..." : "Save" }}
        </button>
    </div>
</template>

<script>
const token = localStorage.getItem("auth_token");
import axios from "axios";
import FormSkeletonVue from "../../../../components/FormSkeletonVue.vue";

export default {
    components: { FormSkeletonVue },
    props: {
        employee_no: { type: String, required: true },
        month: Number,
        year: Number,
        index: Number,
    },
    data() {
        return {
            loading: false,
            initial_loading: false,
            errors: {},
            shifts: [],
            weeklySchedules: [],
            form: {
                user_id: this.employee_no,
                date: "",
                time_in: "",
                break_out: "",
                break_in: "",
                time_out: "",
                overtime_in: "",
                overtime_out: "",
                shift: "",
                weeklyschedule: "",
            },
        };
    },
    async mounted() {
        this.loadShifts();
        this.loadSchedules();
        this.setClickedDate();
    },
    methods: {
        async loadShifts() {
            try {
                const res = await axios.get("/api/shifts", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.shifts = res.data.data;
            } catch (error) {
            }
        },
        async loadSchedules() {
            try {
                const res = await axios.get("/api/work-schedules", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.weeklySchedules = res.data.data;
            } catch (error) {
            }
        },
        async loadTimelog() {
            this.initial_loading = true;
            this.errors = {};
            try {
                const res = await axios.get("/api/fetch-timelogs", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                    params: {
                        user_id: this.employee_no,
                        date: this.form.date, // or start_date / end_date
                    },
                });

                const logs = res.data.data; // assuming the JSON you provided
                if (logs.length > 0) {
                    const log = logs[0]; // take the first record

                    this.form = {
                        user_id: this.employee_no,
                        date: log.date || "",
                        time_in: log.time_in ? log.time_in.split(" ")[1] : "",
                        break_out: log.break_out
                            ? log.break_out.split(" ")[1]
                            : "",
                        break_in: log.break_in
                            ? log.break_in.split(" ")[1]
                            : "",
                        time_out: log.time_out
                            ? log.time_out.split(" ")[1]
                            : "",
                        overtime_in: log.overtime_in
                            ? log.overtime_in.split(" ")[1]
                            : "",
                        overtime_out: log.overtime_out
                            ? log.overtime_out.split(" ")[1]
                            : "",
                        shift: log.shift_id || "",
                        weeklyschedule: log.work_schedule_id || "",
                    };
                } else {
                    // No logs found, reset form
                    this.form = {
                        user_id: this.employee_no,
                        date: this.form.date, // keep current selected date
                        time_in: "",
                        break_out: "",
                        break_in: "",
                        time_out: "",
                        overtime_in: "",
                        overtime_out: "",
                        shift: "",
                        weeklyschedule: "",
                    };
                }
            } catch (error) {
            } finally {
                this.initial_loading = false;
            }
        },
        setClickedDate() {
            // Use passed props (month, year, index) to set date
            const month = this.month ?? new Date().getMonth() + 1;
            const year = this.year ?? new Date().getFullYear();
            const day = this.index ?? new Date().getDate();

            const date = new Date(year, month - 1, day);
            const formatted =
                date.getFullYear() +
                "-" +
                String(date.getMonth() + 1).padStart(2, "0") +
                "-" +
                String(date.getDate()).padStart(2, "0");
            this.form.date = formatted;

            this.loadTimelog();
        },
        async submitLogs() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await axios.post("/api/add-time", this.form, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                });

                Swal.fire({
                    title: "Success!",
                    text: "Logs saved successfully.",
                    icon: "success",
                }).then(() => {
                    this.$emit("success", response.data);
                    this.close();
                    this.resetForm();
                });
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    Swal.fire(
                        "Error",
                        error.response?.data?.message ||
                            "Something went wrong.",
                        "error",
                    );
                }
            } finally {
                this.loading = false;
            }
        },
        resetForm() {
            this.form = {
                user_id: this.employee_no,
                date: "",
                time_in: "",
                break_out: "",
                break_in: "",
                time_out: "",
                overtime_in: "",
                overtime_out: "",
                shift: "",
                weeklyschedule: "",
            };
            this.setClickedDate();
        },
        close() {
            $("#myModal").modal("hide");
        },
    },
    watch: {
        month: "setClickedDate",
        year: "setClickedDate",
        index: "setClickedDate",
    },
};
</script>

<style scoped>
span.text-danger {
    font-size: 0.875rem;
}
</style>
