<template>
  <div class="payroll-registry-container" :class="status" :data-bs-theme="theme">
    <!-- Toolbar -->
    <div class="excel-toolbar">
      <div class="status-badge">
        <i :class="['fa-solid', statusConfig.icon]"></i>
        {{ statusConfig.label }}
      </div>
      <div class="toolbar-left d-flex gap-2">
        <button class="toolbar-btn"><i class="fa-solid fa-print"></i> Print</button>
        <div class="dropdown">
          <button class="toolbar-btn left dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-download"></i> Downloads
          </button>
          <ul class="dropdown-menu dropdown-menu-end ">
            <li><a class="dropdown-item" href="javascript:void(0)" @click="downloadPayroll('registry', payroll_no)">Payroll Registry</a></li>
            <li><a class="dropdown-item" href="javascript:void(0)" @click="downloadPayroll('aut', payroll_no)">Absences & Leaves</a></li>
            <li><a class="dropdown-item" href="javascript:void(0)" @click="downloadPayroll('payslip', payroll_no)">Payslip</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Sheet -->
    <div class="excel-sheet">
      <LoaderVue :visible="loading" :hasBackground="true" status="uploading" message="Uploading, please wait..." />
      <div class="sheet-header">

        <h1 class="sheet-title">PAYROLL FOR CONTRACT PRICE OF PROJECT PERSONNEL</h1>
        <p class="sheet-subtitle">Source of Fund: GAA, LITIGATION, ISSP, TECHNICOM</p>
      </div>

      <div class="sheet-info">
        <div class="info-text">
          We hereby acknowledge to have received the sums therein specified opposite our respective names for our services rendered:
        </div>
        <div class="info-period">Period: <strong>1–15 September 2025</strong></div>
      </div>
      <div class="excel-table-wrapper table-responsive">
        <table class="excel-table">
          <thead>
            <tr class="header-labels">
              <th class="p-4">Emp#</th>
              <th class="p-4">Name / Position</th>
              <th class="p-4">Monthly <br> Rate</th>
              <th class="p-4">Salary <br> Earned</th>
              <th class="p-4">Absences / Lates / Undertime</th>
              <th class="p-4">Overtime</th>
              <th class="p-4">Holiday <br> Excess</th>
              <th class="p-4" style="width: 100px;">Total Salary</th>
              <th class="p-4" style="width: 100px">EWT <br/> (2%)</th>
              <th class="p-4" style="width: 100px">Percentage Tax <br/> (3%)</th>
              <th class="p-4" style="width: 100px">Tax <br/> (EWT: 5%)</th>
              <th class="p-4" style="width: 100px">Overall Tax</th>

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

              <th style="width: 150px">Adjustment</th>
              <th class="net-salary">Net <br> Salary</th>
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
              <td class="text-center text-center">{{ emp.employee_no }}</td>
              <td class="text-center name-cell">
                <div class="employee-name">{{ emp.name }}</div>
                <div class="employee-position">{{ emp.position }}</div>
              </td>

              <td class="text-center number-cell">{{ formatNumber(emp.monthly_rate) }}</td>
              <td class="text-center number-cell">{{ formatNumber(emp.salary_earned) }}</td>
              <td class="text-center number-cell deduction">{{ formatNumber(emp.aut)}}</td>
              <td class="text-center number-cell">{{ formatNumber(emp.overtime)}}</td>
              <td class="text-center number-cell">{{ formatNumber(emp.holiday)}}</td>
              <td class="text-center number-cell earning">{{ formatNumber(emp.total_salary) }}</td>
              <td class="text-center number-cell deduction">{{ formatNumber(emp.ewt_2) }}</td>
              <td class="text-center number-cell deduction">{{ formatNumber(emp.percentage_tax_3) }}</td>
              <td class="text-center number-cell deduction">{{ formatNumber(emp.tax_ewt_5) }}</td>
                <td class="text-center number-cell deduction">{{ formatNumber(emp.w_tax) }}</td>
              <!-- Dynamic Earnings -->
              <td
                v-for="(earning, eIndex) in dynamicEarnings"
                :key="'earning-' + eIndex"
                class="text-center number-cell earning"
              >
                {{ formatNumber(getEarningAmount(emp, earning)) }}
              </td>

              <!-- Dynamic Deductions -->
              <td
                v-for="(deduction, dIndex) in dynamicDeductions"
                :key="'deduction-' + dIndex"
                class="text-center number-cell deduction"
              >
                {{ formatNumber(getDeductionAmount(emp, deduction)) }}
              </td>

              <td class="text-center text-center">
                <input 
                  type="number" 
                  v-model="emp.adjustment"
                  @change="adjustRow(emp)"
                  class="text-center form-control"
                  style="width: 100px"
                />
              </td>

              <td class="text-center number-cell net-salary">{{ formatNumber(emp.net_salary) }}</td>
              <td class="text-center">
                    <textarea style="width: 250px" class="text-center form-control my-3"
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
              <td
                v-for="(earning, eIndex) in dynamicEarnings"
                :key="'earning-total-' + eIndex"
                class="number-cell earning"
              >
                {{ formatNumber(projectTotals(project, 'earnings', earning)) }}
              </td>

              <td
                v-for="(deduction, dIndex) in dynamicDeductions"
                :key="'deduction-total-' + dIndex"
                class="number-cell deduction"
              >
                {{ formatNumber(projectTotals(project, 'deductions', deduction)) }}
              </td>

              <td class="number-cell">{{ formatNumber(projectTotals(project, 'adjustment')) }}</td>
              <td class="number-cell net-salary"><strong>{{ formatNumber(projectTotals(project, 'net_salary')) }}</strong></td>
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
              <td
                v-for="(earning, eIndex) in dynamicEarnings"
                :key="'earning-grand-' + eIndex"
                class="number-cell earning"
              >
                {{ formatNumber(grandTotals('earnings', earning)) }}
              </td>

              <td
                v-for="(deduction, dIndex) in dynamicDeductions"
                :key="'deduction-grand-' + dIndex"
                class="number-cell deduction"
              >
                {{ formatNumber(grandTotals('deductions', deduction)) }}
              </td>

              <td class="number-cell">{{ formatNumber(grandTotals('adjustment')) }}</td>
              <td class="number-cell net-salary"><strong>{{ formatNumber(grandTotals('net_salary')) }}</strong></td>
              <td></td>
            </tr>
          </tfoot>

        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import LoaderVue from '../../../../../components/LoaderVue.vue';
