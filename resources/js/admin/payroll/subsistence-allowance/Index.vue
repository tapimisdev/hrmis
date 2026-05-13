<template>
  <div class="subsistence-page">
    <div class="filter-bar">
      <div class="filter-field">
        <label for="sa-month">Month</label>
        <select id="sa-month" v-model.number="filters.month" class="form-select" :disabled="loading" @change="fetchEmployees">
          <option v-for="month in months" :key="month.value" :value="month.value">
            {{ month.label }}
          </option>
        </select>
      </div>

      <div class="filter-field">
        <label for="sa-year">Year</label>
        <select id="sa-year" v-model.number="filters.year" class="form-select" :disabled="loading" @change="fetchEmployees">
          <option v-for="year in years" :key="year" :value="year">
            {{ year }}
          </option>
        </select>
      </div>

      <button type="button" class="btn btn-primary filter-action" :disabled="loading" @click="fetchEmployees">
        <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        <i v-else class="fa-solid fa-rotate-right me-1"></i>
        {{ loading ? "Loading..." : "Refresh" }}
      </button>
      <button
        type="button"
        class="btn btn-outline-primary filter-action"
        :disabled="loading || bulkSaving || selectedEmployees.length === 0"
        @click="openBulkModal"
      >
        <i class="fa-solid fa-pen-to-square me-1"></i>
        Update
      </button>
    </div>

    <div class="table-wrap" :class="{ 'is-loading': loading }">
      <div v-show="loading" class="table-loading text-center" role="status" aria-live="polite">
        <div class="loading-card">
          <span class="spinner-border text-primary mb-3" aria-hidden="true"></span>
          <span class="visually-hidden">Loading...</span>
          <div>Loading records...</div>
        </div>
      </div>
      <table ref="subsistenceTable" class="table table-hover align-middle mb-0 w-100 subsistence-table">
        <thead>
          <tr>
            <th class="text-center select-column">
              <input
                type="checkbox"
                class="form-check-input"
                :checked="allEmployeesSelected"
                :disabled="loading || employees.length === 0"
                @change="toggleAllEmployees"
              />
            </th>
            <th>Employee</th>
            <th class="text-center">Actual Days</th>
            <th class="text-center amount-cell amount-cell-green">Amount</th>
            <th class="text-center amount-cell amount-cell-red">Deduction Count / Amount</th>
            <th class="text-center amount-cell amount-cell-blue">Total Amount</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in employees" :key="employee.employee_no">
            <td class="text-center select-column">
              <input
                v-model="selectedEmployees"
                type="checkbox"
                class="form-check-input"
                :value="employee.employee_no"
                :disabled="loading || saving || bulkSaving"
              />
            </td>
            <td>
              <div class="employee-name">{{ employee.name }}</div>
              <div class="employee-position">{{ employee.position }}</div>
            </td>
            <td class="text-center">{{ formatCount(employee.actual_days) }}</td>
            <td class="text-center amount-cell amount-cell-green fw-semibold">
              {{ formatMoney(employeeGrossAmount(employee)) }}
            </td>
            <td class="text-center amount-cell amount-cell-red">
              <div>{{ formatCount(employee.deduction_count) }}</div>
              <small>{{ formatMoney(employee.deduction_amount) }}</small>
            </td>
            <td class="text-center amount-cell amount-cell-blue fw-semibold">
              {{ formatMoney(employee.computed_amount) }}
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-outline-primary" @click="openModal(employee)">
                <i class="fa-solid fa-pen-to-square me-1"></i>
                {{ employee.record_id ? "Update" : "Create" }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="sa-modal-backdrop">
      <div class="sa-modal" role="dialog" aria-modal="true">
        <div class="sa-modal-header">
          <div>
            <h5 class="mb-1">{{ form.employee_name }}</h5>
            <div class="text-muted small">{{ form.position }}</div>
          </div>
          <button type="button" class="btn btn-light btn-sm" @click="closeModal">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="sa-modal-body">
          <div class="rule-note">
            <strong>Rates:</strong> ₱150 per full 8-hour day, ₱75 per 4-hour half day, and no allowance below 4 hours.
          </div>

          <div v-if="!bulkMode" class="actual-days-card">
            <span>Actual Days</span>
            <strong>{{ formatCount(preview.actual_days) }}</strong>
            <small>Generated from employee timelogs for the selected month.</small>
          </div>

          <div class="deduction-panel">
            <div class="form-field">
              <div class="field-label-row">
                <label for="deduction-amount">Deduction</label>
                <div class="deduction-help">
                  <button
                    type="button"
                    class="help-trigger"
                    title="Enter the peso amount to deduct from the generated Subsistence Allowance. Use this for disallowed days, meal-covered activities, leave, travel, or audit adjustments."
                    @click.stop="showDeductionHelp = !showDeductionHelp"
                  >
                    <i class="fa-solid fa-question"></i>
                  </button>
                  <div v-if="showDeductionHelp" class="help-popover" @click.stop>
                    Enter the peso amount to deduct from the generated Subsistence Allowance. Use this for disallowed days, meal-covered activities, leave, travel, or audit adjustments.
                  </div>
                </div>
              </div>
              <input
                id="deduction-amount"
                v-model.number="form.deduction_count"
                type="number"
                min="0"
                step="0.5"
                class="form-control"
                placeholder="0"
                @blur="normalizeDeductionCount"
              />
            </div>
            <div class="deduction-legend">
              <div><strong>1</strong> = one day (8 hours)</div>
              <div><strong>0.5</strong> = half day (4 hours)</div>
              <div><strong>1 day</strong> = ₱150 per day</div>
              <div><strong>Half day or above</strong> = ₱75</div>
            </div>
          </div>

          <div v-if="!bulkMode" class="computed-strip">
            <div>
              <span>Amount</span>
              <strong>{{ formatMoney(preview.gross_amount) }}</strong>
            </div>
            <div>
              <span>Deduction Count / Amount</span>
              <strong>{{ formatCount(preview.deduction_count) }} / {{ formatMoney(preview.deduction_amount) }}</strong>
            </div>
            <div>
              <span>Total Amount</span>
              <strong>{{ formatMoney(preview.computed_amount) }}</strong>
            </div>
          </div>

          <div v-if="bulkMode" class="selected-employees-card">
            <span>Selected Employees</span>
            <div class="selected-employees-list">
              <div v-for="employee in selectedEmployeeDetails" :key="employee.employee_no">
                <div class="selected-employee-header">
                  <div>
                    <strong>{{ employee.name }}</strong>
                    <small>{{ employee.position }}</small>
                  </div>
                  <div class="selected-actual-days">
                    <span>Actual Days</span>
                    <strong>{{ formatCount(employee.actual_days) }}</strong>
                  </div>
                </div>
                <div class="selected-computed-strip">
                  <div class="summary-card-green">
                    <span>Amount</span>
                    <strong>{{ formatMoney(employeeGrossAmount(employee)) }}</strong>
                  </div>
                  <div class="summary-card-red">
                    <span>Deduction Count / Amount</span>
                    <strong>{{ formatCount(preview.deduction_count) }} / {{ formatMoney(preview.deduction_amount) }}</strong>
                  </div>
                  <div class="summary-card-blue">
                    <span>Total Amount</span>
                    <strong>{{ formatMoney(employeeBulkComputedAmount(employee)) }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-field">
            <label>Remarks</label>
            <textarea
              v-model.trim="form.remarks"
              class="form-control"
              rows="4"
              placeholder="Tracking reference for future checking and auditing"
            ></textarea>
          </div>
        </div>

        <div class="sa-modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-primary" :disabled="saving" @click="saveRecord">
            <i class="fa-solid fa-floppy-disk me-1"></i>
            {{ saving ? "Saving..." : "Save Record" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

const token = localStorage.getItem("auth_token");

const emptyForm = () => ({
  employee_no: "",
  employee_name: "",
  position: "",
  month: null,
  year: null,
  full_day_count: 0,
  half_day_count: 0,
  below_four_hours_count: 0,
  actual_days: 0,
  deduction_count: 0,
  deduction_amount: 0,
  required_facility_service: true,
  available_at_all_times: true,
  may_leave_breaks: false,
  on_leave: false,
  on_official_travel: false,
  provided_meals: false,
  remarks: "",
});

export default {
  name: "SubsistenceAllowanceIndex",
  data() {
    const today = new Date();
    const query = new URLSearchParams(window.location.search);
    const queryMonth = Number(query.get("month") || query.get("moth"));
    const queryYear = Number(query.get("year"));
    return {
      token,
      loading: false,
      saving: false,
      bulkSaving: false,
      bulkMode: false,
      showModal: false,
      showDeductionHelp: false,
      dataTable: null,
      employees: [],
      selectedEmployees: [],
      filters: {
        month: queryMonth >= 1 && queryMonth <= 12 ? queryMonth : today.getMonth() + 1,
        year: queryYear >= 2000 && queryYear <= 2100 ? queryYear : today.getFullYear(),
      },
      form: emptyForm(),
      months: [
        { value: 1, label: "January" },
        { value: 2, label: "February" },
        { value: 3, label: "March" },
        { value: 4, label: "April" },
        { value: 5, label: "May" },
        { value: 6, label: "June" },
        { value: 7, label: "July" },
        { value: 8, label: "August" },
        { value: 9, label: "September" },
        { value: 10, label: "October" },
        { value: 11, label: "November" },
        { value: 12, label: "December" },
      ],
    };
  },
  computed: {
    years() {
      const current = new Date().getFullYear();
      const years = [];
      for (let year = current - 3; year <= current + 1; year += 1) {
        years.push(year);
      }
      return years;
    },
    preview() {
      const fullDays = Number(this.form.full_day_count) || 0;
      const halfDays = Number(this.form.half_day_count) || 0;
      const deductionCount = this.normalizedHalfStep(this.form.deduction_count);
      const deductionAmount = deductionCount * 150;
      const isEligible =
        this.form.required_facility_service &&
        this.form.available_at_all_times &&
        !this.form.may_leave_breaks &&
        !this.form.on_leave &&
        !this.form.on_official_travel &&
        !this.form.provided_meals;

      const actualDays = fullDays + halfDays * 0.5;
      const grossAmount = isEligible ? fullDays * 150 + halfDays * 75 : 0;

      return {
        actual_days: actualDays,
        deduction_count: deductionCount,
        gross_amount: grossAmount,
        deduction_amount: deductionAmount,
        computed_amount: Math.max(grossAmount - deductionAmount, 0),
        is_eligible: isEligible,
      };
    },
    allEmployeesSelected() {
      return this.employees.length > 0 && this.selectedEmployees.length === this.employees.length;
    },
    selectedEmployeeDetails() {
      return this.employees.filter((employee) =>
        this.selectedEmployees.includes(employee.employee_no)
      );
    },
  },
  mounted() {
    document.addEventListener("click", this.closeDeductionHelpOnOutsideClick);
    this.fetchEmployees();
  },
  beforeUnmount() {
    document.removeEventListener("click", this.closeDeductionHelpOnOutsideClick);
    this.destroyDataTable();
  },
  methods: {
    headers() {
      return {
        Accept: "application/json",
        Authorization: `Bearer ${this.token}`,
      };
    },
    boolValue(value) {
      return value === true || value === 1 || value === "1";
    },
    normalizedHalfStep(value) {
      return Math.max(Math.round((Number(value) || 0) * 2) / 2, 0);
    },
    normalizeDeductionCount() {
      this.form.deduction_count = this.normalizedHalfStep(this.form.deduction_count);
      this.form.deduction_amount = this.form.deduction_count * 150;
    },
    closeDeductionHelpOnOutsideClick(event) {
      if (!this.showDeductionHelp || event.target.closest(".deduction-help")) {
        return;
      }

      this.showDeductionHelp = false;
    },
    syncRouteQuery() {
      const url = new URL(window.location.href);
      url.searchParams.set("month", this.filters.month);
      url.searchParams.set("year", this.filters.year);
      url.searchParams.delete("moth");
      window.history.replaceState({}, "", url.toString());
    },
    async fetchEmployees() {
      this.loading = true;
      try {
        this.destroyDataTable();
        this.syncRouteQuery();

        const response = await axios.get("/api/payroll/subsistence-allowance", {
          params: this.filters,
          headers: this.headers(),
        });

        this.employees = response.data.data || [];
        this.selectedEmployees = [];
        await this.$nextTick();
        this.initDataTable();
      } catch (error) {
        window.ErrorToast?.fire({
          icon: "error",
          title: "Unable to load Subsistence Allowance records.",
        });
      } finally {
        this.loading = false;
      }
    },
    initDataTable() {
      const table = this.$refs.subsistenceTable;
      if (!table || !window.$?.fn?.DataTable) {
        return;
      }

      this.dataTable = $(table).DataTable({
        pageLength: 15,
        lengthMenu: [
          [15, 25, 50, -1],
          [15, 25, 50, "All"],
        ],
        ordering: true,
        autoWidth: false,
        language: {
          emptyTable: "No employees found.",
          lengthMenu: "Show _MENU_ employees per page",
        },
        columnDefs: [
          { orderable: false, targets: [0, 6] },
        ],
      });
    },
    destroyDataTable() {
      const table = this.$refs.subsistenceTable;
      if (!table || !window.$?.fn?.DataTable || !$.fn.DataTable.isDataTable(table)) {
        this.dataTable = null;
        return;
      }

      $(table).DataTable().destroy();
      this.dataTable = null;
    },
    openModal(employee) {
      this.bulkMode = false;
      this.form = {
        employee_no: employee.employee_no,
        employee_name: employee.name,
        position: employee.position,
        month: this.filters.month,
        year: this.filters.year,
        full_day_count: employee.full_day_count || 0,
        half_day_count: employee.half_day_count || 0,
        below_four_hours_count: employee.below_four_hours_count || 0,
        actual_days: employee.actual_days || 0,
        deduction_count: employee.deduction_count || 0,
        deduction_amount: employee.deduction_amount || 0,
        required_facility_service: this.boolValue(employee.required_facility_service),
        available_at_all_times: this.boolValue(employee.available_at_all_times),
        may_leave_breaks: this.boolValue(employee.may_leave_breaks),
        on_leave: this.boolValue(employee.on_leave),
        on_official_travel: this.boolValue(employee.on_official_travel),
        provided_meals: this.boolValue(employee.provided_meals),
        remarks: employee.remarks || "",
      };
      this.showDeductionHelp = false;
      this.showModal = true;
    },
    openBulkModal() {
      if (this.selectedEmployees.length === 0) {
        window.ErrorToast?.fire({
          icon: "warning",
          title: "Select at least one employee to update.",
        });
        return;
      }

      const selected = this.employees.filter((employee) =>
        this.selectedEmployees.includes(employee.employee_no)
      );
      const firstEmployee = selected[0] || {};

      this.bulkMode = true;
      this.form = {
        ...emptyForm(),
        employee_name: `${selected.length} selected employees`,
        position: "Bulk Subsistence Allowance update",
        month: this.filters.month,
        year: this.filters.year,
        full_day_count: firstEmployee.full_day_count || 0,
        half_day_count: firstEmployee.half_day_count || 0,
        below_four_hours_count: firstEmployee.below_four_hours_count || 0,
        actual_days: firstEmployee.actual_days || 0,
        deduction_count: 0,
        deduction_amount: 0,
      };
      this.showDeductionHelp = false;
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.showDeductionHelp = false;
      this.bulkMode = false;
      this.form = emptyForm();
    },
    toggleAllEmployees(event) {
      this.selectedEmployees = event.target.checked
        ? this.employees.map((employee) => employee.employee_no)
        : [];
    },
    payloadFromEmployee(employee, overrides = {}) {
      const deductionCount = this.normalizedHalfStep(
        overrides.deduction_count ?? employee.deduction_count
      );

      return {
        employee_no: employee.employee_no,
        month: this.filters.month,
        year: this.filters.year,
        full_day_count: Number(employee.full_day_count) || 0,
        half_day_count: Number(employee.half_day_count) || 0,
        below_four_hours_count: Number(employee.below_four_hours_count) || 0,
        deduction_amount: deductionCount * 150,
        required_facility_service: this.boolValue(overrides.required_facility_service ?? employee.required_facility_service),
        available_at_all_times: this.boolValue(overrides.available_at_all_times ?? employee.available_at_all_times),
        may_leave_breaks: this.boolValue(overrides.may_leave_breaks ?? employee.may_leave_breaks),
        on_leave: this.boolValue(overrides.on_leave ?? employee.on_leave),
        on_official_travel: this.boolValue(overrides.on_official_travel ?? employee.on_official_travel),
        provided_meals: this.boolValue(overrides.provided_meals ?? employee.provided_meals),
        remarks: overrides.remarks ?? employee.remarks ?? "",
      };
    },
    applyRecordToEmployee(employeeNo, record) {
      this.employees = this.employees.map((employee) => {
        if (employee.employee_no !== employeeNo) {
          return employee;
        }

        return {
          ...employee,
          record_id: record.id,
          full_day_count: Number(record.full_day_count) || 0,
          half_day_count: Number(record.half_day_count) || 0,
          below_four_hours_count: Number(record.below_four_hours_count) || 0,
          actual_days: Number(record.actual_days) || 0,
          deduction_count: Number(record.deduction_count) || 0,
          deduction_amount: Number(record.deduction_amount) || 0,
          computed_amount: Number(record.computed_amount) || 0,
          required_facility_service: this.boolValue(record.required_facility_service),
          available_at_all_times: this.boolValue(record.available_at_all_times),
          may_leave_breaks: this.boolValue(record.may_leave_breaks),
          on_leave: this.boolValue(record.on_leave),
          on_official_travel: this.boolValue(record.on_official_travel),
          provided_meals: this.boolValue(record.provided_meals),
          is_eligible: this.boolValue(record.is_eligible),
          remarks: record.remarks,
        };
      });
    },
    employeeGrossAmount(employee) {
      return (Number(employee.full_day_count) || 0) * 150 + (Number(employee.half_day_count) || 0) * 75;
    },
    employeeBulkComputedAmount(employee) {
      return Math.max(this.employeeGrossAmount(employee) - this.preview.deduction_amount, 0);
    },
    async updateSelectedRecords() {
      if (this.selectedEmployees.length === 0) {
        window.ErrorToast?.fire({
          icon: "warning",
          title: "Select at least one employee to update.",
        });
        return;
      }

      this.bulkSaving = true;
      this.saving = true;
      try {
        this.normalizeDeductionCount();
        const selected = this.employees.filter((employee) =>
          this.selectedEmployees.includes(employee.employee_no)
        );

        this.destroyDataTable();

        for (const employee of selected) {
          const response = await axios.post(
            "/api/payroll/subsistence-allowance",
            this.payloadFromEmployee(employee, this.form),
            { headers: this.headers() }
          );

          this.applyRecordToEmployee(employee.employee_no, response.data.data);
        }

        this.selectedEmployees = [];
        await this.$nextTick();
        this.initDataTable();

        window.SuccesToast?.fire({
          icon: "success",
          title: "Selected Subsistence Allowance records updated.",
        });
        this.closeModal();
      } catch (error) {
        await this.$nextTick();
        this.initDataTable();
        window.ErrorToast?.fire({
          icon: "error",
          title: error.response?.data?.message || "Unable to update selected records.",
        });
      } finally {
        this.bulkSaving = false;
        this.saving = false;
      }
    },
    async saveRecord() {
      if (this.bulkMode) {
        await this.updateSelectedRecords();
        return;
      }

      this.saving = true;
      try {
        this.normalizeDeductionCount();
        const response = await axios.post(
          "/api/payroll/subsistence-allowance",
          this.form,
          { headers: this.headers() }
        );

        const record = response.data.data;
        this.destroyDataTable();
        this.applyRecordToEmployee(this.form.employee_no, record);
        this.$nextTick(() => this.initDataTable());

        window.SuccesToast?.fire({
          icon: "success",
          title: response.data.message || "Record saved.",
        });
        this.closeModal();
      } catch (error) {
        window.ErrorToast?.fire({
          icon: "error",
          title: error.response?.data?.message || "Unable to save record.",
        });
      } finally {
        this.saving = false;
      }
    },
    formatCount(value) {
      return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
      });
    },
    formatMoney(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number(value) || 0);
    },
  },
};
</script>

<style scoped>
.subsistence-page {
  display: grid;
  gap: 16px;
}

.filter-bar {
  display: flex;
  align-items: end;
  gap: 12px;
  padding: 16px;
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
}

.filter-field {
  min-width: 180px;
}

.filter-field label,
.form-field label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--bs-secondary-color);
}

