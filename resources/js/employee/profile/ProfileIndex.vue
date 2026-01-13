<template>
  <div class="container-fluid position-relative py-4 employee-edit">
    <ChangePassModal ref="changePasswordMdl" />
    <div class="row g-4">
      <!-- LEFT PROFILE -->
      <div class="col-xl-3 col-lg-4">
        <div class="position-sticky" style="top: 28px">
          <div class="card border shadow-sm">
            <div class="card-body text-center p-4">
              <div class="position-relative d-inline-block mb-3">
                <img
                  :src="profilePreview"
                  class="rounded-circle border"
                  style="width: 140px; height: 140px; object-fit: cover;"
                />
                <label class="profile-upload btn btn-sm btn-light text-dark shadow">
                  <i class="fa-solid fa-camera"></i>
                  <input type="file" hidden accept="image/*" @change="onProfileChange" />
                </label>
                <div v-if="errors.profile" class="text-danger mt-1">
                  {{ errors.profile[0] }}
                </div>
              </div>

              <h5 class="fw-semibold mb-0">{{ form.firstname }} {{ form.lastname }}</h5>
              <div class="fw-light mb-0 fs-6">{{ user.email }}</div>
              <small class="text-muted">{{ form.employee_no }}</small>

              <hr class="my-4" />

              <div class="text-start">
                <small class="text-muted d-block mb-2">Profile Guidelines</small>
                <ul class="small text-muted ps-3 mb-0">
                  <li>JPG or PNG only</li>
                  <li>Max size 2MB</li>
                  <li>Square recommended</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="d-grid gap-2 mt-3">
            <button
              class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm rounded"
              @click="submitForm"
              :disabled="loading"
            >
              <i class="fa-solid fa-floppy-disk"></i>
              <span>{{ loading ? 'Saving...' : 'Save Changes' }}</span>
            </button>

            <button
              class="btn btn-warning d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm rounded"
              @click="openChangePassword"
            >
              <i class="fa-solid fa-key"></i>
              <span>Change Password</span>
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT FORM -->
      <div class="col-xl-9 col-lg-8">
        <!-- Basic Information -->
        <SectionCard icon="bi bi-person" title="Basic Information">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label required">Employee No</label>
              <input
                class="form-control"
                v-model="form.employee_no"
                disabled
                required
                :class="{ 'is-invalid': errors.employee_no }"
              />
              <div v-if="errors.employee_no" class="invalid-feedback">
                {{ errors.employee_no[0] }}
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label required">Biometric ID</label>
              <input
                class="form-control"
                v-model="form.biometrics_id"
                disabled
                required
                :class="{ 'is-invalid': errors.biometrics_id }"
              />
              <div v-if="errors.biometrics_id" class="invalid-feedback">
                {{ errors.biometrics_id[0] }}
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label required">First Name</label>
              <input
                class="form-control"
                v-model="form.firstname"
                required
                :class="{ 'is-invalid': errors.firstname }"
              />
              <div v-if="errors.firstname" class="invalid-feedback">
                {{ errors.firstname[0] }}
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Middle Name</label>
              <input
                class="form-control"
                v-model="form.middlename"
                :class="{ 'is-invalid': errors.middlename }"
              />
              <div v-if="errors.middlename" class="invalid-feedback">
                {{ errors.middlename[0] }}
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label required">Last Name</label>
              <input
                class="form-control"
                v-model="form.lastname"
                required
                :class="{ 'is-invalid': errors.lastname }"
              />
              <div v-if="errors.lastname" class="invalid-feedback">
                {{ errors.lastname[0] }}
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label">Suffix</label>
              <input
                class="form-control"
                v-model="form.suffix"
                :class="{ 'is-invalid': errors.suffix }"
              />
              <div v-if="errors.suffix" class="invalid-feedback">
                {{ errors.suffix[0] }}
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Personal Details -->
        <SectionCard icon="bi bi-heart-pulse" title="Personal Details">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label required">Birthday</label>
              <input
                type="date"
                class="form-control"
                v-model="form.birthday"
                required
                :class="{ 'is-invalid': errors.birthday }"
              />
              <div v-if="errors.birthday" class="invalid-feedback">
                {{ errors.birthday[0] }}
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label">Age</label>
              <input
                type="number"
                class="form-control"
                v-model="form.age"
                :class="{ 'is-invalid': errors.age }"
              />
              <div v-if="errors.age" class="invalid-feedback">
                {{ errors.age[0] }}
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label required">Civil Status</label>
              <select
                class="form-select"
                v-model="form.civil_status"
                required
                :class="{ 'is-invalid': errors.civil_status }"
              >
                <option :value="null">Select</option>
                <option>Single</option>
                <option>Married</option>
                <option>Widowed</option>
                <option>Separated</option>
              </select>
              <div v-if="errors.civil_status" class="invalid-feedback">
                {{ errors.civil_status[0] }}
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label required">Sex</label>
              <select
                class="form-select"
                v-model="form.sex"
                required
                :class="{ 'is-invalid': errors.sex }"
              >
                <option :value="null">Select</option>
                <option>Male</option>
                <option>Female</option>
              </select>
              <div v-if="errors.sex" class="invalid-feedback">
                {{ errors.sex[0] }}
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Blood Type</label>
              <input
                class="form-control"
                v-model="form.blood_type"
                :class="{ 'is-invalid': errors.blood_type }"
              />
              <div v-if="errors.blood_type" class="invalid-feedback">
                {{ errors.blood_type[0] }}
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Address Information -->
        <SectionCard icon="bi bi-geo-alt" title="Address Information">
          <h6 class="text-muted mb-3">Present Address</h6>
          <div class="row g-3 mb-4">
            <div v-for="f in presentAddress" :key="f.key" class="col-md-4">
              <label class="form-label">{{ f.label }}</label>
              <input
                class="form-control"
                v-model="form[f.key]"
                :class="{ 'is-invalid': errors[f.key] }"
              />
              <div v-if="errors[f.key]" class="invalid-feedback">
                {{ errors[f.key][0] }}
              </div>
            </div>
          </div>

          <h6 class="text-muted mb-3">Permanent Address</h6>
          <div class="row g-3">
            <div v-for="f in permanentAddress" :key="f.key" class="col-md-4">
              <label class="form-label">{{ f.label }}</label>
              <input
                class="form-control"
                v-model="form[f.key]"
                :class="{ 'is-invalid': errors[f.key] }"
              />
              <div v-if="errors[f.key]" class="invalid-feedback">
                {{ errors[f.key][0] }}
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Government IDs -->
        <SectionCard icon="bi bi-card-text" title="Government IDs">
          <div class="row g-3">
            <div v-for="f in govIds" :key="f.key" class="col-md-4">
              <label class="form-label">{{ f.label }}</label>
              <input
                class="form-control"
                v-model="form[f.key]"
                :class="{ 'is-invalid': errors[f.key] }"
              />
              <div v-if="errors[f.key]" class="invalid-feedback">
                {{ errors[f.key][0] }}
              </div>
            </div>
          </div>
        </SectionCard>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from "vue";
