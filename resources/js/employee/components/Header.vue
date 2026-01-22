<template>
    <button @click="toggleMobileMenu" class="d-md-none menu-btn">☰</button>
    <incomplete-logs @incomplete-data="handleData"> </incomplete-logs>
    <div class="d-flex gap-5 justify-content-end align-items-center">
      <widget-component />
      <notification-component :userRole="userRole" :userId="userId" />  
      <profile-component />
    </div>
</template>

<script>
import axios from "axios";
import WidgetComponent from "./parts/Widgets.vue";
import NotificationComponent from "./parts/Notification.vue";
import ProfileComponent from "./parts/Profile.vue";
import IncompleteLogs from "./parts/IncompleteLogs.vue";

export default {
    name: "AppHeader",
    components: {
        WidgetComponent,
        NotificationComponent,
        ProfileComponent,
        IncompleteLogs,
    },
    methods: {
        toggleMobileMenu() {
            document.querySelector("aside")?.classList.toggle("mobile-open");
            document
                .querySelector(".sidebar-overlay")
                ?.classList.toggle("active");
        },
        handleData(data) {
            // Handle incomplete data as before
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