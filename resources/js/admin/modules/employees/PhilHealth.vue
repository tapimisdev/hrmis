<template>
    <form @submit.prevent="submitForm">
        <div class="modal-body">
            <label class="form-label fw-semibold">
                PhilHealth Computation <span class="text-danger">*</span>
            </label>

            <div class="text-muted small mt-1 mb-2">
                Format: <b>rate,floor,ceiling</b> (example:
                <b>5,10000,100000</b>)
            </div>

            <div class="alert alert-info d-flex align-items-start gap-2">
                <i class="fa-solid fa-circle-info mt-1"></i>
                <div>
                    This computation will be applied to
                    <b>all Permanent employees</b> for the
                    <b>entire year {{ year }}</b
                    >. Any existing PhilHealth settings for this year will be
                    <b>overwritten</b>.
                </div>
            </div>

            <input
                v-model.trim="form.computation"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': !!errors.computation }"
                placeholder="5,10000,100000"
                :disabled="loading"
                @input="clearFieldError('computation')"
                required
            />

            <div v-if="errors.computation" class="text-danger mt-1">
                {{ errors.computation[0] }}
            </div>

            <div v-else class="text-muted small mt-2">
                <i class="fa-solid fa-circle-info me-1"></i>
                Example means: rate=5, floor=10000, ceiling=100000
            </div>
        </div>

        <div class="modal-footer">
            <button
                type="button"
                class="btn btn-danger"
                :disabled="loading"
                @click="onCancel"
            >
                Cancel
            </button>

            <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Computing...
                </span>
                <span v-else>
                    <i class="fa-solid fa-plus me-1"></i>
                    Compute
                </span>
            </button>
        </div>
    </form>
</template>

<script>
export default {
    name: "PhilHealthComputationInput",
    props: {
        module_tab: { type: String, required: true },
        year: { type: Number, required: true },
    },
    emits: ["success", "cancel"],
    data() {
        return {
            loading: false,
            errors: {},
            form: {
                computation: "",
                year: this.year,
                module_tab: this.module_tab,
            },
        };
    },
    watch: {
        // keep payload synced if parent changes these props
        year: {
            immediate: true,
            handler(val) {
                this.form.year = val;
            },
        },
        module_tab: {
            immediate: true,
            handler(val) {
                this.form.module_tab = val;
            },
        },
    },
    methods: {
        clearFieldError(field) {
            if (this.errors?.[field]) {
                const copy = { ...this.errors };
                delete copy[field];
                this.errors = copy;
            }
        },

        resetForm() {
            this.form.computation = "";
            // keep year + module_tab from props
            this.form.year = this.year;
            this.form.module_tab = this.module_tab;

            this.errors = {};
            this.loading = false;
        },

        onCancel() {
            this.resetForm();
            this.$emit("cancel");
        },

        submitForm() {
            if (this.loading) return;

            this.loading = true;
            this.errors = {};

            axios
                .post(
                    `/admin/modules/bulk/philhealth/store-employees`,
                    this.form,
                )
                .then((res) => {
                    SuccesToast.fire({
                        title: res?.data?.message || "Successfully computed!",
                    });

                    // emit submitted data to parent
                    this.$emit("success", { ...this.form });

                    this.resetForm();
                })
                .catch((error) => {
                    if (error?.response?.status === 422) {
                        this.errors = error.response.data.errors || {};
                        return;
                    }

                    ErrorToast.fire({
                        title:
                            error?.response?.data?.error ||
                            error?.response?.data?.message ||
                            "An error occurred",
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },
};
</script>
