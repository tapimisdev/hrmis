<template>
  <div>
    <h5 class="mb-3 text-primary text-uppercase">Step 4: Approval & Submission</h5>
    <p class="text-muted mb-4">Select approvers and finalize the payroll.</p>

    <!-- APPROVERS BY LEVEL -->
    <div v-for="(group, level) in groupedUsers" :key="level" class="mb-4">
      <label class="form-label fw-semibold d-flex align-items-center">
        Level {{ level }} Approver
        <span class="text-danger ms-1">*</span>
      </label>

      <select
        class="form-select approver-select"
        :ref="el => setSelectRef(el, level)"
        multiple
        required
        
      >
        <option
          v-for="user in group"
          :key="user.user_id"
          :value="user.user_id"
        >
          {{ user.firstname }} {{ user.lastname }}
        </option>
      </select>
      <small
        v-if="errors[`approved_by.${level}`]"
        class="text-danger"
      >
        {{ errors[`approved_by.${level}`][0] }}
      </small>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ApprovalPayroll',
  props: { 
    modelValue: Object,
    errors: Object
   },
  emits: ["update:modelValue"],
  data() {
    const token = localStorage.getItem("auth_token");
    return {
      token,
      users: [],
      selectRefs: {}, // Store refs manually
      localForm: {
        approved_by: this.modelValue?.approved_by || {}, // structure: { 1: [id1, id2], 2: [id3] }
      },
    };
  },
  computed: {
    groupedUsers() {
      // Group users by level
      const grouped = {};
      this.users.forEach(user => {
        if (!grouped[user.level]) grouped[user.level] = [];
        grouped[user.level].push(user);
      });
      return grouped;
    },
  },
  watch: {
    localForm: {
      deep: true,
      handler(newVal) {
        // Merge instead of replacing
        this.$emit("update:modelValue", {
          ...this.modelValue,
          approved_by: newVal.approved_by
        });
      },
    },
    modelValue: {
      deep: true,
      handler(newVal) {
        if (!newVal) return;

        // Only handle approved_by update — ignore other parent changes
        if (newVal.approved_by && 
            JSON.stringify(newVal.approved_by) !== JSON.stringify(this.localForm.approved_by)) {
          this.localForm.approved_by = { ...newVal.approved_by };
          this.$nextTick(() => this.syncSelect2Values());
        }
      }
    },
    // Reinitialize Select2 if new users come in (dynamic updates)
    users() {
      this.$nextTick(() => this.initSelect2());
    }
  },
  async mounted() {
    await this.fetchApprover();
    this.$nextTick(() => this.initSelect2());
  },
  beforeUnmount() {
    // Destroy all Select2 instances
    Object.keys(this.selectRefs).forEach(level => {
      const ref = this.selectRefs[level];
      if (ref && $(ref).data('select2')) {
        $(ref).off('change'); // Remove event listeners
        $(ref).select2("destroy");
      }
    });
  },
  methods: {
    setSelectRef(el, level) {
      if (el) {
        this.selectRefs[level] = el;
        if (!this.localForm.approved_by[level]) {
          this.localForm.approved_by[level] = [];
        }
      }
    },
    async fetchApprover() {
      try {
        const response = await axios.get("/api/payroll/approvers", {
          headers: { "Authorization": `Bearer ${this.token}` },
        });
        this.users = response.data;
      } catch (error) {
        console.error("Error fetching users:", error);
      }
    },
    syncSelect2Values() {
      // Update Select2 dropdowns with current values
      Object.keys(this.groupedUsers).forEach(level => {
        const ref = this.selectRefs[level];
        if (ref && $(ref).data('select2')) {
          const values = this.localForm.approved_by[level] || [];
          $(ref).val(values).trigger('change.select2');
        }
      });
    },
    initSelect2() {
      // Destroy old instances before re-initializing
      Object.keys(this.selectRefs).forEach(level => {
        const ref = this.selectRefs[level];
        if (ref && $(ref).data('select2')) {
          $(ref).off('change');
          $(ref).select2("destroy");
        }
      });

      // Initialize fresh Select2 instances
      Object.keys(this.groupedUsers).forEach(level => {
        const ref = this.selectRefs[level];
        if (ref) {
          const select = $(ref);
          select.select2({
            placeholder: `Select Level ${level} Approver(s)`,
            width: "100%",
          });

          // Sync changes to Vue model
          select.on("change", (e) => {
            const selected = $(e.target).val() || [];
            // Convert string values to numbers if needed
            const selectedIds = selected.map(id => parseInt(id) || id);
            
            // Use Vue.set for Vue 2 or direct assignment for Vue 3
            if (this.$set) {
              this.$set(this.localForm.approved_by, level, selectedIds);
            } else {
              this.localForm.approved_by[level] = selectedIds;
            }
          });

          // Load existing selections if present
          if (this.localForm.approved_by[level] && this.localForm.approved_by[level].length > 0) {
            select.val(this.localForm.approved_by[level]).trigger('change.select2');
          }
        }
      });
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