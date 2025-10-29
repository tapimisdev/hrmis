<template>
  <div class="payroll-registry-container" :class="status">
    <!-- Toolbar -->
    <div class="excel-toolbar">
      <div class="status-badge">
        <i :class="['fa-solid', statusConfig.icon]"></i>
        {{ statusConfig.label }}
      </div>
      <div class="toolbar-left">
        <button class="toolbar-btn"><i class="fa-solid fa-print"></i> Print</button>
        <button class="toolbar-btn"><i class="fa-solid fa-download"></i> Download</button>
      </div>
    </div>

    <!-- Sheet -->
    <div class="excel-sheet">
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
              <th>Emp#</th>
              <th>Name / Position</th>
              <th>Monthly <br> Rate</th>
              <th>Salary <br> Earned</th>
              <th class="deduction">AUT</th>
              <th>Overtime</th>
              <th>Holiday <br> Excess</th>
              <th class="earning">Total Salary</th>

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

              <th>Adjustment</th>
              <th class="net-salary">Net <br> Salary</th>
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
              <td class="name-cell">
                <div class="employee-name">{{ emp.name }}</div>
                <div class="employee-position">{{ emp.position }}</div>
              </td>

              <td class="number-cell">{{ formatNumber(emp.monthly_rate) }}</td>
              <td class="number-cell">{{ formatNumber(emp.salary_earned) }}</td>
              <td class="number-cell deduction">{{ formatNumber(emp.uat)}}</td>
              <td class="number-cell">{{ formatNumber(emp.overtime)}}</td>
              <td class="number-cell">{{ formatNumber(emp.holiday)}}</td>
              <td class="number-cell earning">{{ formatNumber(emp.total_salary) }}</td>

              <!-- Dynamic Earnings -->
              <td
                v-for="(earning, eIndex) in dynamicEarnings"
                :key="'earning-' + eIndex"
                class="number-cell earning"
              >
                {{ formatNumber(getEarningAmount(emp, earning)) }}
              </td>

              <!-- Dynamic Deductions -->
              <td
                v-for="(deduction, dIndex) in dynamicDeductions"
                :key="'deduction-' + dIndex"
                class="number-cell deduction"
              >
                {{ formatNumber(getDeductionAmount(emp, deduction)) }}
              </td>

              <td 
                class="number-cell"
                contenteditable="true"
                @input="updateValue($event, emp, 'adjustment')" 
                v-html="emp.adjustment">
              </td>
              <td class="number-cell net-salary">{{ formatNumber(emp.net_salary) }}</td>
            </tr>

            <!-- Project Total Row -->
            <tr class="project-total">
              <td colspan="2" class="text-end"><strong>Total ({{ project.name }})</strong></td>
              <td class="number-cell">{{ formatNumber(projectTotals(project, 'monthly_rate')) }}</td>
              <td class="number-cell">{{ formatNumber(projectTotals(project, 'salary_earned')) }}</td>
              <td class="number-cell deduction">{{ formatNumber(projectTotals(project, 'uat')) }}</td>
              <td class="number-cell">{{ formatNumber(projectTotals(project, 'overtime')) }}</td>
              <td class="number-cell">{{ formatNumber(projectTotals(project, 'holiday')) }}</td>
              <td class="number-cell earning">{{ formatNumber(projectTotals(project, 'total_salary')) }}</td>

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
            </tr>
          </tbody>

          <!-- Grand Total Row -->
          <tfoot>
            <tr class="grand-total">
              <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
              <td class="number-cell">{{ formatNumber(grandTotals('monthly_rate')) }}</td>
              <td class="number-cell">{{ formatNumber(grandTotals('salary_earned')) }}</td>
              <td class="number-cell deduction">{{ formatNumber(grandTotals('uat')) }}</td>
              <td class="number-cell">{{ formatNumber(grandTotals('overtime')) }}</td>
              <td class="number-cell">{{ formatNumber(grandTotals('holiday')) }}</td>
              <td class="number-cell earning">{{ formatNumber(grandTotals('total_salary')) }}</td>

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
            </tr>
          </tfoot>

        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { isNullOrUndef } from 'chart.js/helpers';