const token = localStorage.getItem("auth_token");

export default {
  name: "CosPayrollRegistry",
  components: { LoaderVue },
  props: {
    projects: { type: Array, required: true },
    status: { type: String, required: true },
    payroll_no: { type: String, required: true },
  },
  data() {
    return {
      token: token,
      loading: false,
      theme: document.documentElement.getAttribute("data-bs-theme") || "light",
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
    statusConfig() {
      const configs = {
        draft: { label: "Draft", icon: "fa-file-pen", color: "#f39c12", bg: "#fef9e7", darkColor: "#f5a623", darkBg: "#3a2a0f" },
        pending: { label: "Pending Review", icon: "fa-clock", color: "#3498db", bg: "#ebf5fb", darkColor: "#5dade2", darkBg: "#1a2f3a" },
        approved: { label: "Approved", icon: "fa-circle-check", color: "#27ae60", bg: "#eafaf1", darkColor: "#2ecc71", darkBg: "#1a3a28" },
        for_releasing: { label: "For Releasing", icon: "fa-paper-plane", color: "#9b59b6", bg: "#f5eef8", darkColor: "#af7ac5", darkBg: "#2d1f35" },
        completed: { label: "Completed", icon: "fa-circle-check", color: "#16a085", bg: "#e8f8f5", darkColor: "#1abc9c", darkBg: "#183a32" },
        cancelled: { label: "Cancelled", icon: "fa-ban", color: "#e74c3c", bg: "#fadbd8", darkColor: "#ec7063", darkBg: "#3a1f1c" },
        failed: { label: "Failed", icon: "fa-ban", color: "#454444", bg: "#949292", darkColor: "#7f8c8d", darkBg: "#2c2c2c" },
      };
      return configs[this.status] || configs.draft;
    },
  },
  methods: {
    async downloadPayroll(type, payroll_no) {

      const urlArr = {
        'registry': `/api/payroll/salary-pay/${payroll_no}/download`,
        'aut': `/api/payroll/salary-pay/absences-leaves/${payroll_no}/download`,
        'payslip': `/api/payroll/salary-pay/payslip/${payroll_no}/download`,
      }

      const endPoint = urlArr[type];

      try {
          const response = await axios.get(endPoint, {
              headers: { Authorization: `Bearer ${this.token}` },
              responseType: 'blob', 
          });

          const url = window.URL.createObjectURL(new Blob([response.data]));
          const a = document.createElement('a');
          a.href = url;
          a.download = `payroll_registry_${payroll_no}.xlsx`;
          document.body.appendChild(a);
          a.click();

          a.remove();
          window.URL.revokeObjectURL(url);
      } catch (error) {
          console.error('Download failed:', error);
      }
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
                {
                    adjustment: emp.adjustment,
                    remarks: emp.remarks,
                },
                {
                    headers: { Authorization: `Bearer ${this.token}` },
                    responseType: "blob",
                }
            );
            this.$emit("fetch_data");
        } catch (error) {
            console.error(error);
        } finally {
            this.loading = false;
        }
    },
    applyStatusTheme() {
      const { color, bg, darkColor, darkBg } = this.statusConfig;
      const root = this.$el;
      if (this.theme === "dark") {
        root.style.setProperty("--status-color", darkColor);
        root.style.setProperty("--status-bg", darkBg);
      } else {
        root.style.setProperty("--status-color", color);
        root.style.setProperty("--status-bg", bg);
      }
    },
    watchGlobalTheme() {
      const observer = new MutationObserver(() => {
        const newTheme = document.documentElement.getAttribute("data-bs-theme");
        if (newTheme && newTheme !== this.theme) {
          this.theme = newTheme;
          this.applyStatusTheme();
        }
      });
      observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["data-bs-theme"],
      });
    },
  },
  mounted() {
    this.applyStatusTheme();
    this.watchGlobalTheme();
  },
  watch: {
    status() {
      this.applyStatusTheme();
    },
  },
};
</script>

