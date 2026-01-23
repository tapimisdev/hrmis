<template>
    <div class="card shadow-sm bg-body rounded-4">
        <!-- Card Header -->
        <div class="card-header pt-4 pb-4 pb-0">
            <h5 class="fw-semibold mb-0">{{ title }}</h5>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <form @submit.prevent="submitChangePassword">
                <!-- Current Password -->
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input
                        type="password"
                        class="form-control"
                        v-model="password.current"
                        :class="{ 'is-invalid': errors.current_password }"
                    />
                    <div
                        v-if="errors.current_password"
                        class="invalid-feedback"
                    >
                        {{ errors.current_password[0] }}
                    </div>
                </div>

                <!-- New Password -->
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input
                        type="password"
                        class="form-control"
                        v-model="password.new"
                        :class="{ 'is-invalid': errors.new_password }"
                    />
                    <div v-if="errors.new_password" class="invalid-feedback">
                        {{ errors.new_password[0] }}
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <input
                        type="password"
                        class="form-control"
                        v-model="password.confirm"
                        :class="{
                            'is-invalid': errors.new_password_confirmation,
                        }"
                    />
                    <div
                        v-if="errors.new_password_confirmation"
                        class="invalid-feedback"
                    >
                        {{ errors.new_password_confirmation[0] }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end">
                    <button
                        type="submit"
                        class="btn btn-primary px-4 text-uppercase fw-bold"
                        :disabled="loading"
                    >
                        <span
                            v-if="loading"
                            class="spinner-border spinner-border-sm me-2"
                        ></span>
                        {{ loading ? "Saving..." : "Save" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
const token = localStorage.getItem("auth_token");

export default {
    props: {
        title: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isForcedUpdate: "",
            password: {
                current: "",
                new: "",
                confirm: "",
            },
            errors: {},
            loading: false,
        };
    },
    mounted() {
        const token = localStorage.getItem("auth_token");

        if (!token) return;

        axios
            .get("/api/force-update-password", {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            })
            .then((response) => {
                this.isForcedUpdate = response.data.isForcedUpdate ?? false;
            })
            .catch((error) => {
                console.error("API error:", error);
            });
    },
    methods: {
        async submitChangePassword() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await axios.put(
                    "/employee/change-password",
                    {
                        current_password: this.password.current,
                        new_password: this.password.new,
                        new_password_confirmation: this.password.confirm,
                        isForcedUpdate: this.isForcedUpdate,
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: "application/json",
                        },
                    }
                );

                SuccesToast.fire({
                    title:
                        response.data.message ||
                        "Password changed successfully",
                });

                // clear fields
                this.password.current = "";
                this.password.new = "";
                this.password.confirm = "";
                this.isForcedUpdate = "";
                this.$emit("password-changed");
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    ErrorToast.fire({
                        title:
                            error.response?.data?.message ||
                            "An error occurred",
                    });
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
<style scoped lang="scss">
@import "./../../../sass/variables";

.is-invalid {
    border-color: $danger !important;
}
</style>
