<template>
  <div class="card bg-body-secondary rounded-4 py-4 mb-3 shadow-sm">

      <!-- Loading -->
      <div
          v-if="loading"
          class="d-flex justify-content-center align-items-center py-5"
      >
          <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
          <span class="ms-3 fs-5 text-secondary">Loading your logs...</span>
      </div>

      <!-- Content -->
      <div v-else>

          <div class="d-md-flex flex-wrap gap-1 align-items-center border-bottom pb-3 mb-3">

              <div class="d-flex flex-column align-items-center border-end px-5 month-year">
                  <h1 class="month fw-bold p-0 m-0 text-uppercase">
                      {{ getMonthShort() }}
                  </h1>
                  <h1 class="text-body p-0 m-0 fw-bold">
                      {{ getDayToday() }}
                  </h1>
              </div>

              <div class="d-lg-flex justify-content-center justify-content-md-start gap-3 px-3 d-lg-flex-wrap month-year-button">

                  <!-- Clock In -->
                  <button
                      class="btn btn-primary text-uppercase fw-bold py-3 px-4 fw-semibold w-100 my-2"
                      style="white-space: nowrap !important;"
                      @click="setTime(0)"
                      v-if="!isTimeInDisabled"
                      :disabled="!props.isAllowed || buttonLoading === 'timeIn'"
                  >
                      <i v-if="buttonLoading === 'timeIn'" class="fas fa-spinner fa-spin me-2"></i>
                      <i v-else class="fas fa-clock me-2"></i>
                      Clock In
                  </button>

                  <!-- Break Out -->
                  <button
                      class="btn btn-primary text-uppercase fw-bold py-3 px-4 fw-semibold w-100 my-2"
                      style="white-space: nowrap !important;"
                      @click="setTime(2)"
                      v-if="!isBreakOutDisabled"
                      :disabled="!props.isAllowed || buttonLoading === 'breakOut'"
                  >
                      <i v-if="buttonLoading === 'breakOut'" class="fas fa-spinner fa-spin me-2"></i>
                      <i v-else class="fas fa-mug-hot me-2"></i>
                      Take a Break
                  </button>

                  <!-- Break In -->
                  <button
                      class="btn btn-primary text-uppercase fw-bold py-3 px-4 fw-semibold w-100 my-2"
                      style="white-space: nowrap !important;"
                      @click="setTime(3)"
                      v-if="!isBreakInDisabled"
                      :disabled="!props.isAllowed || buttonLoading === 'breakIn'"
                  >
                      <i v-if="buttonLoading === 'breakIn'" class="fas fa-spinner fa-spin me-2"></i>
                      <i v-else class="fas fa-walking me-2"></i>
                      Back to Work
                  </button>

                  <!-- Clock Out -->
                  <button
                      class="btn btn-danger text-uppercase fw-bold py-3 px-4 fw-semibold w-100 my-2"
                      style="white-space: nowrap !important;"
                      @click="setTime(1)"
                      :disabled="!props.isAllowed || isTimeOutDisabled || buttonLoading === 'timeOut'"
                  >
                      <i v-if="buttonLoading === 'timeOut'" class="fas fa-spinner fa-spin me-2"></i>
                      <i v-else class="fas fa-sign-out-alt me-2"></i>
                      Clock Out
                  </button>

                  <!-- OT In -->
                  <button
                      class="btn btn-dark text-uppercase fw-bold py-3 px-4 fw-semibold w-100 my-2"
                      style="white-space: nowrap !important;"
                      @click="setTime(4)"
                      v-if="!isOvertimeInDisabled"
                      :disabled="!props.isAllowed || buttonLoading === 'overtimeIn'"
                  >
                      <i v-if="buttonLoading === 'overtimeIn'" class="fas fa-spinner fa-spin me-2"></i>
                      <i v-else class="fas fa-moon me-2"></i>
                      Start OT
                  </button>

                  <!-- OT Out -->
                  <button
                      class="btn btn-success text-uppercase fw-bold text-dark py-3 px-4 fw-semibold"
                      @click="setTime(5)"
                      v-if="!isOvertimeOutDisabled"
                      :disabled="!props.isAllowed || buttonLoading === 'overtimeOut'"
                  >
                      <i v-if="buttonLoading === 'overtimeOut'" class="fas fa-spinner fa-spin me-2"></i>
                      <i v-else class="fas fa-sun me-2"></i>
                      Finish OT
                  </button>

              </div>

              <div
                  v-if="props.isAllowed && props.isRequiredAr"
                  class="alert alert-info d-flex align-items-start m-3"
              >
                  <i class="fas fa-info-circle me-2 mt-1"></i>
                  <div>
                      <strong>Accomplishment Report is Required</strong>
                      <div class="small mt-1">
                          Daily accomplishment report is needed before clocking out.
                      </div>
                  </div>
              </div>

          </div>

          <!-- Logs -->
          <div class="row py-4 px-5 g-4">
              <div class="col-md-2"><b>Clock In</b><p>{{ log.timeIn || "--:--:--" }}</p></div>
              <div class="col-md-2"><b>Break</b><p>{{ log.breakOut || "--:--:--" }}</p></div>
              <div class="col-md-2"><b>Back</b><p>{{ log.breakIn || "--:--:--" }}</p></div>
              <div class="col-md-2"><b>Clock Out</b><p>{{ log.timeOut || "--:--:--" }}</p></div>
              <div class="col-md-2"><b>OT In</b><p>{{ log.overtimeIn || "--:--:--" }}</p></div>
              <div class="col-md-2"><b>OT Out</b><p>{{ log.overtimeOut || "--:--:--" }}</p></div>
          </div>

      </div>
  </div>

  <AccomplishmentModal
    :show="showArModal"
    @close="showArModal = false"
    @submit="handleClockOut"
  />
