<template>
    <form @submit.prevent="submitForm">
        <div class="modal-body">
            <!-- MODULE TAB (display only) -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Tab <span class="text-danger">*</span>
                </label>

                <input
                    :value="form.module_tab"
                    type="text"
                    class="form-control"
                    disabled
                />

                <div v-if="errors.module_tab" class="text-danger mt-1">
                    {{ errors.module_tab[0] }}
                </div>
            </div>

            <!-- EMPLOYEE NOS -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Employee No <span class="text-danger">*</span>
                    <span class="text-muted small">
                        (use comma for multiple, or type <b>ALL</b>)
                    </span>
                </label>

                <input
                    v-model.trim="form.employee_nos"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': !!errors.employee_nos }"
                    placeholder="EMP001, EMP002 or ALL"
                    @input="clearFieldError('employee_nos')"
                    :disabled="loading"
                    required
                />

                <small class="text-muted">Example: EMP001,EMP002,EMP003</small>

                <div v-if="errors.employee_nos" class="text-danger mt-1">
                    {{ errors.employee_nos[0] }}
                </div>
            </div>

            <!-- FROM MONTH -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    From (Month) <span class="text-danger">*</span>
                </label>

                <select
                    v-model.number="form.from_month"
                    class="form-select"
                    :class="{ 'is-invalid': !!errors.from_month }"
                    @change="onFromChange"
                    :disabled="loading"
                    required
                >
                    <option disabled value="">-- SELECT MONTH --</option>
                    <option
                        v-for="m in months"
                        :key="'from_' + m.value"
                        :value="m.value"
                    >
                        {{ m.label }}
                    </option>
                </select>

                <div v-if="errors.from_month" class="text-danger mt-1">
                    {{ errors.from_month[0] }}
                </div>
            </div>

            <!-- TO MONTH -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    To (Month) <span class="text-danger">*</span>
                </label>

                <select
                    v-model.number="form.to_month"
                    class="form-select"
                    :class="{ 'is-invalid': !!errors.to_month }"
                    @change="clearFieldError('to_month')"
                    :disabled="loading"
                    required
                >
                    <option disabled value="">-- SELECT MONTH --</option>
                    <option
                        v-for="m in months"
                        :key="'to_' + m.value"
                        :value="m.value"
                        :disabled="form.from_month && m.value < form.from_month"
                    >
                        {{ m.label }}
                    </option>
                </select>

                <div v-if="errors.to_month" class="text-danger mt-1">
                    {{ errors.to_month[0] }}
                </div>
            </div>

            <!-- AMOUNT -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Amount <span class="text-danger">*</span>
                    <div class="text-muted small mt-1">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Fixed = exact amount | Percentage = based on salary.
                    </div>
                </label>

                <div class="input-group">
                    <input
                        v-model.number="form.amount"
                        type="number"
                        class="form-control"
                        :placeholder="isPercent ? '0 – 100' : '0.00'"
                        min="0"
                        :max="isPercent ? 100 : undefined"
                        :step="isPercent ? 0.01 : 0.01"
                        :class="{ 'is-invalid': !!errors.amount }"
                        @input="clearFieldError('amount')"
                        :disabled="loading"
                        required
                    />

                    <span class="input-group-text">
                        {{ isPercent ? "%" : "₱" }}
                    </span>
                </div>

                <!-- TYPE selector -->
                <div class="form-text mt-2">
                    <label class="me-3">
                        <input
                            type="radio"
                            value="fixed"
                            v-model="form.amount_type"
                            @change="onAmountTypeChange"
                            :disabled="loading"
                        />
                        Fixed Amount
                    </label>

                    <label>
                        <input
                            type="radio"
                            value="percent"
                            v-model="form.amount_type"
                            @change="onAmountTypeChange"
                            :disabled="loading"
                        />
                        Percentage
                    </label>
                </div>

                <div v-if="errors.amount" class="text-danger mt-1">
                    {{ errors.amount[0] }}
                </div>
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
                    Saving...
                </span>
                <span v-else>
                    <i class="fa-solid fa-plus me-1"></i>
                    Add
                </span>
            </button>
        </div>
    </form>
</template>

<script>
export default {
    name: "BulkEmployeeAmountForm",
    props: {
        module_tab: { type: String, required: true },
        url: { type: String, required: true },
        id: { type: String, required: true },
        year: { type: String, required: true },
    },
    data() {
        return {
            resetKey: 0,
            loading: false,
            errors: {},
            form: {
                module_tab: "",
                employee_nos: "",
                from_month: "",
                to_month: "",
                amount: null,
                amount_type: "fixed", // required
            },
        };
    },
    computed: {
        isPercent() {
            return this.form.amount_type === "percent";
        },

        months() {
            return [
                { value: 1, label: "January" },
                { value: 2, label: "February" },
                { value: 3, label: "March" },
                { value: 4, label: "April" },
                { value: 5, label: "May" },
                { value: 6, label: "June" },
                { value: 7, label: "July" },
                { value: 8, label: "August" },
                { value: 9, label: "Septemper" },
                { value: 10, label: "October" },
                { value: 11, label: "November" },
                { value: 12, label: "December" },
            ];
        },
    },
    watch: {
        module_tab: {
            immediate: true,
            handler(newVal) {
                this.form.module_tab = newVal || "";
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

        onAmountTypeChange() {
            this.clearFieldError("amount");

            // if percent, clamp to 100
            if (this.isPercent && this.form.amount != null) {
                if (this.form.amount > 100) this.form.amount = 100;
                if (this.form.amount < 0) this.form.amount = 0;
            }
        },

        resetForm(moduleTab = this.module_tab) {
            Object.assign(this.form, {
                module_tab: moduleTab || "",
                employee_nos: "",
                from_month: "",
                to_month: "",
                amount: null,
                amount_type: "fixed",
            });

            this.errors = {};
            this.loading = false;
            this.resetKey++; // force month inputs refresh
        },

        onCancel() {
            this.resetForm(this.module_tab);
            this.$emit("cancel");
        },

        submitForm() {
            if (this.loading) return;

            this.loading = true;
            this.errors = {};

            // ensure latest prop is included
            this.form.module_tab = this.module_tab || "";
            this.form.id = this.id;
            this.form.year = this.year;

            axios
                .post(this.url, this.form)
                .then((res) => {
                    SuccesToast.fire({
                        title: res?.data?.message || "Successfully added!",
                    });

                    // parent should refresh table
                    this.$emit("success", { ...this.form });

                    // clear inputs
                    this.resetForm(this.module_tab);
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
