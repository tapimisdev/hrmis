<template>
  <div 
    class="modal fade" 
    id="myModal" 
    tabindex="-1" 
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        
        <!-- Header -->
        <div class="modal-header py-4">
          <h5 class="modal-title fw-bolder text-uppercase">{{ computedTitle }}</h5>
          <button 
            type="button" 
            class="btn-close" 
            data-bs-dismiss="modal" 
            aria-label="Close"
          ></button>
        </div>

        <!-- Content -->
        <div class="modal-body">
          <slot />
        </div>

        <!-- Actions -->
        <div class="modal-footer">
          <button 
            v-for="(action, i) in actions" 
            :key="i" 
            :class="action.class" 
            @click="emitAction(action.type)"
          >
            <i v-if="action.icon" :class="action.icon" class="me-2"></i>
            {{ action.label }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "BaseModal",
  props: {
    type: { type: String, default: "default" }, // NEW
    title: { type: String, default: "" },
    actions: { type: Array, default: () => [] }
  },
  computed: {
    computedTitle() {
      // If `title` prop is passed, use it — otherwise infer from `type`
      if (this.title) return this.title;

      switch (this.type) {
        case "adjustment": return "Add or Adjust Time";
        case "leave": return "Record Leave";
        case "absent": return "Mark as Absent";
        case "restday": return "Set as Rest Day";
        case "ob": return "Record Official Business";
        default: return "Modal";
      }
    }
  },
  emits: ["action"],
  methods: {
    emitAction(type) {
      this.$emit("action", type);
    },
    open() {
      $('#myModal').modal('show');
    },
    close() {
      $('#myModal').modal('hide');
    }
  }
};
</script>