</template>

<script setup>
import axios from "axios";
import { reactive, ref, onMounted, computed } from "vue";
import AccomplishmentModal from "./AccomplishmentModal.vue";

const emit = defineEmits(["submit-log"]);

const props = defineProps({
    isAllowed: Boolean,
    isRequiredAr: Boolean
});

const showArModal = ref(false);

const log = reactive({
    timeIn: "",
    breakOut: "",
    breakIn: "",
    timeOut: "",
    overtimeIn: "",
    overtimeOut: ""
});

const loading = ref(true);
const buttonLoading = ref(null);

const isTimeInDisabled = computed(() => !!log.timeIn);
const isBreakOutDisabled = computed(() => !log.timeIn || !!log.breakOut || log.timeOut);
const isBreakInDisabled = computed(() => !log.breakOut || !!log.breakIn || log.timeOut);
const isTimeOutDisabled = computed(() => !log.timeIn || !!log.timeOut);
const isOvertimeInDisabled = computed(() => !log.timeOut || !!log.overtimeIn);
const isOvertimeOutDisabled = computed(() => !log.overtimeIn || !!log.overtimeOut);

function setTime(type) {

  if (type === 1 && props.isRequiredAr) {
      showArModal.value = true;
      return;
  }

  confirmSubmit(type);
}

function handleClockOut(accomplishment) {
    showArModal.value = false;
    confirmSubmit(1, accomplishment);
}

function confirmSubmit(type, accomplishment = null) {

    const buttonNames = {
        0: "timeIn",
        1: "timeOut",
        2: "breakOut",
        3: "breakIn",
        4: "overtimeIn",
        5: "overtimeOut"
    };

    Swal.fire({
        title: "Are you sure?",
        icon: "question",
        text: "You are about to log this time.",
        showCancelButton: true,
        confirmButtonText: "Save"
    }).then((result) => {
        
        if (result.isConfirmed) {

            buttonLoading.value = buttonNames[type];

            axios.post("/employee/check-in-out", {
                type,
                accomplishment
            })
            .then((response) => {

                SuccesToast.fire({
                    title: response.data.message || "Time logged successfully"
                });

                emit("submit-log");
                getTodayLogs(false);

            })
            .catch((error) => {

                ErrorToast.fire({
                    title:
                        error.response?.data?.error ||
                        error.response?.data?.message ||
                        "An error occurred"
                });

            })
            .finally(() => {
                buttonLoading.value = null;
            });
        }

    });
}

function getMonthShort() {
    return new Date().toLocaleString("default", { month: "short" });
}

function getDayToday() {
    return new Date().getDate();
}

function getTodayLogs(isLoading = true) {

    loading.value = isLoading;

    axios.get("/employee/check-in-out/today-logs")
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
    .finally(() => {
        loading.value = false;
    });
}

onMounted(() => {
    getTodayLogs();
});
</script>

<style scoped>
    .month {
        color: #dc3545;
    }
    @media (max-width: 767.98px) {
      .month-year {
        margin-bottom: 20px;
      }
    }
</style>
