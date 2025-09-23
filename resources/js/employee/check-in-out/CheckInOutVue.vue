<template>
  <div class="card py-4 mb-3">
    <!-- Show loading while fetching logs -->
    <div v-if="loading" class="d-flex justify-content-center align-items-center py-5">
      <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
      <span class="ms-2">Loading your logs...</span>
    </div>

    <!-- Show content only when not loading -->
    <div v-else>
      <div class="d-flex gap-2 align-items-center border-bottom pb-3">
        <div class="d-flex flex-column align-items-center border-end px-5">
          <h4 class="text-danger fw-bolder p-0 m-0">{{ getMonthShort() }}</h4>
          <h1 class="p-0">{{ getDayToday() }}</h1>
        </div>

        <div class="w-100 d-flex gap-4 px-4">
          <button 
            class="btn btn-outline-primary btn-thick-outline py-3 px-5"
            @click="setTime(0)" 
            v-if="!isTimeInDisabled"
            :disabled="isTimeInDisabled || buttonLoading === 'timeIn'">
            <i v-if="buttonLoading === 'timeIn'" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-clock"></i>
            Clock In
          </button>

          <button 
            class="btn btn-outline-primary btn-thick-outline py-3 px-5" 
            @click="setTime(2)" 
            v-if="!isBreakOutDisabled"
            :disabled="isBreakOutDisabled || buttonLoading === 'breakOut'">
            <i v-if="buttonLoading === 'breakOut'" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-mug-hot"></i>
            Take a Break
          </button>

          <button 
            class="btn btn-outline-primary btn-thick-outline py-3 px-5" 
            @click="setTime(3)" 
            v-if="!isBreakInDisabled"
            :disabled="isBreakInDisabled || buttonLoading === 'breakIn'">
            <i v-if="buttonLoading === 'breakIn'" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-walking"></i>
            Back to Work
          </button>

          <button 
            class="btn btn-danger py-3 px-5" 
            @click="setTime(1)"
            :disabled="isTimeOutDisabled || buttonLoading === 'timeOut'">
            <i v-if="buttonLoading === 'timeOut'" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-sign-out-alt"></i>
            Clock Out
          </button>

          <button 
              class="btn btn-primary btn-thick-outline py-3 px-5" 
              @click="setTime(4)"
              v-if="!isOvertimeInDisabled"
              :disabled="isOvertimeInDisabled || buttonLoading === 'overtimeIn'">
              <i v-if="buttonLoading === 'overtimeIn'" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-moon"></i>
              Start OT
          </button>

          <button 
              class="btn btn-warning text-light btn-thick-outline py-3 px-5" 
              @click="setTime(5)"
              v-if="!isOvertimeOutDisabled"
              :disabled="isOvertimeOutDisabled || buttonLoading === 'overtimeOut'">
              <i v-if="buttonLoading === 'overtimeOut'" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-sun"></i>
              Finish OT
          </button>

        </div>
      </div>

      <div class="row py-4 px-5 mb-0 pb-0 p-5 g-5">
        <div class="col-md-2 col-sm-4 col-12">
          <div class="border-end">
            <h5>Clock In</h5>
            {{ log.timeIn || '--:--:--' }}
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-12">
          <div class="border-end">
            <h5>Break</h5>
            {{ log.breakOut || '--:--:--' }}
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-12">
          <div class="border-end">
            <h5>Back to Work</h5>
            {{ log.breakIn || '--:--:--' }}
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-12">
          <div class="border-end">
            <h5>Clock Out</h5>
            {{ log.timeOut || '--:--:--' }}
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-12">
          <div class="border-end">
            <h5>Overtime In</h5>
            {{ log.overtimeIn || '--:--:--' }}
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-12">
          <div>
            <h5>Overtime Out</h5>
            {{ log.overtimeOut || '--:--:--' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { reactive, ref, onMounted, computed } from 'vue';

const log = reactive({
  timeIn: '',
  breakOut: '',
  breakIn: '',
  timeOut: '',
  overtimeIn: '',
  overtimeOut: '',
});

const loading = ref(true);
const buttonLoading = ref(null); // Track which button is currently loading

const isTimeInDisabled = computed(() => !!log.timeIn);
const isBreakOutDisabled = computed(() => !log.timeIn || !!log.breakOut);
const isBreakInDisabled = computed(() => !log.breakOut || !!log.breakIn);
const isTimeOutDisabled = computed(() => !log.timeIn || !!log.timeOut);
const isOvertimeInDisabled = computed(() => !log.timeOut || !!log.overtimeIn);
const isOvertimeOutDisabled = computed(() => !log.overtimeIn || !!log.overtimeOut);

function getMonthShort() {
  return new Date().toLocaleString('default', { month: 'short' });
}

function getDayToday() {
  return new Date().getDate();
}

function getCurrentTime() {
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
}

function setTime(type) {
  Swal.fire({
    title: "Are you sure?",
    icon: "question",
    text: `You are about to log this time.`,
    showCancelButton: true,
    confirmButtonText: "Save",
  }).then((result) => {
    if (result.isConfirmed) {
      buttonLoading.value = type; // Set loading for this button

      axios.post('/employee/check-in-out', { type, date_time: getCurrentTime() })
        .then(response => {
          SuccesToast.fire({
            title: response.data.message || "Time logged successfully"
          });
          window.dispatchEvent(new Event('reload-datatable'));
          getTodayLogs(false);
        })
        .catch(error => {
          console.error('Error setting time:', error);
          ErrorToast.fire({
            title: error.response?.data?.error || error.response?.data?.message || "An error occurred"
          });
        })
        .finally(() => {
          buttonLoading.value = null; // Reset after done
        });
    }
  });
}

function getTodayLogs(isLoading = true) {
  loading.value = isLoading;
  axios.get('/employee/check-in-out/today-logs')
    .then(response => {
      const todayLog = response.data.data;
      if (todayLog) {
        log.timeIn = todayLog.timeIn || '';
        log.breakOut = todayLog.breakOut || '';
        log.breakIn = todayLog.breakIn || '';
        log.timeOut = todayLog.timeOut || '';
        log.overtimeIn = todayLog.overtimeIn || '';
        log.overtimeOut = todayLog.overtimeOut || '';
      }
    })
    .catch(error => {
      console.error('Error fetching today logs:', error);
    })
    .finally(() => {
      loading.value = false;
    });
}

onMounted(() => {
  getTodayLogs();
});
</script>
