<template>
  <div class="card-c overflow-hidden">
    <div class="bg-primary px-2 py-2 text-light">
      <slot name="header"></slot>
    </div>

    <div class=" bg-body-secondary mh-100">
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
  top: 110px;
}
</style>