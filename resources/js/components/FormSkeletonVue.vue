<!-- components/SkeletonForm.vue -->
<template>
  <div class="skeleton-form mb-3">
    <div v-for="row in rows" :key="row" class="skeleton-row">
      <div class="skeleton-label"></div>
      <div class="skeleton-input" v-for="col in columns" :key="col"></div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SkeletonForm",
  props: {
    rows: {
      type: Number,
      default: 5
    },
    columns: {
      type: Number,
      default: 1
    }
  }
};
</script>

<style scoped>
.skeleton-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.skeleton-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-label {
  height: 16px;
  width: 30%;
  border-radius: 4px;
  background: linear-gradient(
    90deg,
    #e0e0e0 25%,
    #f0f0f0 50%,
    #e0e0e0 75%
  );
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

.skeleton-input {
  height: 36px;
  width: 100%;
  border-radius: 4px;
  background: linear-gradient(
    90deg,
    #e0e0e0 25%,
    #f0f0f0 50%,
    #e0e0e0 75%
  );
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
