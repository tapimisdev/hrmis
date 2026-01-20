<template>
  <div>
    <!-- Toggle button -->
    <button
      class="btn btn-primary"
      @click="toggleAnnouncement('announcement', 'ple')"
    >
      Show Announcement Toast
    </button>

    <!-- Toast container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div
        ref="toastEl"
        class="toast"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <div class="toast-header">
          <strong class="me-auto">Announcement</strong>
          <small>Just now</small>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="toast"
            aria-label="Close"
          ></button>
        </div>
        <div class="toast-body">
          {{ announcement ? announcement.title : "Loading..." }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { reactive, ref, onMounted, watch } from "vue";
import axios from "axios";
import * as bootstrap from "bootstrap";

// Global reactive state for notifications
window.notifications = reactive({
  type: "",
  slug: "",
  show: false,
});

export default {
  name: "PushNotification",
  props: {
    type: { type: String, required: true },
    slug: { type: String, required: true },
  },
  data() {
    return {
      announcement: null,
      toastInstance: null,
    };
  },
  mounted() {
    // Initialize toast
    this.toastInstance = new bootstrap.Toast(this.$refs.toastEl);

    // Watch global show flag
    watch(
      () => window.notifications.show,
      (newVal) => {
        if (newVal) {
          this.fetchNotification();
          this.toastInstance.show();
        }
      },
      { immediate: true }
    );
  },
  methods: {
    fetchNotification() {
      const { type, slug } = window.notifications;

      if (type === "announcement") {
        const token = localStorage.getItem("auth_token");

        axios
          .get(`/api/employee/announcements/${slug}`, {
            headers: { Authorization: `Bearer ${token}` },
          })
          .then((res) => {
            this.announcement = res.data;
          })
          .catch((err) => {
            console.error("Failed to fetch announcement:", err);
            this.announcement = { title: "Failed to load announcement" };
          });
      }
    },
    toggleAnnouncement(type, slug) {
      window.notifications.type = type;
      window.notifications.slug = slug;
      window.notifications.show = !window.notifications.show;
    },
  },
};
</script>

<style>
.toast-container {
  z-index: 1080; /* Make sure toast is above other content */
}
</style>
