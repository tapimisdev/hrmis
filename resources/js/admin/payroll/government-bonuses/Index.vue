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
        <PayrollLayout
            :payrolls="payrollList"
            :loading="loading"
            :url="'government-bonuses'"
            @deleted="handleDelete"
            @status-changed="handleChange"
        />
    </div>
</template>

<script>
import SearchSection from "../SearchSection.vue";
import PayrollLayout from "../PayrollLayout.vue";

export default {
    name: "GovernmentBonusIndex",
    components: { PayrollLayout, SearchSection },
    data() {
        const today = new Date();
        const currentMonth = today.toISOString().slice(0, 7);

        return {
            token: localStorage.getItem("auth_token"),
            payrollList: [],
            loading: false,
            bonusTypes: [],
            formDefaults: {
                employment_type: "1",
                government_bonus_type_id: "",
                month: currentMonth,
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
                    key: "month",
                    label: "Month",
                    type: "month",
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
        handleDelete() {
            this.$refs.searchPayroll.search();
        },
        handleChange() {
            this.$refs.searchPayroll.search();
        },
    },
    async mounted() {
        await this.fetchBonusTypes();
        this.$nextTick(() => this.$refs.searchPayroll?.search());
    },
};
</script>
