<template>
    <button @click="toggleMobileMenu" class="d-md-none menu-btn">☰</button>
    <div class="d-flex gap-4 align-items-center">
        <!-- Widget Dropdown -->
        <div class="dropdown position-relative">
            <a
                class="text-decoration-none position-relative d-inline-block"
                href="#"
                id="widgetDropdown"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false"
                style="cursor: pointer"
            >
                <i
                    class="fa-solid fa-gear fa-spin text-light"
                    style="font-size: 1.5rem"
                ></i>
            </a>

            <ul
                class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0"
                aria-labelledby="widgetDropdown"
                style="
                    min-width: 320px;
                    max-width: 380px;
                    border: 1px solid #e0e0e0;
                "
            >
                <!-- Header -->
                <li class="px-4 py-3 border-bottom bg-body">
                    <div
                        class="d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 fw-semibold">Widgets</h6>
                    </div>
                </li>
                <div class="px-4 py-3">
                    <!-- Dark Mode Toggle -->
                    <li class="pb-2">
                        <div
                            class="form-check form-switch d-flex align-items-center gap-2"
                        >
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="darkModeSwitch"
                                v-model="isDarkMode"
                                @change="handleThemeToggle"
                                style="
                                    cursor: pointer;
                                    transform: scale(1.2);
                                    margin-right: 0.5rem;
                                    margin-bottom: 2px;
                                "
                            />
                            <label
                                class="form-check-label text-uppercase fw-medium"
                                for="darkModeSwitch"
                                style="font-size: 12px; cursor: pointer;"
                            >
                                Dark Mode
                            </label>
                        </div>
                    </li>

                    <!-- Timelog Toggle -->
                    <li class="pb-2">
                        <div
                            class="form-check form-switch d-flex align-items-center gap-2"
                        >
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="timelogDiscrepancySwitch"
                                v-model="showTimelogDiscrepancy"
                                @change="handleTimelogToggle"
                                style="
                                    cursor: pointer;
                                    transform: scale(1.2);
                                    margin-right: 0.5rem;
                                    margin-bottom: 2px;
                                "
                            />
                            <label
                                class="form-check-label text-uppercase fw-medium"
                                for="timelogDiscrepancySwitch"
                                style="font-size: 12px; cursor: pointer;"
                            >
                                Timelogs Discrepancy
                            </label>
                        </div>
                    </li>
                </div>
            </ul>
        </div>

        <!-- Notification Dropdown -->
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
                    min-width: 320px;
                    max-width: 380px;
                    border: 1px solid #e0e0e0;
                "
            >
                <!-- Header -->
                <li class="px-4 py-3 border-bottom bg-body">
                    <div
                        class="d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 fw-semibold">Notifications</h6>
                        <span
                            v-if="unreadCount > 0"
                            class="badge bg-danger rounded-pill"
                            >{{ unreadCount }}</span
                        >
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
                    class="text-center py-4 text-muted"
                >
                    <i
                        class="fa-regular fa-bell-slash mb-2"
                        style="font-size: 2rem"
                    ></i>
                    <p class="mb-0">No notifications</p>
                </li>

                <!-- Notification Items -->
                <li
                    v-else
                    v-for="notification in notifications"
                    :key="notification.id"
                >
                    <a
                        class="dropdown-item py-3 px-3 border-bottom"
                        href="#"
                        style="white-space: normal"
                        @click.prevent="markAsRead(notification.id)"
                    >
                        <div class="d-flex gap-2">
                            <div class="flex-shrink-0">
                                <div
                                    class="rounded-circle d-flex align-items-center justify-content-center"
                                    :class="
                                        getNotificationIconClass(
                                            notification.type
                                        )
                                    "
                                    style="width: 40px; height: 40px"
                                >
                                    <i
                                        :class="
                                            getNotificationIcon(
                                                notification.type
                                            )
                                        "
                                    ></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p
                                    class="mb-1 fw-semibold text-dark"
                                    style="font-size: 0.9rem"
                                >
                                    {{ notification.title }}
                                </p>
                                <p
                                    class="mb-1 text-muted"
                                    style="font-size: 0.8rem"
                                >
                                    {{ notification.message }}
                                </p>
                                <small
                                    class="text-muted"
                                    style="font-size: 0.75rem"
                                >
                                    <i class="fa-regular fa-clock me-1"></i
                                    >{{ formatTime(notification.created_at) }}
                                </small>
                            </div>
                        </div>
                    </a>
                </li>

                <!-- Footer -->
                <li class="border-top">
                    <a
                        class="dropdown-item text-center py-2 text-primary fw-semibold"
                        href="#"
                        style="font-size: 0.9rem"
                        @click.prevent="viewAllNotifications"
                    >
                        View all notifications
                    </a>
                </li>
            </ul>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown">
            <a
                class="text-decoration-none position-relative d-inline-block"
                href="#"
                id="profileDropdown"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false"
                style="cursor: pointer"
            >
                <img
                    :src="userAvatar"
                    alt="Profile"
                    class="rounded-circle"
                    style="
                        width: 40px;
                        height: 40px;
                        object-fit: cover;
                        border: 2px solid var(--bs-light);
                    "
                />
                <span
                    class="position-absolute bg-primary rounded-circle d-flex align-items-center justify-content-center"
                    style="
                        width: 18px;
                        height: 18px;
                        bottom: -2px;
                        right: -2px;
                        border: 2px solid white;
                    "
                >
                    <i
                        class="fa-solid fa-chevron-down text-white"
                        style="font-size: 0.5rem"
                    ></i>
                </span>
            </a>

            <ul
                class="dropdown-menu dropdown-menu-end shadow-sm mt-2"
                aria-labelledby="profileDropdown"
                style="min-width: 220px; border: 1px solid #e0e0e0"
            >
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-semibold text-dark small">
                        {{ user.name }}
                    </div>
                    <div class="text-muted" style="font-size: 0.75rem">
                        {{ user.email }}
                    </div>
                </li>
                <li>
                    <a class="dropdown-item py-2 px-3" href="/employee/profile">
                        <i
                            class="fa-regular fa-user me-2"
                            style="width: 18px"
                        ></i
                        >My Account
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1" /></li>
                <li>
                    <button
                        @click="logout"
                        class="dropdown-item py-2 px-3 text-danger w-100 text-start"
                        :disabled="loggingOut"
                    >
                        <i
                            class="fa-solid fa-right-from-bracket me-2"
                            style="width: 18px"
                        ></i
                        >{{ loggingOut ? "Logging out..." : "Logout" }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import axios from "axios";

const token = localStorage.getItem("auth_token");
const name = localStorage.getItem("name");
const email = localStorage.getItem("email");

const HIDE_KEY = "hide_timelog_discrepancy";
const HIDE_DATE_KEY = "hide_timelog_discrepancy_date";

export default {
    name: "AppHeader",
    data() {
        return {
            token,
            user: { name, email },

            notifications: [],
            unreadCount: 0,
            loadingNotifications: false,

            loggingOut: false,

            showTimelogDiscrepancy: true,
            isDarkMode: false,
        };
    },
    computed: {
        userAvatar() {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(
                this.user.name || "User"
            )}&background=4f46e5&color=fff&size=128`;
        },
    },
    mounted() {
        // Initialize dark mode
        this.initTheme();

        // Timelog toggle state
        this.syncTimelogToggle();

        // Fetch notifications
        this.fetchNotificationCount();
        this.notificationInterval = setInterval(
            this.fetchNotificationCount,
            30000
        );

        // Event listener for timelog toggle
        window.addEventListener("timelog-toggle", this.syncTimelogToggle);
    },
    beforeUnmount() {
        clearInterval(this.notificationInterval);
        window.removeEventListener("timelog-toggle", this.syncTimelogToggle);
    },
    methods: {
        /* =====================
       Dark Mode
    ====================== */
        initTheme() {
            const storageKey = "theme-preference";
            const savedTheme = localStorage.getItem(storageKey);
            const systemPrefersDark = window.matchMedia(
                "(prefers-color-scheme: dark)"
            ).matches;

            // Determine theme: saved preference > system preference > light
            const currentTheme =
                savedTheme || (systemPrefersDark ? "dark" : "light");
            this.isDarkMode = currentTheme === "dark";

            // Apply immediately
            this.applyTheme();

            // Listen for system theme changes
            window
                .matchMedia("(prefers-color-scheme: dark)")
                .addEventListener("change", (e) => {
                    this.isDarkMode = e.matches;
                    this.applyTheme();
                    localStorage.setItem(
                        "theme-preference",
                        this.isDarkMode ? "dark" : "light"
                    );
                });
        },

        handleThemeToggle() {
            const theme = this.isDarkMode ? "dark" : "light";
            localStorage.setItem("theme-preference", theme);
            this.applyTheme();
        },

        applyTheme() {
            const theme = this.isDarkMode ? "dark" : "light";

            document.documentElement.classList.remove("light", "dark");
            document.body.classList.remove("light", "dark");
            document.documentElement.classList.add(theme);
            document.body.classList.add(theme);

            // Bootstrap attribute
            document.documentElement.setAttribute("data-bs-theme", theme);

            // Update switch aria-label
            const switchEl = document.querySelector("#darkModeSwitch");
            if (switchEl) switchEl.setAttribute("aria-label", theme);
        },

        /* =====================
       Timelog Toggle
    ====================== */
        syncTimelogToggle() {
            const today = new Date().toDateString();
            const hidden = localStorage.getItem(HIDE_KEY);
            const hideDate = localStorage.getItem(HIDE_DATE_KEY);

            if (hidden === "true" && hideDate === today) {
                this.showTimelogDiscrepancy = false;
            } else {
                localStorage.removeItem(HIDE_KEY);
                localStorage.removeItem(HIDE_DATE_KEY);
                this.showTimelogDiscrepancy = true;
            }
        },

        handleTimelogToggle() {
            if (!this.showTimelogDiscrepancy) {
                localStorage.setItem(HIDE_KEY, "true");
                localStorage.setItem(HIDE_DATE_KEY, new Date().toDateString());
            } else {
                localStorage.removeItem(HIDE_KEY);
                localStorage.removeItem(HIDE_DATE_KEY);
            }
            window.dispatchEvent(new Event("timelog-toggle"));
        },

        /* =====================
       Notifications
    ====================== */
        async fetchNotificationCount() {
            try {
                const res = await axios.get("/api/notifications/unread-count");
                this.unreadCount = res.data.count ?? 0;
            } catch (err) {
                console.error(err);
            }
        },

        async loadNotifications() {
            if (this.notifications.length) return;
            this.loadingNotifications = true;
            try {
                const res = await axios.get("/api/notifications", {
                    params: { limit: 5 },
                });
                this.notifications = res.data.data || [];
            } catch (err) {
                console.error(err);
            } finally {
                this.loadingNotifications = false;
            }
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

        viewAllNotifications() {
            window.location.href = "/notifications";
        },

        /* =====================
       Logout & UI
    ====================== */
        async logout() {
            if (this.loggingOut) return;
            this.loggingOut = true;
            try {
                await axios.post("/logout");
                window.location.href = "/login";
            } catch (err) {
                console.error(err);
                this.loggingOut = false;
            }
        },

        toggleMobileMenu() {
            document.querySelector("aside")?.classList.toggle("mobile-open");
            document
                .querySelector(".sidebar-overlay")
                ?.classList.toggle("active");
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
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../sass/variables";

.dropdown-item {
    &:hover {
        background-color: lighten($primary, 60);
        color: $dark;
        transition: background-color 0.2s ease;
    }
}

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

@media (max-width: 767.98px) {
    .dropdown-menu {
        min-width: 300px !important;
        label {
            font-size: 10px !important;
        }
    }
}
</style>
