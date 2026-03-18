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

    <template #filters>
      <div class="payroll-filter-bar">
        <input
          v-model.trim="searchTerm"
          type="text"
          class="payroll-filter-input"
          placeholder="Search name or employee number"
        />

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
          Showing {{ filteredEmployees.length }} of {{ employees.length }}
        </div>
      </div>
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
            <th>actions</th>
          </tr>
        </thead>

        <tbody>
          <tr class="data-row" v-for="(emp, index) in filteredEmployees" :key="index">
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
                class="form-control border-0 text-center bg-body"
                v-model="emp.adjustments"
                @change="adjustRow(emp)"
              />
            </td>

            <td class="text-center">
              <strong>{{ formatMoney(emp.net_pay) }}</strong>
            </td>

            <td class="text-center">
              <textarea
                class="form-control border-0 bg-body"
                v-model="emp.remarks"
                @change="adjustRow(emp)"
              ></textarea>
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
          <tr v-if="!filteredEmployees.length">
            <td colspan="12" class="text-center py-3">
              No employees found for the selected filters.
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
      searchTerm: "",
      selectedPosition: "",
      remarksFilter: "all",
    };
  },
  computed: {
    positionOptions() {
      const positions = new Set();
      this.employees.forEach((emp) => {
        if (emp.position) {
          positions.add(emp.position);
        }
      });
      return Array.from(positions).sort((a, b) => a.localeCompare(b));
    },
    filteredEmployees() {
      const keyword = this.searchTerm.toLowerCase();
      return this.employees.filter((emp) => {
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
    },
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
      return this.filteredEmployees.reduce((total, emp) => {
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
.excel-table {
  min-width: 100%;
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
