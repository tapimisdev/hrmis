<template>
  <div class="card-c border overflow-hidden">
    <div class="bg-primary px-2 py-2 text-light">
      <slot name="header"></slot>
    </div>

    <div class="mh-100">
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
  background-color:  var(--bs-body-bg);
  z-index: 200;
  border-radius: 2px !important;
  position: sticky;
  top: 110px;
}
</style>