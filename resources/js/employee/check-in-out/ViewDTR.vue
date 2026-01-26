<template>
  <div style="display: flex; justify-content: center; gap: 10px;">
    <div class="form-container">
      <!-- Top header info -->
      <div class="top-row" style="margin-bottom: 3px;">
        <div>No. <input type="text" style="width:50px;" v-model="formData.no" /></div>
        <div>Pay Ending <input type="text" style="width:90px;" v-model="formData.payEnding" /> 20 <input type="text" style="width:30px;" v-model="formData.year" /></div>
      </div>
      <div class="top-row name-position">
        <div class="field-group">
          <label for="name">Name</label>
          <input type="text" id="name" class="field" v-model="formData.name" />
        </div>
        <div class="field-group">
          <label for="position">Position</label>
          <input type="text" id="position" class="field" v-model="formData.position" />
        </div>
      </div>
      <div class="top-row dept-age">
        <div class="field-group">
          <label for="dept">Dept.</label>
          <input type="text" id="dept" class="field" v-model="formData.dept" />
        </div>
        <div class="field-group">
          <label for="age">Age</label>
          <input type="text" id="age" class="field" v-model="formData.age" />
        </div>
      </div>

      <!-- Time in/out table -->
      <table class="time-table">
        <thead>
          <tr>
            <th class="days-col" rowspan="2">Days</th>
            <th colspan="2">MORNING</th>
            <th colspan="2">AFTERNOON</th>
            <th colspan="2">OVERTIME</th>
            <th class="daily-total" rowspan="2">Daily Total</th>
          </tr>
          <tr>
            <th>IN</th>
            <th>OUT</th>
            <th>IN</th>
            <th>OUT</th>
            <th>IN</th>
            <th>OUT</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(day, index) in timeEntries.slice(0, 15)" :key="index">
            <td class="days-col">{{ index + 1 }}</td>
            <td :class="getTimeClass(day, 'morning_in')">{{ getTimeValue(day, 'morning_in') }}</td>
            <td :class="getTimeClass(day, 'morning_out')">{{ getTimeValue(day, 'morning_out') }}</td>
            <td :class="getTimeClass(day, 'afternoon_in')">{{ getTimeValue(day, 'afternoon_in') }}</td>
            <td :class="getTimeClass(day, 'afternoon_out')">{{ getTimeValue(day, 'afternoon_out') }}</td>
            <td :class="getTimeClass(day, 'overtime_in')">{{ getTimeValue(day, 'overtime_in') }}</td>
            <td :class="getTimeClass(day, 'overtime_out')">{{ getTimeValue(day, 'overtime_out') }}</td>
            <td>{{ getDailyTotal(day) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="certify">I hereby certify that the above records are true and correct.</div>
      <div class="footer">
        <div class="signature">EMPLOYEE'S SIGNATURE</div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      timeEntries: [],
      summary: [],
      payrollValue: {},
      formData: {
        no: '',
        payEnding: '',
        year: '',
        name: '',
        position: '',
        dept: '',
        age: ''
      }
    };
  },
  mounted() {
    this.fetchPayrollData();
  },
  methods: {
    async fetchPayrollData() {
      try {
        const token = localStorage.getItem('auth_token'); // Assuming token is stored here
        const response = await axios.get('/api/employee/logs', { // Replace with your actual endpoint
          headers: { Authorization: `Bearer ${token}` }
        });

        const data = response.data;
        this.timeEntries = data.computedData || [];
        this.summary = data.summary || [];
        this.payrollValue = data.payroll_value || {};

        // Optionally populate formData from user data or summary
        // For example, if you have user info in response or elsewhere
        // this.formData.name = data.user?.name || '';
      } catch (error) {
        console.error('Error fetching payroll data:', error);
      }
    },
    getTimeValue(day, type) {
      // Parse and return time based on type
      switch (type) {
        case 'morning_in':
          return day.time_in || '';
        case 'morning_out':
          return day.break ? day.break.split(' to ')[0] : '';
        case 'afternoon_in':
          return day.break ? day.break.split(' to ')[1] : '';
        case 'afternoon_out':
          return day.time_out || '';
        case 'overtime_in':
          return day.overtime ? day.overtime.split(' to ')[0] : '';
        case 'overtime_out':
          return day.overtime ? day.overtime.split(' to ')[1] : '';
        default:
          return '';
      }
    },
    getTimeClass(day, type) {
      // Determine class based on remarks or logic (e.g., if late, use red-time)
      const remarks = day.remarks || [];
      if (remarks.includes('leave') || remarks.includes('absent') || remarks.includes('restday')) {
        return ''; // No class or different styling
      }
      // Simple logic: if late_undertime > 0 for the day (but it's total, so approximate)
      // For demo, use red-time if remarks include 'Discrepancy' or if time is late
      // You can enhance this logic based on your requirements
      if (remarks.includes('Discrepancy') || day.late_undertime > 0) {
        return 'red-time';
      }
      return 'black-time';
    },
    getDailyTotal(day) {
      // Calculate or return daily total (e.g., from paid_hours or total_time_work)
      return day.paid_hours ? `${day.paid_hours} HRS` : '';
    }
  }
};
</script>

