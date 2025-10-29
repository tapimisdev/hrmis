<template>
  <div>
    <SearchPayrollVue
      @payroll-list="handlePayrollList"
    />
    <TablePayrollVue
      :payrolls="payrollList"
      :loading="loading"
    />
  </div>
</template>
<script>
import SearchPayrollVue from './SearchPayrollVue.vue';
import TablePayrollVue from './TablePayrollVue.vue';
export default {
  components: { SearchPayrollVue, TablePayrollVue },
  data(){
    return {
      payrollList: [],
      loading: false
    }
  },
  methods: {
    handlePayrollList(data, isLoading) {
      this.payrollList = data;
      this.loading = isLoading;
    }
  }
}
</script>