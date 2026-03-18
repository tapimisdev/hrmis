<template>
    <div>
        <SearchSection
            ref="searchPayroll"
            :fields="fields"
            :initialForm="formDefaults"
            url="/api/payroll/longevity-pay/processed"
            :headers="headers"
            @payroll-list="handlePayrollList"
        />
        <PayrollLayout
            :payrolls="payrollList"
            :loading="loading"
            :url="'longevity-pay'"
            @deleted="handleDelete"
            @status-changed="handleChange"
        />
    </div>
</template>
<script>
import SearchSection from "../SearchSection.vue";
import PayrollLayout from "../PayrollLayout.vue";

export default {
    name: "LongevityPayIndex",
    components: { PayrollLayout, SearchSection },
    data() {
        const today = new Date();
        const currentMonth = today.toISOString().slice(0, 7);
        return {
            token: localStorage.getItem("auth_token"),
            payrollList: [],
            loading: false,
            formDefaults: {
                employment_type: "1",
                month: currentMonth,
                status: "",
            },
            fields: [
                {
                    key: "employment_type",
                    label: "Employment Type",
                    type: "select",
                    cast: "number",
                    placeholder: "-- CHOOSE EMPLOYMENT TYPE --",
                    options: [{ label: "Regular", value: 1 }],
                },
                {
                    key: "month",
                    label: "Month",
                    type: "month",
                },
            ],
        };
    },
    methods: {
        headers() {
            return {
                Accept: "application/json",
                Authorization: `Bearer ${this.token}`,
            };
        },
        handlePayrollList(data, isLoading) {
            this.payrollList = data;
            this.loading = isLoading;
        },
        handleDelete() {
            this.$refs.searchPayroll.search();
        },
        handleChange() {
            this.$refs.searchPayroll.search();
        },
    },
};
</script>
