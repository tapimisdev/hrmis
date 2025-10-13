<template>
  <div class="modal-body">
    <form @submit.prevent="submitForm">
      <div class="row">
        <!-- Name -->
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label">
              Holiday Name <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              v-model="form.name"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Enter holiday name"
              required
            />
            <span class="text-danger" v-if="errors.name">{{ errors.name[0] }}</span>
          </div>
        </div>

        
         <!-- Type -->
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label">
              Is Repeating <span class="text-danger">*</span>
            </label>
            <select
              v-model="form.is_repeating"
              class="form-select"
              :class="{ 'is-invalid': errors.is_repeating }"
              required
            >
              <option :value="false">No</option>
              <option :value="true">Yes</option>
            </select>
            <span class="text-danger" v-if="errors.is_repeating">{{ errors.is_repeating[0] }}</span>
          </div>
        </div>

        <!-- Date -->
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label">
              Date <span class="text-danger">*</span>
            </label>
            <input
              type="date"
              v-model="form.date"
              disabled
              class="form-control"
              :class="{ 'is-invalid': errors.date }"
              required
            />
            <span class="text-danger" v-if="errors.date">{{ errors.date[0] }}</span>
          </div>
        </div>

        <!-- Type -->
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label">
              Type <span class="text-danger">*</span>
            </label>
            <select
              v-model="form.type"
              class="form-select"
              :class="{ 'is-invalid': errors.type }"
              required
            >
              <option value="" disabled>Select Type</option>
              <option value="regular">Regular</option>
              <option value="special_working">Special Working</option>
              <option value="special_non_working">Special Non-Working</option>
              <option value="company">Company</option>
            </select>
            <span class="text-danger" v-if="errors.type">{{ errors.type[0] }}</span>
          </div>
        </div>

        <!-- No Work Rate -->
        <div class="col-md-4">
          <div class="mb-3">
            <label class="form-label">No Work Rate</label>
            <input
              type="number"
              step="0.01"
              min="0"
              v-model="form.no_work_rate"
              class="form-control"
              :class="{ 'is-invalid': errors.no_work_rate }"
              placeholder="0.00"
            />
            <span class="text-danger" v-if="errors.no_work_rate">{{ errors.no_work_rate[0] }}</span>
          </div>
        </div>

        <!-- Work Rate -->
        <div class="col-md-4">
          <div class="mb-3">
            <label class="form-label">Work Rate</label>
            <input
              type="number"
              step="0.01"
              min="0"
              v-model="form.work_rate"
              class="form-control"
              :class="{ 'is-invalid': errors.work_rate }"
              placeholder="1.00"
            />
            <span class="text-danger" v-if="errors.work_rate">{{ errors.work_rate[0] }}</span>
          </div>
        </div>

        <!-- Overtime Rate -->
        <div class="col-md-4">
          <div class="mb-3">
            <label class="form-label">Overtime Rate</label>
            <input
              type="number"
              step="0.01"
              min="0"
              v-model="form.overtime_rate"
              class="form-control"
              :class="{ 'is-invalid': errors.overtime_rate }"
              placeholder="1.00"
            />
            <span class="text-danger" v-if="errors.overtime_rate">{{ errors.overtime_rate[0] }}</span>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Actions -->
  <div class="modal-footer">
    <button @click="close" type="button" class="btn py-3 px-4 btn-outline-danger">
      <i class="me-2 fas fa-times"></i> Close
    </button>

    <button
      type="submit"
      form="form"
      :disabled="loading"
      @click="submitForm"
      class="btn py-3 px-4 btn-primary"
    >
      <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
      <i v-else class="me-2 fas fa-save"></i>
      {{ loading ? 'Saving...' : 'Save' }}
    </button>
  </div>
</template>

<script>
export default {
  name: 'HolidayForm',
  props: {
    date: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      form: {
        name: '',
        date: '',
        type: 'regular',
        no_work_rate: 0.0,
        work_rate: 1.0,
        overtime_rate: 1.0,
        is_repeating: false,
      },
      errors: {},
      loading: false,
    };
  },
  mounted() {
    this.form.date = this.convertToDate();
  },
  watch: {
    date() {
      this.form.date = this.convertToDate();
    },
  },
  methods: {
    submitForm() {
      this.errors = {};
      this.loading = true;

      // Simulate submission delay
      setTimeout(() => {
        this.loading = false;
        console.log('Form submitted:', this.form);
        this.close();
      }, 1000);
    },

    convertToDate() {
      if (!this.date) return '';
      const d = new Date(this.date);
      return isNaN(d) ? '' : d.toISOString().split('T')[0];
    },

    close() {
      $('#myModal').modal('hide');
    },
  },
};
</script>
