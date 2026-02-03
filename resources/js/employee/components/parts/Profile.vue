<template>
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
                    <i class="fa-regular fa-user me-2" style="width: 18px"></i
                    >My Profile
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
</template>

<script>
import axios from "axios";

export default {
    name: "ProfileComponent",
    data() {
        const name = localStorage.getItem("name");
        const email = localStorage.getItem("email");

        return {
            user: { name, email },
            loggingOut: false,
        };
    },
    computed: {
        userAvatar() {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(
                this.user.name || "User",
            )}&background=4f46e5&color=fff&size=128`;
        },
    },
    methods: {
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

<style lang="scss" scoped>
// No specific styles for profile component in the original
</style>