.filter-action {
  min-width: 112px;
}

.table-wrap {
  position: relative;
  overflow-x: auto;
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  padding: 12px;
}

.table-wrap.is-loading {
  min-height: 260px;
}

.table-wrap.is-loading table,
.table-wrap.is-loading .dataTables_wrapper {
  pointer-events: none;
}

.select-column {
  width: 42px;
}

.form-check-input {
  cursor: pointer;
}

.form-check-input:disabled {
  cursor: not-allowed;
}

.table-loading {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  min-height: 260px;
  background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.88);
  color: var(--bs-body-color);
  font-weight: 700;
}

.loading-card {
  min-width: 190px;
  padding: 18px 22px;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-body-bg);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}

.employee-name {
  font-weight: 700;
}

.employee-position,
.employee-remarks {
  font-size: 12px;
  color: var(--bs-secondary-color);
}

.employee-remarks {
  margin-top: 4px;
}

.amount-cell {
  font-weight: 700;
}

.amount-cell small {
  color: inherit;
  opacity: 0.8;
}

.subsistence-table thead th.amount-cell-green,
.amount-cell-green,
.summary-card-green {
  border-color: rgba(25, 135, 84, 0.3) !important;
  background: rgba(25, 135, 84, 0.12) !important;
  color: #0f5132;
}

