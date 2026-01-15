<template>
    <div class="card bg-body-secondary rounded-4 py-4 mb-3 shadow-sm">
        <!-- Show loading while fetching logs -->
        <div
            v-if="loading"
            class="d-flex justify-content-center align-items-center py-5"
        >
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <span class="ms-3 fs-5 text-secondary">Loading your logs...</span>
        </div>

        <!-- Show content only when not loading -->
        <div v-else>
            <div
                class="d-md-flex gap-1 align-items-center border-bottom pb-3 mb-3"
            >
                <div
                    class="d-flex flex-column align-items-center border-end px-5 month-year"
                >
                    <h1 class="month fw-bold p-0 m-0 text-uppercase">
                        {{ getMonthShort() }}
                    </h1>
                    <h1 class="text-body p-0 m-0 fw-bold">
                        {{ getDayToday() }}
                    </h1>
                </div>

                <div
                    class="w-100 d-md-flex justify-content-center justify-content-md-start gap-3 px-3 d-lg-flex-wrap month-year-button"
                >
                    <button
                        class="btn btn-primary text-uppercase fw-bold py-3 px-4 fw-semibold"
                        @click="setTime(0)"
                        v-if="!isTimeInDisabled"
                        :disabled="
                            isTimeInDisabled || buttonLoading === 'timeIn'
                        "
                        style="border-width: 2px"
                    >
                        <i
                            v-if="buttonLoading === 'timeIn'"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="fas fa-clock me-2"></i>
                        Clock In
                    </button>

                    <button
                        class="btn btn-primary text-uppercase fw-bold py-3 px-4 fw-semibold"
                        @click="setTime(2)"
                        v-if="!isBreakOutDisabled"
                        :disabled="
                            isBreakOutDisabled || buttonLoading === 'breakOut'
                        "
                        style="border-width: 2px"
                    >
                        <i
                            v-if="buttonLoading === 'breakOut'"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="fas fa-mug-hot me-2"></i>
                        Take a Break
                    </button>

                    <button
                        class="btn btn-primary text-uppercase fw-bold py-3 px-4 fw-semibold"
                        @click="setTime(3)"
                        v-if="!isBreakInDisabled"
                        :disabled="
                            isBreakInDisabled || buttonLoading === 'breakIn'
                        "
                        style="border-width: 2px"
                    >
                        <i
                            v-if="buttonLoading === 'breakIn'"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="fas fa-walking me-2"></i>
                        Back to Work
                    </button>

                    <button
                        class="btn btn-danger text-uppercase fw-bold py-3 px-4 fw-semibold"
                        @click="setTime(1)"
                        :disabled="
                            isTimeOutDisabled || buttonLoading === 'timeOut'
                        "
                        style="border-width: 2px"
                    >
                        <i
                            v-if="buttonLoading === 'timeOut'"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="fas fa-sign-out-alt me-2"></i>
                        Clock Out
                    </button>

                    <button
                        class="btn btn-dark text-uppercase fw-bold py-3 px-4 fw-semibold"
                        @click="setTime(4)"
                        v-if="!isOvertimeInDisabled"
                        :disabled="
                            isOvertimeInDisabled ||
                            buttonLoading === 'overtimeIn'
                        "
                        style="border-width: 2px"
                    >
                        <i
                            v-if="buttonLoading === 'overtimeIn'"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="fas fa-moon me-2"></i>
                        Start OT
                    </button>

                    <button
                        class="btn btn-success text-uppercase fw-bold text-dark py-3 px-4 fw-semibold"
                        @click="setTime(5)"
                        v-if="!isOvertimeOutDisabled"
                        :disabled="
                            isOvertimeOutDisabled ||
                            buttonLoading === 'overtimeOut'
                        "
                        style="
                            border-width: 2px;
                            background-color: #ffc107;
                            border-color: #ffc107;
                        "
                    >
                        <i
                            v-if="buttonLoading === 'overtimeOut'"
                            class="fas fa-spinner fa-spin me-2"
                        ></i>
                        <i v-else class="fas fa-sun me-2"></i>
                        Finish OT
                    </button>
                </div>
            </div>

            <div class="row py-4 px-5 g-4">
                <div class="col-md-2 col-sm-4 col-12">
                    <div class="border-end pe-3">
                        <h5 class="text-secondary mb-2 fw-semibold">
                            Clock In
                        </h5>
                        <p class="fs-5 text-body fw-medium mb-0">
                            {{ log.timeIn || "--:--:--" }}
                        </p>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-12">
                    <div class="border-end pe-3">
                        <h5 class="text-secondary mb-2 fw-semibold">Break</h5>
                        <p class="fs-5 text-body fw-medium mb-0">
                            {{ log.breakOut || "--:--:--" }}
                        </p>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-12">
                    <div class="border-end pe-3">
                        <h5 class="text-secondary mb-2 fw-semibold">
                            Back to Work
                        </h5>
                        <p class="fs-5 text-body fw-medium mb-0">
                            {{ log.breakIn || "--:--:--" }}
                        </p>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-12">
                    <div class="border-end pe-3">
                        <h5 class="text-secondary mb-2 fw-semibold">
                            Clock Out
                        </h5>
                        <p class="fs-5 text-body fw-medium mb-0">
                            {{ log.timeOut || "--:--:--" }}
                        </p>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-12">
                    <div class="border-end pe-3">
                        <h5 class="text-secondary mb-2 fw-semibold">
                            Overtime In
                        </h5>
                        <p class="fs-5 text-body fw-medium mb-0">
                            {{ log.overtimeIn || "--:--:--" }}
                        </p>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-12">
                    <div class="pe-3">
                        <h5 class="text-secondary mb-2 fw-semibold">
                            Overtime Out
                        </h5>
                        <p class="fs-5 text-body fw-medium mb-0">
                            {{ log.overtimeOut || "--:--:--" }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { reactive, ref, onMounted, computed } from 'vue';
const emit = defineEmits(['submit-log'])
const log = reactive({
    timeIn: "",
    breakOut: "",
    breakIn: "",
    timeOut: "",
    overtimeIn: "",
    overtimeOut: "",
});

const loading = ref(true);
const buttonLoading = ref(null);

const isTimeInDisabled = computed(() => !!log.timeIn);
const isBreakOutDisabled = computed(
    () => !log.timeIn || !!log.breakOut || log.timeOut
);
const isBreakInDisabled = computed(
    () => !log.breakOut || !!log.breakIn || log.timeOut
);
const isTimeOutDisabled = computed(() => !log.timeIn || !!log.timeOut);
const isOvertimeInDisabled = computed(() => !log.timeOut || !!log.overtimeIn);
const isOvertimeOutDisabled = computed(
    () => !log.overtimeIn || !!log.overtimeOut
);

function getMonthShort() {
    return new Date().toLocaleString("default", { month: "short" });
}

function getDayToday() {
    return new Date().getDate();
}

function setTime(type) {
    const buttonNames = {
        0: "timeIn",
        1: "timeOut",
        2: "breakOut",
        3: "breakIn",
        4: "overtimeIn",
        5: "overtimeOut",
    };

    Swal.fire({
        title: "Are you sure?",
        icon: "question",
        text: `You are about to log this time.`,
        showCancelButton: true,
        confirmButtonText: "Save",
    }).then((result) => {
        if (result.isConfirmed) {
            buttonLoading.value = buttonNames[type];

            axios
                .post("/employee/check-in-out", { type })
                .then((response) => {
                    SuccesToast.fire({
                        title:
                            response.data.message || "Time logged successfully",
                    });
                    window.dispatchEvent(new Event("reload-datatable"));
                    getTodayLogs(false);
                })
                .catch((error) => {
                    console.error("Error setting time:", error);
                    ErrorToast.fire({
                        title:
                            error.response?.data?.error ||
                            error.response?.data?.message ||
                            "An error occurred",
                    });
                })
                .finally(() => {
                    buttonLoading.value = null;
                });
        }
    });
}

function getTodayLogs(isLoading = true) {
    loading.value = isLoading;
    axios
        .get("/employee/check-in-out/today-logs")
        .then((response) => {
            const todayLog = response.data.data;
            if (todayLog) {
                log.timeIn = todayLog.timeIn || "";
                log.breakOut = todayLog.breakOut || "";
                log.breakIn = todayLog.breakIn || "";
                log.timeOut = todayLog.timeOut || "";
                log.overtimeIn = todayLog.overtimeIn || "";
                log.overtimeOut = todayLog.overtimeOut || "";
            }
        })
        .catch((error) => {
            console.error("Error fetching today logs:", error);
        })
        .finally(() => {
            loading.value = false;
        });
}

onMounted(() => {
    getTodayLogs();
});
</script>

<style lang="scss" scoped>
@import "./../../../sass/variables";

.month {
    color: $danger;
}

[data-bs-theme="dark"] {
    .month {
        color: var(--bs-body-color);
    }
}

@media (max-width: 767.98px) {
    button {
      width: 100%;
      margin: 6px 0 6px 0;
    }
    .month-year, .month-year-button {
      margin-bottom: 20px;
    }
}
</style>
