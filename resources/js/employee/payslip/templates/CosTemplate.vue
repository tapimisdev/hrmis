<template>
  <div class="card rounded-4 shadow mb-3">
    <div class="card-body p-4">

      <!-- HEADER -->
      <div class="text-center mb-4">
        <img src="/public/img/dost-tapi.png" class="tapi-logo mb-2" alt="">
        <div class="fw-bold fs-6">
          TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE
        </div>
        <div class="fw-bold mt-2">PAY SLIP</div>
        <div class="small text-muted">
          For the month of
          <span class="text-uppercase fw-semibold">
            {{ payslip.period_covered }}
          </span>
        </div>
      </div>

      <!-- EMPLOYEE INFO -->
      <div class="row mb-4 small">
        <div class="col-md-6">
          Name: <span class="fw-bold">{{ payslip.name }}</span>
        </div>
        <div class="col-md-6 text-md-end">
          Designation: <span class="fw-bold">{{ payslip.position }}</span>
        </div>
      </div>

      <!-- EARNINGS -->
      <div class="mb-4">
        <div class="fw-bold text-uppercase mb-2 border-bottom pb-1">
          Earnings
        </div>

        <table class="table table-sm table-borderless mb-0">
          <tbody>
            <tr
              v-for="(earning, index) in filteredEarnings"
              :key="index"
            >
              <td>{{ earning.description }}</td>
              <td class="text-end">{{ earning.amount }}</td>
            </tr>

            <tr v-if="payslip.aut != 0" class="text-danger">
              <td>Absences / Undertime / Lates</td>
              <td class="text-end">({{ payslip.aut }})</td>
            </tr>

            <tr class="border-top fw-bold">
              <td>Gross Pay</td>
              <td class="text-end">{{ payslip.gross_pay }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- DEDUCTIONS (HIDDEN IF ZERO) -->
      <div v-if="totalDeductions > 0" class="mb-4">
        <div class="fw-bold text-uppercase mb-2 border-bottom pb-1">
          Deductions
        </div>

        <table class="table table-sm table-borderless mb-0">
          <tbody>
            <tr
              v-for="(deduction, index) in filteredDeductions"
              :key="index"
            >
              <td>{{ deduction.description }}</td>
              <td class="text-end text-danger">
                ({{ deduction.amount }})
              </td>
            </tr>

            <tr class="border-top fw-bold text-danger">
              <td>Total Deductions</td>
              <td class="text-end">
                ({{ totalDeductions }})
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- SUMMARY -->
      <div class="pt-3 border-top">
        <table class="table table-borderless mb-0">
          <tbody>
            <tr v-if="payslip.adjustments != 0">
              <td class="fw-bold">Remarks</td>
              <td class="text-end text-info fw-bold">Adjustments</td>
              <td class="text-end text-info fw-bold" style="width:160px">
                {{ payslip.adjustments }}
              </td>
            </tr>

            <tr class="fs-5 fw-bold">
              <td></td>
              <td class="text-end">Net Pay</td>
              <td class="text-end" style="width:160px">
                {{ payslip.net_pay }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: 'CosTemplate',

  props: {
    payslip: {
      type: Object,
      required: true
    }
  },

  computed: {
    filteredEarnings() {
      return this.payslip.earnings?.filter(e => Number(e.amount) !== 0) || [];
    },

    filteredDeductions() {
      return this.payslip.deductions?.filter(d => Number(d.amount) !== 0) || [];
    },

    totalDeductions() {
      return this.filteredDeductions.reduce(
        (total, d) => total + Number(d.amount),
        0
      );
    }
  }
};
</script>

<style scoped lang="scss">
.card {
  font-family: "Inter", "Segoe UI", sans-serif;
}

.tapi-logo {
  height: 50px;
}

table td {
  padding: 4px 0;
}

.border-top {
  border-top: 1px solid #dee2e6 !important;
}
</style>
