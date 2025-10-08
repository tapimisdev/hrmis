<template>
  <div class="position-relative">
    <div class="row mb-3">
      <div
        v-for="(field, index) in selects"
        :key="index"
        class="col-12 col-md-2 mb-3"
      >
        <div class="d-flex gap-2">
          <label class="form-label fw-bold">{{ field.label }}</label>
          <span v-if="errors[field.model]" class="text-danger small">
            ({{ errors[field.model][0] }})
          </span>
        </div>
        <select
          class="form-select"
          v-model="form[field.model]"
          :class="{ 'is-invalid': errors[field.model] }"
        >
          <option value="">{{ field.placeholder }}</option>
          <option
            v-for="(option, i) in field.options"
            :key="i"
            :value="option.value ?? option"
          >
            {{ option.text ?? option }}
          </option>
        </select>
      </div>

      <div class="col-12 col-md-4 mb-3 d-flex align-items-end">
        <button
          type="button"
          id="submit-button"
          class="btn btn-outline-primary text-uppercase"
          @click="search"
          :disabled="loading"
        >
            <span>
              <i class="fas fa-search px-1"></i> Search
            </span>
        </button>
      </div>
    </div>
  </div>
  
</template>

<script>
import LoaderVue from './../../components/LoaderVue.vue';
export default {
  components: { LoaderVue },
  data() {
    const token = localStorage.getItem("auth_token");

    const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
    const months = [
      "January", "February", "March", "April", "May", "June",
      "July", "August", "September", "October", "November", "December"
    ];

    return {
      token,
      loading: false,
      errors: {},
      form: {
        year: new Date().getFullYear(),
        month: new Date().getMonth() + 1,
        cutoff: "",
        status: ""
      },
      selects: [
        { label: "Year", model: "year", placeholder: "-- CHOOSE YEAR --", options: years },
        {
          label: "Month",
          model: "month",
          placeholder: "-- CHOOSE MONTH --",
          options: months.map((m, i) => ({ text: m, value: i + 1 }))
        },
        {
          label: "Cutoff",
          model: "cutoff",
          placeholder: "-- CHOOSE CUTOFF --",
          options: [
            { text: "1st Cutoff", value: "first_cutoff" },
            { text: "2nd Cutoff", value: "second_cutoff" }
          ]
        },
        {
          label: "Status",
          model: "status",
          placeholder: "-- CHOOSE STATUS --",
          options: [
            { text: "Draft", value: "draft" },
            { text: "Pending Approval", value: "pending_approval" },
            { text: "Approved", value: "approved" },
            { text: "For Releasing", value: "for_releasing" },
            { text: "Completed", value: "completed" },
            { text: "Cancelled", value: "cancelled" }
          ]
        }
      ]
    };
  },

  methods: {
    fetchData(stateKey, url, useDataWrapper = false) {
      axios
        .get(url, {
          headers: { Authorization: `Bearer ${this.token}` }
        })
        .then((response) => {
          this[stateKey] = useDataWrapper ? response.data.data : response.data;
        })
        .catch((error) => console.error(error));
    },

    search() {
      this.errors = {};
      this.loading = true;
      this.$emit("payroll-list", [], this.loading);
      axios
        .post("/api/payroll/salary", this.form, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${this.token}`
          }
        })
        .then((response) => {
          console.log(response.data);
          this.$emit("payroll-list", response.data.data, false);
          this.loading = false
        })
        .catch((error) => {
          if (error.response?.status === 401) {
          } else if (error.response?.status === 422) {
            this.errors = error.response.data.errors;
          } else {
            Swal.fire("Error", error.response?.data?.message || "Something went wrong.", "error");
          }
          this.loading = false
          this.$emit("payroll-list", [], false);
        })
    }
  }
};
</script>
