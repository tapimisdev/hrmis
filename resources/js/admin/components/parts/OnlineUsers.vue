<template>
    <div class="dropdown position-relative">
        <a
            class="text-decoration-none position-relative d-inline-block"
            href="#"
            id="onlineUsersDropdown"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            style="cursor: pointer"
        >
            <i
                class="fa-solid fa-user-group text-light"
                style="font-size: 1.2rem"
            ></i>

            <span
                v-if="onlineUsers.length > 0"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success"
                style="font-size: 0.65rem; padding: 0.2rem 0.45rem"
            >
                {{ onlineUsers.length }}
            </span>
        </a>

        <ul
            class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0"
            aria-labelledby="onlineUsersDropdown"
            style="
                min-width: 300px;
                border-radius: 8px;
                border: 1px solid rgba(0, 0, 0, 0.2);
            "
        >
            <!-- Header -->
            <li class="px-4 py-3 border-bottom bg-body">
                <h6 class="mb-0 fw-semibold text-uppercase">Online Users</h6>
            </li>

            <!-- Scrollable content -->
            <div style="max-height: 300px; overflow-y: auto;">
                <!-- Empty -->
                <li
                    v-if="onlineUsers.length === 0"
                    class="text-center py-5 text-muted"
                >
                    <i class="fa-regular fa-user mb-2" style="font-size: 2rem"></i>
                    <p class="mb-0">No users online</p>
                </li>

                <li v-for="user in onlineUsers" :key="user.id">
                    <div class="dropdown-item py-2 px-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-list">
                                <img
                                    v-if="user.profile"
                                    :src="user.profile"
                                    class="rounded-circle"
                                    style="object-fit: cover"
                                />
                                <div
                                    v-else
                                    class="rounded-circle bg-success d-flex align-items-center justify-content-center"
                                    style="color: white"
                                >
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="overlay-online"></div>
                            </div>
                            <div class="flex-grow-1 mt-1">
                                <div class="fw-semibold">{{ user.name }}</div>
                                <small
                                    class="text-muted text-uppercase"
                                    style="
                                        font-size: 11px;
                                        position: relative;
                                        top: -3px;
                                    "
                                >
                                    {{ user.position }}
                                </small>
                            </div>
                        </div>
                    </div>
                </li>
            </div>
        </ul>
    </div>
</template>

<script>
export default {
    name: "OnlineUsers",
    props: {
        userId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            onlineUsers: [],
        };
    },
    mounted() {
        window.Echo.join("online-users")
            .here((users) => {
                this.onlineUsers = users
                    .filter(
                        (user) =>
                            user.id !== this.userId && user.isEmployee == true,
                    )
                    .map((user) => ({
                        id: user.id,
                        name: user.name,
                        profile: user.profile ?? null,
                        position: user.position,
                        isEmployee: user.isEmployee,
                    }));
            })
            .joining((user) => {
                if (user.id !== this.userId && user.isEmployee == true) {
                    this.onlineUsers.push({
                        id: user.id,
                        name: user.name,
                        profile: user.profile ?? null,
                        position: user.position,
                        isEmployee: user.isEmployee,
                    });
                }
            })
            .leaving((user) => {
                this.onlineUsers = this.onlineUsers.filter(
                    (u) => u.id !== user.id,
                );
            });
    },
    beforeUnmount() {
        window.Echo.leave("online-users");
    },
};
</script>

<style scoped>
img {
    border: none !important;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    position: relative;
    overflow: hidden;
}

.user-list {
    cursor: pointer;
    position: relative;
}

.user-list .overlay-online {
    content: "";
    position: absolute;
    width: 12px;
    height: 12px;
    bottom: 2px;
    right: -2px;
    background-color: green;
    border-radius: 50%;
    z-index: 999;
    border: 2px solid white;
}

.dropdown-menu {
    max-height: 400px;
    overflow-y: auto;
}

.dropdown-menu::-webkit-scrollbar {
    width: 6px;
}

.dropdown-menu::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

[data-bs-theme="light"] {
  #onlineUsersDropdown i {
    color: var(--primary) !important;
  }
}
</style>
