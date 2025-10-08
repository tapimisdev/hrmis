<template>
  <div class="table-responsive card p-3">
    <LoaderVue :visible="loading" status="uploading" message="Uploading, please wait..." />
    <table class="table table-sm table-striped align-middle">
      <thead>
        <tr>
          <th>Payroll #</th>
          <th>Cutoff</th>
          <th>Period Covered</th>
          <th>Emp</th>
          <th>Gross</th>
          <th>Deductions</th>
          <th>Net Pay</th>
          <th>Status</th>
          <th>Processed By</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="payroll in payrolls" :key="payroll.id">
          <td>{{ payroll.payroll_no }}</td>
          <td>{{ payroll.cutoff }}</td>
          <td>{{ payroll.period_covered }}</td>
          <td>{{ payroll.no_employee }}</td>
          <td>{{ formatCurrency(payroll.gross_amount) }}</td>
          <td>{{ formatCurrency(payroll.deduction_amount) }}</td>
          <td>{{ formatCurrency(payroll.netpay_amount) }}</td>
          <td>
            <span
              :class="{
                'badge bg-secondary': payroll.status === 'draft',
                'badge bg-warning': payroll.status === 'pending_approval',
                'badge bg-primary': payroll.status === 'approved',
                'badge bg-danger': payroll.status === 'cancelled',
                'badge bg-dark': payroll.status === 'on_hold'
              }"
            >
              {{ payroll.status.replace('_', ' ').toUpperCase() }}
            </span>
          </td>
          <td>{{ payroll.processed_by }}</td>
          <td>{{ payroll.payroll_date }}</td>
            <td>
              <button
                class="btn btn-sm btn-primary me-1"
                title="Manage"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                data-bs-title="Manage Payroll"
              >
              <i class="fa fa-cogs"></i>
              </button>
              <button
                class="btn btn-sm btn-danger"
                title="Cancel"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                data-bs-title="Cancel Payroll"
              >
              <i class="fa fa-ban"></i>
              </button>
            </td>
        </tr>
        <tr v-if="!loading && payrolls.length === 0">
          <td colspan="11" class="text-center text-muted py-3">
            No payroll records found.
          </td>
        </tr>
        <tr v-if="loading">
          <td colspan="11" class="text-center text-muted py-3">
            Loading...
          </td>
        </tr>

      </tbody>
    </table>
  </div>
</template>

<script>
import LoaderVue from '../../components/LoaderVue.vue';
export default {
  components: { LoaderVue },
  name: 'TablePayrollVue',
  props: {
    payrolls: {
      type: Array,
      default: () => []
    },
    loading: Boolean
  },
  data() {
    return {
      error: null,
    };
  },
  methods: {
    formatCurrency(value) {
      if (value == null) return '-';
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
      }).format(value);
    },
  },
};
</script>

<style lang="scss" scoped>
.table {
  th {
    white-space: nowrap;
  }
  td {
    vertical-align: middle;
  }
}
</style>
