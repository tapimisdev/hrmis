<template>
  <div>
    <div class="modal-body">
      <form @submit.prevent="submitForm">
        <div class="row">
          <!-- Holiday Name -->
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-body">
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

          <!-- Date -->
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-body">
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
              <label class="form-label text-body">
                Type <span class="text-danger">*</span>
              </label>
              <select
                v-model="form.type"
                class="form-select"
                :class="{ 'is-invalid': errors.type }"
                required
              >
                <option disabled value="">Select Type</option>
                <option value="regular">Regular</option>
                <option value="special_working">Special Working</option>
                <option value="special_non_working">Special Non-Working</option>
                <option value="company">Company</option>
              </select>
              <span class="text-danger" v-if="errors.type">{{ errors.type[0] }}</span>
            </div>
          </div>

          <!-- Repeat Yearly -->
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-body">
                Repeat Yearly <span class="text-danger">*</span>
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

          <!-- Rates -->
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label text-body">No Work Rate (%)</label>
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

          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label text-body">Work Rate (%)</label>
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

          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label text-body">Overtime Rate (%)</label>
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
      <!-- Close -->
      <button v-if="!isEdit" @click="close" type="button" class="btn px-4 btn-danger">
        <i class="me-2 fas fa-times"></i> Close
      </button>

      <!-- Delete (only when editing) -->
      <button
        v-if="isEdit"
        @click="deleteHoliday"
        type="button"
        class="btn btn-danger"
        :disabled="loading"
      >
        <i v-if="loadingDelete" class="fas fa-spinner fa-spin me-2"></i>
        <i v-else class="me-2 fas fa-trash"></i>
        {{ loadingDelete ? "Deleting..." : "Delete" }}
      </button>

      <!-- Save / Update -->
      <button
        :disabled="loading"
        @click="submitForm"
        class="btn"
        :class="isEdit ? 'btn-secondary' : 'btn-primary'"
      >
        <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
        <i v-else class="me-2 fas fa-save"></i>
        {{ loading ? 'Saving...' : isEdit ? 'Update' : 'Save' }}
      </button>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "HolidayForm",
  props: {
    date: String,
    isEdit: Boolean,
    holiday_id: Number,
  },
  data() {
    return {
      token: localStorage.getItem("auth_token"),
      form: this.defaultForm(),
      errors: {},
      loading: false,
      loadingDelete: false,
    };
  },
  watch: {
    date: {
      immediate: true,
      handler(val) {
        this.form.date = this.convertToDate(val);
      },
    },
    holiday_id: {
      immediate: true,
      handler() {
        this.errors = {};
      },
    },
  },
  emits: ["close", "submit", "delete"],
  methods: {
    defaultForm() {
      return {
        name: "",
        date: "",
        type: "",
        is_repeating: false,
        no_work_rate: 0.0,
        work_rate: 1.0,
        overtime_rate: 1.0,
      };
    },

    convertToDate(dateStr) {
      if (!dateStr) return "";
      const d = new Date(dateStr);
      return isNaN(d) ? "" : d.toISOString().split("T")[0];
    },

    async submitForm() {
      this.loading = true;
      this.errors = {};

      const url = this.isEdit
        ? `/admin/maintenance/holiday/${this.holiday_id}`
        : `/admin/maintenance/holiday`;
      const method = this.isEdit ? "put" : "post";

      try {
        await axios[method](url, this.form, {
          headers: {
            Authorization: `Bearer ${this.token}`,
            Accept: "application/json",
          },
        });

        Swal.fire({
          title: "Success!",
          text: this.isEdit
            ? "Holiday updated successfully."
            : "Holiday added successfully.",
          icon: "success",
        }).then(() => {
          this.$emit("submit");
          this.close();
          if (!this.isEdit) this.resetForm();
        });
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          Swal.fire(
            "Error",
            error.response?.data?.message || "Something went wrong.",
            "error"
          );
        }
      } finally {
        this.loading = false;
      }
    },

    async deleteHoliday() {
      if (!this.holiday_id) return;

      const confirmDelete = await Swal.fire({
        title: "Are you sure?",
        text: "This holiday will be permanently deleted.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
      });

      if (!confirmDelete.isConfirmed) return;

      this.loadingDelete = true;
      try {
        await axios.delete(`/admin/maintenance/holiday/${this.holiday_id}`);

        Swal.fire("Deleted!", "Holiday has been deleted.", "success").then(() => {
          this.$emit("submit");
          this.close();
        });
      } catch (error) {
        Swal.fire(
          "Error",
          error.response?.data?.message || "Failed to delete holiday.",
          "error"
        );
      } finally {
        this.loadingDelete = false;
      }
    },

    close() {
      $("#myModal").modal("hide");
    },

    resetForm() {
      this.form = this.defaultForm();
    },
  },
};
</script>
