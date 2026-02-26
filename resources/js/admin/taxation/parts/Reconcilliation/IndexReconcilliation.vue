<template>
  <TwoColLayout>
    <!-- LEFT SIDE -->
    <template #left>
      <TaxReconcilliationFilters
        :periods="periods"
        :active-period="activePeriod"
        :search="search"
        :selected-division="selectedDivision"
        :selected-unit="selectedUnit"
        :divisions="divisions"
        :units="units"
        :filtered-count="filteredRows.length"
        :total-count="rows.length"
        :has-active-filters="hasActiveFilters"
        @change-period="activePeriod = $event"
        @pull-reconcile="pullFromPayrollAndReconcile"
        @update:search="search = $event"
        @update:selectedDivision="selectedDivision = $event"
        @update:selectedUnit="selectedUnit = $event"
        @clear="clearFilters"
      />

      <TaxReconcilliationTable
        :rows="filteredRows"
        @view="viewRow"
        @edit="editRow"
        @recompute="recomputeRow"
        @delete="deleteRow"
      />
    </template>

    <!-- RIGHT SIDE -->
    <template #right>
      <!-- <TaxTemplate :is_open="showCard">
        <template #header>Card Title</template>
        Card content here...
      </TaxTemplate> -->
    </template>
  </TwoColLayout>
</template>

<script>
import TwoColLayout from "../../components/TwoColLayout .vue";
import TaxReconcilliationTable from "./TaxReconcilliationTable.vue";
import TaxReconcilliationFilters from "./TaxReconcilliationFilters.vue";
// import TaxTemplate from "./../../components/TaxTemplate.vue";

export default {
  name: "IndexReconcilliation",
  components: {
    TwoColLayout,
    TaxReconcilliationTable,
    TaxReconcilliationFilters,
    // TaxTemplate,
  },

  data() {
    return {
      // UI state
      activePeriod: "Q1",
      search: "",
      selectedDivision: "",
      selectedUnit: "",
      showCard: false,

      // tabs
      periods: [
        { key: "Q1", label: "Q1", sub: "Jan–Mar" },
        { key: "Q2", label: "Q2", sub: "Apr–Jun" },
        { key: "Q3", label: "Q3", sub: "Jul–Sep" },
        { key: "NOV", label: "November True-Up" },
        { key: "FINISH", label: "Finish" },
      ],

      // data (placeholder)
      rows: [
        {
          employee_no: "EMP-001",
          division: "Finance Division",
          unit: "Payroll Unit",
          forecasted_annual_taxable: "₱ 520,000.00",
          forecasted_annual_tax: "₱ 45,000.00",
          forecasted_monthly_tax: "₱ 3,750.00",
        },
        {
          employee_no: "EMP-002",
          division: "HR Division",
          unit: "Compensation Unit",
          forecasted_annual_taxable: "₱ 430,000.00",
          forecasted_annual_tax: "₱ 32,000.00",
          forecasted_monthly_tax: "₱ 2,666.67",
        },
        {
          employee_no: "EMP-003",
          division: "Finance Division",
          unit: "Accounting Unit",
          forecasted_annual_taxable: "₱ 610,000.00",
          forecasted_annual_tax: "₱ 55,000.00",
          forecasted_monthly_tax: "₱ 4,583.33",
        },
      ],
    };
  },

  computed: {
    hasActiveFilters() {
      return (
        this.search.trim() !== "" ||
        this.selectedDivision !== "" ||
        this.selectedUnit !== ""
      );
    },

    divisions() {
      const set = new Set(this.rows.map((r) => r.division).filter(Boolean));
      return Array.from(set).sort();
    },

    units() {
      const base = this.selectedDivision
        ? this.rows.filter((r) => r.division === this.selectedDivision)
        : this.rows;

      const set = new Set(base.map((r) => r.unit).filter(Boolean));
      return Array.from(set).sort();
    },

    filteredRows() {
      const s = this.search.trim().toLowerCase();

      return this.rows.filter((r) => {
        const matchSearch =
          !s || String(r.employee_no || "").toLowerCase().includes(s);

        const matchDivision =
          !this.selectedDivision || r.division === this.selectedDivision;

        const matchUnit = !this.selectedUnit || r.unit === this.selectedUnit;

        // later: if you want period-based filtering, add it here with activePeriod
        return matchSearch && matchDivision && matchUnit;
      });
    },
  },

  watch: {
    selectedDivision() {
      this.selectedUnit = "";
    },
  },

  methods: {
    clearFilters() {
      this.search = "";
      this.selectedDivision = "";
      this.selectedUnit = "";
    },

    pullFromPayrollAndReconcile() {
      console.log("PULL FROM PAYROLL & RECONCILE for period:", this.activePeriod);
    },

    viewRow(row) {
      this.showCard = !this.showCard;
      console.log("VIEW", row);
    },

    editRow(row) {
      console.log("EDIT", row);
    },

    recomputeRow(row) {
      console.log("RECOMPUTE", row);
    },

    deleteRow(row) {
      console.log("DELETE", row);
    },
  },
};
</script>
