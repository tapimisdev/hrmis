<template>
  <div class="p-3">
    <ShowProgressBar
        v-if="!isFinished"
        :batchId="batch_id"
        endpoint="/api/payroll/progress"
        cancel-endpoint="/api/payroll/cancel"
        title="Generating payroll..."
        @cancelled="onCancelled"
        @completed="onFinished"
      />
    <CosPayrollRegistry v-else-if="employment_type === 'COS'"
      :projects="employees"
      :status="status"
      :payroll_no="payroll_no"
      @fetch_data="fetchRegistry"
    />
    <div v-else-if="employment_type === 'REGULAR'">
      <RegularPayrollRegistry
        :employees="employees"
        :status="status"
        :payroll_no="payroll_no"
      />
    </div>
  </div>
</template>

<script>
import axios from "axios";
import ShowProgressBar from "./parts/ShowProgressBar.vue";
import CosPayrollRegistry from "./parts/CosPayrollRegistry.vue";
import RegularPayrollRegistry from "./parts/RegularPayrollRegistry.vue";

export default {
  name: 'Show Payroll',
  components: { ShowProgressBar, CosPayrollRegistry, RegularPayrollRegistry },
  props: {
    batch_id: Number|String,
    payroll_id: Number|String,
    payroll_no: String,
    status: String,
    employment_type: String //REGULAR OR COS
  },
  data() {
    return {
      token: localStorage.getItem("auth_token"),
      isFinished: false,
      employees: [],
    };
  },
  methods: {
    onFinished() {
      this.isFinished = true;
      this.fetchRegistry();
    },
    async fetchRegistry() {
      try {
        const response = await axios.get(`/api/payroll/salary/${this.payroll_id}`, {
          headers: {
            Authorization: `Bearer ${this.token}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
        });

        console.log(response.data);
        this.employees = response.data;
      } catch (error) {
        console.error(
          'Failed to fetch registry:',
          error.response?.data || error.message
        );
      }
    },

  },
  mounted() {
    this.fetchRegistry();
  },
};
</script>