.subsistence-table thead th.amount-cell-red,
.amount-cell-red,
.summary-card-red {
  border-color: rgba(220, 53, 69, 0.3) !important;
  background: rgba(220, 53, 69, 0.12) !important;
  color: #842029;
}

.subsistence-table thead th.amount-cell-blue,
.amount-cell-blue,
.summary-card-blue {
  border-color: rgba(var(--bs-primary-rgb), 0.3) !important;
  background: rgba(var(--bs-primary-rgb), 0.12) !important;
  color: var(--bs-primary);
}

.sa-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1055;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.55);
}

.sa-modal {
  width: min(920px, 100%);
  max-height: calc(100vh - 40px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--bs-body-bg);
  border-radius: 8px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
}

.sa-modal-header,
.sa-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--bs-border-color);
}

.sa-modal-footer {
  justify-content: flex-end;
  border-top: 1px solid var(--bs-border-color);
  border-bottom: 0;
  flex-shrink: 0;
}

.sa-modal-body {
  min-height: 0;
  overflow-y: auto;
  overflow-x: visible;
  display: grid;
  gap: 16px;
  padding: 20px;
}

.rule-note,
.computed-strip,
.actual-days-card,
.selected-employees-card,
.deduction-panel {
  padding: 12px;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-tertiary-bg);
}

