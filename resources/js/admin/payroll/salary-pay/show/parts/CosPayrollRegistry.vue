<template>
  <PayrollRegistryLayout
    :status="status"
    :payroll_no="payroll_no"
    :loading="loading"
    :downloads="[
      { key: 'registry', label: 'Payroll Registry' },
      { key: 'aut', label: 'Absences & Leaves' },
      { key: 'payslip', label: 'Payslip' },
    ]"
    @print="handlePrint"
  >
    <!-- Header slots -->
    <template #sheet-type></template>

    <template #agency>
      PAYROLL FOR CONTRACT PRICE OF PROJECT PERSONNEL
    </template>

    <template #title>
      Source of Fund: GAA, LITIGATION, ISSP, TECHNICOM
    </template>

    <template #period>
      Period: <strong>{{ period_covered }}</strong>
    </template>

    <!-- Table slot -->
    <template #table>
      <table class="excel-table">
        <thead>
          <tr class="header-labels">
            <th class="p-4">Emp#</th>
            <th class="p-4">Name / Position</th>
            <th class="p-4">Monthly <br />Rate</th>
            <th class="p-4">Salary <br />Earned</th>
            <th class="p-4">Absences / Lates / Undertime</th>
            <th class="p-4">Overtime</th>
            <th class="p-4">Holiday <br />Excess</th>

            <th class="p-4" style="width: 100px;">Total Salary</th>
            <th class="p-4" style="width: 100px;">EWT <br />(2%)</th>
            <th class="p-4" style="width: 100px;">Percentage Tax <br />(3%)</th>
            <th class="p-4" style="width: 100px;">Tax <br />(EWT: 5%)</th>
            <th class="p-4" style="width: 100px;">Overall Tax</th>
            <th class="p-4" style="width: 100px;">HMO</th>

            <!-- Dynamic Earnings -->
            <th
              v-for="(earning, eIndex) in dynamicEarnings"
              :key="'earning-' + eIndex"
              class="earning"
            >
              {{ earning }}
            </th>

            <!-- Dynamic Deductions -->
            <th
              v-for="(deduction, dIndex) in dynamicDeductions"
              :key="'deduction-' + dIndex"
              class="deduction"
            >
              {{ deduction }}
            </th>

            <th style="width: 150px;">Adjustment</th>
            <th class="net-salary">Net <br />Salary</th>
            <th>Remarks</th>
          </tr>
        </thead>

        <!-- Projects -->
        <tbody v-for="(project, pIndex) in projects" :key="pIndex">
          <tr class="project-header">
            <td colspan="100" class="project-cell">{{ project.name }}</td>
          </tr>

          <!-- Employee Rows -->
          <tr
            v-for="(emp, eIndex) in project.employees"
            :key="eIndex"
            class="data-row"
          >
            <td class="text-center">{{ emp.employee_no }}</td>

            <td class="text-center name-cell">
              <div class="employee-name">{{ emp.name }}</div>
              <div class="employee-position">{{ emp.position }}</div>
            </td>

            <td class="text-center number-cell">{{ formatNumber(emp.monthly_rate) }}</td>
            <td class="text-center number-cell">{{ formatNumber(emp.salary_earned) }}</td>
            <td class="text-center number-cell deduction">{{ formatNumber(emp.aut) }}</td>
            <td class="text-center number-cell">{{ formatNumber(emp.overtime) }}</td>
            <td class="text-center number-cell">{{ formatNumber(emp.holiday) }}</td>
            <td class="text-center number-cell earning">{{ formatNumber(emp.total_salary) }}</td>

            <td class="text-center number-cell deduction">{{ formatNumber(emp.ewt_2) }}</td>
            <td class="text-center number-cell deduction">{{ formatNumber(emp.percentage_tax_3) }}</td>
            <td class="text-center number-cell deduction">{{ formatNumber(emp.tax_ewt_5) }}</td>
            <td class="text-center number-cell deduction">{{ formatNumber(emp.w_tax) }}</td>
            <td class="text-center number-cell deduction">{{ formatNumber(emp.hmo) }}</td>

            <!-- Dynamic Earnings -->
            <td
              v-for="(earning, idx) in dynamicEarnings"
              :key="'earning-' + idx"
              class="text-center number-cell earning"
            >
              {{ formatNumber(getEarningAmount(emp, earning)) }}
            </td>

            <!-- Dynamic Deductions -->
            <td
              v-for="(deduction, idx) in dynamicDeductions"
              :key="'deduction-' + idx"
              class="text-center number-cell deduction"
            >
              {{ formatNumber(getDeductionAmount(emp, deduction)) }}
            </td>

            <td class="text-center">
              <input
                type="number"
                v-model="emp.adjustment"
                @change="adjustRow(emp)"
                class="text-center form-control"
                style="width: 100px;"
              />
            </td>

            <td class="text-center number-cell net-salary">
              {{ formatNumber(emp.net_salary) }}
            </td>

            <td class="text-center">
              <textarea
                style="width: 250px;"
                class="text-center form-control my-3"
                v-model="emp.remarks"
                @change="adjustRow(emp)"
              ></textarea>
            </td>
          </tr>

          <!-- Project Total Row -->
          <tr class="project-total text-center">
            <td colspan="2" class="text-end"><strong>Total ({{ project.name }})</strong></td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'monthly_rate')) }}</td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'salary_earned')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'aut')) }}</td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'overtime')) }}</td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'holiday')) }}</td>
            <td class="number-cell earning">{{ formatNumber(projectTotals(project, 'total_salary')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'ewt_2')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'percentage_tax_3')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'tax_ewt_5')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'w_tax')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'hmo')) }}</td>

            <td
              v-for="(earning, idx) in dynamicEarnings"
              :key="'earning-total-' + idx"
              class="number-cell earning"
            >
              {{ formatNumber(projectTotals(project, 'earnings', earning)) }}
            </td>

            <td
              v-for="(deduction, idx) in dynamicDeductions"
              :key="'deduction-total-' + idx"
              class="number-cell deduction"
            >
              {{ formatNumber(projectTotals(project, 'deductions', deduction)) }}
            </td>

            <td class="number-cell">{{ formatNumber(projectTotals(project, 'adjustment')) }}</td>
            <td class="number-cell net-salary">
              <strong>{{ formatNumber(projectTotals(project, 'net_salary')) }}</strong>
            </td>
            <td></td>
          </tr>
        </tbody>

        <!-- Grand Total Row -->
        <tfoot>
          <tr class="grand-total text-center">
            <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
            <td class="number-cell">{{ formatNumber(grandTotals('monthly_rate')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('salary_earned')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('aut')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('overtime')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('holiday')) }}</td>
            <td class="number-cell earning">{{ formatNumber(grandTotals('total_salary')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('ewt_2')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('percentage_tax_3')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('tax_ewt_5')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('w_tax')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('hmo')) }}</td>

            <td
              v-for="(earning, idx) in dynamicEarnings"
              :key="'earning-grand-' + idx"
              class="number-cell earning"
            >
              {{ formatNumber(grandTotals('earnings', earning)) }}
            </td>

            <td
              v-for="(deduction, idx) in dynamicDeductions"
              :key="'deduction-grand-' + idx"
              class="number-cell deduction"
            >
              {{ formatNumber(grandTotals('deductions', deduction)) }}
            </td>

            <td class="number-cell">{{ formatNumber(grandTotals('adjustment')) }}</td>
            <td class="number-cell net-salary">
              <strong>{{ formatNumber(grandTotals('net_salary')) }}</strong>
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
  name: "CosPayrollRegistry",
  components: { PayrollRegistryLayout },
  props: {
    projects: { type: Array, required: true },
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
      this.projects.forEach((p) =>
        p.employees.forEach((e) =>
          e.deductions?.forEach((d) => names.add(d.deduction_type))
        )
      );
      return Array.from(names);
    },
    dynamicEarnings() {
      const names = new Set();
      this.projects.forEach((p) =>
        p.employees.forEach((e) =>
          e.earnings?.forEach((er) => names.add(er.name))
        )
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
    getEarningAmount(emp, type) {
      const earning = emp.earnings?.find((e) => e.name === type);
      return earning ? Number(earning.amount) : 0;
    },
    projectTotals(project, field, subfield = null) {
      let total = 0;

      project.employees.forEach((emp) => {
        if (field === "earnings") total += this.getEarningAmount(emp, subfield);
        else if (field === "deductions") total += this.getDeductionAmount(emp, subfield);
        else total += Number(emp[field]) || 0;
      });

      return total;
    },
    grandTotals(field, subfield = null) {
      return this.projects.reduce(
        (total, project) => total + this.projectTotals(project, field, subfield),
        0
      );
    },
    async adjustRow(emp) {
      this.loading = true;
      try {
        await axios.post(
          `/api/payroll/salary-pay/items/${this.payroll_no}/${emp.id}`,
          { adjustment: emp.adjustment, remarks: emp.remarks },
          { headers: { Authorization: `Bearer ${this.token}` } }
        );
        this.$emit("fetch_data");
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>

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

.earning {
  background-color: rgba(var(--bs-success-rgb), 0.1);
  max-width: 76px;
  word-wrap: break-word;
  white-space: normal;
}

[data-bs-theme="dark"] .earning {
  background-color: rgba(var(--bs-success-rgb), 0.2);
}

.deduction {
  background-color: rgba(var(--bs-danger-rgb), 0.1);
  max-width: 76px;
  word-wrap: break-word;
  white-space: normal;
}

[data-bs-theme="dark"] .deduction {
  background-color: rgba(var(--bs-danger-rgb), 0.2);
}

.net-salary {
  background-color: rgba(var(--bs-primary-rgb), 0.1);
  font-weight: bold;
}

[data-bs-theme="dark"] .net-salary {
  background-color: rgba(var(--bs-primary-rgb), 0.2);
}

.project-header .project-cell {
  padding: 8px 12px;
  font-weight: bold;
  font-size: 12px;
  text-transform: uppercase;
  text-align: center;
  color: var(--bs-body-color);
}

.data-row .name-cell .employee-name {
  font-weight: bold;
}

.data-row .name-cell .employee-position {
  font-style: italic;
  font-size: 8px;
}

.project-total {
  background-color: rgba(var(--bs-warning-rgb), 0.2);
}

[data-bs-theme="dark"] .project-total {
  background-color: rgba(var(--bs-warning-rgb), 0.15);
}

.project-total td {
  font-weight: bold;
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
