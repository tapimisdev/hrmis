<template>
    <div>
        <SearchSection
            ref="searchPayroll"
            :fields="fields"
            :initialForm="formDefaults"
            url="/api/payroll/government-bonuses/processed"
            :headers="headers"
            @payroll-list="handlePayrollList"
        />

        <section class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="text-muted fw-semibold mb-0">Government Bonus Payrolls</h6>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div v-if="loading" class="py-4 text-center text-muted">
                        <i class="fa fa-spinner fa-spin me-2"></i>
                        Loading payrolls...
                    </div>

                    <div v-else-if="payrollList.length" class="government-bonus-table-shell">
                        <table ref="payrollTable" class="table table-striped table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Bonus Type</th>
                                    <th>Employment Type</th>
                                    <th>Reference No.</th>
                                    <th class="text-end">Employees</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payroll in payrollList" :key="payroll.id">
                                    <td class="fw-semibold" :data-order="formatYear(payroll.month)">
                                        {{ formatYear(payroll.month) }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ payroll.bonus_type_name || payroll.label || "-" }}</div>
                                        <div v-if="payroll.label" class="text-muted small">{{ payroll.label }}</div>
                                    </td>
                                    <td>
                                        <div>{{ payroll.employment_name || "-" }}</div>
                                        <div v-if="payroll.employment_code" class="text-muted small">
                                            {{ payroll.employment_code }}
                                        </div>
                                    </td>
                                    <td class="text-nowrap">{{ payroll.payroll_no || "-" }}</td>
                                    <td class="text-end">{{ payroll.no_employee || 0 }}</td>
                                    <td>
                                        <span class="badge text-bg-light text-capitalize border text-nowrap">
                                            {{ prettyStatus(payroll.status) }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap" :data-order="payroll.created_at || ''">
                                        {{ formatDateTime(payroll.created_at) }}
                                    </td>
                                    <td class="text-end action-cell">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                Action
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern government-bonus-actions">
                                                <li>
                                                    <a class="dropdown-item" :href="manageHref(payroll)">
                                                        <i class="fa fa-eye me-2 text-primary"></i>
                                                        Manage
                                                    </a>
                                                </li>

                                                <li
                                                    v-for="action in payrollActions(payroll)"
                                                    :key="`${payroll.id}-${action.key}`"
                                                >
                                                    <template v-if="action.type === 'divider'">
                                                        <hr class="dropdown-divider" />
                                                    </template>
                                                    <button
                                                        v-else
                                                        class="dropdown-item"
                                                        :class="[action.class, { 'dropdown-item-danger': action.key === 'delete' }]"
                                                        type="button"
                                                        @click="action.onClick"
                                                    >
                                                        <i :class="action.icon"></i>
                                                        {{ action.label }}
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="alert alert-info mb-0" role="alert">
                        No government bonus payroll found for the selected year.
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import SearchSection from "../SearchSection.vue";

export default {
    name: "GovernmentBonusIndex",
    components: { SearchSection },
    data() {
        const today = new Date();
        const currentYear = String(today.getFullYear());

        return {
            token: localStorage.getItem("auth_token"),
            payrollList: [],
            loading: false,
            bonusTypes: [],
            dataTable: null,
            formDefaults: {
                employment_type: "1",
                government_bonus_type_id: "",
                year: currentYear,
                status: "",
            },
        };
    },
    computed: {
        fields() {
            return [
                {
                    key: "employment_type",
                    label: "Employment Type",
                    type: "select",
                    cast: "number",
                    placeholder: "-- CHOOSE EMPLOYMENT TYPE --",
                    options: [
                        { label: "Regular", value: 1 },
                        { label: "COS", value: 2 },
                    ],
                },
                {
                    key: "government_bonus_type_id",
                    label: "Bonus Type",
                    type: "select",
                    cast: "number",
                    placeholder: "-- CHOOSE BONUS TYPE --",
                    options: this.bonusTypes
                        .filter((type) => type?.id !== null && type?.id !== undefined && type?.name)
                        .map((type) => ({
                            label: type.name,
                            value: type.id,
                        })),
                },
                {
                    key: "year",
                    label: "Year",
                    type: "text",
                },
            ];
        },
    },
    methods: {
        headers() {
            return {
                Accept: "application/json",
                Authorization: `Bearer ${this.token}`,
            };
        },
        payrollActions(payroll) {
            const statusActions = {
                draft: [
                    this.statusAction("pending", "Submit for Approval", "fa fa-arrow-right me-2"),
                    { type: "divider", key: "divider-draft" },
                    this.deleteAction(payroll.id),
                ],
                pending: [
                    this.statusAction("draft", "Back to Draft", "fa fa-undo me-2"),
                    this.statusAction("approved", "Approve", "fa fa-circle-check me-2"),
                    { type: "divider", key: "divider-pending" },
                    this.statusAction("cancelled", "Cancel", "fa fa-circle-xmark me-2"),
                ],
                approved: [
                    this.statusAction("for_releasing", "For Releasing", "fa fa-paper-plane me-2"),
                    { type: "divider", key: "divider-approved" },
                    this.statusAction("cancelled", "Cancel", "fa fa-circle-xmark me-2"),
                ],
                for_releasing: [
                    this.statusAction("completed", "Mark as Complete", "fa fa-circle-check me-2"),
                ],
                cancelled: [this.deleteAction(payroll.id)],
                completed: [],
                failed: [this.deleteAction(payroll.id)],
            };

            return (statusActions[payroll.status] || [])
                .map((action) => {
                    if (action.type === "divider") {
                        return action;
                    }

                    return {
                        ...action,
                        onClick: () =>
                            action.key === "delete"
                                ? this.confirmDelete(payroll.id)
                                : this.handleChangeStatus(payroll.id, action.key),
                    };
                });
        },
        statusAction(key, label, icon, extra = {}) {
            return {
                type: "button",
                key,
                label,
                icon,
                ...extra,
            };
        },
        deleteAction(id) {
            return {
                type: "button",
                key: "delete",
                label: "Delete Permanent",
                icon: "fa fa-trash-can me-2",
                class: "text-danger",
                id,
            };
        },
        async fetchBonusTypes() {
            const response = await axios.get("/api/payroll/government-bonuses/bonus-types", {
                headers: this.headers(),
            });

            this.bonusTypes = response.data.data || [];
        },
        handlePayrollList(data, isLoading) {
            this.payrollList = data;
            this.loading = isLoading;

            if (isLoading) {
                this.destroyDataTable();
                return;
            }

            this.$nextTick(() => this.initDataTable());
        },
        refreshList() {
            this.$refs.searchPayroll.search();
        },
        initDataTable() {
            const table = this.$refs.payrollTable;

            if (!table || !window.$?.fn?.DataTable || !this.payrollList.length) {
                return;
            }

            this.destroyDataTable();

            this.dataTable = window.$(table).DataTable({
                pageLength: 10,
                order: [[6, "desc"]],
                columnDefs: [
                    { targets: [7], orderable: false },
                ],
            });
        },
        destroyDataTable() {
            const table = this.$refs.payrollTable;

            if (!table || !window.$?.fn?.DataTable || !window.$.fn.DataTable.isDataTable(table)) {
                this.dataTable = null;
                return;
            }

            window.$(table).DataTable().destroy();
            this.dataTable = null;
        },
        confirmDelete(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) this.deletePayroll(id);
            });
        },
        deletePayroll(id) {
            axios
                .delete(`/api/payroll/government-bonuses/${id}/delete`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then(({ data }) => {
                    if (data?.status === "success") {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Payroll has been successfully deleted!",
                            icon: "success",
                        });
                        this.refreshList();
                    }
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Oops!",
                        text: error?.response?.data?.message || "Something went wrong.",
                        icon: "error",
                    });
                });
        },
        handleChangeStatus(id, nextStatus) {
            const labelMap = {
                draft: "Draft",
                pending: "Pending",
                approved: "Approved",
                for_releasing: "For Releasing",
                completed: "Complete",
                cancelled: "Cancelled",
            };

            Swal.fire({
                title: "Change payroll status?",
                html: `Set status to <b>${labelMap[nextStatus] || nextStatus}</b>?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#198754",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, change it!",
            }).then((result) => {
                if (result.isConfirmed) this.changePayrollStatus(id, nextStatus);
            });
        },
        prettyStatus(value) {
            return String(value || "")
                .replace(/_/g, " ")
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        },
        formatYear(value) {
            if (!value) return "-";
            return String(value).split("-")[0] || "-";
        },
        manageHref(payroll) {
            const baseUrl = `/admin/payroll/government-bonuses/${payroll.payroll_no}`;
            const batchId = payroll?.batch_id;
            const params = new URLSearchParams(window.location.search);

            if (batchId !== null && batchId !== undefined && batchId !== "") {
                params.set("batch_id", batchId);
            }

            const query = params.toString();

            return query ? `${baseUrl}?${query}` : baseUrl;
        },
        formatDateTime(value) {
            if (!value) return "-";

            const date = new Date(String(value).replace(" ", "T"));

            if (isNaN(date.getTime())) return "-";

            return date.toLocaleString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            });
        },
        changePayrollStatus(id, nextStatus) {
            axios
                .patch(
                    `/api/payroll/government-bonuses/${id}/status`,
                    { status: nextStatus },
                    { headers: { Authorization: `Bearer ${this.token}` } }
                )
                .then(({ data }) => {
                    if (data?.status === "success") {
                        Swal.fire({
                            title: "Updated!",
                            text: "Payroll status has been updated successfully.",
                            icon: "success",
                        });
                        this.refreshList();
                    }
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Oops!",
                        text: error?.response?.data?.message || "Something went wrong.",
                        icon: "error",
                    });
                });
        },
    },
    async mounted() {
        await this.fetchBonusTypes();
        this.$nextTick(() => this.$refs.searchPayroll?.search());
    },
    beforeDestroy() {
        this.destroyDataTable();
    },
    beforeUnmount() {
        this.destroyDataTable();
    },
};
</script>

<style scoped>
.government-bonus-table-shell {
    overflow: visible;
}

.action-cell {
    min-width: 9rem;
}

.government-bonus-actions {
    min-width: 16rem;
    z-index: 1080;
}

.government-bonus-actions .dropdown-item,
.government-bonus-actions .dropdown-divider {
    white-space: nowrap;
}

:deep(.dataTables_wrapper) {
    overflow: visible;
}

:deep(.dataTables_wrapper .dataTables_filter),
:deep(.dataTables_wrapper .dataTables_length),
:deep(.dataTables_wrapper .dataTables_info),
:deep(.dataTables_wrapper .dataTables_paginate) {
    margin-top: 0.75rem;
}

:deep(.dataTables_wrapper .dataTables_filter input),
:deep(.dataTables_wrapper .dataTables_length select) {
    margin-left: 0.5rem;
}

:deep(table.dataTable tbody td) {
    vertical-align: middle;
}

@media (max-width: 991.98px) {
    .government-bonus-table-shell {
        overflow-x: auto;
        overflow-y: visible;
    }

    .action-cell {
        min-width: 7.5rem;
    }
}
</style>
