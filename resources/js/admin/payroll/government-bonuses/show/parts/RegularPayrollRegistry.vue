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
    <template #sheet-type>( GOVERNMENT BONUS )</template>
    <template #agency>TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE</template>
    <template #title>PAYROLL OF {{ (bonus_type_name || 'GOVERNMENT BONUS').toUpperCase() }} FOR THE MONTH OF {{ month }}</template>

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

    <template #table>
      <table class="excel-table">
        <thead>
          <tr class="header-labels">
            <th>Emp#</th>
            <th>Name / Position</th>
            <th>Bonus Amount</th>
            <th>Total Amount</th>
            <th v-if="!isManual" style="width: 150px">Adjustments</th>
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

            <td class="text-center">
              <input
                v-if="isManual"
                type="number"
                min="0"
                step="0.01"
                class="form-control border-0 text-center bg-body"
                v-model="emp.bonus_amount"
                @change="adjustRow(emp)"
              />
              <span v-else>{{ formatMoney(emp.bonus_amount) }}</span>
            </td>
            <td class="text-center"><strong>{{ formatMoney(emp.total) }}</strong></td>

            <td v-if="!isManual" class="text-center">
              <input
                type="number"
                class="form-control border-0 text-center bg-body"
                v-model="emp.adjustments"
                @change="adjustRow(emp)"
              />
            </td>

            <td class="text-center"><strong>{{ formatMoney(emp.net_pay) }}</strong></td>

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
            <td :colspan="isManual ? 7 : 8" class="text-center py-3">
              No employees found for the selected filters.
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="grand-total text-center">
            <td colspan="2" class="text-end"><strong>GRAND TOTAL</strong></td>
            <td class="number-cell">{{ formatNumber(grandTotals("bonus_amount")) }}</td>
            <td class="number-cell">{{ formatNumber(grandTotals("total")) }}</td>
            <td v-if="!isManual" class="number-cell">{{ formatNumber(grandTotals("adjustments")) }}</td>
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
  name: "GovernmentBonusPayrollRegistry",
  components: { PayrollRegistryLayout },
  props: {
    employees: { type: Array, required: true },
    status: { type: String, required: true },
    payroll_no: { type: String, required: true },
    month: { type: String, required: true },
    bonus_type_name: { type: String, default: "" },
    computation_type: { type: String, default: "" },
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
    isManual() {
      return this.computation_type === "manual";
    },
    positionOptions() {
      const positions = new Set();
      this.employees.forEach((emp) => {
        if (emp.position) positions.add(emp.position);
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
    async handleDownload() {},
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
      return this.filteredEmployees.reduce((total, emp) => total + (Number(emp[field]) || 0), 0);
    },
    async adjustRow(emp) {
      this.loading = true;
      try {
        await axios.post(
          `/api/payroll/government-bonuses/items/${this.payroll_no}/${emp.id}`,
          {
            bonus_amount: emp.bonus_amount,
            adjustment: this.isManual ? 0 : emp.adjustments,
            remarks: emp.remarks,
          },
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
