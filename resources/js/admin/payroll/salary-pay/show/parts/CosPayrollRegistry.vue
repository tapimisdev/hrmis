<template>
  <PayrollRegistryLayout :status="status" :payroll_no="payroll_no" :loading="loading" :downloads="[
    { key: 'registry', label: 'Payroll Registry (xlxs)' },
    { key: 'aut', label: 'Absences & Leaves (xlxs)' },
    { key: 'payslip', label: 'Payslip (xlxs)' },
  ]" @print="handlePrint">
    <!-- Header slots -->
    <template #sheet-type><span class="d-none"></span></template>

    <template #notice>
      <div v-if="hasUndeductedReferences" class="undeducted-alert" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>
          This payroll has deduction reference amounts that are not yet deducted.
        </span>
      </div>
    </template>

    <template #agency>
      PAYROLL FOR CONTRACT PRICE OF PROJECT PERSONNEL
    </template>

    <template #title>
      Source of Fund: GAA, LITIGATION, ISSP, TECHNICOM
    </template>

    <template #period>
      Period: <strong>{{ period_covered }}</strong>
    </template>

    <template #filters>
      <div class="payroll-filter-bar">
        <input
          v-model.trim="searchTerm"
          type="text"
          class="payroll-filter-input"
          placeholder="Search name or employee number"
        />

        <select v-model="selectedProject" class="payroll-filter-select">
          <option value="">All projects</option>
          <option v-for="project in projectOptions" :key="project" :value="project">
            {{ project }}
          </option>
        </select>

        <select v-model="selectedPosition" class="payroll-filter-select">
          <option value="">All positions</option>
          <option v-for="position in positionOptions" :key="position" :value="position">
            {{ position }}
          </option>
        </select>

        <select v-model="remarksFilter" class="payroll-filter-select">
          <option value="all">All remarks</option>
          <option value="with">With remarks</option>
          <option value="without">Without remarks</option>
        </select>

        <div class="payroll-filter-meta">
          Showing {{ filteredEmployeeCount }} of {{ totalEmployeeCount }}
        </div>
      </div>
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
            <th class="p-4 deferred-reference" style="width: 90px;">AUT <br />Ref</th>
            <th class="p-4 deduction">AUT <br />Deduction</th>
            <th class="p-4">Overtime</th>
            <th class="p-4">Holiday <br />Excess</th>

            <th class="p-4 earning" style="width: 100px;">Total Salary</th>
            <th class="p-4 deferred-reference" style="width: 90px;">EWT <br />Ref</th>
            <th class="p-4 deduction" style="width: 100px;">EWT <br />(2%)</th>
            <th class="p-4 deferred-reference" style="width: 90px;">Percentage Tax <br />Ref</th>
            <th class="p-4 deduction" style="width: 100px;">Percentage Tax <br />(3%)</th>
            <th class="p-4 deferred-reference" style="width: 90px;">Tax EWT <br />Ref</th>
            <th class="p-4 deduction" style="width: 100px;">Tax <br />(EWT: 5%)</th>
            <th class="p-4 deferred-reference" style="width: 90px;">Overall Tax <br />Ref</th>
            <th class="p-4 deduction" style="width: 100px;">Overall Tax</th>
            <th class="p-4 deferred-reference" style="width: 90px;">HMO <br />Ref</th>
            <th class="p-4 deduction" style="width: 100px;">HMO</th>

            <th style="width: 150px;">Adjustment</th>
            <th class="net-salary">Net <br />Salary</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>

        <!-- Projects -->
        <tbody v-for="(project, pIndex) in filteredProjects" :key="pIndex">
          <tr class="project-header">
            <td colspan="100" class="project-cell">
              <span class="project-cell-label">{{ project.name }}</span>
            </td>
          </tr>

          <!-- Employee Rows -->
          <tr v-for="(emp, eIndex) in project.employees" :key="eIndex" class="data-row">
            <td class="text-center">{{ emp.employee_no }}</td>

            <td class="text-center name-cell">
              <div class="employee-name">{{ emp.name }}</div>
              <div class="employee-position">{{ emp.position }}</div>
            </td>

            <td class="text-center number-cell">{{ formatNumber(emp.monthly_rate) }}</td>
            <td class="text-center number-cell">{{ formatNumber(emp.salary_earned) }}</td>
            <td class="text-center number-cell deferred-reference reference-cell">
              {{ formatNumber(deferredDeductionReferenceAmount(emp, 'aut')) }}
            </td>
            <td class="text-center number-cell deduction breakdown-cell">
              <div class="breakdown-total">{{ formatNumber(emp.aut) }}</div>
              <div v-if="hasDeductionBreakdown(emp, 'aut')" class="deduction-breakdown">
                <div
                  v-for="(item, itemIndex) in deductionBreakdown(emp, 'aut')"
                  :key="`${emp.employee_no}-aut-${itemIndex}`"
                  class="deduction-breakdown-row"
                >
                  <div class="deduction-breakdown-main">
                    <span class="deduction-breakdown-payroll">{{ deductionBreakdownLabel(item) }}</span>
                    <span class="deduction-breakdown-amount">{{ formatNumber(item.amount) }}</span>
                  </div>
                  <div class="deduction-breakdown-source">
                    <span v-if="item.period_covered" class="deduction-breakdown-period">
                      {{ item.period_covered }}
                    </span>
                  </div>
                </div>
              </div>
            </td>
            <td class="text-center number-cell">{{ formatNumber(emp.overtime) }}</td>
            <td class="text-center number-cell">{{ formatNumber(emp.holiday) }}</td>
            <td class="text-center number-cell earning">{{ formatNumber(emp.total_salary) }}</td>

            <td class="text-center number-cell deferred-reference reference-cell">
              {{ formatNumber(deferredDeductionReferenceAmount(emp, 'ewt_2')) }}
            </td>
            <td class="text-center number-cell deduction breakdown-cell">
              <div class="breakdown-total">{{ formatNumber(emp.ewt_2) }}</div>
              <div v-if="hasDeductionBreakdown(emp, 'ewt_2')" class="deduction-breakdown">
                <div
                  v-for="(item, itemIndex) in deductionBreakdown(emp, 'ewt_2')"
                  :key="`${emp.employee_no}-ewt-2-${itemIndex}`"
                  class="deduction-breakdown-row"
                >
                  <div class="deduction-breakdown-main">
                    <span class="deduction-breakdown-payroll">{{ deductionBreakdownLabel(item) }}</span>
                    <span class="deduction-breakdown-amount">{{ formatNumber(item.amount) }}</span>
                  </div>
                  <span v-if="item.period_covered" class="deduction-breakdown-period">
                    {{ item.period_covered }}
                  </span>
                </div>
              </div>
            </td>
            <td class="text-center number-cell deferred-reference reference-cell">
              {{ formatNumber(deferredDeductionReferenceAmount(emp, 'percentage_tax_3')) }}
            </td>
            <td class="text-center number-cell deduction breakdown-cell">
              <div class="breakdown-total">{{ formatNumber(emp.percentage_tax_3) }}</div>
              <div v-if="hasDeductionBreakdown(emp, 'percentage_tax_3')" class="deduction-breakdown">
                <div
                  v-for="(item, itemIndex) in deductionBreakdown(emp, 'percentage_tax_3')"
                  :key="`${emp.employee_no}-percentage-tax-3-${itemIndex}`"
                  class="deduction-breakdown-row"
                >
                  <div class="deduction-breakdown-main">
                    <span class="deduction-breakdown-payroll">{{ deductionBreakdownLabel(item) }}</span>
                    <span class="deduction-breakdown-amount">{{ formatNumber(item.amount) }}</span>
                  </div>
                  <span v-if="item.period_covered" class="deduction-breakdown-period">
                    {{ item.period_covered }}
                  </span>
                </div>
              </div>
            </td>
            <td class="text-center number-cell deferred-reference reference-cell">
              {{ formatNumber(deferredDeductionReferenceAmount(emp, 'tax_ewt_5')) }}
            </td>
            <td class="text-center number-cell deduction breakdown-cell">
              <div class="breakdown-total">{{ formatNumber(emp.tax_ewt_5) }}</div>
              <div v-if="hasDeductionBreakdown(emp, 'tax_ewt_5')" class="deduction-breakdown">
                <div
                  v-for="(item, itemIndex) in deductionBreakdown(emp, 'tax_ewt_5')"
                  :key="`${emp.employee_no}-tax-ewt-5-${itemIndex}`"
                  class="deduction-breakdown-row"
                >
                  <div class="deduction-breakdown-main">
                    <span class="deduction-breakdown-payroll">{{ deductionBreakdownLabel(item) }}</span>
                    <span class="deduction-breakdown-amount">{{ formatNumber(item.amount) }}</span>
                  </div>
                  <span v-if="item.period_covered" class="deduction-breakdown-period">
                    {{ item.period_covered }}
                  </span>
                </div>
              </div>
            </td>
            <td class="text-center number-cell deferred-reference reference-cell">
              {{ formatNumber(deferredDeductionReferenceAmount(emp, 'w_tax')) }}
            </td>
            <td class="text-center number-cell deduction breakdown-cell">
              <div class="breakdown-total">{{ formatNumber(emp.w_tax) }}</div>
              <div v-if="hasDeductionBreakdown(emp, 'w_tax')" class="deduction-breakdown">
                <div
                  v-for="(item, itemIndex) in deductionBreakdown(emp, 'w_tax')"
                  :key="`${emp.employee_no}-w-tax-${itemIndex}`"
                  class="deduction-breakdown-row"
                >
                  <div class="deduction-breakdown-main">
                    <span class="deduction-breakdown-payroll">{{ deductionBreakdownLabel(item) }}</span>
                    <span class="deduction-breakdown-amount">{{ formatNumber(item.amount) }}</span>
                  </div>
                  <span v-if="item.period_covered" class="deduction-breakdown-period">
                    {{ item.period_covered }}
                  </span>
                </div>
              </div>
            </td>
            <td class="text-center number-cell deferred-reference reference-cell">
              {{ formatNumber(deferredDeductionReferenceAmount(emp, 'hmo')) }}
            </td>
            <td class="text-center number-cell deduction breakdown-cell">
              <div class="breakdown-total">{{ formatNumber(emp.hmo) }}</div>
              <div v-if="hasDeductionBreakdown(emp, 'hmo')" class="deduction-breakdown">
                <div
                  v-for="(item, itemIndex) in deductionBreakdown(emp, 'hmo')"
                  :key="`${emp.employee_no}-hmo-${itemIndex}`"
                  class="deduction-breakdown-row"
                >
                  <div class="deduction-breakdown-main">
                    <span class="deduction-breakdown-payroll">{{ deductionBreakdownLabel(item) }}</span>
                    <span class="deduction-breakdown-amount">{{ formatNumber(item.amount) }}</span>
                  </div>
                  <span v-if="item.period_covered" class="deduction-breakdown-period">
                    {{ item.period_covered }}
                  </span>
                </div>
              </div>
            </td>

            <td class="text-center">
              <input type="number" v-model="emp.adjustment" @change="adjustRow(emp)"
                class="text-center bg-body-secondary form-control" style="width: 100px;" />
            </td>

            <td class="text-center number-cell net-salary">
              {{ formatNumber(emp.net_salary) }}
            </td>

            <td class="text-center">
              <textarea style="width: 250px;" class="text-center bg-body-secondary form-control my-3"
                v-model="emp.remarks" @change="adjustRow(emp)"></textarea>
            </td>
            <td class="text-center">
                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    @click="$emit('delete', emp)"
                    title="Delete"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
          </tr>

          <!-- Project Total Row -->
          <tr class="project-total text-center">
            <td colspan="2" class="text-end"><strong>Total ({{ project.name }})</strong></td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'monthly_rate')) }}</td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'salary_earned')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(projectDeferredReferenceTotals(project, 'aut')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'aut')) }}</td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'overtime')) }}</td>
            <td class="number-cell">{{ formatNumber(projectTotals(project, 'holiday')) }}</td>
            <td class="number-cell earning">{{ formatNumber(projectTotals(project, 'total_salary')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(projectDeferredReferenceTotals(project, 'ewt_2')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'ewt_2')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(projectDeferredReferenceTotals(project, 'percentage_tax_3')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'percentage_tax_3')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(projectDeferredReferenceTotals(project, 'tax_ewt_5')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'tax_ewt_5')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(projectDeferredReferenceTotals(project, 'w_tax')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'w_tax')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(projectDeferredReferenceTotals(project, 'hmo')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'hmo')) }}</td>

            <td class="number-cell">{{ formatNumber(projectTotals(project, 'adjustment')) }}</td>
            <td class="number-cell net-salary">
              <strong>{{ formatNumber(projectTotals(project, 'net_salary')) }}</strong>
            </td>
            <td></td>
            <td></td>
          </tr>
        </tbody>

        <tbody v-if="!filteredProjects.length">
          <tr>
            <td colspan="23" class="text-center py-3">
              No employees found for the selected filters.
            </td>
          </tr>
        </tbody>
        <!-- Grand Total Row -->
        <tfoot>
          <tr class="grand-total text-center">
            <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
            <td class="number-cell">{{ formatNumber(grandTotals('monthly_rate')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('salary_earned')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(grandDeferredReferenceTotals('aut')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('aut')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('overtime')) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals('holiday')) }}</td>
            <td class="number-cell earning">{{ formatNumber(grandTotals('total_salary')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(grandDeferredReferenceTotals('ewt_2')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('ewt_2')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(grandDeferredReferenceTotals('percentage_tax_3')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('percentage_tax_3')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(grandDeferredReferenceTotals('tax_ewt_5')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('tax_ewt_5')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(grandDeferredReferenceTotals('w_tax')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('w_tax')) }}</td>
            <td class="number-cell deferred-reference">{{ formatNumber(grandDeferredReferenceTotals('hmo')) }}</td>
            <td class="number-cell deduction">{{ formatNumber(grandTotals('hmo')) }}</td>

            <td class="number-cell">{{ formatNumber(grandTotals('adjustment')) }}</td>
            <td class="number-cell net-salary">
              <strong>{{ formatNumber(grandTotals('net_salary')) }}</strong>
            </td>
            <td></td>
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
      searchTerm: "",
      selectedProject: "",
      selectedPosition: "",
      remarksFilter: "all",
    };
  },
  computed: {
    totalEmployeeCount() {
      return this.projects.reduce((count, project) => count + project.employees.length, 0);
    },
    filteredEmployeeCount() {
      return this.filteredProjects.reduce((count, project) => count + project.employees.length, 0);
    },
    hasUndeductedReferences() {
      return this.filteredProjects.some((project) =>
        project.employees.some((emp) => this.deferredDeductionReference(emp).length > 0)
      );
    },
    projectOptions() {
      return this.projects
        .map((project) => project.name)
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b));
    },
    positionOptions() {
      const positions = new Set();
      this.projects.forEach((project) => {
        project.employees.forEach((emp) => {
          if (emp.position) {
            positions.add(emp.position);
          }
        });
      });
      return Array.from(positions).sort((a, b) => a.localeCompare(b));
    },
    filteredProjects() {
      const keyword = this.searchTerm.toLowerCase();
      return this.projects
        .filter((project) => !this.selectedProject || project.name === this.selectedProject)
        .map((project) => {
          const employees = project.employees.filter((emp) => {
            const matchesSearch =
              !keyword ||
              String(emp.name || "").toLowerCase().includes(keyword) ||
              String(emp.employee_no || "").toLowerCase().includes(keyword);
            const matchesPosition = !this.selectedPosition || emp.position === this.selectedPosition;
            const hasRemarks = Boolean(String(emp.remarks || "").trim());
            const matchesRemarks =
              this.remarksFilter === "all" ||
              (this.remarksFilter === "with" && hasRemarks) ||
              (this.remarksFilter === "without" && !hasRemarks);

            return matchesSearch && matchesPosition && matchesRemarks;
          });

          return { ...project, employees };
        })
        .filter((project) => project.employees.length > 0);
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
    deductionBreakdown(emp, key) {
      const breakdowns = emp.deduction_breakdowns || {};
      const items = Array.isArray(breakdowns[key])
        ? breakdowns[key]
        : key === "aut" && Array.isArray(emp.aut_breakdown)
          ? emp.aut_breakdown
          : [];

      return items.filter((item) => Number(item.amount) > 0);
    },
    hasDeductionBreakdown(emp, key) {
      return this.deductionBreakdown(emp, key).length > 1;
    },
    deferredDeductionReference(emp) {
      return Array.isArray(emp.deferred_deduction_reference)
        ? emp.deferred_deduction_reference.filter((item) => Number(item.amount) > 0)
        : [];
    },
    deferredDeductionReferenceAmount(emp, key) {
      const item = this.deferredDeductionReference(emp).find((reference) => reference.key === key);
      return Number(item?.amount) || 0;
    },
    deductionBreakdownLabel(item) {
      return item.payroll_no || item.label || "Payroll";
    },
    projectTotals(project, field, subfield = null) {
      let total = 0;

      project.employees.forEach((emp) => {
        total += Number(emp[field]) || 0;
      });

      return total;
    },
    projectDeferredReferenceTotals(project, key) {
      return project.employees.reduce(
        (total, emp) => total + this.deferredDeductionReferenceAmount(emp, key),
        0
      );
    },
    grandTotals(field, subfield = null) {
      return this.filteredProjects.reduce(
        (total, project) => total + this.projectTotals(project, field, subfield),
        0
      );
    },
    grandDeferredReferenceTotals(key) {
      return this.filteredProjects.reduce(
        (total, project) => total + this.projectDeferredReferenceTotals(project, key),
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
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.excel-table {
  min-width: 100%;
}

.undeducted-alert {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 0 0 12px;
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(245, 158, 11, 0.45);
  border-radius: 6px;
  background: rgba(245, 158, 11, 0.16);
  color: #f59e0b;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0;
  text-align: center;
  text-transform: uppercase;
}

.undeducted-alert i {
  color: #f59e0b;
}

.earning {
  max-width: 150px;
  overflow-wrap: anywhere;
  white-space: normal;
}

.deduction {
  max-width: 150px;
  overflow-wrap: anywhere;
  white-space: normal;
}

.deferred-reference {
  max-width: 170px;
  overflow-wrap: anywhere;
  white-space: normal;
  opacity: 0.58;
  filter: grayscale(0.2);
}

.breakdown-cell {
  min-width: 175px;
  vertical-align: top;
}

.reference-cell {
  min-width: 180px;
  vertical-align: top;
  background-color: rgba(148, 163, 184, 0.14);
}

.breakdown-total {
  font-weight: 700;
}

.deduction-breakdown {
  margin-top: 8px;
  font-size: 10.5px;
  line-height: 1.3;
  text-align: left;
}

.deduction-breakdown-row {
  padding: 5px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.deduction-breakdown-main {
  display: flex;
  gap: 8px;
  justify-content: space-between;
  align-items: baseline;
}

.deduction-breakdown-source {
  min-width: 0;
}

.deduction-breakdown-payroll {
  color: #bfdbfe;
  font-weight: 700;
  overflow-wrap: normal;
  word-break: normal;
}

.deduction-breakdown-period {
  display: block;
  color: rgba(255, 255, 255, 0.78);
  font-size: 10px;
  margin-top: 1px;
}

.deduction-breakdown-amount {
  color: #fff;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  white-space: nowrap;
}

.net-salary {
  font-weight: 700;
}

.project-header .project-cell {
  padding: 8px 12px;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  text-align: center;
}

.data-row .name-cell .employee-name {
  font-weight: 700;
}

.data-row .name-cell .employee-position {
  font-size: 10px;
}

.project-total td {
  font-weight: 700;
}

.grand-total {
  font-weight: 700;
}
</style>
