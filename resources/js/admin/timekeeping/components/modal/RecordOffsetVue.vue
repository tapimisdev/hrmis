<template>
    <div class="modal-body">
        <form @submit.prevent="submitRequest">
            <div class="row">

                <!-- Date -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            Selected Date <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            disabled
                            v-model="form.selectedDates[0].date"
                            class="form-control"
                        />
                    </div>
                </div>

                <!-- Shift -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            Select Shift <span class="text-danger">*</span>
                        </label>

                        <select
                            v-model="form.selectedDates[0].shift"
                            class="form-select"
                            :class="{ 'is-invalid': errors['selectedDates.0.shift'] }"
                        >
                            <option value="">- CHOOSE -</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="wholeday">Whole Day</option>
                        </select>

                        <span
                            v-if="errors['selectedDates.0.shift']"
                            class="text-danger"
                        >
                            {{ errors['selectedDates.0.shift'][0] }}
                        </span>
                    </div>
                </div>

                <!-- Reason -->
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">
                            Reason <span class="text-danger">*</span>
                        </label>

                        <textarea
                            v-model="form.reason"
                            class="form-control"
                            :class="{ 'is-invalid': errors.reason }"
                            rows="3"
                        ></textarea>

                        <span v-if="errors.reason" class="text-danger">
                            {{ errors.reason[0] }}
                        </span>
                    </div>
                </div>

                <!-- Attachments -->
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">
                            Attachments <span class="text-danger">*</span>
                        </label>

                        <input
                            type="file"
                            class="form-control"
                            :class="{ 'is-invalid': errors.attachments }"
                            multiple
                            @change="handleFileUpload"
                        />

                        <small class="text-muted">
                            Allowed: PDF, JPG, PNG (max 5MB each)
                        </small>

                        <span v-if="errors.attachments" class="text-danger">
                            {{ errors.attachments[0] }}
                        </span>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Footer -->
    <div class="modal-footer">
        <button @click="close" class="btn btn-danger px-4">
            <i class="fas fa-times me-2"></i> Close
        </button>

        <button
            class="btn btn-primary px-4"
            :disabled="loading"
            @click="submitRequest"
        >
            <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
            <i v-else class="fas fa-save me-2"></i>
            {{ loading ? "Saving..." : "Save" }}
        </button>
    </div>
</template>

<script>
import axios from "axios";
const token = localStorage.getItem("auth_token");

export default {
    props: {
        employee_id: { type: [String, Number], required: true },
        month: Number,
        year: Number,
        index: Number,
    },

    data() {
        return {
            loading: false,
            errors: {},
            attachments: [],

            form: {
                isDirectlyApproved: true,
                user_id: this.employee_id,
                selectedDates: [
                    {
                        date: "",
                        shift: "",
                    },
                ],
                reason: "",
            },
        };
    },

    mounted() {
        this.setDefaultDate();
    },

    methods: {
        handleFileUpload(event) {
            this.attachments = Array.from(event.target.files);
        },

        async submitRequest() {
            if (this.loading) return;

            this.loading = true;
            this.errors = {};

            try {
                const formData = new FormData();

                Object.entries(this.form).forEach(([key, value]) => {
                    formData.append(
                        key,
                        typeof value === "object"
                            ? JSON.stringify(value)
                            : value
                    );
                });

                this.attachments.forEach((file, i) => {
                    formData.append(`attachments[${i}]`, file);
                });

                const { data } = await axios.post("/api/offset", formData, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });

                Swal.fire(
                    "Success!",
                    "Request submitted successfully.",
                    "success"
                );

                this.$emit("success", data);
                this.resetForm();
                this.close();
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    Swal.fire(
                        "Error",
                        error.response?.data?.message || "Something went wrong",
                        "error"
                    );
                }
            } finally {
                this.loading = false;
            }
        },

        resetForm() {
            this.form.reason = "";
            this.form.selectedDates[0].shift = "";
            this.attachments = [];
            this.errors = {};
        },

        setDefaultDate() {
            const year = this.year ?? new Date().getFullYear();
            const month = this.month ?? new Date().getMonth() + 1;
            const day = this.index ?? new Date().getDate();

            const date = new Date(year, month - 1, day);
            this.form.selectedDates[0].date =
                date.getFullYear() +
                "-" +
                String(date.getMonth() + 1).padStart(2, "0") +
                "-" +
                String(date.getDate()).padStart(2, "0");
        },

        close() {
            $("#myModal").modal("hide");
        },
    },

    watch: {
        index: "setDefaultDate",
        month: "setDefaultDate",
        year: "setDefaultDate",
    },
};
</script>

<style scoped>
.text-danger {
    font-size: 0.85rem;
}
</style>
