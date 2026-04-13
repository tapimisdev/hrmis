<template>
  <article class="payroll-card" :class="{ 'payroll-card--loading': loading }">
    <div class="payroll-card__accent" :style="{ background: accent }"></div>

    <div class="payroll-card__content">
      <div class="payroll-card__header">
        <div class="min-w-0">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h6 class="payroll-card__title mb-0 text-truncate">
              {{ payroll.label || "Untitled Payroll" }}
            </h6>

            <span class="status-pill" :style="statusPill">
              {{ prettyStatus(payroll.status) }}
            </span>

            <span
              v-if="showAutIndicator"
              class="status-pill aut-pill"
              :style="autIndicatorPill"
            >
              {{ autIndicatorLabel }}
            </span>
          </div>

          <div class="payroll-card__subtitle">
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

        <div class="dropdown">
          <button
            class="payroll-card__menu"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="Actions"
            :disabled="loading"
          >
            <i v-if="loading" class="fa fa-spinner fa-spin"></i>
            <i v-else class="fa fa-ellipsis-v"></i>
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
                  :disabled="action.disabled"
                  @click="action.onClick()"
                >
                  <template v-if="loading && loadingAction === action.loadingKey">
                    <i class="fa fa-spinner fa-spin me-2"></i>
                    Processing...
                  </template>
                  <template v-else>
                    <i :class="action.icon"></i>
                    {{ action.label }}
                  </template>
                </button>
              </template>
            </li>
          </ul>
        </div>
      </div>

      <div class="payroll-card__meta">
        <span class="meta-chip">
          <i class="fa-regular fa-calendar me-2"></i>
          {{ payrollPeriod }}
        </span>

        <span class="meta-chip">
          <i class="fa fa-users me-2"></i>
          {{ payroll.no_employee }} Employees
        </span>

        <span class="meta-chip">
          <i class="fa fa-tag me-2"></i>
          Batch: {{ shortId(payroll.batch_id) }}
        </span>

        <span class="meta-chip">
          <i class="fa-regular fa-clock me-2"></i>
          {{ formatDateTime(payroll.created_at) }}
        </span>
      </div>
    </div>
  </article>
</template>

<script>
const STATUS_META = {
  draft: {
    color: "#f5c15d",
    background: "rgba(245, 193, 93, 0.12)",
    borderColor: "rgba(245, 193, 93, 0.4)",
  },
  pending: {
    color: "#8cc9ff",
    background: "rgba(140, 201, 255, 0.12)",
    borderColor: "rgba(140, 201, 255, 0.38)",
  },
  approved: {
    color: "#38d27d",
    background: "rgba(56, 210, 125, 0.12)",
    borderColor: "rgba(56, 210, 125, 0.42)",
  },
  for_releasing: {
    color: "#d8b4fe",
    background: "rgba(216, 180, 254, 0.12)",
    borderColor: "rgba(216, 180, 254, 0.38)",
  },
  completed: {
    color: "#5eead4",
    background: "rgba(94, 234, 212, 0.12)",
    borderColor: "rgba(94, 234, 212, 0.38)",
  },
  cancelled: {
    color: "#fda4af",
    background: "rgba(253, 164, 175, 0.12)",
    borderColor: "rgba(253, 164, 175, 0.38)",
  },
  failed: {
    color: "#cbd5e1",
    background: "rgba(203, 213, 225, 0.12)",
    borderColor: "rgba(203, 213, 225, 0.3)",
  },
}

const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]

