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
            <div class="position-relative" style="width: 40px; height: 40px">
                <div
                    v-if="!avatarLoaded"
                    class="rounded-circle bg-secondary position-absolute top-0 start-0 w-100 h-100 skeleton"
                ></div>

                <img
                    :src="userAvatarUrl || defaultAvatar"
                    alt="Profile"
                    class="rounded-circle w-100 h-100"
                    @load="onAvatarLoad"
                    :style="{
                        objectFit: 'cover',
                        border: '2px solid var(--bs-light)',
                        opacity: avatarLoaded ? 1 : 0,
                        transition: 'opacity 0.3s ease-in-out',
                    }"
                />
            </div>

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
                <div class="fw-semibold text-dark small">{{ user.name }}</div>
                <div class="text-muted" style="font-size: 0.75rem">
                    {{ user.email }}
                </div>
            </li>
            <li>
                <a class="dropdown-item py-2 px-3" href="/employee/profile">
                    <i class="fa-regular fa-user me-2" style="width: 18px"></i>
                    My Profile
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
                    ></i>
                    {{ loggingOut ? "Logging out..." : "Logout" }}
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
        return {
            user: {
                employee_no: null,
                name: "User",
                email: "",
                profile_image: null,
            },
            loggingOut: false,
            userAvatarUrl: "",
            defaultAvatar: "",
            avatarLoaded: false,
        };
    },

    mounted() {
        this.loadProfile();
    },

    methods: {
        async loadProfile() {
            try {
                const { data } = await axios.get("/employee/profile");

                this.user.employee_no = data.personal?.employee_no || null;
                this.user.name = data.user?.name || "User";
                this.user.email = data.user?.email || "";
                this.user.profile_image = data.personal?.profile ?? null;

                this.defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(
                    this.user.name,
                )}&background=4f46e5&color=fff&size=128`;

                this.userAvatarUrl = this.defaultAvatar;

                if (this.user.profile_image && this.user.employee_no) {
                    let avatarUrl = this.user.profile_image.startsWith(
                        "/storage",
                    )
                        ? this.user.profile_image
                        : `/storage/users/${this.user.employee_no}/profile-image/${this.user.profile_image}`;

                    try {
                        await axios.head(avatarUrl);
                        this.userAvatarUrl = avatarUrl;
                    } catch {
                        this.userAvatarUrl = this.defaultAvatar;
                    }
                }
            } catch {
                this.defaultAvatar = `https://ui-avatars.com/api/?name=User&background=4f46e5&color=fff&size=128`;
                this.userAvatarUrl = this.defaultAvatar;
            }
        },
        onAvatarLoad() {
            this.avatarLoaded = true;
        },
        async logout() {
            if (this.loggingOut) return;
            this.loggingOut = true;

            try {
                await axios.post("/logout");

                delete axios.defaults.headers.common["Authorization"];
                localStorage.removeItem("auth_token");
                sessionStorage.removeItem("auth_token");

                window.location.replace("/login");
            } catch (err) {
            } finally {
                this.loggingOut = false;
            }
        },
    },
};
</script>

<style scoped>
.skeleton {
    animation: pulse 1.5s infinite;
    z-index: 2;
}

@keyframes pulse {
    0% {
        opacity: 0.6;
    }
    50% {
        opacity: 0.3;
    }
    100% {
        opacity: 0.6;
    }
}
</style>
