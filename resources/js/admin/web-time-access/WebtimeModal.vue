<template>
  <div
    class="modal fade"
    id="scheduleModal"
    tabindex="-1"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
  >
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content modern-modal">
        
        <!-- Header -->
        <div class="modal-header modern-header border-bottom">
          <div class="d-flex align-items-center gap-2">
            <div class="icon-wrapper">
              <i class="text-light fas fa-clock"></i>
            </div>

            <div>
              <h5 class="modal-title mb-0">Schedule Web Time Access</h5>
              <small class="text-muted d-block">
                Set when selected employees can use web time-in/out.
              </small>
            </div>
          </div>

          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            :disabled="loading"
          ></button>
        </div>

        <!-- Body -->
        <div class="modal-body">

          <!-- Selected Employees -->
          <div class="selected-box mb-3">

            <!-- Error Summary -->
            <div
              v-if="errorMessage || errorList.length"
              class="alert alert-danger d-flex align-items-start gap-2 mb-3"
            >
              <i class="fas fa-triangle-exclamation mt-1"></i>

              <div>
                <div class="fw-semibold">Please fix the following:</div>

                <div v-if="errorMessage" class="small">
                  {{ errorMessage }}
                </div>

                <ul v-if="errorList.length" class="mb-0 mt-2 small">
                  <li v-for="(m, i) in errorList" :key="i">
                    {{ m }}
                  </li>
                </ul>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <div class="fw-semibold">Selected Employees</div>
                <small class="text-muted">
                  {{ selected_ids?.length || 0 }} selected
                </small>
              </div>
            </div>

            <div v-if="selected_ids?.length" class="chip-wrap">
              <span
                v-for="id in selected_ids"
                :key="id"
                class="chip"
                title="Employee No."
              >
                <i class="fas fa-id-badge me-1"></i>
                {{ id }}
              </span>
            </div>

            <div v-else class="empty-selected">
              <i class="fas fa-info-circle me-1"></i>
              No employees selected. Select employees first to apply a schedule.
            </div>

          </div>

          <form @submit.prevent="submitSchedule">

            <!-- Schedule Type -->
            <div class="mb-3">
              <label
                class="form-label fw-semibold"
                :class="{ 'text-danger': errors.type }"
              >
                Schedule Type
              </label>

              <div class="btn-group w-100" role="group">

                <input
                  class="btn-check"
                  type="radio"
                  id="schedule-always"
                  value="always"
                  v-model="form.type"
                  @change="onTypeChange"
                  :disabled="loading"
                />
                <label class="btn btn-outline-primary" for="schedule-always">
                  <i class="fas fa-infinity me-1"></i> Always
                </label>

                <input
                  class="btn-check"
                  type="radio"
                  id="schedule-week"
                  value="days_of_week"
                  v-model="form.type"
                  @change="onTypeChange"
                  :disabled="loading"
                />
                <label class="btn btn-outline-primary" for="schedule-week">
                  <i class="fas fa-calendar-week me-1"></i> Days of Week
                </label>

                <input
                  class="btn-check"
                  type="radio"
                  id="schedule-dates"
                  value="specific_dates"
                  v-model="form.type"
                  @change="onTypeChange"
                  :disabled="loading"
                />
                <label class="btn btn-outline-primary" for="schedule-dates">
                  <i class="fas fa-calendar-day me-1"></i> Specific Dates
                </label>

              </div>

              <small class="text-muted d-block mt-2">
                Choose one rule. Switching type clears the previous selection.
              </small>

              <span v-if="errors.type" class="text-danger d-block mt-1">
                {{ errors.type[0] }}
              </span>
            </div>

            <!-- Accomplishment Report -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Is Required Accomplishment Report?
              </label>

              <div class="btn-group w-100" role="group">

                <input
                  class="btn-check"
                  type="radio"
                  id="report-yes"
                  :value="true"
                  v-model="form.is_required_accomplishment"
                  :disabled="loading"
                />
                <label class="btn btn-outline-primary" for="report-yes">
                  Yes
                </label>

                <input
                  class="btn-check"
                  type="radio"
                  id="report-no"
                  :value="false"
                  v-model="form.is_required_accomplishment"
                  :disabled="loading"
                />
                <label class="btn btn-outline-primary" for="report-no">
                  No
                </label>

              </div>
            </div>

            <!-- Always -->
            <div
              v-if="form.type === 'always'"
              class="alert alert-success d-flex align-items-start gap-2"
            >
              <i class="fas fa-check-circle mt-1"></i>

              <div>
                <div class="fw-semibold">Always Allowed</div>
                <small class="d-block">
                  Selected employees can use web time-in/out anytime.
                </small>
              </div>
            </div>

            <!-- Days of Week -->
            <div v-if="form.type === 'days_of_week'" class="mb-3">

              <label class="form-label fw-semibold">Pick Days</label>

              <div
                class="weekday-grid"
                :class="{ 'border border-danger rounded p-2': errors.days_of_week }"
              >
                <button
                  v-for="day in weekDays"
                  :key="day"
                  type="button"
                  class="btn btn-sm"
                  :class="form.days_of_week.includes(day)
                    ? 'btn-primary'
                    : 'btn-outline-primary'"
                  @click="toggleWeekDay(day)"
                  :disabled="loading"
                >
                  {{ day }}
                </button>
              </div>

              <small class="text-muted d-block mt-2">
                Selected:
                <span class="fw-semibold">
                  {{ form.days_of_week.join(', ') || 'None' }}
                </span>
              </small>

              <span v-if="errors.days_of_week" class="text-danger d-block mt-1">
                {{ errors.days_of_week[0] }}
              </span>

            </div>

            <!-- Specific Dates -->
            <div v-if="form.type === 'specific_dates'" class="mb-3">

              <label class="form-label fw-semibold">Add Dates</label>

              <div class="d-flex gap-2">
                <input
                  type="date"
                  class="form-control"
                  v-model="newDate"
                  :min="minDate"
                  :disabled="loading"
                  @keydown.enter.prevent="addSpecificDate"
                />

                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="addSpecificDate"
                  :disabled="loading || !newDate"
                >
                  <i class="fas fa-plus"></i>
                </button>
              </div>

              <div class="mt-3">

                <div v-if="!form.specific_dates.length" class="empty-state">
                  <i class="fas fa-calendar-plus me-1"></i>
                  No dates yet. Add at least one date.
                </div>

                <ul v-else class="list-group">
                  <li
                    v-for="(date, i) in form.specific_dates"
                    :key="date"
                    class="list-group-item d-flex justify-content-between align-items-center"
                  >
                    <span class="d-flex align-items-center gap-2">
                      <i class="fas fa-calendar-alt text-muted"></i>
                      {{ date }}
                    </span>

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger"
                      @click="removeSpecificDate(i)"
                      :disabled="loading"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </li>
                </ul>

              </div>

            </div>

            <!-- Summary -->
            <div v-if="form.type" class="rule-summary mt-3">
              <div class="fw-semibold mb-1">
                <i class="fas fa-magic me-1"></i> Summary
              </div>

              <small class="text-muted d-block">
                {{ summaryText }}
              </small>
            </div>

          </form>
        </div>

        <!-- Footer -->
        <div class="modal-footer">

          <button
            @click="close"
            class="btn btn-danger"
            :disabled="loading"
          >
            <i class="me-2 fas fa-times"></i> Close
          </button>

          <button
            :disabled="loading || !canSubmit"
            @click="submitSchedule"
            class="btn btn-primary"
          >
            <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
            <i v-else class="me-2 fas fa-save"></i>

            {{ loading ? "Saving..." : "Save Schedule" }}
          </button>

        </div>

      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

