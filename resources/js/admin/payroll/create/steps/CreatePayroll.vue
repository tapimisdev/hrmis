<template>
  <div class="">
    <h5 class="mb-3 text-primary text-uppercase">Step 1: Create Payroll</h5>
    <p class="text-muted mb-4">Fill in all payroll details and review before sending it for approval.</p>
    <div class="row g-3">
      <!-- Label Field -->
      <div class="col-12 col-md-5">
        <label class="form-label fw-bold">Label</label>
        <input
          type="text"
          class="form-control"
          v-model="localForm.label"
          :class="{ 'is-invalid': errors.label }"
        />
        <small v-if="errors.label" class="text-danger">{{ errors.label[0] }}</small>
      </div>

      <!-- Date Field -->
      <div class="col-12 col-md-6">
        <label class="form-label fw-bold">Date</label>
        <input
          type="date"
          class="form-control"
          v-model="localForm.date"
          :class="{ 'is-invalid': errors.date }"
        />
        <small v-if="errors.date" class="text-danger">{{ errors.date[0] }}</small>
      </div>

      <!-- Cutoff Select -->
      <div class="col-12 col-md-7">
        <label class="form-label fw-bold">Cutoff</label>
        <select
          class="form-select"
          v-model="localForm.cutoff"
          :class="{ 'is-invalid': errors.cutoff }"
        >
          <option value="">-- CHOOSE CUTOFF --</option>
          <option value="first_cutoff">1st Cutoff</option>
          <option value="second_cutoff">2nd Cutoff</option>
        </select>
        <small v-if="errors.cutoff" class="text-danger">{{ errors.cutoff[0] }}</small>
      </div>

      <!-- Employment Type Select (Static) -->
      <div class="col-12 col-md-5">
        <label class="form-label fw-bold">Employment Type</label>
        <select
          class="form-select"
          v-model="localForm.employment_type_id"
          :class="{ 'is-invalid': errors.employment_type_id }"
        >
          <option value="">-- CHOOSE EMPLOYMENT TYPE --</option>
          <option
            v-for="(type, index) in employment_types"
            :key="index"
            :value="type.id ?? type"
          >
            {{ type.name }}
          </option>
        </select>
        <small v-if="errors.employment_type_id" class="text-danger">{{ errors.employment_type_id[0] }}</small>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CreatePayroll',
  props: {
    modelValue: Object,
    errors: Object
  },
  emits: ["update:modelValue"],
  data() {
    const token = localStorage.getItem("auth_token");
    return {
      token,
      loading: false,
      localForm: {
        label: '',
        cutoff: '',
        employment_type_id: '',
        date: new Date().toISOString().split("T")[0],
      },
      employment_types: [],
    };
  },
  watch: {
    localForm: {
      deep: true,
      handler(newVal) {
        this.$emit("update:modelValue", newVal);
      },
    },
  },
  created() {
    this.fetchData('employment_types', '/api/get-employment-types', true);
  },
  methods: {
    fetchData(stateKey, url, useDataWrapper = false) {
      axios.get(url, { headers: { Authorization: `Bearer ${this.token}` } })
        .then(response => {
          this[stateKey] = useDataWrapper ? response.data.data : response.data;
        });
    },
  }
};
</script>