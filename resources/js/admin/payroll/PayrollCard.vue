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
                {{ payroll.label || "Untitled Payroll" }}
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
const STATUS_THEME = {
  draft: "warning",
  pending: "secondary",
  pending_approval: "secondary",
  approved: "success",
  for_releasing: "info",
  complete: "info",
  completed: "info",
  cancelled: "danger",
  failed: "dark",
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

    themeName() {
      return STATUS_THEME[this.statusKey] || "secondary"
    },

    accent() {
      // uses Bootstrap variables (works with dark/light)
      return `var(--bs-${this.themeName})`
    },

    statusPill() {
      const rgb = (n) => `rgba(var(--bs-${n}-rgb), 0.14)`
      const border = (n) => `rgba(var(--bs-${n}-rgb), 0.26)`

      return {
        color: `var(--bs-${this.themeName})`,
        background: rgb(this.themeName),
        borderColor: border(this.themeName),
      }
    },

    manageHref() {
      return `/admin/payroll/${this.url}/${this.payroll.payroll_no}?batch_id=${this.payroll.batch_id}`
    },

    actions() {
      // Base action always present
      const items = [
        {
          type: "link",
          label: "Manage",
          href: this.manageHref,
          icon: "fa fa-eye me-2 text-primary",
        },
      ]

      // Status-based actions
      const byStatus = {
        draft: [
          {
            type: "button",
            label: "Proceed to Pending",
            icon: "fa fa-arrow-right me-2 text-success",
            onClick: () => this.$emit("change-status", this.payroll.id, "pending"),
          },
          { type: "divider" },
          {
            type: "button",
            label: "Cancel",
            class: "text-danger",
            icon: "fa fa-ban me-2",
            onClick: () => this.$emit("cancel", this.payroll.id),
          },
        ],

        pending: [
          {
            type: "button",
            label: "Back to Draft",
            icon: "fa fa-undo me-2 text-warning",
            onClick: () => this.$emit("change-status", this.payroll.id, "draft"),
          },
          { type: "divider" },
          {
            type: "button",
            label: "Cancel",
            class: "text-danger",
            icon: "fa fa-ban me-2",
            onClick: () => this.$emit("cancel", this.payroll.id),
          },
        ],

        approved: [
          {
            type: "button",
            label: "Cancel",
            class: "text-danger",
            icon: "fa fa-ban me-2",
            onClick: () => this.$emit("cancel", this.payroll.id),
          },
        ],

        // view-only
        for_releasing: [],
        cancelled: [],
        complete: [],
        completed: [],
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
