<template>
  <div class="container py-4">
    <div class="row g-4">
      <!-- Step Navigation -->
      <div class="col-12 col-md-4">
        <div class="vertical-stepper">
          <div
            v-for="(step, index) in steps"
            :key="index"
            class="step-item d-flex align-items-start position-relative p-3 rounded"
            :class="{ active: currentStep === index, completed: index < currentStep }"
            @click="goToStep(index)"
          >
            <!-- Step Icon -->
            <div class="circle flex-shrink-0 me-3 mt-1">
              <i v-if="index < currentStep" class="fas fa-check text-white"></i>
              <span v-else>{{ index + 1 }}</span>
            </div>

            <!-- Step Label -->
            <div>
              <h6 class="fw-bold mb-1">{{ step.label }}</h6>
              <p class="small mb-0 text-muted">{{ step.desc }}</p>
            </div>

            <!-- Connector -->
            <div
              v-if="index < steps.length - 1"
              class="connector position-absolute start-4 top-100 translate-middle-x"
            ></div>
          </div>
        </div>
      </div>

      <!-- Step Content -->
      <div class="col-12 col-md-8">
        <div class="card border-0 shadow-lg p-4 animate-step position-relative">
          <LoaderVue :visible="loading" status="loading" message="loading, please wait..." />
          <keep-alive>
            <component 
              :is="steps[currentStep].component" 
              v-model="form"
              :errors
              :employees
            />
          </keep-alive>

          <div class="d-flex justify-content-between mt-3 border-top pt-4">
            <button
              class="btn btn-outline-secondary px-4 py-2 rounded-pill"
              :disabled="currentStep === 0"
              @click="prevStep"
            >
              <i class="fas fa-arrow-left me-2"></i> Back
            </button>

            <button
              v-if="currentStep < steps.length - 1"
              class="btn btn-outline-primary px-4 py-2 rounded-pill"
              :disabled="loading"
              @click="nextStep"
            >
              <span v-if="loading">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Loading...
              </span>
              <span v-else>
                Next <i class="fas fa-arrow-right ms-2"></i>
              </span>
            </button>

            <button
              v-else
              class="btn btn-primary px-4 py-2 rounded-pill"
              @click="submitForm"
            >
              <i class="fas fa-cog me-2"></i> Generate
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CreatePayroll from "./steps/CreatePayroll.vue";
import AdjustmentPayroll from "./steps/AdjustmentPayroll.vue";
import ReviewPayroll from "./steps/ReviewPayroll.vue";
import ApprovalPayroll from "./steps/ApprovalPayroll.vue";
import LoaderVue from "../../../components/LoaderVue.vue";

export default {
  components: { CreatePayroll, AdjustmentPayroll, ReviewPayroll, ApprovalPayroll, LoaderVue },
  data() {
    return {
      token: localStorage.getItem("auth_token"),
      currentStep: 0,
      loading: false,
      errors: {},
      employees: [],
      form: {
        label: "",
        cutoff: "",
        employment_type_id: "",
        date: new Date().toISOString().split("T")[0],
      },
      steps: [
        {
          label: "Create Payroll",
          desc: "Set the basic payroll details.",
          component: CreatePayroll,
        },
        {
          label: "Employee Eligibility Review",
          desc: "View eligible and ineligible employees with remarks.",
          component: ReviewPayroll,
        },
        {
          label: "Suspensions & Holidays",
          desc: "Record any suspensions or holidays affecting payroll.",
          component: AdjustmentPayroll,
        },
        {
          label: "Approval & Submission",
          desc: "Finalize and submit payroll for approval.",
          component: ApprovalPayroll,
        },
      ],
    };
  },
  methods: {
    async nextStep() {
      if (this.currentStep === 0) {
        const valid = await this.validateAndGetReview();
        if (!valid) return;
      }
      if (this.currentStep < this.steps.length - 1) this.currentStep++;
    },
    prevStep() {
      if (this.currentStep > 0) this.currentStep--;
    },
    goToStep(index) {
      if (index <= this.currentStep) this.currentStep = index;
    },
    async validateAndGetReview() {
      this.loading = true;
      this.errors = {};
      const payload = { ...this.form };

      try {
        const res = await axios.post(
          "/api/payroll/validate-and-fetch-employees",
          payload,
          {
            headers: {
              Accept: "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
        console.log(res.data.data)
        this.employees = res.data.data;
        return true;
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
        } else {
          if(error.response?.data?.message === 'No employees found for this employment type.') {
           Swal.fire(
              "No Employees Found",
              error.response?.data?.message || "Please add employees to this payroll before proceeding.",
              "info"
            );
          } else {
            Swal.fire(
              "Error",
              error.response?.data?.message || "Something went wrong.",
              "error"
            );
          }
        }
        return false;
      } finally {
        this.loading = false;
      }
    },
    submitForm() {
      Swal.fire("Payroll Generated", "Payroll submitted successfully.", "success");
    },
  },
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';
$border-color: #e9ecef;
$text-muted: #6c757d;
$bg-hover: #f8f9fa;

.vertical-stepper {
  position: relative;
  .step-item {
    transition: all 0.3s ease;
    cursor: pointer;

    &:hover {
      transform: translateX(3px);
    }
    &.active {
      
      .circle {
        background-color: $primary !important;
        color: #fff;
        box-shadow: 0 0 10px rgba($primary, 0.4);
      }

      h6, p {
        color: $primary;
      }
    }

    &.completed {

      .circle {
        background-color: $success !important;
        color: #fff;
      }

      h6, p {
        color: $success;
      }
      
      .connector {
        background-color: $success;
      }
    }

    .circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: lighten($border-color, 10%);
      background-color: #efeff0;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: 600;
      transition: all 0.3s ease;
      flex-shrink: 0;
      margin-right: 0.75rem;
    }

    .connector {
      width: 3px;
      height: 80px;
      background-color: $border-color;
      position: absolute;
      left: 32px;
      top: 30% !important;
      transform: translateY(-50%);
      z-index: -1;
    }
  }
}

/* --- Animation --- */
.animate-step {
  animation: fadeInUp 0.4s ease;

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(15px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
}
</style>
