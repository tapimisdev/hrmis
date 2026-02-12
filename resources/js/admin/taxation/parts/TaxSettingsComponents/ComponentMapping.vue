<template>
  <div class="card shadow-sm h-100">
    <div class="card-header d-flex align-items-center gap-2">
      <i class="fa-solid fa-diagram-project text-muted"></i>
      <span class="fw-semibold">Component Mapping</span>
    </div>

    <div class="card-body">
      <div class="d-flex flex-column gap-2">
        <div v-for="(item, idx) in items" :key="idx" class="d-flex align-items-start gap-2">
          <i
            class="fa-solid fa-check mt-1"
            :class="item.ok ? 'text-success' : 'text-muted'"
          ></i>

          <div class="small">
            <div class="fw-semibold">{{ item.label }}</div>
            <div class="text-muted">{{ item.note }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ComponentMapping",
  props: {
    items: { type: Array, default: () => [] },
  },
};
</script>
