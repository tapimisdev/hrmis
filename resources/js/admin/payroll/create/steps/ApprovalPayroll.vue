<template>
  <div>
    <h5 class="mb-3 text-primary text-uppercase">Step 4: Approval & Submission</h5>
    <p class="text-muted mb-4">Select approvers and finalize the payroll.</p>
    <!-- APPROVERS (Select2 Multiple) -->
    <div class="mb-3">
      <label class="form-label fw-semibold">Approving Officers</label>
      <select ref="approversSelect" class="form-select" multiple>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.name }}
        </option>
      </select>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ApprovalPayroll',
  props: ["modelValue"],
  emits: ["update:modelValue"],
  data() {
    const token = localStorage.getItem("auth_token");
    return {
      token,
      users: [],
      localForm: {
        approved_by: this.modelValue.approved_by || [],
      },
    };
  },
  watch: {
    localForm: {
      deep: true,
      handler(newVal) {
        this.$emit("update:modelValue", newVal);
      },
    },
  },
  mounted() {
    this.fetchUsers().then(() => {
      this.initSelect2();
    });
  },
  beforeUnmount() {
    // destroy Select2 instance to avoid memory leaks
    if (this.$refs.approversSelect) {
      $(this.$refs.approversSelect).select2("destroy");
    }
  },
  methods: {
    async fetchUsers() {
      try {
        const response = await axios.get("/api/users", {
          headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${this.token}`, // or localStorage.getItem('token')
          },
        });
        this.users = response.data;
      } catch (error) {
        console.error("Error fetching users:", error);
      }
    },
    initSelect2() {
      const select = $(this.$refs.approversSelect);

      select.select2({
        placeholder: "Select Approving Officers",
        width: "100%",
      });

      // Sync Vue model when changed
      select.on("change", (e) => {
        this.localForm.approved_by = $(e.target).val() || [];
      });

      // Initialize with existing values
      if (this.localForm.approved_by.length > 0) {
        select.val(this.localForm.approved_by).trigger("change");
      }
    },
  },
};
</script>

<style scoped>
.select2-container .select2-selection--multiple {
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  min-height: 38px;
  padding: 4px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
  background-color: #0d6efd;
  border: none;
  color: white;
  padding: 2px 8px;
  margin-top: 3px;
  border-radius: 0.25rem;
  font-size: 0.85rem;
}
</style>
