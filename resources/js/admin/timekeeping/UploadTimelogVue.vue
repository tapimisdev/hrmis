<template>

  <div class="card shadow p-3">
    <LoaderVue :visible="loading" status="uploading" message="Uploading, please wait..." />
    <div class="card-header bg-transparent">
        <h4 class="mb-1 pt-3 text-uppercase fw-medium">
            Import Form
        </h4>
    </div>
    
    <div class="card-body">
      <form @submit.prevent="submitForm">
        <div class="row">
          <!-- Shift -->
          <div class="col-12 col-md-6 mb-3">
              <FormSkeletonVue v-if="loading" :rows="1" :columns="1"/>
              <div v-else>
                  <label for="shift" class="mb-3">Shift</label>
                  <select v-model="form.shift" 
                          id="shift" 
                          class="form-select text-uppercase"
                          :class="{ 'is-invalid': errors.shift }">
                      <option value="">- CHOOSE -</option>
                      <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                          {{ shift.name }}
                      </option>
                  </select>
                  <span class="text-danger" v-if="errors.shift">{{ errors.shift[0] }}</span>
              </div>
          </div>

          <!-- Work Schedule -->
          <div class="col-12 col-md-6 mb-4">
              <FormSkeletonVue v-if="loading" :rows="1" :columns="1"/>
              <div v-else>
                  <label for="schedule" class="mb-3">Work Schedule</label>
                  <select v-model="form.schedule" 
                          id="schedule" 
                          class="form-select text-uppercase"
                          :class="{ 'is-invalid': errors.schedule }">
                      <option value="">- CHOOSE -</option>
                      <option v-for="schedule in schedules" :key="schedule.id" :value="schedule.id">
                          {{ schedule.name }}
                      </option>
                  </select>
                  <span class="text-danger" v-if="errors.schedule">{{ errors.schedule[0] }}</span>
              </div>
          </div>

          <!-- File Upload -->
          <div class="col-12 mb-3">
            <FormSkeletonVue v-if="loading" :rows="1" :columns="3"/>
            <div v-else>
              <label class="form-label fw-bold">Upload File (CSV or Excel)</label>
              <div 
                  class="upload-box text-center p-5 border border-2 border-dashed rounded-3" 
                  @click="triggerFileInput"
                  :class="{ 'is-invalid-file': errors.file }"
              >
                  <i class="fa-solid fa-file-arrow-up fa-2x mb-3 text-primary"></i>
                  <p class="mb-1 fw-semibold">Click or drag file to upload</p>
                  <small class="text-muted">Supported: CSV, XLS, XLSX</small>

                  <!-- Hidden file input -->
                  <input 
                      type="file" 
                      ref="fileInput"
                      class="d-none"
                      accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                      @change="handleFileUpload"
                  />
              </div>
              <span class="text-danger" v-if="errors.file">{{ errors.file[0] }}</span>


              <!-- Show filename if selected -->
              <div v-if="form.file" class="mt-3 d-flex justify-content-center">
                <span class="badge bg-success p-2">
                  <i class="fa-solid fa-check me-1"></i> {{ form.file.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" class="btn btn-primary px-4 py-3">
              <i class="fa-solid fa-upload me-2"></i> Upload
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
const token = localStorage.getItem('auth_token');
import FormSkeletonVue from '../../components/FormSkeletonVue.vue';
import LoaderVue from '../../components/LoaderVue.vue';
export default {
  components: { FormSkeletonVue, LoaderVue },
  data() {
    return {
      shifts: [],
      schedules: [],
      loading: false,
      form: {
          shift: "",
          schedule: "",
          file: null,     
      },
      errors: {},
    }
  },
  created() {
    this.fetchData('shifts', '/api/shifts', true);
    this.fetchData('schedules', '/api/work-schedules', true);
  },
  methods: {
    fetchData(stateKey, url, useDataWrapper = false) {
      axios.get(url, {
          headers: { Authorization: `Bearer ${token}` }
      }).then(response => {
          this[stateKey] = useDataWrapper ? response.data.data : response.data;
      });
    },
    triggerFileInput() {
      this.$refs.fileInput.click();
    },
    handleFileUpload(event) {
      this.form.file = event.target.files[0];
    },
    async submitForm() {
      this.loading = true;
      this.errors = {};

      try {
          // Example: create FormData for upload
          let formData = new FormData();
          formData.append("shift", this.form.shift);
          formData.append("schedule", this.form.schedule);
          formData.append("file", this.form.file);

          const res = await axios.post('/api/timekeeping/import-timelogs', formData, {
              headers: { 
                  'Accept': 'application/json',
                  'Authorization': `Bearer ${token}`
              }
          });
          Swal.fire({
              title: "Success!",
              text: res.data.message ?? "timelog successfully imported.",
              icon: "success"
          })
      } catch (error) {
          if (error.response?.status === 422) {
              this.errors = error.response.data.errors;
          } else {
              Swal.fire("Error", error.response?.data?.message || "Something went wrong.", "error");
          }
      } finally {
          this.loading = false;
      }
    },
  }
}
</script>

<style lang="scss" scoped>
@import '../../../sass/variables';
.upload-box {
  cursor: pointer;
  &:hover {
    transition: all 0.3s ease;
    background-color: $light;
  }
}

.is-invalid-file {
  border-color: $danger !important; 
  color: $danger !important; 
  i {
    color: $danger !important; 
  }
} 
</style>