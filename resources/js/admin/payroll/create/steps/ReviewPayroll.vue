<template>
  <div>
    <h5>Step 2: Review Details</h5>
    <p><strong>Payroll Name:</strong> {{ modelValue.name }}</p>
    <p><strong>Period:</strong> {{ modelValue.period }}</p>
    <div class="alert alert-info">
      Please confirm the details before approval.
    </div>
  </div>
</template>

<script>
export default { props: ["modelValue"] };
</script>