export default {
  name: "PayrollCard",

  props: {
    payroll: { type: Object, required: true },
    url: { type: String, required: true },
    loadingAction: { type: String, default: "" },
    loading: { type: Boolean, default: false },
  },

  computed: {
    statusKey() {
      return this.payroll?.status || ""
    },

    statusMeta() {
      return (
        STATUS_META[this.statusKey] || {
          color: "#cbd5e1",
          background: "rgba(203, 213, 225, 0.12)",
          borderColor: "rgba(203, 213, 225, 0.3)",
        }
      )
    },

    accent() {
      return this.statusMeta.color
    },

    statusPill() {
      return {
        color: this.statusMeta.color,
        background: this.statusMeta.background,
        borderColor: this.statusMeta.borderColor,
      }
    },

    showAutIndicator() {
      return this.url === "salary-pay" && Number(this.payroll?.employment_type_id) === 1
    },

    autIndicatorApplied() {
      return Boolean(this.payroll?.is_aut_deducted)
    },

    autIndicatorLabel() {
      return this.autIndicatorApplied ? "AUT Deducted" : "AUT Pending"
    },

    autIndicatorPill() {
      return this.autIndicatorApplied
        ? {
            color: "#166534",
            background: "#dcfce7",
            borderColor: "#bbf7d0",
          }
        : {
            color: "#fde68a",
            background: "rgba(253, 230, 138, 0.12)",
            borderColor: "rgba(253, 230, 138, 0.38)",
          }
    },

    payrollPeriod() {
      const monthYear = this.formatMonthYear(this.payroll?.month)
      const cutoff = this.formatCutoff(this.payroll?.cutoff)

      if (monthYear === "-" && cutoff === "-") return "-"
      if (monthYear === "-") return cutoff
      if (cutoff === "-") return monthYear

      return `${monthYear} • ${cutoff}`
    },

    manageHref() {
      const baseUrl = `/admin/payroll/${this.url}/${this.payroll.payroll_no}`
      const batchId = this.payroll?.batch_id

      return batchId !== null && batchId !== undefined && batchId !== ""
        ? `${baseUrl}?batch_id=${batchId}`
        : baseUrl
    },

    actions() {
      const makeButton = (config) => ({
        ...config,
        loadingKey: config.loadingKey || "",
        disabled: this.loading,
      })

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
          makeButton({
            type: "button",
            label: "Submit for Approval",
            icon: "fa fa-arrow-right me-2",
            loadingKey: "pending",
            onClick: () => this.$emit("change-status", this.payroll.id, "pending"),
          }),
          { type: "divider" },
          makeButton({
            type: "button",
            label: "Delete Permanent",
            class: "text-danger",
            icon: "fa fa-trash-can me-2",
            loadingKey: "delete",
            onClick: () => this.$emit("cancel", this.payroll.id),
          }),
        ],
        pending: [
          makeButton({
            type: "button",
            label: "Back to Draft",
            icon: "fa fa-undo me-2",
            loadingKey: "draft",
            onClick: () => this.$emit("change-status", this.payroll.id, "draft"),
          }),
          makeButton({
            type: "button",
            label: "Approve",
            icon: "fa fa-circle-check me-2",
            loadingKey: "approved",
            onClick: () => this.$emit("change-status", this.payroll.id, "approved"),
          }),
          { type: "divider" },
          makeButton({
            type: "button",
            label: "Cancel",
            icon: "fa fa-circle-xmark me-2",
            loadingKey: "cancelled",
            onClick: () => this.$emit("change-status", this.payroll.id, "cancelled"),
          }),
        ],
        approved: [
          makeButton({
            type: "button",
            label: "For Releasing",
            icon: "fa fa-paper-plane me-2",
            loadingKey: "for_releasing",
            onClick: () => this.$emit("change-status", this.payroll.id, "for_releasing"),
          }),
          { type: "divider" },
          makeButton({
            type: "button",
            label: "Cancel",
            icon: "fa fa-circle-xmark me-2",
            loadingKey: "cancelled",
            onClick: () => this.$emit("change-status", this.payroll.id, "cancelled"),
          }),
        ],
        for_releasing: [
          makeButton({
            type: "button",
            label: "Mark as Complete",
            icon: "fa fa-circle-check me-2",
            loadingKey: "completed",
            onClick: () => this.$emit("change-status", this.payroll.id, "completed"),
          }),
        ],
        cancelled: [
          makeButton({
            type: "button",
            label: "Delete Permanent",
            class: "text-danger",
            icon: "fa fa-trash-can me-2",
            loadingKey: "delete",
            onClick: () => this.$emit("cancel", this.payroll.id),
          }),
        ],
        completed: [],
        failed: [
          makeButton({
            type: "button",
            label: "Delete Permanent",
            class: "text-danger",
            icon: "fa fa-trash-can me-2",
            loadingKey: "delete",
            onClick: () => this.$emit("cancel", this.payroll.id),
          }),
        ],
      }

      return items.concat(byStatus[this.statusKey] ?? [])
    },
  },

  methods: {
    prettyStatus(value) {
      return String(value || "")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
    },

    formatMonthYear(value) {
      if (!value) return "-"

      const [year, month] = String(value).split("-")
      const monthIndex = parseInt(month, 10) - 1

      return `${MONTHS[monthIndex] || month} ${year}`
    },

    formatCutoff(value) {
      const labels = {
        first_cutoff: "1st Cutoff",
        second_cutoff: "2nd Cutoff",
      }

      return labels[value] || "-"
    },

    shortId(value) {
      if (!value) return "-"
      return String(value).split("-")[0]
    },

    formatDateTime(value) {
      if (!value) return "-"

      const date = new Date(String(value).replace(" ", "T"))

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
.payroll-card {
  display: flex;
  gap: 1rem;
  padding: 1.15rem 1.2rem;
  border-radius: 1rem;
  border: 1px solid rgba(100, 116, 139, 0.45);
  background:
    radial-gradient(circle at top right, rgba(52, 211, 153, 0.08), transparent 24%),
    linear-gradient(180deg, rgba(31, 41, 55, 0.98), rgba(31, 41, 55, 0.98));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.payroll-card--loading {
  opacity: 0.72;
}

.payroll-card__accent {
  width: 6px;
  min-height: 104px;
  flex: 0 0 auto;
  border-radius: 999px;
  margin-top: 0.35rem;
  box-shadow: 0 0 14px rgba(52, 211, 153, 0.18);
}

.payroll-card__content {
  flex: 1 1 auto;
  min-width: 0;
}

.payroll-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.payroll-card__title {
  color: #f8fafc;
  font-size: 1.05rem;
  font-weight: 700;
}

.payroll-card__subtitle {
  margin-top: 0.55rem;
  color: #cbd5e1;
  font-size: 0.93rem;
}

.payroll-card__menu {
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: #e2e8f0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.payroll-card__menu:hover:not(:disabled) {
  background: rgba(148, 163, 184, 0.12);
}

.payroll-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-top: 1rem;
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  min-height: 2rem;
  padding: 0.38rem 0.8rem;
  border-radius: 999px;
  border: 1px solid rgba(100, 116, 139, 0.45);
  background: rgba(51, 65, 85, 0.62);
  color: #e2e8f0;
  font-size: 0.82rem;
  line-height: 1;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  min-height: 2rem;
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  border: 1px solid;
  font-size: 0.78rem;
  line-height: 1;
  font-weight: 600;
  text-transform: capitalize;
}

.aut-pill {
  font-weight: 700;
}

@media (max-width: 767.98px) {
  .payroll-card {
    padding: 1rem;
  }

  .payroll-card__header {
    flex-direction: column;
  }

  .payroll-card__menu {
    align-self: flex-end;
  }

  .payroll-card__accent {
    min-height: 86px;
  }
}
</style>
