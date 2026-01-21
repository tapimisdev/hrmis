<template>
  <div class="p-3 border rounded-3">
    <div class="d-flex gap-3">
      <!-- Accent -->
      <div class="payroll-accent" :style="{ background: accent }"></div>

      <div class="flex-grow-1 min-w-0">
        <!-- Top row -->
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="min-w-0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h6 class="mb-0 fw-semibold text-truncate">
                {{ toCamelCase(payroll.label || "Untitled Payroll") }}
              </h6>

              <span class="status-pill" :style="statusPill">
                {{ prettyStatus(payroll.status) }}
              </span>
            </div>

            <div class="text-muted small mt-1">
              <span class="me-2">
                {{ payroll.employment_code }} — {{ payroll.employment_name }}
              </span>
              <span class="mx-1">•</span>
              <span>
                Ref:
                <span class="fw-semibold">{{ payroll.payroll_no }}</span>
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="dropdown">
            <button
              class="btn btn-sm"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              title="Actions"
            >
              <i class="fa fa-ellipsis-v"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li v-for="(action, idx) in actions" :key="idx">
                <template v-if="action.type === 'divider'">
                  <hr class="dropdown-divider" />
                </template>

                <template v-else-if="action.type === 'link'">
                  <a class="dropdown-item" :href="action.href">
                    <i :class="action.icon"></i>
                    {{ action.label }}
                  </a>
                </template>

                <template v-else>
                  <button
                    class="dropdown-item"
                    :class="action.class"
                    type="button"
                    @click="action.onClick()"
                  >
                    <i :class="action.icon"></i>
                    {{ action.label }}
                  </button>
                </template>
              </li>
            </ul>
          </div>
        </div>

        <!-- Meta row -->
        <div class="d-flex flex-wrap gap-2 mt-3">
          <span class="meta-chip">
            <i class="fa-regular fa-calendar me-2"></i>
            {{ formatMonthYear(payroll.month) }}
          </span>

          <span class="meta-chip">
            <i class="fa fa-users me-2"></i>
            {{ payroll.no_employee }} Employees
          </span>

          <span class="meta-chip text-muted">
            <i class="fa fa-tag me-2"></i>
            Batch: {{ shortId(payroll.batch_id) }}
          </span>

          <span class="meta-chip text-muted">
            <i class="fa-regular fa-clock me-2"></i>
            {{ formatDateTime(payroll.created_at) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const STATUS_META = {
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
}

const MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]

export default {
  name: "PayrollCard",

  props: {
    payroll: { type: Object, required: true },
    url: { type: String, required: true },
  },

  computed: {
    statusKey() {
      return this.payroll?.status || ""
    },

    // detect dark mode from bootstrap attribute (works with Bootstrap 5.3)
    isDark() {
      return document?.documentElement?.getAttribute("data-bs-theme") === "dark"
    },

    statusMeta() {
      return (
        STATUS_META[this.statusKey] || {
          label: "Unknown",
          icon: "fa-circle-question",
          color: "#6c757d",
          bg: "#f8f9fa",
          darkColor: "#adb5bd",
          darkBg: "#2c2c2c",
        }
      )
    },

    accent() {
      // used for highlights / icons / borders
      return this.isDark ? this.statusMeta.darkColor : this.statusMeta.color
    },

    statusPill() {
      return {
        color: this.isDark ? this.statusMeta.darkColor : this.statusMeta.color,
        background: this.isDark ? this.statusMeta.darkBg : this.statusMeta.bg,
        borderColor: this.isDark ? this.statusMeta.darkColor : this.statusMeta.color,
      }
    },

    manageHref() {
      return `/admin/payroll/${this.url}/${this.payroll.payroll_no}?batch_id=${this.payroll.batch_id}`
    },

    actions() {
      const items = [
        {
          type: "link",
          label: "Manage",
          href: this.manageHref,
          icon: "fa fa-eye me-2 text-primary",
        },
      ]

      const byStatus = {
        draft: [
          {
            type: "button",
            label: "Submit for Approval",
            icon: "fa fa-arrow-right me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "pending"),
          },
          { type: "divider" },
          {
            type: "button",
            label: "Delete Permanent",
            class: "text-danger",
            icon: "fa fa-trash-can me-2",
            onClick: () => this.$emit("cancel", this.payroll.id),
          },
        ],

        pending: [
          {
            type: "button",
            label: "Back to Draft",
            icon: "fa fa-undo me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "draft"),
          },
          {
            type: "button",
            label: "Approve",
            icon: "fa fa-circle-check me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "approved"),
          },
          { type: "divider" },
          {
            type: "button",
            label: "Cancel",
            icon: "fa fa-circle-xmark me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "cancelled"),
          },
        ],

        approved: [
          {
            type: "button",
            label: "Back to Draft",
            icon: "fa fa-undo me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "draft"),
          },
          {
            type: "button",
            label: "For Releasing",
            icon: "fa fa-paper-plane me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "for_releasing"),
          },
          { type: "divider" },
          {
            type: "button",
            label: "Cancel",
            icon: "fa fa-circle-xmark me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "cancelled"),
          },
        ],

        for_releasing: [
          {
            type: "button",
            label: "Mark as Complete",
            icon: "fa fa-circle-check me-2",
            onClick: () => this.$emit("change-status", this.payroll.id, "completed"),
          },
        ],

        cancelled: [],
        completed: [],
        failed: [],
      }

      return items.concat(byStatus[this.statusKey] ?? [])
    },
  },

  methods: {
    prettyStatus(v) {
      return String(v || "")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (l) => l.toUpperCase())
    },

    toCamelCase(text) {
      return text
        .toLowerCase()
        .replace(/(?:^\w|[A-Z]|\b\w)/g, (word, index) =>
          index === 0 ? word.toLowerCase() : word.toUpperCase()
        )
        .replace(/\s+/g, '')
    },

    formatMonthYear(ym) {
      if (!ym) return "-"
      const [y, m] = String(ym).split("-")
      const mi = parseInt(m, 10) - 1
      return `${MONTHS[mi] || m} ${y}`
    },

    shortId(uuid) {
      if (!uuid) return "-"
      return String(uuid).split("-")[0]
    },

    formatDateTime(dt) {
      if (!dt) return "-"
      const date = new Date(String(dt).replace(" ", "T"))

      if (isNaN(date.getTime())) return "-"

      return date.toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      })
    },
  },
}
</script>

<style scoped lang="scss">
.payroll-accent {
  width: 6px;
  border-radius: 999px;
  flex: 0 0 auto;
  min-height: 64px;
  margin-top: 2px;
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  padding: 4px 6px;
  border-radius: 999px;
  border: 1px solid var(--bs-border-color);
  background: rgba(var(--bs-body-color-rgb), 0.04);
  font-size: 0.82rem;
  line-height: 1;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  line-height: 1;
  border: 1px solid var(--bs-border-color);
  text-transform: capitalize;
}
</style>
