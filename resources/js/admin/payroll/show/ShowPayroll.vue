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
    />
    <div v-else-if="employment_type === 'REGULAR'">
      <div class="alert alert-primary">REGULAR TO HA</div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import ShowProgressBar from "./ShowProgressBar.vue";
import CosPayrollRegistry from "./CosPayrollRegistry.vue";

export default {
  name: 'Show Payroll',
  components: { ShowProgressBar, CosPayrollRegistry },
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
        const token = localStorage.getItem('token'); // or however you store it

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
