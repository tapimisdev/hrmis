<template>
  <div class="payroll-registry-container" :class="status" :data-bs-theme="theme">
    <!-- Toolbar -->
    <div class="excel-toolbar">
      <div class="toolbar-meta">
        <div class="toolbar-title">{{ payroll_no }} Payroll</div>
        <div class="status-badge">
          <i :class="['fa-solid', statusConfig.icon]"></i>
          {{ statusConfig.label }}
        </div>
      </div>

      <div class="toolbar-left d-flex gap-2">
        <div class="dropdown">
          <button class="toolbar-btn left dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="fa-solid fa-download"></i> Export
          </button>

          <ul class="dropdown-menu dropdown-menu-end">
            <li v-for="item in downloads" :key="item.key">
              <a class="dropdown-item" href="javascript:void(0)" @click="downloadPayroll(item.key, payroll_no)">
                {{ item.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Sheet -->
    <div class="excel-sheet">
      <LoaderVue :visible="loading" :hasBackground="true" status="uploading" message="Uploading, please wait..." />

      <!-- Header -->
      <div class="sheet-header">
        <h1 class="sheet-title">
          <div class="toolbar-description mb-1">
            <slot name="sheet-type">( PERMANENT )</slot>
          </div>

          <slot name="agency">
            TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE
          </slot>
        </h1>

        <p class="sheet-subtitle">
          <slot name="title">GENERAL PAYROLL FOR SALARY</slot>
        </p>
      </div>

      <!-- Info -->
      <div class="sheet-info">
        <div class="info-text">
          <slot name="info-text">
            We hereby acknowledge to have received the sums therein specified
            opposite our respective names for our services rendered:
          </slot>
        </div>

        <div class="info-period">
          <slot name="period">
            Period: <strong>1–15 September 2025</strong>
          </slot>
        </div>
      </div>

      <div v-if="$slots.filters" class="sheet-filters">
        <slot name="filters"></slot>
      </div>

      <!-- Table Slot Only -->
      <div class="excel-table-wrapper table-responsive">
        <slot name="table"></slot>
      </div>

      <!-- Optional footer slot (signatories, notes, etc.) -->
      <div class="sheet-footer">
        <slot name="footer"></slot>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import LoaderVue from "../../components/LoaderVue.vue";

const token = localStorage.getItem("auth_token");

export default {
  name: "PayrollRegistryLayout",
  components: { LoaderVue },
  props: {
    status: { type: String, required: true },
    payroll_no: { type: String, required: true },
    loading: { type: Boolean, default: false },

    /**
     * Allows you to customize which download options show in the dropdown.
     * Keys must match the urlArr keys inside downloadPayroll.
     */
    downloads: {
      type: Array,
      default: () => [
        { key: "registry", label: "Payroll Registry" },
        { key: "payslip", label: "Payslip" },
      ],
    },
  },
  data() {
    return {
      token,
      theme: document.documentElement.getAttribute("data-bs-theme") || "light",
    };
  },
  computed: {
    statusConfig() {
      const configs = {
        draft: {
          label: "Draft",
          icon: "fa-file-pen",
          color: "#f39c12",
          bg: "#fef9e7",
          darkColor: "#f5a623",
          darkBg: "#3a2a0f",
        },
        pending: {
          label: "Pending Review",
          icon: "fa-clock",
          color: "#3498db",
          bg: "#ebf5fb",
          darkColor: "#5dade2",
          darkBg: "#1a2f3a",
        },
        approved: {
          label: "Approved",
          icon: "fa-circle-check",
          color: "#27ae60",
          bg: "#eafaf1",
          darkColor: "#2ecc71",
          darkBg: "#1a3a28",
        },
        for_releasing: {
          label: "For Releasing",
          icon: "fa-paper-plane",
          color: "#9b59b6",
          bg: "#f5eef8",
          darkColor: "#af7ac5",
          darkBg: "#2d1f35",
        },
        completed: {
          label: "Completed",
          icon: "fa-circle-check",
          color: "#16a085",
          bg: "#e8f8f5",
          darkColor: "#1abc9c",
          darkBg: "#183a32",
        },
        cancelled: {
          label: "Cancelled",
          icon: "fa-ban",
          color: "#e74c3c",
          bg: "#fadbd8",
          darkColor: "#ec7063",
          darkBg: "#3a1f1c",
        },
        failed: {
          label: "Failed",
          icon: "fa-ban",
          color: "#454444",
          bg: "#949292",
          darkColor: "#7f8c8d",
          darkBg: "#2c2c2c",
        },
      };

      return configs[this.status] || configs.draft;
    },
  },
  methods: {
    async downloadPayroll(type, payroll_no) {
      const urlArr = {
        registry: `/api/payroll/salary-pay/${payroll_no}/download`,
        aut: `/api/payroll/salary-pay/absences-leaves/${payroll_no}/download`,
        payslip: `/api/payroll/salary-pay/payslip/${payroll_no}/download`,
      };

      const endPoint = urlArr[type];

      try {
        const response = await axios.get(endPoint, {
          headers: { Authorization: `Bearer ${this.token}` },
          responseType: "blob",
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const a = document.createElement("a");
        a.href = url;
        a.download = `${payroll_no}.xlsx`;
        document.body.appendChild(a);
        a.click();

        a.remove();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Download failed:", error);
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
  --status-color: #6b7280;
  --status-bg: #f3f4f6;
  --panel-bg: #ffffff;
  --line-color: #e5e7eb;
  --header-bg: #f8fafc;
  --muted-text: #6b7280;

  background: var(--status-bg);
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  min-height: 100vh;
  transition: all 0.3s ease;
  padding: 16px;
  color: var(--bs-body-color);
}

[data-bs-theme="dark"] .payroll-registry-container {
  --status-bg: #12161d;
  --panel-bg: #1b222c;
  --line-color: #2f3b4c;
  --header-bg: #1f2b37;
  --muted-text: #9ca3af;
  background: var(--bs-secondary-bg, #212529);
}

.excel-toolbar {
  background: var(--panel-bg);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 10px 14px;
  border: 1px solid var(--line-color);
  border-radius: 10px;
  margin-bottom: 14px;
}

.toolbar-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.toolbar-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--bs-body-color, #111827);
  white-space: nowrap;
}

.toolbar-btn {
  background: var(--header-bg);
  border: 1px solid var(--line-color);
  color: var(--bs-body-color, #111827);
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease;
}

.toolbar-btn:hover {
  background: rgba(148, 163, 184, 0.14);
  border-color: rgba(148, 163, 184, 0.6);
}

.toolbar-btn i {
  margin-right: 5px;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--status-bg);
  color: var(--status-color);
  border: 1px solid rgba(107, 114, 128, 0.2);
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.excel-sheet {
  background: var(--panel-bg);
  margin: 0;
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
  border: 1px solid var(--line-color);
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

[data-bs-theme="dark"] .excel-sheet {
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.25);
}

.sheet-header {
  padding: 18px 20px 8px;
  text-align: center;
  border-bottom: 1px solid var(--line-color);
  background: var(--header-bg);
}

.sheet-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--bs-body-color, #111827);
  margin: 0 0 4px 0;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.sheet-subtitle {
  font-size: 11px;
  color: var(--muted-text);
  margin-bottom: 0;
}

.sheet-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  background: var(--panel-bg);
  border-bottom: 1px solid var(--line-color);
  font-size: 12px;
}

.sheet-filters {
  padding: 10px 20px;
  border-bottom: 1px solid var(--line-color);
  background: var(--panel-bg);
}

.sheet-filters :deep(.payroll-filter-bar) {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.sheet-filters :deep(.payroll-filter-input),
.sheet-filters :deep(.payroll-filter-select) {
  border: 1px solid var(--line-color);
  background: var(--header-bg);
  color: var(--bs-body-color, #111827);
  border-radius: 8px;
  font-size: 12px;
  min-height: 34px;
  padding: 6px 10px;
}

.sheet-filters :deep(.payroll-filter-input) {
  min-width: 260px;
}

.sheet-filters :deep(.payroll-filter-select) {
  min-width: 170px;
}

.sheet-filters :deep(.payroll-filter-meta) {
  margin-left: auto;
  color: var(--muted-text);
  font-size: 11px;
  font-weight: 600;
}

.excel-table-wrapper {
  padding: 0;
  max-height: calc(100vh - 280px);
  overflow: auto;
}

.excel-table-wrapper :deep(.excel-table) {
  width: 100%;
  min-width: max-content;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12px;
}

.excel-table-wrapper :deep(.excel-table th),
.excel-table-wrapper :deep(.excel-table td) {
  border-right: 1px solid var(--line-color);
  border-bottom: 1px solid var(--line-color);
  padding: 8px 10px;
  vertical-align: middle;
  background: var(--panel-bg);
}

.excel-table-wrapper :deep(.excel-table th:first-child),
.excel-table-wrapper :deep(.excel-table td:first-child) {
  border-left: 1px solid var(--line-color);
}

.excel-table-wrapper :deep(.header-labels th) {
  background: var(--header-bg);
  color: #475569;
  font-size: 11px;
  font-weight: 700;
  text-transform: none;
  letter-spacing: 0.02em;
  position: sticky;
  top: 0;
  z-index: 25;
}

.excel-table-wrapper :deep(.data-row:nth-child(even) td) {
  background: rgba(148, 163, 184, 0.08);
}

.excel-table-wrapper :deep(.header-labels th:nth-child(1)) {
  left: 0;
  z-index: 35;
  min-width: 90px;
}

.excel-table-wrapper :deep(.header-labels th:nth-child(2)) {
  left: 90px;
  z-index: 34;
  min-width: 220px;
}

.excel-table-wrapper :deep(.data-row > td:nth-child(1)) {
  position: sticky;
  left: 0;
  z-index: 20;
  background: var(--panel-bg);
  min-width: 90px;
}

.excel-table-wrapper :deep(.data-row > td:nth-child(2)) {
  position: sticky;
  left: 90px;
  z-index: 19;
  background: var(--panel-bg);
  min-width: 220px;
}

.excel-table-wrapper :deep(.excel-table .earning) {
  background: #edf9f0 !important;
}

.excel-table-wrapper :deep(.excel-table .deduction) {
  background: #fff1f1 !important;
}

.excel-table-wrapper :deep(.excel-table .net-salary) {
  background: #eef2ff !important;
  font-weight: 700;
}

.excel-table-wrapper :deep(.excel-table .grand-total td) {
  background: #f3f4f6 !important;
  color: var(--bs-body-color, #111827) !important;
  font-weight: 700;
}

.excel-table-wrapper :deep(.excel-table .project-total td) {
  background: #f4f6f8 !important;
  font-weight: 700;
}

.excel-table-wrapper :deep(.excel-table .project-header .project-cell) {
  background: #e9edf2 !important;
  color: #334155;
  font-weight: 700;
}

.excel-table-wrapper :deep(.excel-table .employee-name) {
  font-weight: 700;
}

.excel-table-wrapper :deep(.excel-table .employee-position) {
  color: var(--muted-text);
  font-size: 10px;
}

.excel-table-wrapper :deep(.excel-table input.form-control),
.excel-table-wrapper :deep(.excel-table textarea.form-control) {
  border: 1px solid var(--line-color) !important;
  background: #fff;
  border-radius: 8px;
  font-size: 12px;
  box-shadow: none;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .header-labels th) {
  color: #cbd5e1;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.data-row:nth-child(even) td) {
  background: rgba(148, 163, 184, 0.06);
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.data-row > td:nth-child(1)),
[data-bs-theme="dark"] .excel-table-wrapper :deep(.data-row > td:nth-child(2)) {
  background: var(--panel-bg);
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .earning) {
  background: rgb(50, 119, 96) !important;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .deduction) {
  background: rgb(126, 74, 74) !important;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .net-salary) {
  background: rgb(39, 40, 92) !important;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .grand-total td) {
  background: rgba(148, 163, 184, 0.16) !important;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .project-total td) {
  background: rgba(148, 163, 184, 0.14) !important;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.excel-table .project-header .project-cell) {
  background: rgba(148, 163, 184, 0.2) !important;
  color: #cccccc;
}

@media (max-width: 992px) {
  .payroll-registry-container {
    padding: 10px;
  }

  .excel-toolbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .toolbar-meta {
    width: 100%;
    justify-content: space-between;
  }

  .toolbar-left {
    width: 100%;
    justify-content: flex-end;
  }

  .excel-table-wrapper {
    max-height: calc(100vh - 300px);
  }

  .sheet-info {
    flex-direction: column;
    align-items: flex-start;
  }

  .sheet-filters {
    padding: 10px;
  }

  .sheet-filters :deep(.payroll-filter-input),
  .sheet-filters :deep(.payroll-filter-select) {
    min-width: 100%;
    width: 100%;
  }

  .sheet-filters :deep(.payroll-filter-meta) {
    margin-left: 0;
  }
}

.sheet-footer {
  padding: 16px 20px;
}
</style>
