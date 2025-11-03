<template>
  <div>
    <div class="modal-body">
      <form @submit.prevent="submitForm">
        <div class="row">
          <!-- Name -->
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-body">
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
                v-model="form.suspensions[0].date"
                disabled
                class="form-control"
                :class="{ 'is-invalid': errors['suspensions.0.date'] }"
                required
              />
              <span class="text-danger" v-if="errors['suspensions.0.date']">{{ errors['suspensions.0.date'][0] }}</span>
            </div>
          </div>

          <!-- Description -->
          <div class="col-md-12">
            <div class="mb-3">
              <label class="form-label text-body">
                Description <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
                placeholder="Enter description"
                rows="4"
                required
              ></textarea>
              <span class="text-danger" v-if="errors.description">{{ errors.description[0] }}</span>
            </div>
          </div>

          <!-- Type -->
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-body">
                Type <span class="text-danger">*</span>
              </label>
              <select
                v-model="form.suspensions[0].type"
                class="form-select"
                :class="{ 'is-invalid': errors['suspensions.0.type'] }"
                required
              >
                <option disabled value="">Select Type</option>
                <option value="whole_day">Whole Day</option>
                <option value="half_day">Half Day</option>
              </select>
              <span class="text-danger" v-if="errors['suspensions.0.type']">{{ errors['suspensions.0.type'][0] }}</span>
            </div>
          </div>

          <!-- Shift -->
          <div class="col-md-6" v-if="form.suspensions[0].type === 'half_day'">
            <div class="mb-3">
              <label class="form-label text-body">
                Shift <span class="text-danger">*</span>
              </label>
              <select
                v-model="form.suspensions[0].shift"
                class="form-select"
                :class="{ 'is-invalid': errors['suspensions.0.shift'] }"
                required
              >
                <option disabled value="">Select Shift</option>
                <option value="morning">Morning</option>
                <option value="afternoon">Afternoon</option>
              </select>
              <span class="text-danger" v-if="errors['suspensions.0.shift']">{{ errors['suspensions.0.shift'][0] }}</span>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Actions -->
    <div class="modal-footer">
      <!-- Close -->
      <button
        v-if="!isEdit"
        @click="close"
        type="button"
        class="btn px-4 btn-danger"
      >
        <i class="me-2 fas fa-times"></i> Close
      </button>

      <!-- Delete (Edit only) -->
      <button
        v-if="isEdit"
        @click="deleteSuspension"
        type="button"
        class="btn btn-danger"
        :disabled="loadingDelete"
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
        {{ loading ? "Saving..." : isEdit ? "Update" : "Save" }}
      </button>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "SuspensionForm",
  props: {
    date: String,
    isEdit: Boolean,
    suspension_id: Number,
    suspension_date_id: Number
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
        this.form.suspensions[0].date = this.convertToDate(val);
      },
    },
    suspension_id: {
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
        description: "",
        suspensions: [
          {
            date: "",
            type: "whole_day",
            shift: "",
          },
        ],
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
        ? `/admin/service/suspensions/${this.suspension_id}`
        : `/admin/service/suspensions`;
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
            ? "Suspension updated successfully."
            : "Suspension added successfully.",
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

    async deleteSuspension() {
      if (!this.suspension_date_id) return;

      const confirmDelete = await Swal.fire({
        title: "Are you sure?",
        text: "This suspension will be permanently deleted.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
      });

      if (!confirmDelete.isConfirmed) return;

      this.loadingDelete = true;
      try {
        await axios.delete(`/admin/service/suspensions-dates/${this.suspension_date_id}`, {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        });

        Swal.fire("Deleted!", "Suspension has been deleted.", "success").then(() => {
          this.$emit("submit");
          this.close();
        });
      } catch (error) {
        Swal.fire(
          "Error",
          error.response?.data?.message || "Failed to delete suspension.",
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
