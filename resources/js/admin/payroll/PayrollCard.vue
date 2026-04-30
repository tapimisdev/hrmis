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

      <div v-if="hasDeferredDeduction" class="deduction-schedule">
        <div class="deduction-schedule__icon">
          <i class="fa fa-receipt"></i>
        </div>
        <div class="min-w-0">
          <div class="deduction-schedule__label">Deduction scheduled</div>
          <div class="deduction-schedule__value">
            Apply on {{ deferredDeductionLabel }}
          </div>
        </div>
      </div>
    </div>
  </article>
</template>

<script>
const STATUS_META = {
  draft: {
    color: "var(--bs-warning-text-emphasis)",
    background: "var(--bs-warning-bg-subtle)",
    borderColor: "var(--bs-warning-border-subtle)",
    accent: "var(--bs-warning)",
  },
  pending: {
    color: "var(--bs-primary-text-emphasis)",
    background: "var(--bs-primary-bg-subtle)",
    borderColor: "var(--bs-primary-border-subtle)",
    accent: "var(--bs-primary)",
  },
  approved: {
    color: "var(--bs-success-text-emphasis)",
    background: "var(--bs-success-bg-subtle)",
    borderColor: "var(--bs-success-border-subtle)",
    accent: "var(--bs-success)",
  },
  for_releasing: {
    color: "var(--bs-info-text-emphasis)",
    background: "var(--bs-info-bg-subtle)",
    borderColor: "var(--bs-info-border-subtle)",
    accent: "var(--bs-info)",
  },
  completed: {
    color: "var(--bs-success-text-emphasis)",
    background: "var(--bs-success-bg-subtle)",
    borderColor: "var(--bs-success-border-subtle)",
    accent: "var(--bs-success)",
  },
  cancelled: {
    color: "var(--bs-danger-text-emphasis)",
    background: "var(--bs-danger-bg-subtle)",
    borderColor: "var(--bs-danger-border-subtle)",
    accent: "var(--bs-danger)",
  },
  failed: {
    color: "var(--bs-secondary-text-emphasis)",
    background: "var(--bs-secondary-bg-subtle)",
    borderColor: "var(--bs-secondary-border-subtle)",
    accent: "var(--bs-secondary)",
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
          color: "var(--bs-secondary-text-emphasis)",
          background: "var(--bs-secondary-bg-subtle)",
          borderColor: "var(--bs-secondary-border-subtle)",
          accent: "var(--bs-secondary)",
        }
      )
    },

    accent() {
      return this.statusMeta.accent
    },

    statusPill() {
      return {
        color: this.statusMeta.color,
        background: this.statusMeta.background,
        borderColor: this.statusMeta.borderColor,
      }
    },

    isRegularSalaryPayroll() {
      return this.url === "salary-pay" && Number(this.payroll?.employment_type_id) === 1
    },

    hasDeferredDeduction() {
      return (
        this.url === "salary-pay" &&
        Number(this.payroll?.employment_type_id) === 2 &&
        [false, 0, "0"].includes(this.payroll?.apply_deduction) &&
        Boolean(this.payroll?.deduction_deferred_date)
      )
    },

    deferredDeductionLabel() {
      const cutoff = this.formatCutoff(this.payroll?.deduction_deferred_cutoff)
      const date = this.formatDate(this.payroll?.deduction_deferred_date)

      if (cutoff === "-") return date
      if (date === "-") return cutoff

      return `${date} (${cutoff})`
    },

    payrollPeriod() {
      const monthYear = this.formatMonthYear(this.payroll?.month)
      const cutoff = this.formatCutoff(this.payroll?.cutoff)

      if (monthYear === "-" && cutoff === "-") return "-"
      if (monthYear === "-") return cutoff
      if (this.isRegularSalaryPayroll) return monthYear
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

    formatDate(value) {
      if (!value) return "-"

      const date = new Date(String(value).replace(" ", "T"))

      if (isNaN(date.getTime())) return "-"

      return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    },
  },
}
</script>

<style scoped lang="scss">
.payroll-card {
  display: flex;
  gap: 1rem;
  width: 100%;
  height: 100%;
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1px solid var(--bs-border-color);
  background: var(--bs-body-bg);
  box-shadow: 0 0.5rem 1.25rem rgba(var(--bs-body-color-rgb), 0.06);
}

.payroll-card--loading {
  opacity: 0.72;
}

.payroll-card__accent {
  width: 5px;
  min-height: 96px;
  flex: 0 0 auto;
  border-radius: 999px;
  margin-top: 0.35rem;
}

.payroll-card__content {
  display: flex;
  flex-direction: column;
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
  color: var(--bs-body-color);
  font-size: 1.05rem;
  font-weight: 700;
}

.payroll-card__subtitle {
  margin-top: 0.55rem;
  color: var(--bs-secondary-color);
  font-size: 0.93rem;
}

.payroll-card__menu {
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--bs-secondary-color);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.payroll-card__menu:hover:not(:disabled) {
  background: var(--bs-tertiary-bg);
  color: var(--bs-body-color);
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
  border: 1px solid var(--bs-border-color);
  background: var(--bs-tertiary-bg);
  color: var(--bs-body-color);
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

.deduction-schedule {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.75rem;
  max-width: 28rem;
  padding: 0.7rem 0.85rem;
  border: 1px solid var(--bs-warning-border-subtle);
  border-left: 4px solid var(--bs-warning);
  border-radius: 0.55rem;
  background: var(--bs-warning-bg-subtle);
  color: var(--bs-body-color);
}

.deduction-schedule__icon {
  width: 2rem;
  height: 2rem;
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: rgba(var(--bs-warning-rgb), 0.18);
  color: var(--bs-warning-text-emphasis);
}

.deduction-schedule__label {
  color: var(--bs-warning-text-emphasis);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  line-height: 1.1;
  text-transform: uppercase;
}

.deduction-schedule__value {
  margin-top: 0.2rem;
  color: var(--bs-body-color);
  font-size: 0.86rem;
  line-height: 1.25;
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

  .deduction-schedule {
    max-width: none;
  }
}
</style>