const token = localStorage.getItem("auth_token");

export default {
    name: "ScheduleModal",

    props: {
        selected_ids: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            weekDays: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],

            loading: false,
            errors: {},
            errorMessage: "",

            newDate: "",

            form: {
                type: "",
                days_of_week: [],
                specific_dates: [],
                is_required_accomplishment: null,
            },
        };
    },

    computed: {
        minDate() {
            const d = new Date();

            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
                2,
                "0"
            )}-${String(d.getDate()).padStart(2, "0")}`;
        },

        canSubmit() {
            if (!this.selected_ids?.length || !this.form.type) return false;

            if (this.form.type === "days_of_week") {
                return this.form.days_of_week.length > 0;
            }

            if (this.form.type === "specific_dates") {
                return this.form.specific_dates.length > 0;
            }

            return true;
        },

        summaryText() {
            const n = this.selected_ids?.length || 0;

            if (this.form.type === "always") {
                return `Always allowed for ${n} employee(s).`;
            }

            if (this.form.type === "days_of_week") {
                return `Allowed every ${
                    this.form.days_of_week.join(", ") || "—"
                }.`;
            }

            if (this.form.type === "specific_dates") {
                return `Allowed on: ${
                    this.form.specific_dates.join(", ") || "—"
                }.`;
            }

            return "";
        },

        errorList() {
            return Object.values(this.errors || {}).flat();
        },
    },

    methods: {
        open(schedule = null) {
            if (schedule) {
                this.form = {
                    type: schedule.type || "",
                    days_of_week: schedule.days_of_week || [],
                    specific_dates: schedule.specific_dates || [],
                    is_required_accomplishment:
                        schedule.is_required_accomplishment ?? null,
                };
            }

            $("#scheduleModal").modal("show");
        },

        close() {
            $("#scheduleModal").modal("hide");
            this.reset();
        },

        reset() {
            this.form = {
                type: "",
                days_of_week: [],
                specific_dates: [],
                is_required_accomplishment: null,
            };

            this.newDate = "";
            this.errors = {};
            this.errorMessage = "";
            this.loading = false;
        },

        onTypeChange() {
            this.errors = {};
            this.errorMessage = "";
            this.newDate = "";

            this.form.days_of_week = [];
            this.form.specific_dates = [];
        },

        toggleWeekDay(day) {
            if (this.errors.days_of_week) {
                delete this.errors.days_of_week;
            }

            const index = this.form.days_of_week.indexOf(day);

            if (index >= 0) {
                this.form.days_of_week.splice(index, 1);
            } else {
                this.form.days_of_week.push(day);
            }
        },

        addSpecificDate() {
            if (this.errors.specific_dates) {
                delete this.errors.specific_dates;
            }

            if (!this.newDate) return;

            if (!this.form.specific_dates.includes(this.newDate)) {
                this.form.specific_dates.push(this.newDate);
            }

            this.form.specific_dates.sort();
            this.newDate = "";
        },

        removeSpecificDate(index) {
            this.form.specific_dates.splice(index, 1);

            if (this.errors.specific_dates) {
                delete this.errors.specific_dates;
            }
        },

        validateClient() {
            this.errors = {};
            this.errorMessage = "";

            if (!this.selected_ids?.length) {
                this.errorMessage = "No employees selected.";
                return false;
            }

            if (!this.form.type) {
                this.errors.type = ["Please choose a schedule type."];
                return false;
            }

            if (
                this.form.type === "days_of_week" &&
                !this.form.days_of_week.length
            ) {
                this.errors.days_of_week = [
                    "Please select at least one day.",
                ];
                return false;
            }

            if (
                this.form.type === "specific_dates" &&
                !this.form.specific_dates.length
            ) {
                this.errors.specific_dates = [
                    "Please add at least one date.",
                ];
                return false;
            }

            return true;
        },

        handleError(err) {
            if (err.response?.status === 422) {
                this.errors = err.response.data.errors || {};
                this.errorMessage = err.response.data.message || "";
                return;
            }

            if (!err.response) {
                this.errorMessage = "Network error. Please try again.";
                return;
            }

            if (err.response.status === 401) {
                this.errorMessage = "Session expired. Please login again.";
                return;
            }

            if (err.response.status === 403) {
                this.errorMessage =
                    "You don’t have permission to do this.";
                return;
            }

            this.errorMessage =
                err.response?.data?.message || "Something went wrong.";
        },

        async submitSchedule() {
            if (!this.validateClient()) return;

            this.loading = true;

            try {
                const payload = {
                    employee_nos: this.selected_ids,
                    type: this.form.type,
                    is_required_accomplishment:
                        this.form.is_required_accomplishment,

                    days_of_week:
                        this.form.type === "days_of_week"
                            ? this.form.days_of_week
                            : [],

                    specific_dates:
                        this.form.type === "specific_dates"
                            ? this.form.specific_dates
                            : [],
                };

                const res = await axios.post(
                    "/admin/timekeeping/web-time-access",
                    payload,
                    {
                        headers: {
                            Authorization: `Bearer ${token}`,
                        },
                    }
                );

                Swal.fire(
                    "Success",
                    "Schedule saved successfully!",
                    "success"
                );

                this.close();
                this.$emit("saved", res.data);
            } catch (err) {
                this.handleError(err);

                if (err.response?.status !== 422) {
                    Swal.fire(
                        "Error",
                        this.errorMessage || "Something went wrong.",
                        "error"
                    );
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style lang="scss" scoped>
.modal-body {
    max-height: 65vh;
    overflow-y: auto;
}

.selected-box {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.02);
}

.chip-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    max-height: 90px;
    overflow: auto;
    padding-right: 4px;
}
.chip {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 0.85rem;
    background: rgba(13, 110, 253, 0.12);
    border: 1px solid rgba(13, 110, 253, 0.25);
    color: #0d6efd;
}

.empty-selected,
.empty-state {
    font-size: 0.9rem;
    color: var(--bs-body-color);
    padding: 10px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.03);
}

.weekday-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.rule-summary {
    border-left: 4px solid rgba(13, 110, 253, 0.6);
    background: rgba(13, 110, 253, 0.06);
    padding: 10px 12px;
    border-radius: 10px;
}

span.text-danger {
    font-size: 0.875rem;
}

.btn-check:checked + .btn,
.btn-check:checked + .btn:hover,
:not(.btn-check) + .btn:active,
:not(.btn-check) + .btn:active:hover,
.btn:first-child:active,
.btn:first-child:active:hover,
.btn.active,
.btn.active:hover,
.btn.show,
.btn.show:hover {
    color: aliceblue !important;
}
</style>
