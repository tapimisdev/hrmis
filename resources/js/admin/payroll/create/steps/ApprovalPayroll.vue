<template>
  <div>
    <h5>Step 3: Approval & Submission</h5>
    <p>Your payroll is ready for approval.</p>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select v-model="localForm.status" class="form-select">
        <option value="">-- Choose Status --</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
      </select>
    </div>
  </div>
</template>

<script>
export default {
  props: ["modelValue"],
  emits: ["update:modelValue"],
  computed: {
    localForm: {
      get() { return this.modelValue; },
      set(val) { this.$emit("update:modelValue", val); }
    }
  }
};
</script>
