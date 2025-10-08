<template>
  <div>
    <h5>Step 1: Create Payroll</h5>
    <p class="text-muted">Fill in all payroll details and review before sending it for approval.</p>
    <div class="row mb-3">
      <!-- Date Field -->
      <div class="col-12 col-md-6 mb-3">
        <div class="d-flex gap-2">
          <label class="form-label fw-bold">Label</label>
          <span v-if="errors.label" class="text-danger small">
            ({{ errors.label[0] }})
          </span>
        </div>

        <input
          type="text"
          class="form-control"
          v-model="form.label"
          :class="{ 'is-invalid': errors.label }"
        />
      </div>

      <!-- Date Field -->
      <div class="col-12 col-md-6 mb-3">
        <div class="d-flex gap-2">
          <label class="form-label fw-bold">Date</label>
          <span v-if="errors.date" class="text-danger small">
            ({{ errors.date[0] }})
          </span>
        </div>

        <input
          type="date"
          class="form-control"
          v-model="form.date"
          :class="{ 'is-invalid': errors.date }"
        />
      </div>

      <!-- Dynamic Select Fields -->
      <div
        v-for="(field, index) in selects"
        :key="index"
        class="col-12 col-md-6 mb-3"
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
    </div>
  </div>
</template>

<script>
export default {
  data() {
    const token = localStorage.getItem("auth_token");
    return {
      token,
      loading: false,
      errors: {},
      form: {
        year: new Date().getFullYear(),
        month: new Date().getMonth() + 1,
        cutoff: "",
        status: "",
        date: new Date().toISOString().split("T")[0], // default today's date
      },
      selects: [
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
            { text: "Pending Approval", value: "pending" },
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
    submitPayroll() {
      this.errors = {};
      this.loading = true;

      axios.post("/api/payroll/salary", this.form, {
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${this.token}`
        }
      })
      .then((response) => {
        
      })
      .catch((error) => {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          Swal.fire("Error", error.response?.data?.message || "Something went wrong.", "error");
        }
      }).finally(() => {
        this.loading = false;
      });
    }
  }
};
</script>
