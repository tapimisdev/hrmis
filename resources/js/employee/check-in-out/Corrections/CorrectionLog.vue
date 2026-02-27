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
                    <div class="header-content mb-0">
                        <div class="icon-wrapper">
                            <i class="text-light fas fa-clock"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">
                                Timelog Correction Request
                            </h5>
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
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Date
                                        <span
                                            class="text-danger fw-bold text-uppercase"
                                            style="font-size: 10px"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        type="date"
                                        v-model="form.date"
                                        class="form-control"
                                        disabled
                                        :class="{ 'is-invalid': errors.date }"
                                        required
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.date"
                                        >{{ errors.date[0] }}</span
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
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Time In
                                        <span
                                            class="text-danger fw-bold text-uppercase"
                                            style="font-size: 10px"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        type="time"
                                        step="1"
                                        v-model="form.time_in"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.time_in,
                                        }"
                                        required
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.time_in"
                                        >{{ errors.time_in[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Break Out -->
                            <div class="col-md-3">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Break Out</label
                                    >
                                    <input
                                        type="time"
                                        step="1"
                                        v-model="form.break_out"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.break_out,
                                        }"
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.break_out"
                                        >{{ errors.break_out[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Break In -->
                            <div class="col-md-3">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Break In</label
                                    >
                                    <input
                                        type="time"
                                        step="1"
                                        v-model="form.break_in"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.break_in,
                                        }"
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.break_in"
                                        >{{ errors.break_in[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Time Out -->
                            <div class="col-md-3">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Time Out
                                        <span
                                            class="text-danger fw-bold text-uppercase"
                                            style="font-size: 10px"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        type="time"
                                        step="1"
                                        v-model="form.time_out"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.time_out,
                                        }"
                                        required
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.time_out"
                                        >{{ errors.time_out[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Overtime In -->
                            <div class="col-md-6">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Overtime In</label
                                    >
                                    <input
                                        type="time"
                                        step="1"
                                        v-model="form.overtime_in"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.overtime_in,
                                        }"
                                        required
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.overtime_in"
                                        >{{ errors.overtime_in[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Overtime Out -->
                            <div class="col-md-6">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Overtime Out</label
                                    >
                                    <input
                                        type="time"
                                        step="1"
                                        v-model="form.overtime_out"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.overtime_out,
                                        }"
                                        required
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.overtime_out"
                                        >{{ errors.overtime_out[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Concern Type-->
                            <div class="col-md-12">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />

                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                    >
                                        Concern Type
                                        <span
                                            class="text-danger fw-bold text-uppercase"
                                            style="font-size: 10px"
                                            >*</span
                                        >
                                    </label>

                                    <div
                                        :class="[
                                            { 'is-invalid': errors.concern },
                                            'd-flex gap-4',
                                        ]"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="concern_oo"
                                                value="OO"
                                                v-model="form.concern"
                                                name="concern"
                                            />
                                            <label
                                                class="form-check-label"
                                                for="concern_oo"
                                            >
                                                OO - System Out of Order
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="concern_f"
                                                value="F"
                                                v-model="form.concern"
                                                name="concern"
                                            />
                                            <label
                                                class="form-check-label"
                                                for="concern_f"
                                            >
                                                F - Failure to perform actions
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="concern_ie"
                                                value="IE"
                                                v-model="form.concern"
                                                name="concern"
                                            />
                                            <label
                                                class="form-check-label"
                                                for="concern_ie"
                                            >
                                                IE - Incorrect Entry
                                            </label>
                                        </div>
                                    </div>

                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.concern"
                                    >
                                        {{ errors.concern[0] }}
                                    </span>
                                </div>
                            </div>

                            <!-- Reasons -->
                            <div class="col-md-12">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Explain your concern
                                        <span
                                            class="text-danger fw-bold text-uppercase"
                                            style="font-size: 10px"
                                            >*</span
                                        ></label
                                    >
                                    <textarea
                                        v-model="form.remarks"
                                        class="form-control"
                                        rows="8"
                                        placeholder="Write something..."
                                        :class="{
                                            'is-invalid': errors.remarks,
                                        }"
                                        required
                                    ></textarea>
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.remarks"
                                        >{{ errors.remarks[0] }}</span
                                    >
                                </div>
                            </div>

                            <!-- Attachment -->
                            <div class="col-md-12">
                                <FormSkeletonVue
                                    v-if="initial_loading"
                                    :rows="1"
                                    :columns="1"
                                />
                                <div v-else class="mb-4">
                                    <label
                                        class="form-label fw-bold text-uppercase"
                                        style="font-size: 11px"
                                        >Supporting Attachment/s
                                        <span
                                            class="text-danger fw-bold text-uppercase"
                                            style="font-size: 10px"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        type="file"
                                        @change="handleFileUpload"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.attachment,
                                        }"
                                    />
                                    <span
                                        class="text-danger fw-bold text-uppercase"
                                        style="font-size: 10px"
                                        v-if="errors.attachment"
                                        >{{ errors.attachment[0] }}</span
                                    >
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
                        <i
                            v-if="loading"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="me-2 fas fa-save"></i>

                        {{ loading ? "Requesting..." : "Request" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
const token = localStorage.getItem("auth_token");
import axios from "axios";
import FormSkeletonVue from "../../../components/FormSkeletonVue.vue";

export default {
    name: "correction modal",
    components: { FormSkeletonVue },
    data() {
        return {
            employee_id: "",
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
            },
        };
    },
    async mounted() {},
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
                        Authorization: `Bearer ${token}`,
                    },
                    params: {
                        date: this.form.date, // or start_date / end_date
                    },
                });

                const logs = res.data.data; // assuming the JSON you provided
                if (logs.length > 0) {
                    const log = logs[0]; // take the first record

                    this.form = {
                        user_id: this.employee_id,
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
                        user_id: this.employee_id,
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

                const response = await axios.post(
                    "/api/request-correction",
                    formData,
                    {
                        headers: {
                            Accept: "application/json",
                            Authorization: `Bearer ${token}`,
                            "Content-Type": "multipart/form-data",
                        },
                    },
                );

                Swal.fire({
                    title: "Success!",
                    text: "TCR requested succesfully.",
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
                user_id: this.employee_id,
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
        handleFileUpload(event) {
            this.form.attachment = event.target.files[0]; // store the selected file
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
