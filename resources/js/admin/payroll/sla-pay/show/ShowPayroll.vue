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
            />
        </div>
    </div>
</template>

<script>
import axios from "axios";
import ShowProgressBar from "./parts/ShowProgressBar.vue";
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
    },
    mounted() {
        this.fetchRegistry();
    },
};
</script>
