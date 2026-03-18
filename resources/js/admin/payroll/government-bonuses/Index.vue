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

            <div class="row g-3" v-if="payrollList.length">
                <div v-for="payroll in payrollList" :key="payroll.id" class="col-12 col-md-6">
                    <PayrollCard
                        :url="'government-bonuses'"
                        :payroll="payroll"
                        @change-status="handleChangeStatus"
                        @cancel="confirmDelete"
                    />
                </div>
            </div>

            <div v-else class="alert alert-info mb-0" role="alert" style="max-width: 420px">
                No government bonus payroll found for the selected year.
            </div>
        </section>
    </div>
</template>

<script>
import SearchSection from "../SearchSection.vue";
import PayrollCard from "../PayrollCard.vue";

export default {
    name: "GovernmentBonusIndex",
    components: { PayrollCard, SearchSection },
    data() {
        const today = new Date();
        const currentYear = String(today.getFullYear());

        return {
            token: localStorage.getItem("auth_token"),
            payrollList: [],
            loading: false,
            bonusTypes: [],
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
                    options: [{ label: "Regular", value: 1 }],
                },
                {
                    key: "government_bonus_type_id",
                    label: "Bonus Type",
                    type: "select",
                    cast: "number",
                    placeholder: "-- CHOOSE BONUS TYPE --",
                    options: this.bonusTypes.map((type) => ({
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
        async fetchBonusTypes() {
            const response = await axios.get("/api/payroll/government-bonuses/bonus-types", {
                headers: this.headers(),
            });

            this.bonusTypes = response.data.data || [];
        },
        handlePayrollList(data, isLoading) {
            this.payrollList = data;
            this.loading = isLoading;
        },
        refreshList() {
            this.$refs.searchPayroll.search();
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
};
</script>
