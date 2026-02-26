<template>
  <div class="card-c overflow-hidden">
    <div class="bg-primary px-3 py-2 text-light">
      <slot name="header"></slot>
    </div>

    <div class="px-3 py-3 bg-body-secondary mh-100">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    is_open: {
      type: Boolean,
      required: true
    }
  }
}
</script>

<style scoped>
.card-c {
  border-radius: 2px !important;
  position: sticky;
  top: 80px;
}
</style>