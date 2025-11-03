<template>
  <div 
    class="modal fade" 
    id="myModal" 
    tabindex="-1" 
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
  >
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" :class="size">
      <div class="modal-content modern-modal">
        
        <!-- Header -->
        <div class="modal-header modern-header border-bottom">
          <div class="header-content">
            <div class="icon-wrapper">
              <i class="text-body" :class="headerIcon"></i>
            </div>
            <div class="header-text">
              <h5 class="modal-title text-body text-uppercase">{{ title }}</h5>
              <p class="subtitle text-body mb-0" v-if="subtitle">{{ subtitle }}</p>
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
const token = localStorage.getItem('auth_token');

export default {
  props: {
    headerIcon: { type: String, default: "fa-solid fa-circle-question" },
    title: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    size: { type: String, default: "modal-lg" },
  },
  methods: {
    close() {
      $('#myModal').modal('hide');
    },
    open() {
      $('#myModal').modal('show');
    },
  },
};
</script>
