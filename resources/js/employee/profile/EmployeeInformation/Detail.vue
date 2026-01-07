<template>
  <div class="detail">
    <label>{{ label }}</label>
    <p>{{ value || '—' }}</p>
  </div>
</template>

<script>
export default {
  props: ['label', 'value']
};
</script>
