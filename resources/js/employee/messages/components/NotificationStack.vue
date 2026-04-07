<template>
    <transition-group name="toast" tag="div" class="messages-toast-stack">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="messages-toast"
            :class="`messages-toast--${notification.type}`"
            role="status"
            aria-live="polite"
        >
            <div class="messages-toast__icon">
                <i :class="getNotificationIcon(notification.type)"></i>
            </div>
            <div class="messages-toast__content">
                <div class="messages-toast__title">{{ notification.title }}</div>
                <div class="messages-toast__message">
                    {{ notification.message }}
                </div>
            </div>
            <button
                type="button"
                class="messages-toast__close"
                aria-label="Dismiss notification"
                @click="$emit('dismiss', notification.id)"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </transition-group>
</template>

<script>
export default {
    name: "NotificationStack",
    props: {
        notifications: {
            type: Array,
            default: () => [],
        },
        getNotificationIcon: {
            type: Function,
            required: true,
        },
    },
    emits: ["dismiss"],
};
</script>
