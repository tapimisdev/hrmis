<template>
    <div>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div
                v-for="(toast, index) in toasts"
                :key="toast.id"
                ref="toastRefs"
                class="toast slide-left px-2"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
            >
                <div class="toast-header py-3">
                    <i
                        class="fa-solid fa-bell fa-shake"
                        style="margin-right: 12px"
                    ></i>
                    <strong class="me-auto">New Notification</strong>
                    <small class="text-body-secondary">{{
                        toast.timeAgo
                    }}</small>
                    <button
                        type="button"
                        class="btn-close"
                        @click="removeToast(toast.id)"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="toast-body position-relative">
                    <div>
                        <p
                            class="fw-medium toast-message"
                            v-html="toast.message"
                        ></p>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <small class="text-decoration-underline"
                            >click to view details</small
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as bootstrap from "bootstrap";

export default {
    name: "PushNotification",
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
    data() {
        const token = localStorage.getItem("auth_token");
        return {
            token,
            toasts: [],
            nextId: 1,
            audio: null,
        };
    },
    mounted() {
        // Preload notification sound
        this.audio = new Audio("/sounds/notification.mp3");
        this.audio.preload = "auto";

        // Load persisted toasts from sessionStorage
        const savedToasts = sessionStorage.getItem("toasts");
        if (savedToasts) {
            const parsed = JSON.parse(savedToasts);
            parsed.forEach((toast) => {
                this.addToast(toast, false);
            });
        }

        // Employees receive employee-wide notifications
        if (this.userRole === "employee") {
            window.Echo.channel("employees.notifications")
                .listen("notification-event", (e) => {
                    this.addToast({
                        id: e.id,
                        message: this.formatMessage(
                            e.data.message || "posted a new notification!",
                        ),
                        link: e.data.link || null,
                    });
                })
                .listen(".notification-event", (e) => {
                    this.addToast({
                        id: e.id,
                        message: this.formatMessage(
                            e.data.message || "posted a new notification!",
                        ),
                        link: e.data.link || null,
                    });
                });
        }

        // Admins receive admin-wide notifications
        if (this.userRole === "admin") {
            window.Echo.channel("admins.notifications")
                .listen("notification-event", (e) => {
                    this.addToast({
                        id: e.id,
                        message: this.formatMessage(
                            e.data.message || "posted a new notification!",
                        ),
                        link: e.data.link || null,
                    });
                })
                .listen(".notification-event", (e) => {
                    this.addToast({
                        id: e.id,
                        message: this.formatMessage(
                            e.data.message || "posted a new notification!",
                        ),
                        link: e.data.link || null,
                    });
                });
        }

        window.Echo.private(`user.notifications.${this.userId}`)
        .listen("notification-event", (e) => {
            this.addToast({
                id: e.id,
                message: this.formatMessage(
                    e.data.message || "sent you a notification!",
                ),
                link: e.data.link || null,
            });
        })
        .listen(".notification-event", (e) => {
            this.addToast({
                id: e.id,
                message: this.formatMessage(
                    e.data.message || "sent you a notification!",
                ),
                link: e.data.link || null,
            });
        });
    },
    methods: {
        addToast(notification = null, saveToSession = true) {
            const newToast = {
                id: notification.id,
                message:
                    notification?.message || "This is a test notification!",
                link: notification?.link || null,
                timeAgo: "Just now",
                createdAt: Date.now(),
            };

            this.toasts.push(newToast);

            if (saveToSession) this.updateSessionStorage();

            this.$nextTick(() => {
                const toastEl =
                    this.$refs.toastRefs[this.$refs.toastRefs.length - 1];
                const toastInstance = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 30000,
                });

                toastEl.classList.add("slide-in");
                toastInstance.show();

                toastEl.addEventListener(
                    "hide.bs.toast",
                    () => {
                        toastEl.classList.remove("slide-in");
                        toastEl.classList.add("slide-out");
                    },
                    { once: true },
                );

                toastEl.addEventListener(
                    "hidden.bs.toast",
                    () => {
                        this.removeToast(newToast.id, false); // remove toast and update session
                    },
                    { once: true },
                );

                // Click to redirect and remove immediately
                if (newToast.link) {
                    toastEl.style.cursor = "pointer";
                    toastEl.addEventListener(
                        "click",
                        (event) => {
                            if (event.target.closest(".btn-close")) return;
                            this.removeToast(newToast.id);
                            // Dynamic API endpoint based on role
                            const apiEndpoint =
                                this.userRole === "admin"
                                    ? "/api/admin/notifications"
                                    : "/api/employee/notifications";
                            axios
                                .post(
                                    apiEndpoint,
                                    {
                                        notification_id: newToast.id,
                                        user_id: this.userId,
                                    },
                                    {
                                        headers: {
                                            Authorization: `Bearer ${this.token}`,
                                            Accept: "application/json",
                                        },
                                    },
                                )
                                .then((response) => {
                                    window.location.href = newToast.link;
                                })
                                .catch((error) => {
                                    console.error(error);
                                });
                        },
                        { once: true },
                    );
                }

                // Play sound
                this.playNotificationSound();

                // Auto-remove after 30s (safeguard in case Bootstrap fails)
                setTimeout(() => {
                    this.removeToast(newToast.id);
                }, 30000);
            });
        },

        removeToast(id, updateSession = true) {
            const index = this.toasts.findIndex((t) => t.id === id);
            if (index !== -1) {
                const toastEl = this.$refs.toastRefs[index];
                const toastInstance = bootstrap.Toast.getInstance(toastEl);
                if (toastInstance) toastInstance.hide();
            }
            this.toasts = this.toasts.filter((t) => t.id !== id);
            if (updateSession) this.updateSessionStorage();
        },

        formatMessage(message) {
            if (!message) return "";

            // Convert %bi ... %bi to bold italic
            message = message.replace(
                /%bi(.*?)%bi/g,
                '<span class="fw-bold fst-italic">$1</span>',
            );

            // Convert %b ... %b to medium font
            message = message.replace(
                /%b(.*?)%b/g,
                '<span class="fw-medium">$1</span>',
            );

            return message;
        },

        updateSessionStorage() {
            sessionStorage.setItem("toasts", JSON.stringify(this.toasts));
        },

        playNotificationSound() {
            if (!this.audio) return;
            this.audio.currentTime = 0;
            this.audio.play().catch((err) => {
                console.warn("Unable to play sound:", err);
            });
        },
    },
};
</script>

<style>
.toast {
    background-color: var(--bs-body-bg);
    margin-top: 0.5rem;
}

.toast-container {
    z-index: 99999;
}

.toast.slide-left {
    transform: translateX(180%);
    opacity: 0;
}

.toast.slide-left.slide-in {
    animation: slideInLeft 0.5s forwards;
}

.toast.slide-left.slide-out {
    animation: slideOutRight 0.5s forwards;
}

.toast-message {
    display: -webkit-box;
    -webkit-line-clamp: 2; /* number of lines */
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}

@keyframes slideInLeft {
    0% {
        transform: translateX(180%);
        opacity: 0;
    }
    100% {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    0% {
        transform: translateX(0);
        opacity: 1;
    }
    100% {
        transform: translateX(180%);
        opacity: 0;
    }
}
</style>
