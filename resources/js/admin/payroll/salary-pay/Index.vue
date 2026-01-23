<template>
    <div>
        <SearchPayrollVue
            ref="searchPayroll"
            url="/api/payroll/sla-pay/processed"
            @payroll-list="handlePayrollList"
        />
        <PayrollLayout
            :payrolls="payrollList"
            :loading="loading"
            :url="'salary-pay'"
            @deleted="handleDelete"
            @status-changed="handleChange"
        ></PayrollLayout>
    </div>
</template>
<script>
import PayrollLayout from "./../PayrollLayout.vue";
import SearchPayrollVue from "./SearchPayrollVue.vue";
export default {
    name: "SalaryPayIndex",
    components: { SearchPayrollVue, PayrollLayout },
    data() {
        const today = new Date();
        const currentMonth = today.toISOString().slice(0, 7);
        return {
            payrollList: [],
            formDefaults: {
                employment_type: "1",
                month: currentMonth,
                status: "",
            },
            loading: false,
        };
    },
    methods: {
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
