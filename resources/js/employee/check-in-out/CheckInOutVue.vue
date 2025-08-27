<template>
    <h1 class="text-xl fw-bold text-uppercase mb-5">Daily time Record</h1>
    <table class="table table-bordered table-lg mb-3 text-center align-middle mb-5">
        <thead>
            <tr>
                <th>Time In</th>
                <th>Lunch Out</th>
                <th>Lunch In</th>
                <th>Time Out</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-bold text-success">{{ log.timeIn || '--:--:--' }}</td>
                <td class="fw-bold text-warning">{{ log.breakOut || '--:--:--' }}</td>
                <td class="fw-bold text-primary">{{ log.breakIn || '--:--:--' }}</td>
                <td class="fw-bold text-danger">{{ log.timeOut || '--:--:--' }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                 <td>
                    <button class="btn btn-primary btn-lg w-100 py-3" 
                            @click="setTime('timeIn')" 
                            :disabled="isTimeInDisabled"
                            >
                            Time In</button>
                </td>
                <td>
                    <button class="btn btn-warning btn-lg w-100 py-3" 
                            @click="setTime('breakOut')" 
                            :disabled="isBreakOutDisabled"
                            >
                            Break Out</button>
                </td>
                <td>
                    <button class="btn btn-warning btn-lg w-100 py-3" 
                            @click="setTime('breakIn')" 
                            :disabled="isBreakInDisabled"
                            >
                            Break In</button>
                </td>
                <td>
                    <button class="btn btn-primary btn-lg w-100 py-3" 
                            @click="setTime('timeOut')" 
                            :disabled="isTimeOutDisabled"
                            >
                            Time Out</button>
                </td>
            </tr>
        </tfoot>
    </table>
</template>

<style scoped>
</style>

<script setup>
import axios from 'axios';
import { reactive, onMounted, computed } from 'vue';

const log = reactive({
  timeIn: '',
  breakOut: '',
  breakIn: '',
  timeOut: ''
});

const isTimeInDisabled = computed(() => !!log.timeIn);
const isBreakOutDisabled = computed(() => !log.timeIn || !!log.breakOut);
const isBreakInDisabled = computed(() => !log.breakOut || !!log.breakIn);
const isTimeOutDisabled = computed(() => !log.breakIn || !!log.timeOut);

function getCurrentTime() {
  const now = new Date();

  const year = now.getFullYear();
  const month = (now.getMonth() + 1).toString().padStart(2, '0'); // months are 0-based
  const day = now.getDate().toString().padStart(2, '0');

  const hours = now.getHours().toString().padStart(2, '0'); // 24-hour format
  const minutes = now.getMinutes().toString().padStart(2, '0');
  const seconds = now.getSeconds().toString().padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}
function setTime(type) {

    Swal.fire({
        title: "Are you sure?",
        icon: "question",
        text: `You are about to log your ${type.replace(/([A-Z])/g, ' $1').toLowerCase()}.`,
        showCancelButton: true,
        confirmButtonText: "Save",
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post('/employee/check-in-out', { type, date_time: getCurrentTime() })
            .then(response => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "bottom-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "success",
                    title: response.data.message || "Time logged successfully"
                });
                getTodayLogs();
            })
            .catch(error => {
                console.error('Error setting time:', error);
                const Toast = Swal.mixin({
                    toast: true,
                    position: "bottom-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "error",
                    title: error.response.data.message || "An error occurred"
                });
            });

        }
    });
  
}
function getTodayLogs() {
  axios.get('/employee/check-in-out/today-logs')
    .then(response => {
      const todayLog = response.data.data;
      if (todayLog) {
        log.timeIn = todayLog.timeIn || '';
        log.breakOut = todayLog.breakOut || '';
        log.breakIn = todayLog.breakIn || '';
        log.timeOut = todayLog.timeOut || '';
      }
    })
    .catch(error => {
      console.error('Error fetching today logs:', error);
    });
}
onMounted(() => {
    getTodayLogs();
});
</script>