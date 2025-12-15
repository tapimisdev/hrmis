<template>
    <div class="">
        <h5 class="mb-3 text-primary text-uppercase">Step 1: Create Payroll</h5>
        <p class="text-muted mb-4">
            Fill in all payroll details and review before sending it for
            approval.
        </p>
        <div class="row g-3">
            <!-- Label Field -->
            <div class="col-12 col-md-7">
                <label class="form-label text-body fw-bold">Label</label>
                <input
                    type="text"
                    class="form-control"
                    v-model="localForm.label"
                    :class="{ 'is-invalid': errors.label }"
                />
                <small v-if="errors.label" class="text-danger">{{
                    errors.label[0]
                }}</small>
            </div>

            <!-- Month Field -->
            <div class="col-12 col-md-5">
                <label class="form-label text-body fw-bold">Month</label>
                <input
                    type="month"
                    class="form-control"
                    v-model="localForm.month"
                    :class="{ 'is-invalid': errors.month }"
                />
                <small v-if="errors.month" class="text-danger">{{
                    errors.month[0]
                }}</small>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CreatePayroll",
    props: {
        modelValue: Object,
        errors: Object,
    },
    emits: ["update:modelValue"],
    data() {
        const token = localStorage.getItem("auth_token");
        return {
            token,
            loading: false,
            localForm: {
                label: "",
                employment_type_id: 1,
            },
            employment_types: [],
        };
    },
    watch: {
        localForm: {
            deep: true,
            handler(newVal) {
                this.$emit("update:modelValue", newVal);
            },
        },
    },
    created() {
        this.fetchData("employment_types", "/api/get-employment-types", true);
    },
    methods: {
        fetchData(stateKey, url, useDataWrapper = false) {
            axios
                .get(url, {
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then((response) => {
                    this[stateKey] = useDataWrapper
                        ? response.data.data
                        : response.data;
                });
        },
    },
};
</script>
