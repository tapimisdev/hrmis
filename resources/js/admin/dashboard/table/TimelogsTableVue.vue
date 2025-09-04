<template>
  <div class="card cardiness">
    <div class="card-body">
      <h2 class="h4 mb-4 fw-bold text-uppercase">Timelog Table</h2>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col">Employee Name</th>
              <th scope="col">Division</th>
              <th scope="col">Unit</th>
              <th scope="col">Timestamp</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(log, index) in paginatedLogs" :key="index">
              <td>{{ log.employee_name }}</td>
              <td>{{ log.division }}</td>
              <td>{{ log.unit }}</td>
              <td>{{ log.timestamp }}</td>
              <td>{{ log.action }}</td>
            </tr>
            <tr v-if="paginatedLogs.length === 0">
              <td colspan="7" class="text-center text-muted py-3">
                No records found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <button
          class="btn btn-outline-primary btn-sm"
          @click="prevPage"
          :disabled="page === 1"
        >
          Previous
        </button>

        <span class="fw-semibold">Page {{ page }} of {{ totalPages }}</span>

        <button
          class="btn btn-outline-primary btn-sm"
          @click="nextPage"
          :disabled="page === totalPages"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "TimelogTable",
  data() {
    return {
      logs: [], // Will be populated by API
      page: 1,
      perPage: 5,
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.logs.length / this.perPage) || 1;
    },
    paginatedLogs() {
      const start = (this.page - 1) * this.perPage;
      return this.logs.slice(start, start + this.perPage);
    },
  },
  methods: {
    async fetchLogs() {
      try {
        const response = await fetch("/api/timelogs");
        if (!response.ok) throw new Error("API error");
        this.logs = await response.json();
      } catch (error) {
        console.error("Failed to fetch logs:", error);
        this.logs = [
          {
            employee_name: "John Doe",
            division: "HR",
            unit: "Recruitment",
            timestamp: "2025-09-04 08:40 AM",
            action: "Time In",
          },
          {
            employee_name: "Jane Smith",
            division: "IT",
            unit: "Development",
            timestamp: "2025-09-04 08:32 AM",
            action: "Time In",
          },
          {
            employee_name: "Mark Lee",
            division: "Finance",
            unit: "Payroll",
            timestamp: "2025-09-04 08:23 AM",
            action: "Time In",
          },
        ];
      }
    },
    nextPage() {
      if (this.page < this.totalPages) this.page++;
    },
    prevPage() {
      if (this.page > 1) this.page--;
    },
  },
  mounted() {
    this.fetchLogs();
  },
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';
</style>
