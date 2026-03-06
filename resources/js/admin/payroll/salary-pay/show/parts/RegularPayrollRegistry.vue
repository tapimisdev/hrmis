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
  >
    <!-- Header slots -->
    <template #sheet-type>( PERMANENT )</template>
    <template #agency>TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE</template>
    <template #title>GENERAL PAYROLL FOR SALARY</template>

    <template #period>
      Period: <strong>{{ period_covered }}</strong>
    </template>

    <!-- Table slot -->
    <template #table>
      <table class="excel-table">
        <thead>
          <tr class="header-labels">
            <th>Emp#</th>
            <th>Name / Position</th>
            <th>Monthly <br />Rate</th>
            <th>Salary <br />Grade</th>
            <th class="deduction">AUT</th>
            <th>Overtime</th>
            <th>Holiday <br />Excess</th>

            <!-- Dynamic Deductions -->
            <th
              v-for="(deduction, dIndex) in dynamicDeductions"
              :key="'deduction-' + dIndex"
              class="deduction"
              style="min-width: 120px; width: 100%; max-width: 300px; text-align: center;"
            >
              {{ deduction }}
            </th>

            <th
              class="earning"
              style="min-width: 120px; width: 100%; max-width: 300px; text-align: center;"
            >
              Total Deductions
            </th>

            <th style="width: 150px">Adjustment</th>

            <th
              class="net-salary"
              style="min-width: 120px; width: 100%; max-width: 300px; text-align: center;"
            >
              Net <br />Salary
            </th>

            <th style="min-width: 200px; width: 100%; max-width: 300px; text-align: center;">
              Remarks
            </th>
          </tr>
        </thead>

        <tbody>
          <tr class="data-row" v-for="(emp, index) in employees" :key="index">
            <td
              class="text-center"
              style="min-width: 100px; width: 100%; max-width: 300px; text-align: center;"
            >
              {{ emp.employee_no }}
            </td>

            <td
              class="name-cell"
              style="min-width: 200px; width: 100%; max-width: 300px; text-align: center;"
            >
              <div class="employee-name">{{ emp.name }}</div>
              <div class="employee-position">{{ emp.position }}</div>
            </td>

            <td class="text-center" style="min-width: 120px; text-align: center;">
              {{ formatMoney(emp.monthly_rate) }}
            </td>

            <td class="text-center" style="min-width: 120px; text-align: center;">
              {{ emp.salary_grade }}
            </td>

            <td class="text-center" style="min-width: 120px; text-align: center;">
              {{ formatMoney(emp.aut) }}
            </td>

            <td class="text-center" style="min-width: 120px; text-align: center;">
              {{ formatMoney(emp.overtime) }}
            </td>

            <td class="text-center" style="min-width: 120px; text-align: center;">
              {{ formatMoney(emp.holiday) }}
            </td>

            <!-- Dynamic Deductions -->
            <td
              v-for="(deduction, dIndex) in dynamicDeductions"
              :key="'deduction-' + dIndex"
              class="number-cell deduction text-center"
            >
              {{ formatNumber(getDeductionAmount(emp, deduction)) }}
            </td>

            <td class="text-center">{{ formatMoney(emp.total_deductions) }}</td>

            <td class="number-cell">
              <input
                type="number"
                v-model="emp.salary_adjustment"
                @change="adjustRow(emp)"
                class="form-control border-0"
                style="min-width: 150px; width: 100%; max-width: 300px; text-align: center;"
              />
            </td>

            <td class="text-center">{{ emp.net_pay }}</td>

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
            <td class="number-cell">{{ formatNumber(grandTotals('monthly_rate')) }}</td>
            <td class="number-cell"> - </td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('aut')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('overtime')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('holiday')) }}</td>

            <td
              v-for="(deduction, dIndex) in dynamicDeductions"
              :key="'deduction-grand-' + dIndex"
              class="number-cell deduction"
            >
              {{ formatNumber(grandTotals('deductions', deduction)) }}
            </td>

            <td class="number-cell">{{ formatNumber(grandTotals('total_deductions')) }}</td>
            <td class="number-cell earning">{{ formatNumber(grandTotals('salary_adjustment')) }}</td>
            <td class="number-cell net-salary">
              <strong>{{ formatNumber(grandTotals('net_pay')) }}</strong>
            </td>
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
  name: "PermanentPayrollRegistry",
  components: { PayrollRegistryLayout },
  props: {
    employees: { type: Array, required: true },
    status: { type: String, required: true },
    payroll_no: { type: String, required: true },
    period_covered: String
  },
  data() {
    return {
      token,
      loading: false,
    };
  },
  computed: {
    dynamicDeductions() {
      const names = new Set();
      this.employees.forEach((p) =>
        p.deductions?.forEach((d) => names.add(d.deduction_type))
      );
      return Array.from(names);
    },
  },
  methods: {
    handlePrint() {
      window.print();
    },
    formatNumber(value) {
      const num = Number(value);
      return !isNaN(num) && num !== 0
        ? num.toLocaleString(undefined, { minimumFractionDigits: 2 })
        : "-";
    },
    getDeductionAmount(emp, type) {
      const deduction = emp.deductions?.find((d) => d.deduction_type === type);
      return deduction ? Number(deduction.amount) : 0;
    },
    grandTotals(field, subfield = null) {
      return this.employees.reduce((total, emp) => {
        if (field === "deductions" && subfield) {
          const found = emp.deductions?.find((d) => d.deduction_type === subfield);
          return total + (found ? Number(found.amount) : 0);
        }
        const val = Number(emp[field]) || 0;
        return total + val;
      }, 0);
    },
    async adjustRow(emp) {
      this.loading = true;
      try {
        await axios.post(
          `/api/payroll/salary-pay/items/${this.payroll_no}/${emp.id}`,
          { adjustment: emp.salary_adjustment, remarks: emp.remarks },
          { headers: { Authorization: `Bearer ${this.token}` } }
        );
        this.$emit("fetch_data");
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    formatMoney(value) {
      return new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number(value));
    },
  },
};
</script>

<style scoped>
.excel-table {
  min-width: 100%;
}

.earning {
  max-width: 96px;
  overflow-wrap: anywhere;
  white-space: normal;
}

.deduction {
  max-width: 96px;
  overflow-wrap: anywhere;
  white-space: normal;
}

.net-salary {
  font-weight: 700;
}

.data-row .name-cell .employee-name {
  font-weight: 700;
}

.data-row .name-cell .employee-position {
  font-size: 10px;
}

.grand-total {
  font-weight: 700;
}
</style>