.selected-employees-card {
  display: grid;
  gap: 10px;
  background: var(--bs-body-bg);
}

.selected-employees-card > span {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--bs-secondary-color);
}

.selected-employees-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 8px;
}

.selected-employees-list > div {
  display: grid;
  gap: 10px;
  padding: 8px 10px;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-tertiary-bg);
}

.selected-employee-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.selected-employee-header > div:first-child {
  min-width: 0;
  display: grid;
  gap: 2px;
}

.selected-employees-list small {
  color: var(--bs-secondary-color);
}

.selected-actual-days {
  min-width: 88px;
  padding: 6px 8px;
  border: 1px solid rgba(var(--bs-primary-rgb), 0.25);
  border-radius: 8px;
  background: rgba(var(--bs-primary-rgb), 0.08);
  text-align: center;
}

.selected-actual-days span {
  display: block;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--bs-secondary-color);
}

.selected-actual-days strong {
  display: block;
  font-size: 20px;
  line-height: 1.1;
  color: var(--bs-primary);
}

.selected-computed-strip {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.selected-computed-strip > div:last-child {
  grid-column: 1 / -1;
}

.selected-computed-strip > div {
  padding: 8px;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-body-bg);
}

.selected-computed-strip span {
  display: block;
  font-size: 11px;
  color: var(--bs-secondary-color);
}

