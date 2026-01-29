<template>
    <div
        class="modal fade"
        id="scheduleHistoryModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div
            class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"
        >
            <div class="modal-content modern-modal">
                <!-- Header -->
                <div class="modal-header modern-header border-bottom pb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrapper">
                            <i class="text-light fas fa-history"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">
                                Web Time Access History
                            </h5>
                            <small class="text-muted d-block">
                                Latest schedule is shown on top.
                            </small>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        :disabled="loading"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Error -->
                    <div
                        v-if="errorMessage"
                        class="alert alert-danger d-flex align-items-start gap-2"
                    >
                        <i class="fas fa-triangle-exclamation mt-1"></i>
                        <div class="small">{{ errorMessage }}</div>
                    </div>

                    <!-- Loading -->
                    <div
                        v-if="loading"
                        class="d-flex align-items-center gap-2 text-muted"
                    >
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading history...</span>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="!history.length" class="empty-state">
                        <i class="fas fa-inbox me-1"></i>
                        No schedule history found.
                    </div>

                    <!-- List -->
                    <div v-else class="list-group">
                        <div
                            v-for="(item, idx) in history"
                            :key="item.id || idx"
                            class="list-group-item history-item"
                        >
                            <div
                                class="d-flex justify-content-between align-items-start gap-2"
                            >
                                <div
                                    class="d-flex align-items-center gap-2 flex-wrap"
                                >
                                    <span
                                        class="badge"
                                        :class="badgeClass(item.type)"
                                    >
                                        <i
                                            :class="typeIcon(item.type)"
                                            class="me-1"
                                        ></i>
                                        {{ typeLabel(item.type) }}
                                    </span>

                                    <span class="text-muted small">
                                        Effectivity:
                                        <span class="fw-semibold">{{
                                            item.effectivity_date || "—"
                                        }}</span>
                                    </span>

                                    <span class="text-muted small">
                                        Saved:
                                        <span class="fw-semibold">{{
                                            formatDateTime(item.created_at)
                                        }}</span>
                                    </span>

                                    <span
                                        v-if="idx === 0"
                                        class="badge bg-success"
                                    >
                                        <i class="fas fa-star me-1"></i> Active
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 small">
                                <div class="fw-semibold">Rule</div>
                                <div class="text-muted">
                                    {{ ruleText(item) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button
                        class="btn btn-danger"
                        data-bs-dismiss="modal"
                        :disabled="loading"
                    >
                        <i class="fas fa-times me-2"></i> Close
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
    name: "ScheduleHistoryModal",
    data() {
        return {
            loading: false,
            errorMessage: "",
            employeeNo: null,
            history: [],
        };
    },
    methods: {
        open(employeeNo) {
            this.employeeNo = employeeNo;
            this.history = [];
            this.errorMessage = "";
            $("#scheduleHistoryModal").modal("show");
            this.fetchHistory();
        },
        close() {
            $("#scheduleHistoryModal").modal("hide");
        },
        async fetchHistory() {
            if (!this.employeeNo) return;

            this.loading = true;
            this.errorMessage = "";

            try {
                const res = await axios.get(
                    `/admin/timekeeping/web-time-access/${this.employeeNo}`,
                    {
                        headers: { Authorization: `Bearer ${token}` },
                    }
                );

                // expects: { data: [...] } latest first
                this.history = res.data?.data || [];
            } catch (err) {
                if (err.response?.status === 404) {
                    this.history = [];
                    this.errorMessage = "No history found for this employee.";
                } else if (!err.response) {
                    this.errorMessage = "Network error. Please try again.";
                } else {
                    this.errorMessage =
                        err.response?.data?.message ||
                        "Failed to load history.";
                }
            } finally {
                this.loading = false;
            }
        },

        // UI helpers
        badgeClass(type) {
            if (type === "always") return "bg-warning text-dark";
            if (type === "days_of_week") return "bg-primary";
            if (type === "specific_dates") return "bg-light text-dark";
            return "bg-secondary";
        },
        typeLabel(type) {
            return type === "always"
                ? "Always"
                : type === "days_of_week"
                ? "Days of Week"
                : type === "specific_dates"
                ? "Specific Dates"
                : "Unknown";
        },
        typeIcon(type) {
            return type === "always"
                ? "fas fa-infinity"
                : type === "days_of_week"
                ? "fas fa-calendar-week"
                : type === "specific_dates"
                ? "fas fa-calendar-day"
                : "fas fa-question-circle";
        },
        ruleText(item) {
            if (item.type === "always") return "Allowed anytime.";
            if (item.type === "days_of_week") {
                const days = (item.days_of_week || []).join(", ");
                return days
                    ? `Allowed every ${days}.`
                    : "Allowed on selected weekdays.";
            }
            if (item.type === "specific_dates") {
                const dates = (item.specific_dates || []).join(", ");
                return dates
                    ? `Allowed on: ${dates}.`
                    : "Allowed on selected dates.";
            }
            return "—";
        },
        formatDateTime(value) {
            if (!value) return "—";
            // simple formatting, works for "YYYY-MM-DD HH:mm:ss" or ISO
            const d = new Date(value);
            if (isNaN(d.getTime())) return value;
            return d.toLocaleString();
        },
    },
};
</script>

<style scoped lang="scss">
.modal-body {
    max-height: 65vh;
    overflow-y: auto;
}

.history-item {
    border-radius: 12px;
    margin-bottom: 10px;
}

.empty-state {
    font-size: 0.95rem;
    padding: 12px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.03);
}
</style>