import axios from "axios";
import ChangePassModal from "./ChangePassModal.vue";

export default {
  name: "EmployeeEdit",
  components: {
    ChangePassModal,
    SectionCard: {
      props: ["icon", "title"],
      template: `
        <section class="card border shadow-sm mb-4">
          <div class="card-header bg-body-secondary fw-semibold">
            <i :class="icon + ' me-2'"></i> {{ title }}
          </div>
          <div class="card-body">
            <slot></slot>
          </div>
        </section>
      `,
    },
  },
  props: {
    id: { type: Number, required: true },
  },
  data() {
    return {
      form: {
        biometrics_id: "",
        employee_no: "",
        firstname: "",
        middlename: "",
        lastname: "",
        suffix: "",
        birthday: "",
        age: "",
        civil_status: null,
        sex: null,
        blood_type: "",
        profile: null,
        present_block: "",
        present_street: "",
        present_subdivision: "",
        present_barangay: "",
        present_city: "",
        present_province: "",
        present_zip: "",
        permanent_block: "",
        permanent_street: "",
        permanent_subdivision: "",
        permanent_barangay: "",
        permanent_city: "",
        permanent_province: "",
        permanent_zip: "",
        gsis_no: "",
        pagibig_no: "",
        philhealth_no: "",
        sss_no: "",
        tin_no: "",
        philsys_no: "",
      },
      user: {
        email: ""
      },
      loading: false,
      errors: {},
      profilePreview: ref("/images/default-avatar.png"),
      presentAddress: [
        { key: "present_block", label: "Block" },
        { key: "present_street", label: "Street" },
        { key: "present_subdivision", label: "Subdivision" },
        { key: "present_barangay", label: "Barangay" },
        { key: "present_city", label: "City" },
        { key: "present_province", label: "Province" },
        { key: "present_zip", label: "Zip Code" },
      ],
      permanentAddress: [
        { key: "permanent_block", label: "Block" },
        { key: "permanent_street", label: "Street" },
        { key: "permanent_subdivision", label: "Subdivision" },
        { key: "permanent_barangay", label: "Barangay" },
        { key: "permanent_city", label: "City" },
        { key: "permanent_province", label: "Province" },
        { key: "permanent_zip", label: "Zip Code" },
      ],
      govIds: [
        { key: "gsis_no", label: "GSIS No" },
        { key: "pagibig_no", label: "PAG-IBIG No" },
        { key: "philhealth_no", label: "PhilHealth No" },
        { key: "sss_no", label: "SSS No" },
        { key: "tin_no", label: "TIN No" },
        { key: "philsys_no", label: "PhilSys No" },
      ],
    };
  },
  mounted() {
    this.loadProfile();
  },
  methods: {
    async loadProfile() {
      try {
        const { data } = await axios.get(`/employee/profile`);
        Object.assign(this.form, data.personal);
        Object.assign(this.user, data.user);
        if (data.personal.profile) this.profilePreview = data.personal.profile;
      } catch (e) {
        alert("Failed to load employee data");
      }
    },
    onProfileChange(e) {
      const file = e.target.files[0];
      if (!file) return;
      this.form.profile = file;
      this.profilePreview = URL.createObjectURL(file);
    },
    async submitForm() {
        this.loading = true;
        this.errors = {};

        try {
            const payload = new FormData();

            // Loop through all form fields
            Object.entries(this.form).forEach(([key, value]) => {
                if (key === "profile") {
                    // Only append profile if it's a File (new upload)
                    if (value instanceof File) {
                        payload.append(key, value);
                    }
                    // If profile is a URL (unchanged), do NOT append
                } else {
                    // Append other fields, converting null to empty string
                    payload.append(key, value ?? "");
                }
            });

            const response = await axios.post(`/employee/profile`, payload, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            SuccesToast.fire({
                title: response.data.message || "Updated successfully",
            });
        } catch (error) {
            if (error.response?.status === 422) {
                this.errors = error.response.data.errors || {};
            } else {
                ErrorToast.fire({
                    title:
                        error.response?.data?.error ||
                        error.response?.data?.message ||
                        "An error occurred",
                });
            }
        } finally {
            this.loading = false;
        }
    },
    openChangePassword() {
        this.$refs.changePasswordMdl.open();
    }
  },
};
</script>

<style scoped>
.profile-upload {
  position: absolute;
  bottom: 0;
  right: 0;
  border-radius: 50%;
}

.card {
  border-radius: 12px;
}

.required::after {
  content: " *";
  color: #dc3545;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease-in-out;
}
</style>
