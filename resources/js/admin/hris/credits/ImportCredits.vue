<template>
  <div class="uploading">
    <form @submit.prevent="saveCredits">
      <div class="row my-3">

        <!-- Credits Type -->
        <div class="col-12 col-md-4 mb-3">
          <label for="credit_type" class="mb-3">Credits Type</label>
          <select id="credit_type" class="form-select text-uppercase" v-model="selectedCreditType">
            <option value="">- CHOOSE -</option>
            <option v-for="type in creditTypes" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Leave Type (only if Credits Type is leave) -->
        <div class="col-12 col-md-4 mb-3" v-if="selectedCreditType === 'leave'">
          <label for="leave_type" class="mb-3">Leave Type</label>
          <select id="leave_type" class="form-select text-uppercase" v-model="selectedLeaveType">
            <option value="">- CHOOSE -</option>
            <option v-for="leave in leaveTypes" :key="leave.id" :value="leave.id">
              {{ leave.name }}
            </option>
          </select>
        </div>

        <!-- File Upload -->
        <div class="col-12 mb-3">
          <label class="form-label fw-bold">Upload File (CSV or Excel)</label>
          <div 
            class="upload-box text-center p-5 border border-2 border-dashed rounded-3"
            @click="triggerFileInput"
          >
            <i class="fa-solid fa-file-arrow-up fa-2x mb-3 text-primary"></i>
            <p class="mb-1 fw-semibold">Click or drag file to upload</p>
            <small class="text-muted">Supported: CSV, XLS, XLSX</small>
            <input 
              type="file" 
              class="d-none" 
              ref="fileInput"
              accept=".csv, .xls, .xlsx"
              @change="onFileUpload"
            >
          </div>
        </div>

      </div>

      <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-upload me-2"></i> Upload
        </button>
      </div>
    </form>

    <!-- Preview Table -->
    <div v-if="!upload" class="mt-4">
      <h5>Preview Credits</h5>
      <table border="1" cellpadding="5">
        <thead>
          <tr>
            <th>#</th>
            <th>Employee ID</th>
            <th>Credit Type</th>
            <th>Amount</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(credit, index) in creditsData.credits" :key="index">
            <td>{{ index + 1 }}</td>
            <td>{{ credit.employee_id }}</td>
            <td>{{ credit.credit_type_id }}</td>
            <td>{{ credit.amount }}</td>
            <td><button @click="removeCredit(index)">Remove</button></td>
          </tr>
        </tbody>
      </table>
      <button class="btn btn-secondary mt-3" @click="backToForm">Back</button>
    </div>
  </div>
</template>

<script>
const token = localStorage.getItem('auth_token');

export default {
  props: {
    creditTypes: {
      type: Array,
      default: () => []
    },
    leaveTypes: {
      type: Array,
      default: () => []
    },
    employeeNo: {
      type: [String, Number],
      default: ''
    }
  },

  data() {
    return {
      upload: true,
      selectedCreditType: '',
      selectedLeaveType: '',
      creditsData: {
        credits: [],
        details: {}
      }
    };
  },

  methods: {
    triggerFileInput() {
      this.$refs.fileInput.click();
    },

    onFileUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (e) => {
        const text = e.target.result;
        const rows = text.split('\n').map(r => r.trim()).filter(r => r);

        if (!rows.length) return;

        // Assume first row is headers
        const headers = rows[0].split(',').map(h => h.trim());
        const dataRows = rows.slice(1);

        const credits = dataRows.map(row => {
          const values = row.split(',').map(v => v.trim());
          const obj = {};
          headers.forEach((h, i) => {
            obj[h.toLowerCase()] = values[i] || '';
          });
          return {
            employee_id: obj.employeeid || obj.employee_id || this.employeeNo,
            credit_type_id: this.selectedCreditType,
            leave_type_id: this.selectedCreditType === 'leave' ? this.selectedLeaveType : null,
            amount: obj.amount || '0'
          };
        });

        this.creditsData = {
          credits,
          details: { total: credits.length }
        };
        this.upload = false;
      };
      reader.readAsText(file);
    },

    removeCredit(index) {
      this.creditsData.credits.splice(index, 1);
    },

    backToForm() {
      this.upload = true;
      this.creditsData = { credits: [], details: {} };
      this.selectedCreditType = '';
      this.selectedLeaveType = '';
    },

    saveCredits() {
      if (!this.creditsData.credits.length) {
        alert("No credits to save!");
        return;
      }

      axios.post('/api/save', this.creditsData, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(() => {
        alert("Credits saved successfully!");
        this.backToForm();
      })
      .catch(err => {
        console.error(err);
        alert("Error saving credits!");
      });
    }
  }
};
</script>

<style scoped>
.uploading {
  margin: auto;
}
.upload-box {
  cursor: pointer;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th {
  background: #f0f0f0;
}
button {
  margin-right: 5px;
  padding: 4px 8px;
  cursor: pointer;
}
</style>
