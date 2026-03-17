<template>
    <!-- Notification Component -->
    <li class="nav-item">
      <online-users :user-id="userId"/>      
    </li>
    <li class="nav-item">
      <notification :user-role="userRole" :user-id="userId" />    
    </li>
    <li>
        <div class="toggle-container">
            <button
                class="theme-toggle"
                id="theme-toggle"
                title="Toggles light & dark"
                :aria-label="
                    isLightTheme
                        ? 'Switch to dark mode'
                        : 'Switch to light mode'
                "
                aria-live="polite"
                @click="toggleTheme"
            >
                <div class="toggle-icon sun" :class="{ active: isLightTheme }">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="5"
                            fill="#FFD700"
                            stroke="#FFD700"
                        />
                        <line x1="12" y1="1" x2="12" y2="3" stroke="#FFD700" />
                        <line
                            x1="12"
                            y1="21"
                            x2="12"
                            y2="23"
                            stroke="#FFD700"
                        />
                        <line
                            x1="4.22"
                            y1="4.22"
                            x2="5.64"
                            y2="5.64"
                            stroke="#FFD700"
                        />
                        <line
                            x1="18.36"
                            y1="18.36"
                            x2="19.78"
                            y2="19.78"
                            stroke="#FFD700"
                        />
                        <line x1="1" y1="12" x2="3" y2="12" stroke="#FFD700" />
                        <line
                            x1="21"
                            y1="12"
                            x2="23"
                            y2="12"
                            stroke="#FFD700"
                        />
                        <line
                            x1="4.22"
                            y1="19.78"
                            x2="5.64"
                            y2="18.36"
                            stroke="#FFD700"
                        />
                        <line
                            x1="18.36"
                            y1="5.64"
                            x2="19.78"
                            y2="4.22"
                            stroke="#FFD700"
                        />
                    </svg>
                </div>
                <div
                    class="toggle-icon moon"
                    :class="{ active: !isLightTheme }"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"
                            fill="#93C5FD"
                            stroke="#93C5FD"
                        />
                    </svg>
                </div>
            </button>
            <div class="tooltip">
                <span class="tooltip-text">{{
                    isLightTheme
                        ? "Switch to dark mode"
                        : "Switch to light mode"
                }}</span>
            </div>
        </div>
    </li>
    <li class="nav-item d-none d-lg-block dropdown">
        <a
            id="navbarDropdown"
            class="nav-link dropdown-toggle text-uppercase"
            href="#"
            role="button"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
        >
            {{ username }}
        </a>
        <div
            class="dropdown-menu dropdown-menu-end dropdown-menu-modern"
            aria-labelledby="navbarDropdown"
        >
            <a class="dropdown-item" href="#" @click.prevent="logout">
                <i class="fa-solid fa-right-from-bracket"></i> {{ loggingOut ? "Logging out..." : "Logout" }}
            </a>
        </div>
    </li>
</template>

<script>
import Notification from "./parts/Notification.vue";
import OnlineUsers from "./parts/OnlineUsers.vue";
export default {
    name: "AdminHeader",
    components: {
        Notification,
        OnlineUsers
    },
    props: {
        username: {
            type: String,
            required: true,
        },
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
        return {
            loggingOut: false,
            isLightTheme: true,
        };
    },
    methods: {
        toggleTheme() {
            this.isLightTheme = !this.isLightTheme;
            // Emit event to parent for global theme change, or handle via store
            this.$emit("theme-toggled", this.isLightTheme);
        },
        async logout() {
            if (this.loggingOut) return;
            this.loggingOut = true;
            try {
                await axios.post("/logout");
                window.location.href = "/login";
            } catch (err) {
                console.error(err);
            } finally {
                this.loggingOut = false;
            }
        },
    },
};
</script>

<style scoped>
svg {
    font-size: 20px !important;
}
</style>
