<template>
    <div>
        <SkeletonProfile v-if="loading" :lines="4"/>

        <!-- Profile Card -->
        <div v-else class="card p-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                <!-- Profile Picture -->
                <div class="flex-shrink-0">
                    <img
                        class="profile-picture rounded-circle border border-3 border-light shadow-sm"
                        :src="profile.picture"
                        alt="Profile Picture"
                    />
                </div>

                <!-- Content Section -->
                <div class="content w-100">
                    <h3 class="fw-bold text-dark text-uppercase mb-4 border-bottom pb-3">
                        <span class="text-info">Name:</span>
                        {{ profile.name }}
                    </h3>

                    <div class="row g-3">
                        <div
                            class="col-md-6"
                            v-for="(info, index) in infoCards"
                            :key="index"
                        >
                            <div class="info-card p-3 rounded-3 border h-100">
                                <h6 class="fw-bold mb-1 text-info">
                                    {{ info.label }}
                                </h6>
                                <p class="mb-0 text-dark fw-semibold">
                                    {{ info.value }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timelogs Tally -->
        <p class="fw-bold">Timekeeping Summary</p>
        <div class="row g-3 mb-3">
            <div
                class="col-md-3"
                v-for="(card, index) in tallyCards"
                :key="index"
            >
                <div class="card p-3">
                    <h5>{{ card.label }}</h5>
                    <h2 class="text-info text-end">{{ card.value }}</h2>
                </div>
            </div>
        </div>

        <!-- Timelogs Filter -->
        <p class="fw-bold">Filter Options</p>
        <div class="card p-4 mb-3">
            <div class="d-flex gap-2">
                <!-- Month Dropdown -->
                <select class="form-select" @change="emitDate" v-model="localMonth">
                    <option v-for="(month, index) in months" :key="index" :value="index + 1">
                        {{ month }}
                    </option>
                </select>

                <!-- Year Dropdown -->
                <select class="form-select" @change="emitDate" v-model="localYear">
                    <option value="">Select Year</option>
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import SkeletonProfile from './SkeletonProfile.vue';
export default {
    components: { SkeletonProfile },
    props: {
        month: {
            type: Number,
            default: () => new Date().getMonth() + 1 // 1-12
        },
        year: {
            type: Number,
            default: () => new Date().getFullYear()
        },
        employee_id: { 
            type: String, 
            required: true
        },
    },
    data() {
        return {
            loading: false,
            localMonth: this.month,
            localYear: this.year,
            profile: {},
            infoCards: [],
            tallyCards: [
                { label: "Total HRS", value: "235 HRS" },
                { label: "Overtime", value: "120 MINS" },
                { label: "Late / Undertime", value: "45 MINS" },
                { label: "Absent", value: "2 Days" },
                { label: "Leaves", value: "1 Day" },
                { label: "Holiday", value: "2 Days" },
                { label: "Suspensions", value: "0" }
            ],
            months: [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December"
            ],
            years: Array.from({ length: 2 }, (_, i) => new Date().getFullYear() - i)
        };
    },
    mounted() {
        this.getEmployeeInformation();
    },
    methods: {
        emitDate() {
            this.$emit('update-date', { month: this.localMonth, year: this.localYear });
        },
        async getEmployeeInformation() {
            this.loading = true;
            try {
                const response = await axios.get(`/admin/timekeeping/daily-time-record/${this.employee_id}/employee_information`);
                this.profile = response.data.profile;
                this.infoCards = response.data.infoCards;
                console.log(response.data);
            } catch (error) {
                console.error("Error fetching logs:", error);
            }
            this.loading = false;
        }
    },
    watch: {
        month(newVal) {
            this.localMonth = newVal;
        },
        year(newVal) {
            this.localYear = newVal;
        }
    }
};
</script>

<style lang="scss" scoped>
@import "./../../../../sass/variables";

.profile-picture {
    width: 160px;
    height: 160px;
    border: 4px solid $primary !important;
}
</style>
