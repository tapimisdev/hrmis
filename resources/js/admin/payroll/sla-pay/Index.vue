<template>
    <div>
        <SearchPayrollVue
            ref="searchPayroll"
            @payroll-list="handlePayrollList"
        />
        <TablePayrollVue
            :payrolls="payrollList"
            :loading="loading"
            @deleted="handleDelete"
        />
    </div>
</template>
<script>
import SearchPayrollVue from "./SearchPayrollVue.vue";
import TablePayrollVue from "./TablePayrollVue.vue";
export default {
    name: "SlaPayIndex",
    components: { SearchPayrollVue, TablePayrollVue },
    data() {
        return {
            payrollList: [],
            loading: false,
        };
    },
    methods: {
        handlePayrollList(data, isLoading) {
            this.payrollList = data;
            this.loading = isLoading;
        },
        handleDelete() {
            if (this.$refs.searchPayroll) {
                this.$refs.searchPayroll.fetchData();
            }
        },
    },
};
</script>
