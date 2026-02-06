<template>
  <PayrollRegistryLayout
    :status="status"
    :payroll_no="payroll_no"
    :loading="loading"
    :downloads="[
      { key: 'registry', label: 'Payroll Registry' },
      { key: 'payslip', label: 'Payslip' },
    ]"
    @print="handlePrint"
    @download="handleDownload"
  >
    <!-- Header slots -->
    <template #sheet-type>( SLA PAY )</template>
    <template #agency>TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE</template>
    <template #title>
      PAYROLL OF SUBSISTENCE AND LAUNDRY ALLOWANCE PAY FOR THE MONTH OF {{ month }}
    </template>

    <template #period>
      Month: <strong>{{ month }}</strong>
    </template>

    <!-- Table slot -->
    <template #table>
      <table class="excel-table">
        <thead>
          <tr class="header-labels">
            <th>Emp#</th>
            <th>Name / Position</th>
            <th>
              Subsistence <br />
              Allowance<br />
              (22 days)
            </th>
            <th>
              Laundry <br />
              Allowance<br />
              (₱500)
            </th>
            <th>Total SLA</th>
            <th>
              Deduction <br />
              Late/UT's <br />
              <small>
                per DOST AO <br />
                No. 003
              </small>
            </th>
            <th>
              Uniform <br />
              Deduction
            </th>
            <th>
              Less: Health <br />
              Card c/o <br />
              TAPIEA
            </th>
            <th style="width: 150px">Adjustments</th>
            <th>Net Amount</th>
            <th style="min-width: 220px">Remarks</th>
          </tr>
        </thead>

        <tbody>
          <tr class="data-row" v-for="(emp, index) in employees" :key="index">
            <td class="text-center">{{ emp.employee_no }}</td>

            <td class="name-cell">
              <div class="employee-name">{{ emp.name }}</div>
              <div class="employee-position">{{ emp.position }}</div>
            </td>

            <td class="text-center">{{ formatMoney(emp.subsistence_allowance) }}</td>
            <td class="text-center">{{ formatMoney(emp.laundry_allowance) }}</td>
            <td class="text-center"><strong>{{ formatMoney(emp.total_sla) }}</strong></td>

            <td class="text-center">{{ formatMoney(emp.ut_deductions) }}</td>
            <td class="text-center">{{ formatMoney(emp.uniform_deduction) }}</td>
            <td class="text-center">{{ formatMoney(emp.healthcard) }}</td>

            <td class="text-center">
              <input
                type="number"
                class="form-control border-0 text-center"
                v-model="emp.adjustments"
                @change="adjustRow(emp)"
              />
            </td>

            <td class="text-center">
              <strong>{{ formatMoney(emp.net_pay) }}</strong>
            </td>

            <td class="text-center">
              <textarea
                class="form-control border-0"
                v-model="emp.remarks"
                @change="adjustRow(emp)"
              ></textarea>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="grand-total text-center">
            <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>

            <td class="number-cell">{{ formatNumber(grandTotals("subsistence_allowance")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("laundry_allowance")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("total_sla")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("ut_deductions")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("uniform_deduction")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("healthcard")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("adjustments")) }}</td>
            <td class="number-cell"><strong>{{ formatNumber(grandTotals("net_pay")) }}</strong></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </template>
  </PayrollRegistryLayout>
</template>

<script>
import axios from "axios";
import PayrollRegistryLayout from "../../../PayrollRegistryLayout.vue";

const token = localStorage.getItem("auth_token");

export default {
  name: "SlaPayrollRegistry",
  components: { PayrollRegistryLayout },
  props: {
    employees: { type: Array, required: true },
    status: { type: String, required: true },
    payroll_no: { type: String, required: true },
    month: { type: String, required: true },
  },
  data() {
    return {
      token,
      loading: false,
    };
  },
  methods: {
    handlePrint() {
      window.print();
    },

    // Layout should emit: this.$emit('download', { key: 'registry' | 'payslip' })
    async handleDownload({ key }) {
      // TODO: wire endpoints
      // const urlArr = {
      //   registry: `/api/payroll/sla-pay/download/registry/${this.payroll_no}`,
      //   payslip: `/api/payroll/sla-pay/download/payslip/${this.payroll_no}`,
      // };
      // await this.downloadFile(urlArr[key], `${key}_${this.payroll_no}.xlsx`);
    },

    formatNumber(value) {
      const num = Number(value);
      return !isNaN(num) && num !== 0
        ? num.toLocaleString(undefined, { minimumFractionDigits: 2 })
        : "-";
    },

    formatMoney(value) {
      return new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number(value) || 0);
    },

    grandTotals(field) {
      return this.employees.reduce((total, emp) => {
        return total + (Number(emp[field]) || 0);
      }, 0);
    },

    async adjustRow(emp) {
      this.loading = true;
      try {
        await axios.post(
          `/api/payroll/sla-pay/items/${this.payroll_no}/${emp.id}`,
          { adjustment: emp.adjustments, remarks: emp.remarks },
          { headers: { Authorization: `Bearer ${this.token}` } }
        );

        this.$emit("fetch_data");
      } catch (error) {
        console.error(error.response?.data || error.message);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
/* ✅ only table styles; layout owns toolbar/sheet/theme/status colors */

.excel-table {
  width: 100%;
  border-collapse: collapse;
}

.excel-table th,
.excel-table td {
  border: 1px solid var(--bs-border-color, #d0d0d0);
  padding: 2px 8px;
  font-size: 11px;
}

.header-labels th {
  text-align: center;
  font-weight: 700;
  color: var(--status-color);
  background: var(--bs-table-bg, white);
}

[data-bs-theme="dark"] .header-labels th {
  background: var(--bs-body-bg);
}

.data-row .name-cell .employee-name {
  font-weight: bold;
}

.data-row .name-cell .employee-position {
  font-style: italic;
  font-size: 8px;
}

.grand-total {
  border-top: 2px solid rgba(var(--bs-dark-rgb), 1);
  font-weight: bolder;
  height: 60px;
  background-color: var(--status-color);
  color: var(--status-bg);
}

[data-bs-theme="dark"] .grand-total {
  background-color: var(--status-color);
  color: white;
}
</style>