<style scoped>
.payroll-registry-container {
  --status-color: #ccc;
  --status-bg: #f9f9f9;
  
  --bs-success-rgb: 25, 135, 84;
  --bs-danger-rgb: 220, 53, 69;
  --bs-primary-rgb: 13, 110, 253;
  --bs-warning-rgb: 255, 193, 7;
  --bs-dark-rgb: 33, 37, 41;
  
  background: var(--status-bg);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  min-height: 100vh;
  transition: all 0.3s ease;
  padding-bottom: 24px;
  color: var(--bs-body-color);
}

/* Dark mode adjustments */
[data-bs-theme="dark"] .payroll-registry-container {
  --status-bg: #1a1d20;
  background: var(--bs-secondary-bg, #212529);
}

/* Toolbar */
.excel-toolbar {
  background: var(--status-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 16px;
  color: white;
  transition: background 0.3s ease;
}

.toolbar-left {
  display: flex;
  gap: 4px;
}

.toolbar-btn {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.4);
  color: white;
  padding: 6px 12px;
  border-radius: 3px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.toolbar-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.toolbar-btn i {
  margin-right: 6px;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  color: var(--status-color);
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 600;
}

/* Excel Sheet */
.excel-sheet {
  background: var(--bs-body-bg, white);
  margin: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  border: 1px solid var(--bs-border-color, #d0d0d0);
  position: relative;
}

[data-bs-theme="dark"] .excel-sheet {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.sheet-header {
  padding: 24px 24px 8px 24px;
  text-align: center;
  border-bottom: 2px solid var(--bs-border-color, #e0e0e0);
}

.sheet-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--bs-body-color, #333);
  margin: 0 0 8px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sheet-subtitle {
  font-size: 12px;
  color: var(--bs-secondary-color, #666);
}

.sheet-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: var(--bs-secondary-bg, #f9f9f9);
  border-bottom: 1px solid var(--bs-border-color, #e0e0e0);
}

[data-bs-theme="dark"] .sheet-info {
  background: rgba(255, 255, 255, 0.05);
}

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

.project-header .row-number {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  padding: 4px;
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

.total {
  border-top: 2px solid rgba(var(--bs-dark-rgb), 0.4);
  font-weight: bold;
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