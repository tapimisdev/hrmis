<template>
    <div>
        <!-- ACTIVE -->
        <section class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="text-muted fw-semibold mb-0">Active Payrolls</h6>
            </div>

            <div class="row g-3">
                <template v-if="activePayrolls.length">
                    <div v-for="item in activePayrolls" :key="item.id" class="col-12 col-md-6">
                        <PayrollCard :url="url" :payroll="item" @change-status="handleChangeStatus"
                            @cancel="confirmDelete" />
                    </div>
                </template>

                <template v-else>
                    <div class="col-12">
                        <div class="alert alert-info mb-0" role="alert" style="max-width: 420px">
                            No active payroll for this date yet.
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- INACTIVE -->
        <section v-if="inactivePayrolls.length" class="mb-4">
            <h6 class="text-muted fw-semibold mb-2">Others</h6>

            <div class="row g-3">
                <div v-for="item in inactivePayrolls" :key="item.id" class="col-12 col-md-4">
                    <PayrollCard :url="url" :payroll="item" @change-status="handleChangeStatus"
                        @cancel="confirmDelete" />
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import PayrollCard from "./PayrollCard.vue"

const INACTIVE_STATUSES = new Set(["draft", "cancelled"])

export default {
    name: "PayrollList",
    components: { PayrollCard },

    props: {
        payrolls: { type: Array, required: true },
        loading: { type: Boolean, required: true },
        url: { type: String, required: true },
    },

    computed: {
        activePayrolls() {
            return this.payrolls.filter(p => !INACTIVE_STATUSES.has(p.status))
        },
        inactivePayrolls() {
            return this.payrolls.filter(p => INACTIVE_STATUSES.has(p.status))
        },
    },

    methods: {
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
                if (result.isConfirmed) this.deletePayroll(id)
            })
        },

        deletePayroll(id) {
            const token = localStorage.getItem("auth_token")

            axios
                .delete(`/api/payroll/${this.url}/${id}/delete`, {
                    headers: { Authorization: `Bearer ${token}` },
                })
                .then(({ data }) => {
                    if (data?.status === "success") {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Payroll has been successfully deleted!",
                            icon: "success",
                        })
                        this.$emit("deleted")
                    }
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Oops!",
                        text: error?.response?.data?.message || "Something went wrong.",
                        icon: "error",
                    })
                })
        },

        // NEW: same pattern as delete
        confirmChangeStatus(id, nextStatus) {
            const labelMap = {
                draft: "Draft",
                pending: "Pending",
                pending_approval: "Pending",
                approved: "Approved",
                for_releasing: "For Releasing",
                cancelled: "Cancelled",
                complete: "Complete",
                completed: "Complete",
            }

            Swal.fire({
                title: "Change payroll status?",
                html: `Set status to <b>${labelMap[nextStatus] || nextStatus}</b>?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#198754", // bootstrap success
                cancelButtonColor: "#6c757d",  // bootstrap secondary
                confirmButtonText: "Yes, change it!",
            }).then((result) => {
                if (result.isConfirmed) this.changePayrollStatus(id, nextStatus)
            })
        },

        changePayrollStatus(id, nextStatus) {
            const token = localStorage.getItem("auth_token")

            axios
                // adjust endpoint/method to your backend
                .patch(
                    `/api/payroll/${this.url}/${id}/status`,
                    { status: nextStatus },
                    { headers: { Authorization: `Bearer ${token}` } }
                )
                .then(({ data }) => {
                    if (data?.status === "success") {
                        Swal.fire({
                            title: "Updated!",
                            text: "Payroll status has been updated successfully.",
                            icon: "success",
                        })

                        // emit so parent/page can refresh list or update item
                        this.$emit("status-changed")
                    } else {
                        Swal.fire({
                            title: "Oops!",
                            text: data?.message || "Unable to update status.",
                            icon: "error",
                        })
                    }
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Oops!",
                        text: error?.response?.data?.message || "Something went wrong.",
                        icon: "error",
                    })
                })
        },

        // this is what your PayrollCard already emits
        handleChangeStatus(id, nextStatus) {
            // Just forward to confirm step
            this.confirmChangeStatus(id, nextStatus)
        },
    },
}
</script>
