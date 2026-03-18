<template>
    <div class="p-3">
        <ShowProgressBar
            v-if="!isFinished"
            :batchId="batch_id"
            endpoint="/api/payroll/progress"
            cancel-endpoint="/api/payroll/cancel"
            title="Generating payroll..."
            @cancelled="onCancelled"
            @completed="onFinished"
        />
        <div v-else-if="employment_type === 'REGULAR'">
            <RegularPayrollRegistry
                :employees="employees"
                :status="status"
                :payroll_no="payroll_no"
                :month="month"
                @fetch_data="fetchRegistry"
                @delete="deleteEmployeePayroll"
            />
        </div>
    </div>
</template>

<script>
import axios from "axios";
import ShowProgressBar from "../../ShowProgressBar.vue";
import RegularPayrollRegistry from "./parts/RegularPayrollRegistry.vue";

export default {
    name: "SlaPayView",
    components: { ShowProgressBar, RegularPayrollRegistry },
    props: {
        batch_id: Number | String,
        payroll_id: Number | String,
        payroll_no: String,
        status: String,
        employment_type: String,
    },
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            isFinished: false,
            employees: [],
        };
    },
    methods: {
        onFinished() {
            this.isFinished = true;
            this.fetchRegistry();
        },
        async fetchRegistry() {
            try {
                const response = await axios.get(
                    `/api/payroll/sla-pay/${this.payroll_id}`,
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                    }
                );

                this.month = response.data.month_year;
                this.employees = response.data.employees;
            } catch (error) {
                console.error(
                    "Failed to fetch registry:",
                    error.response?.data || error.message
                );
            }
        },
        async deleteEmployeePayroll(emp) {
            const result = await Swal.fire({
                title: "Confirm Deletion",
                html: `
            <p><strong>${emp.name}</strong> payroll will be deleted.</p>
            <p class="text-danger mt-2">Type <b>DELETE</b> to confirm.</p>
            <input id="confirmInput" class="swal2-input" placeholder="Type DELETE">
        `,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Delete",
                preConfirm: () => {
                    const input = document.getElementById("confirmInput").value;

                    if (input !== "DELETE") {
                        Swal.showValidationMessage(
                            "You must type DELETE to proceed"
                        );
                        return false;
                    }

                    return true;
                },
            });

            if (!result.isConfirmed) return;

            try {
                await axios.delete(
                    `/api/payroll/sla-pay/${emp.id}/${this.employment_type}`,
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                        },
                    }
                );

                await Swal.fire({
                    title: "Deleted!",
                    text: "Employee payroll has been removed.",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false,
                });

                this.fetchRegistry();
            } catch (error) {
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message || "Failed to delete.",
                    icon: "error",
                });
            }
        },
    },
    mounted() {
        this.fetchRegistry();
    },
};
</script>
