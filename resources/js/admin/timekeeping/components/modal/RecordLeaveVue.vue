<template>
    <div>
        <form @submit.prevent="submitLeave">
            <div class="row">
                <div class="col-md-12">
                    <!-- Leave Type -->
                    <div class="mb-3">
                        <label class="form-label">Leave Type <span class="text-danger"> *</span></label>
                        <select v-model="form.leave_id" 
                                class="form-select" 
                                :class="{ 'is-invalid': errors.leave_id }" 
                                required
                                >
                            <option value="">Select Leave</option>
                            <option v-for="leave in leaveTypes" :key="leave.id" :value="leave.id">
                                {{ leave.name }}
                            </option>
                        </select>
                        <span class="text-danger leave_id_error" v-if="errors.leave_id">{{ errors.leave_id[0] }}</span>
                    </div>
                </div>
                <div class="col-md-5">
                     <!-- Start Date -->
                    <div class="mb-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" 
                                disabled 
                                v-model="form.start_date" 
                                class="form-control" 
                                :class="{ 'is-invalid': errors.start_date }"
                                required />
                        <span class="text-danger start_date_error" v-if="errors.start_date">{{ errors.start_date[0] }}</span>
                    </div>
                </div>
                <div class="col-md-5">
                    <!-- End Date -->
                    <div class="mb-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" 
                                v-model="form.end_date" 
                                class="form-control" 
                                :class="{ 'is-invalid': errors.end_date }"
                                required />
                        <span class="text-danger end_date_error" v-if="errors.end_date">{{ errors.end_date[0] }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <!-- Number of Days -->
                    <div class="mb-3">
                        <label class="form-label">Days <span class="text-danger">*</span></label>
                        <input type="number"
                            v-model.number="form.days"
                            disabled
                            class="form-control"
                            :class="{ 'is-invalid': errors.days }"
                            min="1"
                            @input="updateEndDate"
                            required />
                        <span class="text-danger days_error" v-if="errors.days">{{ errors.days[0] }}</span>
                    </div>
                </div>
                <div class="col-md-12">
                    <!-- Reason -->
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger"> *</span></label>
                        <textarea v-model="form.reason" 
                                    class="form-control" 
                                    :class="{ 'is-invalid': errors.reason }"
                                    rows="3" 
                                    required>
                                </textarea>
                        <span class="text-danger reason_error" v-if="errors.reason">{{ errors.reason[0] }}</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
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
            leaveTypes: [], // fix: should be array
            form: {
                user_id: this.employee_id,
                leave_id: "",
                days: 1,
                start_date: "",
                end_date: "",
                reason: "",
                status: "pending"
            }
        };
    },
    mounted() {
        this.loadLeaves();
        this.getDefaultDate(); // set initial date
    },
    methods: {
        async loadLeaves() {
            this.loading = true;
            try {
                const response = await axios.get(`/api/leaves`);
                this.leaveTypes = response.data.leaves;
            } catch (error) {
                console.error("Error fetching leaves:", error);
            }
            this.loading = false;
        },
       async submitLeave() {
            this.loading = true;
            this.errors = {}; // reset previous errors

            try {
                // Prepare form data for file uploads if needed
                const formData = new FormData();
                for (const key in this.form) {
                    formData.append(key, this.form[key]);
                }

                const response = await axios.post('/api/leaves', formData, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'multipart/form-data'
                    }
                });

                // Success
                Swal.fire({
                    title: "Success!",
                    text: "Your leave application has been submitted.",
                    icon: "success"
                }).then(() => {
                    this.$emit("leave-added", response.data);
                    this.resetForm();
                });

            } catch (error) {
                if (error.response?.status === 422) {
                    // Laravel validation errors
                    this.errors = error.response.data.errors;
                    // Optional: scroll to first error
                    const firstErrorField = Object.keys(this.errors)[0];
                    document.getElementById(firstErrorField)?.scrollIntoView({ behavior: 'smooth' });
                } else {
                    Swal.fire({
                        title: "Oops!",
                        text: error.response?.data?.message || "Something went wrong.",
                        icon: "error"
                    });
                }
            } finally {
                this.loading = false;
            }
        },
        resetForm() {
            this.form = {
                user_id: this.employee_id,
                leave_id: "",
                start_date: "",
                days: 1,
                end_date: "",
                reason: "",
                status: "pending"
            };
            this.errors = {};
        },
        getDefaultDate() {
            const month = this.month ?? new Date().getMonth() + 1;
            const year = this.year ?? new Date().getFullYear();
            const day = this.index ?? new Date().getDate();

            const date = new Date(year, month - 1, day);
            const formatted = date.getFullYear() + '-' +
                              String(date.getMonth() + 1).padStart(2, '0') + '-' +
                              String(date.getDate()).padStart(2, '0');

            this.form.start_date = formatted;
            this.form.end_date = formatted;
        }
    },
    watch: {
        index: 'getDefaultDate',
        month: 'getDefaultDate',
        year: 'getDefaultDate'
    }
};
</script>

<style scoped>
span.text-danger {
    font-size: 0.875rem;
}
</style>
