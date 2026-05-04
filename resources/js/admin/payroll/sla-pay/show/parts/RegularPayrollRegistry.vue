<template>
  <PayrollRegistryLayout
    :status="status"
    :payroll_no="payroll_no"
    :loading="loading"
    :downloads="[{ key: 'registry', label: 'Registry' }]"
    :download-endpoints="{
      registry: `/api/payroll/sla-pay/download/registry/${payroll_no}`,
    }"
    @print="handlePrint"
    @download="handleDownload"
  >
    <!-- Header slots -->
    <template #sheet-type><span class="d-none"></span></template>
    <template #agency>TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE</template>
    <template #title>
      PAYROLL OF SUBSISTENCE AND LAUNDRY ALLOWANCE PAY FOR THE MONTH OF {{ month?.toUpperCase() }}
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
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <template v-for="group in groupedEmployees" :key="group.id">
            <tr class="project-header division-header">
              <td colspan="12" class="project-cell division-cell">
                <span class="project-cell-label">{{ group.name }}</span>
              </td>
            </tr>

            <tr class="data-row" v-for="emp in group.employees" :key="emp.id">
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

            <tr class="project-total division-total text-center">
              <td colspan="2" class="text-end"><strong>SUBTOTAL</strong></td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "subsistence_allowance")) }}</td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "laundry_allowance")) }}</td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "total_sla")) }}</td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "ut_deductions")) }}</td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "uniform_deduction")) }}</td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "healthcard")) }}</td>
              <td class="number-cell">{{ formatNumber(groupTotals(group.employees, "adjustments")) }}</td>
              <td class="number-cell"><strong>{{ formatNumber(groupTotals(group.employees, "net_pay")) }}</strong></td>
              <td></td>
              <td></td>
            </tr>
          </template>
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
    groupedEmployees() {
      const groups = [];
      const indexes = new Map();

      this.filteredEmployees.forEach((emp) => {
        const groupId = emp.division_id || `division:${this.divisionName(emp)}`;

        if (!indexes.has(groupId)) {
          indexes.set(groupId, groups.length);
          groups.push({
            id: groupId,
            name: this.divisionName(emp),
            employees: [],
          });
        }

        groups[indexes.get(groupId)].employees.push(emp);
      });

      return groups;
    },
  },
  methods: {
    handlePrint() {
      window.print();
    },

    async handleDownload({ key }) {
      if (key !== "registry") return;

      try {
        const response = await axios.get(
          `/api/payroll/sla-pay/download/registry/${this.payroll_no}`,
          {
            headers: { Authorization: `Bearer ${this.token}` },
            responseType: "blob",
          }
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const a = document.createElement("a");
        a.href = url;
        a.download = `${this.payroll_no}.xlsx`;
        document.body.appendChild(a);
        a.click();

        a.remove();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Download failed:", error);
      }
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

    divisionName(emp) {
      const name = String(emp.division_name || "No Division").trim();
      const code = String(emp.division_code || "").trim();

      if (!code || name.includes(`(${code})`)) {
        return name;
      }

      return `${name} (${code})`;
    },

    groupTotals(employees, field) {
      return employees.reduce((total, emp) => {
        return total + (Number(emp[field]) || 0);
      }, 0);
    },

    grandTotals(field) {
      return this.groupTotals(this.filteredEmployees, field);
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

.division-cell {
  text-transform: uppercase;
  white-space: nowrap !important;
}

.division-total {
  font-weight: 700;
}
</style>
