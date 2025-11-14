<template>
  <div v-if="data && data.length" class="attachments-section mt-4">
    <h6 class="attachments-title mb-3">📎 Attachments</h6>
    <div class="d-flex flex-wrap gap-2 attachments-list">
      <div 
        v-for="attachment in data" 
        :key="attachment.id" 
        class="attachment-item"
        style="width: fit-content; min-width: 120px;"
      >
        <a 
          :href="`/storage/events/attachments/${attachment.filename}`" 
          :download="attachment.title"
          class="text-decoration-none"
        >
          <div 
            class="file-bubble bg-body-secondary text-body rounded-3 shadow-sm py-2 px-3 d-flex flex-column justify-content-center h-100"
            style="cursor: pointer; width: fit-content;"
          >
            <div class="d-flex align-items-center gap-2">
              <i class="fa-regular fa-file-lines fs-4"></i>
              <div>
                <span 
                  class="fw-semibold d-block text-truncate" 
                  :title="attachment.title"
                  style="max-width: 200px;"
                >
                  {{ attachment.title }}
                </span>
                <small class="text-body fw-bold">
                  {{ attachment.size }}
                </small>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    data: Object
  }
}
</script>

<style scoped lang="scss">

</style>