<template>
  <div
    class="payroll-registry-container"
    :class="status"
    :data-bs-theme="theme"
  >
    <!-- Toolbar -->
    <div class="excel-toolbar">
      <div class="status-badge">
        <i :class="['fa-solid', statusConfig.icon]"></i>
        {{ statusConfig.label }}
      </div>

      <div class="toolbar-left d-flex gap-2">
        <button class="toolbar-btn" @click="$emit('print')">
          <i class="fa-solid fa-print"></i> Print
        </button>

        <div class="dropdown">
          <button
            class="toolbar-btn left dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <i class="fa-solid fa-download"></i> Downloads
          </button>

          <ul class="dropdown-menu dropdown-menu-end">
            <li v-for="item in downloads" :key="item.key">
              <a
                class="dropdown-item"
                href="javascript:void(0)"
                @click="downloadPayroll(item.key, payroll_no)"
              >
                {{ item.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Sheet -->
    <div class="excel-sheet">
      <LoaderVue
        :visible="loading"
        :hasBackground="true"
        status="uploading"
        message="Uploading, please wait..."
      />

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
  --status-color: #ccc;
  --status-bg: #f9f9f9;

  --bs-success-rgb: 25, 135, 84;
  --bs-danger-rgb: 220, 53, 69;
  --bs-primary-rgb: 13, 110, 253;
  --bs-warning-rgb: 255, 193, 7;
  --bs-dark-rgb: 33, 37, 41;

  background: var(--status-bg);
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
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
  position: sticky;
  top: 0;
  z-index: 120;
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

/* Sheet */
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

.excel-table-wrapper {
  padding: 0;
  max-height: calc(100vh - 300px);
  overflow: auto;
}

.excel-table-wrapper :deep(.excel-table) {
  min-width: 100%;
}

.excel-table-wrapper :deep(.header-labels th) {
  position: sticky;
  top: 0;
  z-index: 25;
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
  background: var(--bs-body-bg, #fff);
  min-width: 90px;
}

.excel-table-wrapper :deep(.data-row > td:nth-child(2)) {
  position: sticky;
  left: 90px;
  z-index: 19;
  background: var(--bs-body-bg, #fff);
  min-width: 220px;
}

[data-bs-theme="dark"] .excel-table-wrapper :deep(.data-row > td:nth-child(1)),
[data-bs-theme="dark"] .excel-table-wrapper :deep(.data-row > td:nth-child(2)) {
  background: var(--bs-body-bg, #212529);
}

@media (max-width: 992px) {
  .excel-table-wrapper {
    max-height: calc(100vh - 250px);
  }
}

.sheet-footer {
  padding: 16px 24px;
}
</style>