<style>
/* Include the styles from the provided HTML */
.absences-cell {
  padding: 0;
  vertical-align: top;
}
.absences-table {
  width: 100%;
  height: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  font-size: 8px;
}
.absences-table th,
.absences-table td {
  border: 1px solid black;
  padding: 3px;
}
.absences-table th {
  width: 55%;
  text-align: left;
  font-weight: normal;
}
.absences-table td {
  width: 22.5%;
}
.abs-total th {
  text-align: center;
  font-weight: bold;
  border-top: 1px solid black;
}
.top-border {
  border-top: 1px solid black;
}
.net-pay {
  font-weight: bold;
  border-top: 1px solid black;
}
body {
  font-family: 'Poppins', sans-serif;
  font-size: 8px;
  margin: 20px;
}
.form-container {
  width: 350px;
  border: 1px solid black;
  padding: 5px 10px;
}
/* Top header lines */
.top-row {
  display: flex;
  justify-content: space-between;
  font-weight: bold;
  margin-bottom: 5px;
}
.top-row input,
.top-row select {
  border: none;
  border-bottom: 1px solid black;
  font-weight: normal;
  width: 90px;
  text-align: center;
}
.top-row .name-position,
.top-row .dept-age {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
}
.top-row .field-group {
  display: flex;
  align-items: center;
}
.top-row .field {
  border: none;
  border-bottom: 1px solid black;
  width: 120px;
  text-align: center;
  font-weight: normal;
  font-size: 11px;
}
/* Table for Hours, Rate, Amount & Absences */
.table-main {
  border-collapse: collapse;
  width: 100%;
  margin-bottom: 5px;
}
.table-main th, 
.table-main td {
  border: 1px solid black;
  text-align: center;
  padding: 2px 4px;
}
.table-main th {
  font-weight: normal;
  font-size: 9px;
}
.table-main .hours-rate-amount th {
  border-right: none;
}
.table-main .deductions th {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  font-weight: bold;
  width: 12px;
  padding: 2px 0;
}
.table-main .deductions td {
  border-left: none;
  text-align: left;
  font-size: 8px;
  padding-left: 5px;
  vertical-align: top;
  height: 70px;
}
/* Deductions rows text alignment */
.deductions-text {
  font-size: 8px;
  line-height: 1.2;
  padding-left: 3px;
}
/* Earnings and deductions totals */
.totals-row td {
  border-top: none !important;
  font-weight: bold;
  text-align: right;
  padding-right: 5px;
  height: 16px;
}
/* Time in/out table */
.time-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 8px;
  margin-top: 20px;
}
.time-table th,
.time-table td {
  border: 1px solid black;
  padding: 1px 3px;
  text-align: center;
  font-family: monospace;
  font-size: 8.5px;
  vertical-align: top;
}
.time-table th.days-col {
  width: 15px;
  vertical-align: middle;
}
.time-table th.daily-total {
  width: 30px;
}
.time-table tbody tr {
  height: 22px;
}
.time-table td.days-col {
  text-align: left;
  padding-left: 4px;
  font-weight: normal;
}
/* Time cell colors */
.red-time {
  color: red;
  text-decoration: underline;
  font-weight: bold;
}
.black-time {
  color: black;
  text-decoration: underline;
}
/* Small certification text */
.certify {
  font-size: 7px;
  margin-top: 16px;
  margin-bottom: 16px;
  font-style: italic;
  text-align: center;
}
/* Bottom footer */
.footer {
  display: flex;
  justify-content: center;
  font-size: 7px;
}
.footer .model {
  font-weight: bold;
  line-height: 1.2;
}
.footer .signature {
  border-top: 1px solid black;
  width: 140px;
  text-align: center;
}
</style>