export default {
  name: "CosPayrollRegistry",
  props: {
    projects: {
      type: Array,
      required: true,
    },
    status: {
      type: String,
      required: true
    }
  },
  data() {
    return {

    };
  },
  computed: {
    /** Extract all unique deduction names across all employees */
    dynamicDeductions() {
      const names = new Set();
      this.projects.forEach((p) =>
        p.employees.forEach((e) =>
          e.deductions?.forEach((d) => names.add(d.deduction_type))
        )
      );
      return Array.from(names);
    },

    /** Extract all unique earning names across all employees */
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
        draft: { label: "Draft", icon: "fa-file-pen", color: "#f39c12", bg: "#fef9e7" },
        pending: { label: "Pending Review", icon: "fa-clock", color: "#3498db", bg: "#ebf5fb" },
        approved: { label: "Approved", icon: "fa-circle-check", color: "#27ae60", bg: "#eafaf1" },
        for_releasing: { label: "For Releasing", icon: "fa-paper-plane", color: "#9b59b6", bg: "#f5eef8" },
        completed: { label: "Completed", icon: "fa-circle-check", color: "#16a085", bg: "#e8f8f5" },
        cancelled: { label: "Cancelled", icon: "fa-ban", color: "#e74c3c", bg: "#fadbd8" },
        failed: { label: "Failed", icon: "fa-ban", color: "#454444", bg: "#949292" },
      };
      return configs[this.status] || configs.draft;
    },
  },
  methods: {
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
    applyStatusTheme() {
      const { color, bg } = this.statusConfig;
      const root = this.$el;
      root.style.setProperty("--status-color", color);
      root.style.setProperty("--status-bg", bg);
    },
    projectTotals(project, field, subfield = null) {
      let total = 0;
      project.employees.forEach(emp => {
        if (field === 'earnings') {
          total += this.getEarningAmount(emp, subfield);
        } else if (field === 'deductions') {
          total += this.getDeductionAmount(emp, subfield);
        } else {
          total += Number(emp[field]) || 0;
        }
      });
      return total;
    },
    grandTotals(field, subfield = null) {
      let total = 0;
      this.projects.forEach(project => {
        total += this.projectTotals(project, field, subfield);
      });
      return total;
    },

    updateValue(event, emp, field) {
      let newValue = event.target.innerText.trim();
      newValue = newValue.replace(/[^0-9.-]/g, '');

      emp[field] = newValue;
    }

  },
  mounted() {
    this.applyStatusTheme();
  },
  watch: {
    status() {
      this.applyStatusTheme();
    },
  },
};
</script>

<style lang="scss" scoped>
@import '../../../../sass/variables';

.payroll-registry-container {
  --status-color: #ccc;
  --status-bg: #f9f9f9;
  background: var(--status-bg);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  min-height: 100vh;
  transition: all 0.3s ease;
  padding-bottom: 24px;
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

    &:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    i {
      margin-right: 6px;
    }
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
}

/* Excel Sheet */
.excel-sheet {
  background: white;
  margin: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  border: 1px solid #d0d0d0;
}

.sheet-header {
  padding: 24px 24px 8px 24px;
  text-align: center;
  border-bottom: 2px solid #e0e0e0;

  .sheet-title {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .sheet-subtitle {
    font-size: 12px;
    color: #666;
  }
}

.sheet-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: #f9f9f9;
  border-bottom: 1px solid #e0e0e0;
}

.excel-table {
  width: 100%;
  border-collapse: collapse;

  th,
  td {
    border: 1px solid #d0d0d0;
    padding: 2px 8px;
    font-size: 11px;
    // text-align: center;
  }

  .header-labels th {
    text-align: center;
    font-weight: 700;
    color: var(--status-color);
  }

  .earning {
    background-color: rgba($success, 0.1);
    max-width: 76px;
    word-wrap: break-word;
    white-space: normal;
  }

  .deduction {
    background-color: rgba($danger, 0.1);
    max-width: 76px;
    word-wrap: break-word;
    white-space: normal;
  }

  .net-salary {
    background-color: rgba($primary, 0.1);
    font-weight: bold;
  }

  .project-header {
    .row-number {
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      padding: 4px;
    }
    
    .project-cell {
      padding: 8px 12px;
      font-weight: bold;
      font-size: 12px;
      text-transform: uppercase;
      text-align: center;
      color: $dark;
    }
  }

  .data-row {
      .name-cell {
      .employee-name {
        font-weight: bold;
      }
      .employee-position {
        font-style: italic;
        font-size: 8px;
      }
    }
  }
}
.total {
  border-top: 2px solid rgba($dark, 0.4);
  font-weight: bold;
}

.project-total {
  background-color: rgba($warning, 0.2);
  td {
    font-weight: bold;
  }
}

.grand-total {
  border-top: 2px solid rgba($dark, 1);
  font-weight: bolder;
  height: 60px;
  background-color: var(--status-color);
  color: var(--status-bg);
}
</style>
