<template>
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <table v-if="!loading" class="table table-bordered">
            <tr>
                <th>Date:</th>
                <td>{{ formatDate(overtime.date) }}</td>
            </tr>
            <tr>
                <th>Start Time:</th>
                <td>{{ formatTime(overtime.start_time) }}</td>
            </tr>
            <tr>
                <th>End Time:</th>
                <td>{{ formatTime(overtime.end_time) }}</td>
            </tr>
            <tr>
                <th>Total Hours:</th>
                <td>{{ overtime.total_hours }} hrs</td>
            </tr>
            <tr>
                <th>Reason:</th>
                <td>{{ overtime.reason }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span :class="['badge', statusClass]">
                        {{ overtime.status }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td>{{ formatDateTime(overtime.created_at) }}</td>
            </tr>
        </table>

        <!-- <div v-if="!loading" class="d-flex justify-content-end gap-3">
            <p><strong>Approver:</strong> {{ overtime.approver }}</p>
            <p><strong>Approved At:</strong> {{ formatDateTime(overtime.approved_at) }}</p>
        </div> -->

        <div v-if="loading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
        </div>
    </div>
</template>

<script>
const token = localStorage.getItem("auth_token");
import axios from "axios";

export default {
    name: "OvertimeApplication",
    props: {
        employee_id: { type: String, required: true },
        month: { type: Number, required: true },
        year: { type: Number, required: true },
        index: { type: Number, required: true }
    },
    data() {
        return {
            loading: false,
            errors: {},
            overtime: {
                docId: "",
                date: "",
                start_time: "",
                end_time: "",
                total_hours: "",
                reason: "",
                status: "",
                created_at: "",
                approver: "",
                approved_at: ""
            }
        };
    },
    computed: {
        statusClass() {
            return this.overtime.status === "approved"
                ? "bg-success text-light"
                : this.overtime.status === "pending text-light"
                ? "bg-warning text-light"
                : "bg-secondary text-light";
        }
    },
    mounted() {
        this.fetchOvertime();
    },
    methods: {
        async fetchOvertime() {
            this.loading = true;
            this.errors = {};
            try {
                const date = this.buildDate(this.year, this.month, this.index);

                const response = await axios.get(`/api/get-overtime`, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                    params: {
                        user_id: this.employee_id,
                        date: date
                    }
                });

                this.overtime = response.data.overtime ?? this.overtime;
            } catch (error) {
                console.error("Error fetching overtime:", error);
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                }
            } finally {
                this.loading = false;
            }
        },
        buildDate(year, month, day) {
            const d = new Date(year, month - 1, day);
            return (
                d.getFullYear() +
                "-" +
                String(d.getMonth() + 1).padStart(2, "0") +
                "-" +
                String(d.getDate()).padStart(2, "0")
            );
        },
        formatDate(dateStr) {
            if (!dateStr) return "-";
            const d = new Date(dateStr);
            return new Intl.DateTimeFormat("en-US", {
                weekday: "short",
                year: "numeric",
                month: "long",
                day: "numeric"
            }).format(d);
        },
        formatTime(timeStr) {
            if (!timeStr) return "-";
            const d = new Date(`1970-01-01T${timeStr}`);
            return new Intl.DateTimeFormat("en-US", {
                hour: "numeric",
                minute: "numeric",
                hour12: true
            }).format(d);
        },
        formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return "-";
            const d = new Date(dateTimeStr);
            return new Intl.DateTimeFormat("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "numeric",
                minute: "numeric",
                hour12: true
            }).format(d);
        }
    },
    watch: {
        index: "fetchOvertime",
        month: "fetchOvertime",
        year: "fetchOvertime"
    }
};
</script>

<style lang="scss" scoped>
.table tr td {
    padding: 12px;
}
</style>
