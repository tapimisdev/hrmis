<template>
    <div class="modal-body">
        <form @submit.prevent="submitOvertime">
            <div class="row">
                <!-- Date -->
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date"
                            v-model="form.date"
                            class="form-control"
                            :class="{ 'is-invalid': errors.date }"
                            disabled
                            required />
                        <span class="text-danger" v-if="errors.date">{{ errors.date[0] }}</span>
                    </div>
                </div>

                <!-- Total Hours -->
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label">Total Hours</label>
                        <input type="number"
                            v-model="form.total_hours"
                            class="form-control"
                            disabled />
                    </div>
                </div>

                <!-- Start Time -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time"
                            v-model="form.start_time"
                            class="form-control"
                            :class="{ 'is-invalid': errors.start_time }"
                            required />
                        <span class="text-danger" v-if="errors.start_time">{{ errors.start_time[0] }}</span>
                    </div>
                </div>

                <!-- End Time -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="time"
                            v-model="form.end_time"
                            class="form-control"
                            :class="{ 'is-invalid': errors.end_time }"
                            required />
                        <span class="text-danger" v-if="errors.end_time">{{ errors.end_time[0] }}</span>
                    </div>
                </div>

                <!-- Reason -->
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea v-model="form.reason"
                            class="form-control"
                            :class="{ 'is-invalid': errors.reason }"
                            rows="3"
                            required>
                        </textarea>
                        <span class="text-danger" v-if="errors.reason">{{ errors.reason[0] }}</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Actions -->
    <div class="modal-footer">
        <button @click="close" class="btn py-3 px-4 btn-outline-danger">
            <i class="me-2 fas fa-times"></i> Close
        </button>

        <button
            :disabled="loading"
            @click="submitOvertime"
            class="btn py-3 px-4 btn-primary">
            <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
            <i v-else class="me-2 fas fa-save"></i>
            {{ loading ? 'Saving...' : 'Save' }}
        </button>
    </div>
</template>

<script>
const token = localStorage.getItem('auth_token');
import axios from "axios";

export default {
    props: {
        employee_id: { type: String, required: true },
        month: Number,
        year: Number,
        index: Number
    },
    data() {
        return {
            loading: false,
            errors: {},
            form: {
                user_id: this.employee_id,
                date: "",
                start_time: "",
                end_time: "",
                total_hours: 0,
                reason: "",
            }
        };
    },
    mounted() {
        this.setDefaultDate();
    },
    methods: {
        async submitOvertime() {
            this.loading = true;
            this.errors = {};

            try {
                const res = await axios.post('/api/add-overtime', this.form, {
                      headers: {
                          'Accept': 'application/json',
                          'Authorization': `Bearer ${token}`
                      }
                  });

                Swal.fire({
                    title: "Success!",
                    text: "Overtime request submitted successfully.",
                    icon: "success"
                }).then(() => {
                    this.$emit("success", res.data);
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
                start_time: "",
                end_time: "",
                total_hours: 0,
                reason: "",
            };
            this.errors = {};
        },
        close() {
            $('#myModal').modal('hide');
        },
        computetotal_hours() {
            if (!this.form.start_time || !this.form.end_time) return;

            // Parse HH:mm into numbers
            const [startHour, startMinute] = this.form.start_time.split(":").map(Number);
            const [endHour, endMinute] = this.form.end_time.split(":").map(Number);

            const start = new Date(1970, 0, 1, startHour, startMinute);
            const end = new Date(1970, 0, 1, endHour, endMinute);

            let diff = (end - start) / (1000 * 60 * 60); // hours
            if (diff < 0) diff += 24; // overnight shift

            this.form.total_hours = parseFloat(diff.toFixed(2));
        },
        setDefaultDate() {
            const year = this.year ?? new Date().getFullYear();
            const month = this.month ?? (new Date().getMonth() + 1);
            const day = this.index ?? new Date().getDate();

            const date = new Date(year, month - 1, day);
            const formatted = date.getFullYear() + '-' +
                            String(date.getMonth() + 1).padStart(2, '0') + '-' +
                            String(date.getDate()).padStart(2, '0');

            this.form.date = formatted;
        },
    },
    watch: {
        year: 'setDefaultDate',
        month: 'setDefaultDate',
        index: 'setDefaultDate',
        'form.start_time': 'computetotal_hours',
        'form.end_time': 'computetotal_hours'
    }
};
</script>
