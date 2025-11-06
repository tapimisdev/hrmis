<template>
  <div 
    class="modal fade" 
    id="myModal" 
    tabindex="-1" 
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content modern-modal">
        
        <!-- Header -->
        <div class="modal-header modern-header border-bottom">
          <div class="header-content">
            <div class="icon-wrapper">
              <i :class="headerIcon"></i>
            </div>
            <div class="header-text">
              <h5 class="modal-title">{{ computedTitle }}</h5>
              <p class="subtitle mb-0" v-if="subtitle">{{ subtitle }}</p>
            </div>
          </div>
          <button 
            type="button" 
            class="btn-close" 
            data-bs-dismiss="modal" 
            aria-label="Close"
          ></button>
        </div>

        <!-- Body and Footer -->
        <slot />

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "BaseModal",
  props: {
    type: { type: String, default: "default" },
    title: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    actions: { type: Array, default: () => [] }
  },
  computed: {
    computedTitle() {
      if (this.title) return this.title;

      switch (this.type) {
        case "adjustment": return "Add or Adjust Time";
        case "view_overtime": return "Overtime";
        case "leave": return "Record Leave";
        case "absent": return "Mark as Absent";
        case "overtime": return "Add Overtime";
        case "restday": return "Set as Rest Day";
        case "ob": return "Record Official Business";
        default: return "Modal";
      }
    },
    headerIcon() {
      switch (this.type) {
        case "adjustment": return "fas fa-clock";
        case "view_overtime": return "fas fa-business-time";
        case "leave": return "fas fa-umbrella-beach";
        case "absent": return "fas fa-user-times";
        case "overtime": return "fas fa-clock";
        case "restday": return "fas fa-calendar-day";
        case "ob": return "fas fa-briefcase";
        default: return "fas fa-file-alt";
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
  }
};
</script>