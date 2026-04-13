<template>
  <div>
    <PayrollSection
      title="Active Payrolls"
      :payrolls="activePayrolls"
      :url="url"
      column-class="col-12 col-md-6"
      empty-message="No active payroll for this date yet."
      :show-empty-state="true"
      @change-status="handleChangeStatus"
      @cancel="confirmDelete"
    >
      <template #card="{ payroll, url }">
        <slot name="payroll-card" :payroll="payroll" :url="url" :is-active="true">
          <PayrollCard
            :url="url"
            :payroll="payroll"
            :loading="actionPayrollId === payroll.id"
            :loading-action="actionPayrollId === payroll.id ? actionKey : ''"
            @change-status="handleChangeStatus"
            @cancel="confirmDelete"
          />
        </slot>
      </template>
    </PayrollSection>

    <PayrollSection
      title="Others"
      :payrolls="inactivePayrolls"
      :url="url"
      column-class="col-12 col-md-4"
      :show-section="inactivePayrolls.length > 0"
      @change-status="handleChangeStatus"
      @cancel="confirmDelete"
    >
      <template #card="{ payroll, url }">
        <slot name="payroll-card" :payroll="payroll" :url="url" :is-active="false">
          <PayrollCard
            :url="url"
            :payroll="payroll"
            :loading="actionPayrollId === payroll.id"
            :loading-action="actionPayrollId === payroll.id ? actionKey : ''"
            @change-status="handleChangeStatus"
            @cancel="confirmDelete"
          />
        </slot>
      </template>
    </PayrollSection>
  </div>
</template>

<script>
import PayrollCard from "./PayrollCard.vue";
import PayrollSection from "./PayrollSection.vue";

const INACTIVE_STATUSES = new Set(["draft", "cancelled"]);

export default {
  name: "PayrollList",
  components: { PayrollCard, PayrollSection },

  props: {
    payrolls: { type: Array, required: true },
    loading: { type: Boolean, required: true },
    url: { type: String, required: true },
  },

  data() {
    return {
      actionPayrollId: null,
      actionKey: "",
    };
  },

  computed: {
    activePayrolls() {
      return this.payrolls.filter((p) => !INACTIVE_STATUSES.has(p.status));
    },
    inactivePayrolls() {
      return this.payrolls.filter((p) => INACTIVE_STATUSES.has(p.status));
    },
  },

  methods: {
    setActionLoading(id, actionKey) {
      this.actionPayrollId = id;
      this.actionKey = actionKey;
    },

    clearActionLoading() {
      this.actionPayrollId = null;
      this.actionKey = "";
    },

    confirmDelete(id) {
      Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
      }).then((result) => {
        if (result.isConfirmed) this.deletePayroll(id);
      });
    },

    deletePayroll(id) {
      const token = localStorage.getItem("auth_token");
      this.setActionLoading(id, "delete");

      axios
        .delete(`/api/payroll/${this.url}/${id}/delete`, {
          headers: { Authorization: `Bearer ${token}` },
        })
        .then(({ data }) => {
          if (data?.status === "success") {
            Swal.fire({
              title: "Deleted!",
              text: "Payroll has been successfully deleted!",
              icon: "success",
            });
            this.$emit("deleted");
          }
        })
        .catch((error) => {
          Swal.fire({
            title: "Oops!",
            text: error?.response?.data?.message || "Something went wrong.",
            icon: "error",
          });
        })
        .finally(() => {
          this.clearActionLoading();
        });
    },

    confirmChangeStatus(id, nextStatus) {
      const labelMap = {
        draft: "Draft",
        pending: "Pending",
        pending_approval: "Pending",
        approved: "Approved",
        for_releasing: "For Releasing",
        cancelled: "Cancelled",
        complete: "Complete",
        completed: "Complete",
      };

      Swal.fire({
        title: "Change payroll status?",
        html: `Set status to <b>${labelMap[nextStatus] || nextStatus}</b>?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, change it!",
      }).then((result) => {
        if (result.isConfirmed) this.changePayrollStatus(id, nextStatus);
      });
    },

    changePayrollStatus(id, nextStatus) {
      const token = localStorage.getItem("auth_token");
      this.setActionLoading(id, nextStatus);

      axios
        .patch(
          `/api/payroll/${this.url}/${id}/status`,
          { status: nextStatus },
          { headers: { Authorization: `Bearer ${token}` } }
        )
        .then(({ data }) => {
          if (data?.status === "success") {
            Swal.fire({
              title: "Updated!",
              text: "Payroll status has been updated successfully.",
              icon: "success",
            });

            this.$emit("status-changed");
          } else {
            Swal.fire({
              title: "Oops!",
              text: data?.message || "Unable to update status.",
              icon: "error",
            });
          }
        })
        .catch((error) => {
          Swal.fire({
            title: "Oops!",
            text: error?.response?.data?.message || "Something went wrong.",
            icon: "error",
          });
        })
        .finally(() => {
          this.clearActionLoading();
        });
    },

    handleChangeStatus(id, nextStatus) {
      this.confirmChangeStatus(id, nextStatus);
    },
  },
};
</script>
