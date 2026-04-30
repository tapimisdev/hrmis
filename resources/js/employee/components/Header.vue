<template>
    <button @click="toggleMobileMenu" class="d-md-none menu-btn">☰</button>
    <incomplete-logs> </incomplete-logs>
    <div class="d-flex gap-4 justify-content-end align-items-center">
      <widget-component />
      <online-users :user-id="userId" :user-role="userRole" />
      <notification-component :user-role="userRole" :user-id="userId" />  
      <feedback-component :user-id="userId" />
      <profile-component />
    </div>
</template>

<script>
import WidgetComponent from "./parts/Widgets.vue";
import NotificationComponent from "./parts/Notification.vue";
import FeedbackComponent from "./parts/Feedback.vue";
import ProfileComponent from "./parts/Profile.vue";
import IncompleteLogs from "./parts/IncompleteLogs.vue";
import OnlineUsers from "../../admin/components/parts/OnlineUsers.vue";

export default {
    name: "HeaderVue",
    props: {
      userRole: {
          type: String,
          required: true,
      },
      userId: {
          type: Number,
          required: true,
      },
    },  
    components: {
        WidgetComponent,
        NotificationComponent,
        FeedbackComponent,
        ProfileComponent,
        IncompleteLogs,
        OnlineUsers,
    },
    methods: {
        toggleMobileMenu() {
            document.querySelector("aside")?.classList.toggle("mobile-open");
            document
                .querySelector(".sidebar-overlay")
                ?.classList.toggle("active");
        },
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../sass/variables";

.menu-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: $light;
    cursor: pointer;
    transition: transform 0.1s ease-in-out;
    &:hover {
        transform: scale(1.03);
    }
}
</style>