.selected-computed-strip strong {
  display: block;
  margin-top: 2px;
}

.actual-days-card {
  display: grid;
  gap: 4px;
  border-color: rgba(var(--bs-primary-rgb), 0.35);
  background: rgba(var(--bs-primary-rgb), 0.08);
}

.actual-days-card span,
.actual-days-card small {
  color: var(--bs-secondary-color);
}

.actual-days-card span {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.actual-days-card strong {
  font-size: 34px;
  line-height: 1;
  color: var(--bs-primary);
}

.deduction-panel {
  background: var(--bs-body-bg);
  overflow: visible;
}

.deduction-legend {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px 14px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--bs-border-color);
  color: var(--bs-secondary-color);
  font-size: 12px;
}

.deduction-legend strong {
  color: var(--bs-body-color);
}

.field-label-row {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  overflow: visible;
}

.field-label-row label {
  margin-bottom: 0;
}

.deduction-help {
  position: relative;
  display: inline-flex;
  align-items: center;
  overflow: visible;
}

.help-trigger {
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 1px solid var(--bs-border-color);
  border-radius: 50%;
  background: var(--bs-tertiary-bg);
  color: var(--bs-secondary-color);
  font-size: 11px;
  line-height: 1;
}

.help-trigger i {
  display: block;
  line-height: 1;
}

.help-trigger:hover {
  color: var(--bs-primary);
  border-color: rgba(var(--bs-primary-rgb), 0.45);
}

.help-popover {
  position: absolute;
  left: 50%;
  bottom: calc(100% + 10px);
  z-index: 1065;
  width: min(360px, calc(100vw - 80px));
  transform: translateX(-50%);
  padding: 10px 12px;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-body-bg);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.16);
  color: var(--bs-body-color);
  font-size: 13px;
  font-weight: 400;
  text-transform: none;
}

.computed-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.computed-strip span {
  display: block;
  font-size: 12px;
  color: var(--bs-secondary-color);
}

.computed-strip strong {
  display: block;
  margin-top: 2px;
}

@media (max-width: 768px) {
  .filter-bar,
  .computed-strip {
    grid-template-columns: 1fr;
    display: grid;
  }

  .help-popover {
    left: 0;
    bottom: calc(100% + 10px);
    transform: none;
  }

  .deduction-legend {
    grid-template-columns: 1fr;
  }

  .selected-computed-strip {
    grid-template-columns: 1fr;
  }

  .filter-field {
    min-width: 0;
  }
}
</style>
