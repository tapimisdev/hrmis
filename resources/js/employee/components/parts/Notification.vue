<template>
    <div class="dropdown position-relative">
        <a
            class="text-decoration-none position-relative d-inline-block"
            href="#"
            id="notificationDropdown"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            style="cursor: pointer"
            @click="loadNotifications"
        >
            <i
                class="fa-regular fa-bell text-light"
                style="font-size: 1.5rem"
            ></i>
            <span
                v-if="unreadCount > 0"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size: 0.65rem; padding: 0.25rem 0.45rem"
            >
                {{ unreadCount }}
                <span class="visually-hidden">unread notifications</span>
            </span>
        </a>

        <ul
            class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0"
            aria-labelledby="notificationDropdown"
            style="
                min-width: 420px;
                max-width: 380px;
                border: 1px solid #e0e0e0;
            "
        >
            <!-- Header -->
            <li
                class="px-4 py-3 border-bottom bg-body"
                style="position: sticky; top: 0; opacity: 1; z-index: inherit"
            >
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-uppercase">
                        Notifications
                    </h6>
                </div>
            </li>

            <!-- Loading State -->
            <li v-if="loadingNotifications" class="text-center py-4">
                <div
                    class="spinner-border spinner-border-sm text-primary"
                    role="status"
                >
                    <span class="visually-hidden">Loading...</span>
                </div>
            </li>

            <!-- Empty State -->
            <li
                v-else-if="notifications.length === 0"
                class="text-center py-5 text-muted"
            >
                <i
                    class="fa-regular fa-bell-slash mb-2"
                    style="font-size: 2rem"
                ></i>
                <p class="mb-0">No notifications</p>
            </li>
            <li v-else v-for="(notification, key) in notifications" :key="key">
                <a
                    :class="['dropdown-item py-3 px-3', key <= 1 ? 'mt-2' : '']"
                    style="white-space: normal; cursor: pointer"
                    @click.prevent="
                        navigateUrl(notification.id, notification.data.link)
                    "
                >
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <div
                                class="rounded-circle d-flex align-items-center justify-content-center"
                                :class="
                                    getNotificationIconClass(notification.type)
                                "
                                style="width: 40px; height: 40px"
                            >
                                <i
                                    :class="
                                        getNotificationIcon(notification.type)
                                    "
                                ></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 position-relative">
                            <p
                                class="mb-1 fw-semibold notification-message"
                                style="font-size: 12px; margin-right: 35px"
                                v-html="
                                    formatMessage(notification.data.message)
                                "
                            ></p>
                            <small
                                class="text-muted"
                                style="font-size: 0.75rem"
                            >
                                <i class="fa-regular fa-clock me-1"></i
                                >{{ formatTime(notification.created_at) }}
                            </small>
                            <div
                                v-if="!notification.is_read"
                                style="
                                    position: absolute;
                                    right: 8px;
                                    bottom: 40%;
                                "
                            >
                                <i
                                    style="font-size: 11px"
                                    :class="[
                                        'fa-solid fa-circle',
                                        !notification.is_read
                                            ? 'text-info'
                                            : '',
                                    ]"
                                >
                                </i>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            <li
                class="border-top"
                v-if="hasMore"
                style="
                    position: sticky;
                    bottom: 0;
                    background-color: inherit !important;
                "
            >
                <a
                    class="dropdown-item text-center py-3 text-uppercase fw-semibold"
                    href="#"
                    style="font-size: 10px"
                    @click.prevent="viewMoreNotification"
                >
                    View More Notification
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "NotificationComponent",
    data() {
        const token = localStorage.getItem("auth_token");

        return {
            token,
            notifications: [],
            unreadCount: 0,
            loadingNotifications: false,
            currentOffset: 0,
            hasMore: true,
            displayedIds: new Set(), // Track displayed notification IDs to avoid duplicates
        };
    },
    mounted() {
        this.fetchNotifications("unread");

        window.Echo.channel("public-channel").listen(
            ".public-channel-event",
            (e) => {
                this.fetchNotifications("unread");
            },
        );
    },
    beforeUnmount() {
        clearInterval(this.notificationInterval);
    },
    methods: {
        async fetchNotifications(filter = null, limit = 10, offset = 0) {
            // Set loading only for full notifications fetch (not for unread count)
            if (!filter) {
                this.loadingNotifications = true;
            }
            try {
                // Only include filter if it's not null
                const params = filter ? { filter, limit } : { limit, offset: this.currentOffset };

                const res = await axios.get("/api/employee/notifications", {
                    params,
                    headers: { Authorization: `Bearer ${this.token}` },
                });

                // Update unreadCount if fetching unread notifications
                if (filter === "unread") {
                    this.unreadCount = res.data?.length ?? 0;
                    console.log(this.unreadCount);
                } else {
                    // For initial load (offset 0), reset the list and displayed IDs
                    if (this.currentOffset === 0) {
                        this.notifications = [];
                        this.displayedIds.clear();
                    }
                    // Filter out already displayed notifications by ID
                    const newNotifications = res.data.filter(
                        (n) => !this.displayedIds.has(n.id)
                    );
                    // Add new IDs to the set
                    newNotifications.forEach((n) => this.displayedIds.add(n.id));
                    // Append new notifications to the list
                    this.notifications = [...this.notifications, ...newNotifications];
                    // Check if there are more notifications
                    this.hasMore = res.data.length === limit;
                    console.log(res.data);
                }
            } catch (err) {
                console.error("Error fetching notifications:", err);
            } finally {
                // Reset loading only for full notifications fetch
                if (!filter) {
                    this.loadingNotifications = false;
                }
            }
        },
        async loadNotifications() {
            this.currentOffset = 0; // Reset offset for initial load
            await this.fetchNotifications();
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
        async markAsRead(id) {
            try {
                await axios.post(`/api/notifications/${id}/read`);
                const n = this.notifications.find((n) => n.id === id);
                if (n && !n.read_at) {
                    n.read_at = new Date();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            } catch (err) {
                console.error(err);
            }
        },
        viewMoreNotification() {
            this.currentOffset += 10; // Increment offset for next batch
            this.fetchNotifications(null, 10, this.currentOffset);
        },
        getNotificationIcon(type) {
            return (
                {
                    success: "fa-solid fa-check text-success",
                    warning: "fa-solid fa-exclamation text-warning",
                    alert: "fa-solid fa-triangle-exclamation text-danger",
                    message: "fa-solid fa-message text-info",
                }[type] || "fa-solid fa-bell text-primary"
            );
        },
        getNotificationIconClass(type) {
            return (
                {
                    success: "bg-success bg-opacity-10",
                    warning: "bg-warning bg-opacity-10",
                    alert: "bg-danger bg-opacity-10",
                    message: "bg-info bg-opacity-10",
                }[type] || "bg-primary bg-opacity-10"
            );
        },
        formatTime(time) {
            const diff = Date.now() - new Date(time);
            const mins = Math.floor(diff / 60000);
            if (mins < 1) return "Just now";
            if (mins < 60) return `${mins} min ago`;
            if (mins < 1440) return `${Math.floor(mins / 60)} hrs ago`;
            return new Date(time).toLocaleDateString();
        },
        navigateUrl(notification_id, redirectURL) {
            axios
                .post(
                    "/api/employee/notifications",
                    {
                        notification_id: notification_id,
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: "application/json",
                        },
                    },
                )
                .then((response) => {
                    window.location.href = redirectURL;
                })
                .catch((error) => {
                    console.error(error);
                });
        },
    },
};
</script>

<style lang="scss" scoped>
.notification-message {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}

.dropdown-menu {
    max-height: 500px;
    overflow-y: auto;
    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    &::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
        &:hover {
            background: #555;
        }
    }
}
</style>