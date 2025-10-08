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
            :class="{
              active: currentStep === index,
              completed: index < currentStep
            }"
            @click="goToStep(index)"
          >
            <!-- Circle -->
            <div class="circle flex-shrink-0 me-3 mt-1">
              <i
                v-if="index < currentStep"
                class="fas fa-check text-white"
              ></i>
              <span v-else>{{ index + 1 }}</span>
            </div>

            <!-- Step Label -->
            <div>
              <h6 class="fw-bold mb-1">{{ step.label }}</h6>
              <p class="small mb-0 text-muted">{{ step.desc }}</p>
            </div>

            <!-- Connector Line -->
            <div
              v-if="index < steps.length - 1"
              class="connector position-absolute start-4 top-100 translate-middle-x"
            ></div>
          </div>
        </div>
      </div>

      <!-- Step Content -->
      <div class="col-12 col-md-8">
        <div class="card border-0 shadow-lg p-4 animate-step">
          <component :is="steps[currentStep].component" v-model="form" />

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
              @click="nextStep"
            >
              Next <i class="fas fa-arrow-right ms-2"></i>
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
import ReviewPayroll from "./steps/ReviewPayroll.vue";
import ApprovalPayroll from "./steps/ApprovalPayroll.vue";

export default {
  data() {
    return {
      currentStep: 0,
      form: { name: "", period: "", cutoff: "", status: "" },
      steps: [
        {
          label: "Create Payroll",
          desc: "Set the basic payroll details.",
          component: CreatePayroll
        },
        {
          label: "Review Details",
          desc: "Verify and adjust employee data.",
          component: ReviewPayroll
        },
        {
          label: "Approval & Submission",
          desc: "Finalize and submit the payroll for approval.",
          component: ApprovalPayroll
        }
      ]
    };
  },
  methods: {
    nextStep() {
      if (this.currentStep < this.steps.length - 1) this.currentStep++;
    },
    prevStep() {
      if (this.currentStep > 0) this.currentStep--;
    },
    goToStep(index) {
      if (index <= this.currentStep) this.currentStep = index;
    },
    submitForm() {
      console.log("✅ Final Payroll Data:", this.form);
      alert("Payroll process completed successfully!");
    }
  }
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

    .circle {
      background-color: #efeff0 !important;
    }

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
    }

    .circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: lighten($border-color, 10%);
      color: $text-muted;
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

