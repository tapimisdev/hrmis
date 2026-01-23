<template>
    <div class="position-relative">
        <div class="row mb-3">

            <!-- EMPLOYMENT TYPE -->
            <div class="col-12 col-md-3 mb-3">
                <label class="form-label fw-bold">
                    Employment Type
                    <span v-if="errors.employment_type" class="text-danger small ms-1">
                        ({{ errors.employment_type[0] }})
                    </span>
                </label>

                <select
                    v-model.number="form.employment_type"
                    class="form-select"
                    :class="{ 'is-invalid': errors.employment_type }"
                    @change="search"
                >
                    <option disabled value="">-- CHOOSE EMPLOYMENT TYPE -- </option>
                    <option :value="1">Regular</option>
                    <option :value="2">Contractual</option>
                </select>
            </div>

            <!-- MONTH -->
            <div class="col-12 col-md-3 mb-3">
                <label class="form-label fw-bold">
                    Month
                    <span v-if="errors.month" class="text-danger small ms-1">
                        ({{ errors.month[0] }})
                    </span>
                </label>

                <input
                    type="month"
                    class="form-control"
                    v-model="form.month"
                    :class="{ 'is-invalid': errors.month }"
                    @change="search"
                />
            </div>

            <!-- STATUS -->
            <div class="col-12 col-md-3 mb-3">
                <label class="form-label fw-bold">
                    Status
                    <span v-if="errors.status" class="text-danger small ms-1">
                        ({{ errors.status[0] }})
                    </span>
                </label>

                <select
                    class="form-select"
                    v-model="form.status"
                    :class="{ 'is-invalid': errors.status }"
                    @change="search"
                >
                    <option disabled value="">-- CHOOSE STATUS --</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="for_releasing">For Releasing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
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
            dataStorage: [],
            form: {
                employment_type: 1,
                month: currentMonth,
                status: "",
            },
        };
    },

    methods: {
        search() {
            this.errors = {};
            this.loading = true;

            this.$emit("payroll-list", this.datastorage, true);

            axios
                .post("/api/payroll/pera-rata/processed", this.form, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${this.token}`,
                    },
                })
                .then((response) => {
                    this.$emit("payroll-list", response.data.data, false);
                    this.datastorage = response.data.data;
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
