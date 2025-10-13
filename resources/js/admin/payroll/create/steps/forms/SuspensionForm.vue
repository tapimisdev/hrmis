<template>
  <div class="modal-body">
    <form @submit.prevent="submitForm">
      <div class="row">
        <!-- From Time -->
        <div class="col-md-10">
          <div class="mb-3">
            <label class="form-label">
              Name <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              v-model="form.name"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Enter name"
              required
            />
            <span class="text-danger" v-if="errors.from_time">{{ errors.from_time[0] }}</span>
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
              class="form-control"
              :class="{ 'is-invalid': errors.date }"
              disabled
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
              <option value="whole_day">Whole Day</option>
              <option value="half_day">Half Day</option>
            </select>
            <span class="text-danger" v-if="errors.type">{{ errors.type[0] }}</span>
          </div>
        </div>

        <!-- From Time -->
        <div class="col-md-6" v-if="form.type === 'half_day'">
          <div class="mb-3">
            <label class="form-label">
              From Time
            </label>
            <input
              type="time"
              v-model="form.from_time"
              class="form-control"
              :class="{ 'is-invalid': errors.from_time }"
              required
            />
            <span class="text-danger" v-if="errors.from_time">{{ errors.from_time[0] }}</span>
          </div>
        </div>

        <!-- To Time -->
        <div class="col-md-6" v-if="form.type === 'half_day'">
          <div class="mb-3">
            <label class="form-label">
              To Time
            </label>
            <input
              type="time"
              v-model="form.to_time"
              class="form-control"
              :class="{ 'is-invalid': errors.to_time }"
              required
            />
            <span class="text-danger" v-if="errors.to_time">{{ errors.to_time[0] }}</span>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Actions -->
  <div class="modal-footer">
    <button @click="close" class="btn py-3 px-4 btn-outline-danger">
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
  name: 'SuspensionForm',
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
        type: 'whole_day',
        from_time: '',
        to_time: '',
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
    }
  },
  methods: {
    submitForm() {
      this.errors = {};
      this.loading = true;

      this.loading = false;
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
