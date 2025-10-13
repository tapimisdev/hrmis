<template>
  <div>
    <h5 class="mb-1 text-primary text-uppercase">Step 2: Employee Eligibility Review</h5>
    <p class="text-muted m-0 mt-2"><strong>Payroll Label:</strong> {{ modelValue.label }}</p>
    <p class="text-muted m-0 mb-3">
      <strong>Period:</strong> {{ payrollPeriod }}
    </p>
    <div class="accordion" id="eligibilityAccordion">
      <!-- Eligible Employees -->
      <div class="accordion-item mb-3 shadow-sm border-0 rounded">
        <h2 class="accordion-header" id="headingEligible">
          <button
            class="accordion-button bg-success bg-opacity-10 text-success fw-semibold"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#collapseEligible"
            aria-expanded="true"
            aria-controls="collapseEligible"
          >
            <i class="fas fa-circle-check me-2"></i>
            Eligible Employees ({{ employees.eligible?.length ?? 0 }})
          </button>
        </h2>
        <div
          id="collapseEligible"
          class="accordion-collapse collapse show"
          aria-labelledby="headingEligible"
          data-bs-parent="#eligibilityAccordion"
        >
          <div class="accordion-body">
            <div
                v-if="employees.eligible.length"
                class="list-group list-group-flush"
              >
              <div
                  v-for="employee in employees.eligible"
                  :key="employee.id"
                  class="list-group-item d-flex justify-content-between align-items-center"
                >
                <div>
                  <strong>{{ employee.suffix }} {{ employee.firstname }} {{ employee.middlename }} {{ employee.lastname }}</strong>
                  <div class="text-muted small">
                    {{ employee.position }} — {{ employee.division }}
                  </div>
                  <div v-for="remark in employee.remarks">
                    <a v-if="remark.value != 0"
                       class="text-underline"
                       href="#"
                       @click.prevent="openRemark(remark.url)">
                      <span class="text-warning small" >{{ remark.text }}</span>
                    </a>
                  </div>  
                </div>
                
                <span class="badge bg-success">Eligible</span>
              </div>
            </div>
            <p v-else class="text-muted mb-0">No eligible employees found.</p>
          </div>
        </div>
      </div>

      <!-- Ineligible Employees -->
      <div class="accordion-item mb-3 shadow-sm border-0 rounded">
        <h2 class="accordion-header" id="headingIneligible">
          <button
            class="accordion-button collapsed bg-danger bg-opacity-10 text-danger fw-semibold"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#collapseIneligible"
            aria-expanded="false"
            aria-controls="collapseIneligible"
          >
              <i class="fas fa-circle-xmark me-2"></i>
              Ineligible Employees ({{ employees.not_eligible?.length ?? 0 }})
          </button>
        </h2>
        <div
          id="collapseIneligible"
          class="accordion-collapse collapse"
          aria-labelledby="headingIneligible"
          data-bs-parent="#eligibilityAccordion"
        >
          <div class="accordion-body">
            <div
              v-if="employees.not_eligible"
              class="list-group list-group-flush"
            >
              <div
                v-for="employee in employees.not_eligible"
                :key="employee.id"
                class="list-group-item"
              >
                <div class="fw-semibold">{{ employee.suffix }} {{ employee.firstname }} {{ employee.middlename }} {{ employee.lastname }}</div>
                <div class="text-muted small">
                  {{ employee.position }} — {{ employee.division }}
                </div>
                <div class=" mt-1">
                  <a
                    class="text-danger small"
                    href="#"
                    @click.prevent="openRemark(employee.remarks[0].url)"
                  >
                    <strong>Reason:</strong> {{ employee.remarks[0].text }}
                  </a>
                </div>
              </div>
            </div>
            <p v-else class="text-muted mb-0">No ineligible employees found.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ReviewPayroll',
  props: {
    modelValue: [String, Number, Object, Array],
    employees: {
      type: Object,
      default: () => ({ eligible: [], not_eligible: [] })
    }
  },
  data() {
    return {
      
    };
  },
  methods: {
   openRemark(url) {
      if (!url) return;
      window.open(
        url,
        '_blank',
        'toolbar=yes,scrollbars=yes,resizable=yes,width=800,height=600'
      );
    }
  },
  computed: {
    payrollPeriod() {
      if (!this.modelValue.cutoff || !this.modelValue.date) return '';

      const date = new Date(this.modelValue.date);
      const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
      const monthYear = date.toLocaleString(undefined, { month: 'long', year: 'numeric' });

      const period = this.modelValue.cutoff === 'first_cutoff'
        ? `1 - 15`
        : `16 - ${lastDay}`;

      return `${period} ${monthYear}`;
    }
  }
};
</script>
