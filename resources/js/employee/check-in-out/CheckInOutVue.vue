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
                <td class="fw-bold text-warning">{{ log.lunchOut || '--:--:--' }}</td>
                <td class="fw-bold text-primary">{{ log.lunchIn || '--:--:--' }}</td>
                <td class="fw-bold text-danger">{{ log.timeOut || '--:--:--' }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>
                    <button class="btn btn-primary btn-lg w-100 py-3" @click="setTime('timeIn')" :disabled="!!log.timeIn">Time In</button>
                </td>
                <td>
                    <button class="btn btn-outline-primary btn-lg w-100 py-3" @click="setTime('lunchOut')" :disabled="!log.timeIn || !!log.lunchOut">Break Out</button>
                </td>
                <td>
                    <button class="btn btn-outline-primary btn-lg w-100 py-3" @click="setTime('lunchIn')" :disabled="!log.lunchOut || !!log.lunchIn">Break In</button>
                </td>
                <td>
                    <button class="btn btn-primary btn-lg w-100 py-3" @click="setTime('timeOut')" :disabled="!log.lunchIn || !!log.timeOut">Time Out</button>
                </td>
            </tr>
        </tfoot>
    </table>
</template>

<style scoped>
button:disabled {
    cursor: not-allowed !important;
    opacity: 0.6 !important;
    pointer-events: auto !important;
}
</style>

<script setup>
import axios from 'axios';
import { reactive } from 'vue';

function getCurrentTime() {
  const now = new Date();
  let hours = now.getHours();
  const minutes = now.getMinutes().toString().padStart(2, '0');
  const seconds = now.getSeconds().toString().padStart(2, '0');
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12;
  hours = hours ? hours : 12;
  return `${hours.toString().padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
}

const log = reactive({
  timeIn: '',
  lunchOut: '',
  lunchIn: '',
  timeOut: ''
});

function setTime(type) {
  axios.post('/employee/check-in-out', { type, time: getCurrentTime() })
    .then(response => {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
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
            title: "Signed in successfully"
        });
        if (!log[type]) {
            log[type] = getCurrentTime();
        }
    //   log[type] = response.data.time;
    })
    .catch(error => {
      console.error('Error setting time:', error);
      const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
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
            title: error.response.data.message || "An error occurred"
        });
    });

}

function resetLog() {
  log.timeIn = '';
  log.lunchOut = '';
  log.lunchIn = '';
  log.timeOut = '';
}
</script>