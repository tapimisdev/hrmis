<template>
    <div class="modal-body">
        <form @submit.prevent="markAsSo">
            <div class="row">
                <!-- Date -->
                <div class="col-12 col-md-4">
                    <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1" />
                    <div v-else class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            v-model="form.date"
                            class="form-control"
                            disabled
                            :class="{ 'is-invalid': errors.date }"
                            required
                        />
                        <span class="text-danger" v-if="errors.date">{{ errors.date[0] }}</span>
                    </div>
                </div>

                <!-- Special Order No -->
                <div class="col-12 col-md-8">
                    <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1" />
                    <div v-else class="mb-3">
                        <label class="form-label">Local Travel No. <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            v-model="form.lto_no"
                            class="form-control"
                            :class="{ 'is-invalid': errors.lto_no }"
                            required
                        />
                        <span class="text-danger" v-if="errors.lto_no">{{ errors.lto_no[0] }}</span>
                    </div>
                </div>

                <!-- Shift -->
                <div class="col-12 col-md-6">
                    <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1" />
                    <div v-else class="mb-3">
                        <label class="form-label">Shift <span class="text-danger">*</span></label>
                        <select
                            v-model="form.shift"
                            class="form-select"
                            :class="{ 'is-invalid': errors.shift }"
                            required
                        >
                            <option value=""> - CHOOSE - </option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="wholeday" selected>Whole Day</option>
                        </select>
                        <span class="text-danger" v-if="errors.shift">{{ errors.is_hazardous[0] }}</span>
                    </div>
                </div>

                <!-- Is Hazardous -->
                <div class="col-12 col-md-6">
                    <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1" />
                    <div v-else class="mb-3">
                        <label class="form-label">Is Hazardous? <span class="text-danger">*</span></label>
                        <select
                            v-model="form.is_hazardous"
                            class="form-select"
                            :class="{ 'is-invalid': errors.is_hazardous }"
                            required
                        >
                            <option value=""> - CHOOSE - </option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                        <span class="text-danger" v-if="errors.is_hazardous">{{ errors.is_hazardous[0] }}</span>
                    </div>
                </div>
                
                <!-- Remarks -->
                <div class="col-12 col-md-12">
                    <FormSkeletonVue v-if="initial_loading" :rows="1" :columns="1" />
                    <div v-else class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea
                            v-model="form.remarks"
                            class="form-control"
                            rows="5"
                            :class="{ 'is-invalid': errors.remarks }"
                            placeholder="Write Something..."
                        ></textarea>
                        <span class="text-danger" v-if="errors.remarks">{{ errors.remarks[0] }}</span>
                    </div>
                </div>

                <!-- Attachments -->
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Attachments <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control"
                            :class="{ 'is-invalid': errors.attachments }"
                            multiple
                            @change="handleFileUpload"
                        />
                        <small class="text-muted">Allowed: PDF, JPG, PNG (max 5MB each)</small>
                        <span v-if="errors.attachments" class="text-danger">{{ errors.attachments[0] }}</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-footer">
        <button @click="close" class="btn btn-danger px-4">
            <i class="fas fa-times me-2"></i> Close
        </button>
        <button class="btn btn-primary px-4" :disabled="loading" @click="markAsSo">
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
            attachments: [],
            form: {
                user_id: this.employee_no,
                date: "",
                shift: "wholeday",
                lto_no: "",
                remarks: "",
                is_hazardous: "",
            },
        };
    },
    mounted() {
        this.setClickedDate();
    },
    methods: {
        close() {
            $("#myModal").modal("hide");
        },
        setClickedDate() {
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
        },
        handleFileUpload(event) {
            this.attachments = Array.from(event.target.files);
        },
        async markAsSo() {
            if (this.loading) return;

            this.loading = true;
            this.errors = {};

            try {
                const formData = new FormData();

                Object.entries(this.form).forEach(([key, value]) => {
                    formData.append(key, value);
                });

                this.attachments.forEach((file, i) => {
                    formData.append(`attachments[${i}]`, file);
                });

                const { data } = await axios.post("/api/mark-as-lto", formData, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });

                Swal.fire("Success!", "Local travel order submitted successfully.", "success");

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
            this.form.is_hazardous = "";
            this.attachments = [];
            this.errors = {};
        },
    },
};
</script>

<style scoped lang="scss">
.modal-confirm {
    text-align: center;
    padding: 2rem;

    .icon {
        font-size: 3.5rem;
        color: var(--bs-secondary);
        margin-bottom: 1rem;
    }

    p {
        font-size: 1.0625rem;
        color: var(--bs-body-color);
        margin: 0 0 2rem 0;
        line-height: 1.5;
    }

    .modal-confirm-footer {
        display: flex;
        justify-content: center;
        gap: 0.5rem;

        .btn {
            font-size: 1rem;
            i {
                font-size: 1rem;
            }
        }
    }
}
</style>
