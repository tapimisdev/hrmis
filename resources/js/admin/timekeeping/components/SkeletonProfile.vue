<template>
  <div class="card p-4 mb-4 placeholder-glow">
    <div class="d-flex flex-column flex-md-row align-items-center gap-4">
      
      <!-- Profile Picture Placeholder -->
      <div class="flex-shrink-0">
        <div 
          class="placeholder bg-secondary rounded-circle border border-3 border-light shadow-sm"
          style="width: 120px; height: 120px;"
        ></div>
      </div>

      <!-- Content Section -->
      <div class="content w-100">
        <!-- Name Placeholder -->
        <h3 class="fw-bold text-dark text-uppercase mb-4 border-bottom pb-3">
          <span class="placeholder bg-info rounded d-inline-block" style="width: 80px; height: 20px;"></span>
          <span class="placeholder bg-secondary rounded d-inline-block ms-2" style="width: 180px; height: 20px;"></span>
        </h3>

        <!-- Info Cards Placeholder -->
        <div class="row g-3">
          <div class="col-md-6" v-for="n in infoCardCount" :key="n">
            <div class="info-card p-3 rounded-3 border h-100">
              <h6 class="fw-bold mb-1">
                <span class="placeholder bg-info rounded d-block" style="width: 50%; height: 14px;"></span>
              </h6>
              <p class="mb-0">
                <span class="placeholder bg-secondary rounded d-block" style="width: 70%; height: 14px;"></span>
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SkeletonProfileCard",
  props: {
    /**
     * Number of info cards to render as skeletons
     */
    infoCardCount: {
      type: Number,
      default: 4,
    },
  },
};
</script>

<style scoped>
.placeholder {
  display: inline-block;
}
</style>
