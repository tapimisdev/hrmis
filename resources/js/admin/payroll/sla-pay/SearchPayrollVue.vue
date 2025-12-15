<template>
    <div class="position-relative">
        <div class="row mb-3">
            <!-- MONTH INPUT -->
            <div class="col-12 col-md-3 mb-3">
                <div class="d-flex gap-2">
                    <label class="form-label fw-bold">Month</label>
                    <span v-if="errors.month" class="text-danger small">
                        ({{ errors.month[0] }})
                    </span>
                </div>

                <input
                    type="month"
                    class="form-control"
                    v-model="form.month"
                    :class="{ 'is-invalid': errors.month }"
                />
            </div>

            <!-- STATUS SELECT -->
            <div class="col-12 col-md-3 mb-3">
                <div class="d-flex gap-2">
                    <label class="form-label fw-bold">Status</label>
                    <span v-if="errors.status" class="text-danger small">
                        ({{ errors.status[0] }})
                    </span>
                </div>

                <select
                    class="form-select"
                    v-model="form.status"
                    :class="{ 'is-invalid': errors.status }"
                >
                    <option value="">-- CHOOSE STATUS --</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="for_releasing">For Releasing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- BUTTON -->
            <div class="col-12 col-md-4 mb-3 d-flex align-items-end">
                <button
                    type="button"
                    id="submit-button"
                    class="btn btn-warning text-uppercase"
                    @click="search"
                    :disabled="loading"
                >
                    <span><i class="fas fa-search px-1"></i> Search</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        const token = localStorage.getItem("auth_token");

        // Get current YYYY-MM
        const today = new Date();
        const currentMonth = today.toISOString().slice(0, 7); // "YYYY-MM"

        return {
            token,
            loading: false,
            errors: {},

            form: {
                month: currentMonth,
                status: "",
            },
        };
    },

    methods: {
        search() {
            this.errors = {};
            this.loading = true;

            this.$emit("payroll-list", [], this.loading);

            axios
                .post("/api/payroll/sla-pay/processed", this.form, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${this.token}`,
                    },
                })
                .then((response) => {
                    console.log(response);
                    this.$emit("payroll-list", response.data.data, false);
                    this.loading = false;
                })
                .catch((error) => {
                    if (error.response?.status === 422) {
                        this.errors = error.response.data.errors;
                    } else {
                        Swal.fire(
                            "Error",
                            error.response?.data?.message ||
                                "Something went wrong.",
                            "error"
                        );
                    }
                    this.loading = false;
                    this.$emit("payroll-list", [], false);
                });
        },
    },

    watch: {
        form: {
            deep: true,
            handler(newVal) {
                const params = new URLSearchParams(newVal).toString();
                const newUrl = `${window.location.pathname}?${params}`;
                window.history.replaceState({}, "", newUrl);
            },
        },
    },

    mounted() {

        const params = new URLSearchParams(window.location.search);

        for (const [key, value] of params.entries()) {
            if (this.form.hasOwnProperty(key)) {
                this.form[key] = value;
            }
        }

        this.search();
    },
};
</script>
