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
                v-if="onlineCount > 0"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success"
                style="font-size: 0.65rem; padding: 0.2rem 0.45rem"
            >
                {{ onlineCount }}
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
                <li
                    v-if="loadingUsers"
                    class="text-center py-5 text-muted"
                >
                    <div
                        class="spinner-border spinner-border-sm text-secondary"
                        role="status"
                    >
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </li>

                <!-- Empty -->
                <li
                    v-else-if="users.length === 0"
                    class="text-center py-5 text-muted"
                >
                    <i class="fa-regular fa-user mb-2" style="font-size: 2rem"></i>
                    <p class="mb-0">No users found</p>
                </li>

                <template v-else>
                    <li
                        v-for="user in sortedUsers"
                        :key="user.id"
                        class="cursor-pointer"
                        @click="openMessageBox(user)"
                        @keyup.enter="openMessageBox(user)"
                        tabindex="0"
                        role="button"
                    >
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
                                    <div
                                        class="overlay-online"
                                        :class="user.isOnline ? 'bg-success' : 'bg-secondary'"
                                    ></div>
                                </div>
                                <div class="flex-grow-1 mt-1">
                                    <div class="fw-semibold">{{ user.name }}</div>
                                    <small
                                        class="text-muted"
                                        style="
                                            font-size: 11px;
                                            position: relative;
                                            top: -3px;
                                        "
                                    >
                                        {{ user.statusLabel }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </li>
                </template>
            </div>
        </ul>

        <div
            v-if="messagePanelOpen"
            class="message-overlay"
            @click.self="closeMessageBox"
        >
            <div class="message-panel shadow-lg">
                <div class="message-panel__header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-list">
                            <img
                                v-if="selectedUser?.profile"
                                :src="selectedUser.profile"
                                class="rounded-circle"
                                style="object-fit: cover"
                            />
                            <div
                                v-else
                                class="rounded-circle bg-success d-flex align-items-center justify-content-center"
                                style="color: white"
                            >
                                {{ selectedUser?.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div
                                class="overlay-online"
                                :class="selectedUser?.isOnline ? 'bg-success' : 'bg-secondary'"
                            ></div>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ selectedUser?.name }}</div>
                            <small class="text-muted">{{ selectedUser?.statusLabel }}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm theme-button" @click="closeMessageBox">
                        Close
                    </button>
                </div>

                <div class="message-panel__body">
                    <div v-if="loadingConversation" class="text-center py-4 text-muted">
                        Loading conversation...
                    </div>

                    <div
                        v-else-if="conversationMessages.length === 0"
                        class="text-center py-4 text-muted"
                    >
                        No messages yet. Start the conversation.
                    </div>

                    <div v-else class="d-flex flex-column gap-2">
                        <div
                            v-for="message in conversationMessages"
                            :key="message.id"
                            class="message-bubble"
                            :class="message.is_mine ? 'message-bubble--mine' : 'message-bubble--theirs'"
                        >
                            <div
                                v-if="message.reply_to_id"
                                class="reply-preview"
                            >
                                <div class="reply-preview__body">
                                    {{ getReplyPreview(message) }}
                                </div>
                            </div>
                            <div>{{ message.body }}</div>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <small>{{ formatMessageTime(message.created_at) }}</small>
                                <button
                                    type="button"
                                    class="btn btn-link p-0 text-decoration-none reply-action"
                                    @click="startReply(message)"
                                    title="Reply"
                                >
                                    <i class="fa-solid fa-reply"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <form class="message-panel__footer" @submit.prevent="sendMessage">
                    <div
                        v-if="replyTargetMessage"
                        class="reply-composer mb-3"
                    >
                        <div class="reply-composer__bar">
                            <div class="reply-composer__meta">
                                <small class="reply-composer__label">Replying</small>
                                <div class="reply-composer__body">
                                    {{ replyTargetMessage.body }}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-link p-0 reply-composer__cancel"
                                @click="cancelReply"
                                title="Cancel reply"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                    <textarea
                        v-model="messageDraft"
                        class="form-control"
                        rows="3"
                        placeholder="Type a message..."
                        :disabled="sendingMessage || !selectedUser"
                    ></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            1:1 message
                        </small>
                        <button
                            type="submit"
                            class="btn px-4 theme-button"
                            :disabled="sendingMessage || !messageDraft.trim()"
                        >
                            {{ sendingMessage ? "Sending..." : "Send" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "OnlineUsers",
    props: {
        userId: {
            type: Number,
            default: null,
        },
    },
    data() {
        const token = localStorage.getItem("auth_token");
        return {
            token,
            users: [],
            onlineUserIds: [],
            loadingUsers: false,
            statusInterval: null,
            messagePanelOpen: false,
            selectedUser: null,
            conversationMessages: [],
            loadingConversation: false,
            sendingMessage: false,
            messageDraft: "",
            messageChannel: null,
            replyTargetMessage: null,
        };
    },
    computed: {
        onlineCount() {
            return this.users.filter((user) => user.isOnline).length;
        },
        sortedUsers() {
            return [...this.users].sort((a, b) => {
                if (a.isOnline !== b.isOnline) {
                    return a.isOnline ? -1 : 1;
                }

                return a.name.localeCompare(b.name);
            });
        },
    },
    mounted() {
        window.Echo.join("online-users")
            .here((users) => {
                this.onlineUserIds = users.map((user) => user.id);
                this.saveLastSeen(users.map((user) => user.id));
                this.syncUsersOnlineState();
            })
            .joining((user) => {
                if (!this.onlineUserIds.includes(user.id)) {
                    this.onlineUserIds.push(user.id);
                }
                this.markUserSeen(user.id);
                this.syncUsersOnlineState();
            })
            .leaving((user) => {
                this.onlineUserIds = this.onlineUserIds.filter(
                    (id) => id !== user.id,
                );
                this.markUserSeen(user.id);
                this.syncUsersOnlineState();
            });

        this.loadUsers();
        this.statusInterval = setInterval(() => {
            this.syncUsersOnlineState();
        }, 60000);

        this.subscribeToDirectMessages();
    },
    beforeUnmount() {
        window.Echo.leave("online-users");
        this.leaveDirectMessageChannel();
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
        }
    },
    methods: {
        async loadUsers() {
            this.loadingUsers = true;

            try {
                const { data } = await axios.get("/api/users", {
                    headers: this.token
                        ? { Authorization: `Bearer ${this.token}` }
                        : {},
                });

                this.users = data.map((user) => ({
                    ...user,
                    isSelf: this.userId ? user.id === this.userId : false,
                    isOnline: this.onlineUserIds.includes(user.id),
                    lastSeenAt: this.getLastSeen(user.id),
                    statusLabel: this.getStatusLabel(user.id),
                }))
                .filter((user) => !user.isSelf);
            } catch (error) {
                console.error("Failed to load online users list:", error);
                this.users = [];
            } finally {
                this.loadingUsers = false;
            }
        },
        syncUsersOnlineState() {
            this.users = this.users.map((user) => ({
                ...user,
                isOnline: this.onlineUserIds.includes(user.id),
                lastSeenAt: this.getLastSeen(user.id),
                statusLabel: this.getStatusLabel(user.id),
            }));
        },
        openMessageBox(user) {
            this.selectedUser = user;
            this.messagePanelOpen = true;
            this.messageDraft = "";
            this.replyTargetMessage = null;
            this.loadConversation();
        },
        closeMessageBox() {
            this.messagePanelOpen = false;
            this.selectedUser = null;
            this.conversationMessages = [];
            this.messageDraft = "";
            this.replyTargetMessage = null;
            this.loadingConversation = false;
        },
        async loadConversation(scrollAfterLoad = true) {
            if (!this.selectedUser) return;

            this.loadingConversation = true;

            try {
                const { data } = await axios.get(
                    `/api/direct-messages/${this.selectedUser.id}`,
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                this.conversationMessages = data.messages ?? [];
                this.loadingConversation = false;

                if (scrollAfterLoad) {
                    this.$nextTick(() => {
                        const body = this.$el.querySelector(".message-panel__body");
                        if (body) {
                            body.scrollTop = body.scrollHeight;
                        }
                    });
                }
            } catch (error) {
                console.error("Failed to load conversation:", error);
                this.conversationMessages = [];
            } finally {
                this.loadingConversation = false;
            }
        },
        async sendMessage() {
            const body = this.messageDraft.trim();
            if (!body || !this.selectedUser) return;

            this.sendingMessage = true;

            try {
                const { data } = await axios.post(
                    "/api/direct-messages",
                    {
                        recipient_id: this.selectedUser.id,
                        body,
                        reply_to_id: this.replyTargetMessage?.id ?? null,
                    },
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                if (data.message) {
                    this.upsertConversationMessage({
                        ...data.message,
                        is_mine: true,
                    });
                    this.messageDraft = "";
                    this.replyTargetMessage = null;

                    this.$nextTick(() => {
                        const bodyEl = this.$el.querySelector(".message-panel__body");
                        if (bodyEl) {
                            bodyEl.scrollTop = bodyEl.scrollHeight;
                        }
                    });
                }
            } catch (error) {
                console.error("Failed to send message:", error);
            } finally {
                this.sendingMessage = false;
            }
        },
        subscribeToDirectMessages() {
            if (!this.userId) return;

            this.leaveDirectMessageChannel();

            this.messageChannel = window.Echo.private(`direct-messages.${this.userId}`)
                .listen(".direct-message.sent", (event) => {
                    const message = event?.message;
                    if (!message) return;

                    const partnerId =
                        message.sender_id === this.userId
                            ? message.recipient_id
                            : message.sender_id;

                    if (message.sender_id === this.userId) {
                        if (
                            this.messagePanelOpen &&
                            this.selectedUser &&
                            this.selectedUser.id === partnerId
                        ) {
                            this.upsertConversationMessage({
                                ...message,
                                is_mine: true,
                            });

                            this.$nextTick(() => {
                                const bodyEl = this.$el.querySelector(".message-panel__body");
                                if (bodyEl) {
                                    bodyEl.scrollTop = bodyEl.scrollHeight;
                                }
                            });
                        }

                        return;
                    }

                    const senderUser = this.users.find((user) => user.id === partnerId) || {
                        id: partnerId,
                        name: "New message",
                        profile: null,
                        isOnline: true,
                        statusLabel: "Online",
                    };

                    if (!this.messagePanelOpen || !this.selectedUser || this.selectedUser.id !== partnerId) {
                        this.selectedUser = senderUser;
                        this.messagePanelOpen = true;
                        this.messageDraft = "";
                        this.loadConversation();
                        return;
                    }

                    this.upsertConversationMessage({
                        ...message,
                        is_mine: false,
                    });

                    this.loadingConversation = false;

                    this.$nextTick(() => {
                        const bodyEl = this.$el.querySelector(".message-panel__body");
                        if (bodyEl) {
                            bodyEl.scrollTop = bodyEl.scrollHeight;
                        }
                    });
                });
        },
        leaveDirectMessageChannel() {
            if (!this.userId) return;

            window.Echo.leave(`direct-messages.${this.userId}`);
            this.messageChannel = null;
        },
        upsertConversationMessage(message) {
            const existingIndex = this.conversationMessages.findIndex(
                (item) => item.id === message.id,
            );

            if (existingIndex === -1) {
                this.conversationMessages.push(message);
                return;
            }

            this.conversationMessages.splice(existingIndex, 1, message);
        },
        startReply(message) {
            this.replyTargetMessage = message;
            this.messageDraft = "";
        },
        cancelReply() {
            this.replyTargetMessage = null;
        },
        getReplyPreview(message) {
            const replyTarget = message.reply_to
                ? message.reply_to
                : this.conversationMessages.find((item) => item.id === message.reply_to_id);

            if (!replyTarget) return "Original message not available";

            return replyTarget.body;
        },
        getLastSeenStore() {
            try {
                return JSON.parse(localStorage.getItem("online-users-last-seen") || "{}");
            } catch {
                return {};
            }
        },
        saveLastSeen(userIds = []) {
            const store = this.getLastSeenStore();
            const now = Date.now();

            userIds.forEach((id) => {
                store[id] = now;
            });

            localStorage.setItem("online-users-last-seen", JSON.stringify(store));
        },
        markUserSeen(userId) {
            const store = this.getLastSeenStore();
            store[userId] = Date.now();
            localStorage.setItem("online-users-last-seen", JSON.stringify(store));
        },
        getLastSeen(userId) {
            const store = this.getLastSeenStore();
            return store[userId] ?? null;
        },
        getStatusLabel(userId) {
            const isOnline = this.onlineUserIds.includes(userId);

            if (isOnline) {
                return "Online";
            }

            const lastSeenAt = this.getLastSeen(userId);

            if (!lastSeenAt) {
                return "Offline";
            }

            const mins = Math.max(1, Math.floor((Date.now() - lastSeenAt) / 60000));

            if (mins < 60) return `Active ${mins} min ago`;
            if (mins < 1440) return `Active ${Math.floor(mins / 60)} hrs ago`;
            return `Active ${Math.floor(mins / 1440)} days ago`;
        },
        formatMessageTime(timestamp) {
            if (!timestamp) return "";

            const date = new Date(timestamp);
            const mins = Math.floor((Date.now() - date.getTime()) / 60000);

            if (mins < 1) return "just now";
            if (mins < 60) return `${mins} min ago`;
            if (mins < 1440) return `${Math.floor(mins / 60)} hrs ago`;
            return `${Math.floor(mins / 1440)} days ago`;
        },
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

.cursor-pointer {
    cursor: pointer;
}

.user-list .overlay-online {
    content: "";
    position: absolute;
    width: 12px;
    height: 12px;
    bottom: 2px;
    right: -2px;
    border-radius: 50%;
    z-index: 999;
    border: 2px solid white;
}

.user-list .overlay-online.bg-secondary {
    background-color: #9ca3af !important;
}

.message-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 2000;
}

.message-panel {
    width: min(100%, 620px);
    max-height: min(90vh, 760px);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bs-border-color);
}

.message-panel__header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: var(--bs-body-bg);
}

.message-panel__body {
    padding: 1rem 1.25rem;
    overflow-y: auto;
    flex: 1;
    background: linear-gradient(
        180deg,
        rgba(var(--bs-body-bg-rgb), 0.96) 0%,
        rgba(var(--bs-body-bg-rgb), 1) 100%
    );
}

.message-panel__footer {
    border-top: 1px solid var(--bs-border-color);
    padding: 1rem 1.25rem 1.25rem;
    background: var(--bs-body-bg);
}

.message-bubble {
    max-width: 78%;
    padding: 0.75rem 0.9rem;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    word-break: break-word;
}

.message-bubble small {
    opacity: 0.7;
}

.reply-preview,
.reply-composer {
    padding: 0.7rem 0.8rem;
    border-radius: 14px;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
    background: rgba(var(--bs-body-color-rgb), 0.03);
}

.reply-preview {
    margin-bottom: 0.6rem;
    border-left: 3px solid var(--bs-primary);
    padding: 0.45rem 0.7rem 0.45rem 0.8rem;
}

.reply-composer__label {
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.65;
    margin-bottom: 0.25rem;
}

.reply-preview__body,
.reply-composer__body {
    font-size: 0.92rem;
    color: var(--bs-body-color);
    word-break: break-word;
}

.reply-action {
    font-size: 0.8rem;
    color: rgba(var(--bs-body-color-rgb), 0.55);
}

.reply-action:hover {
    color: var(--bs-primary);
}

.message-bubble--mine {
    align-self: flex-end;
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.95), var(--bs-primary));
    color: var(--bs-white);
    border-bottom-right-radius: 6px;
    box-shadow: 0 6px 20px rgba(var(--bs-primary-rgb), 0.12);
}

.message-bubble--theirs {
    align-self: flex-start;
    background: rgba(var(--bs-body-color-rgb), 0.05);
    color: var(--bs-body-color);
    border-bottom-left-radius: 6px;
}

.theme-button {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
}

.theme-button:hover {
    background: rgba(var(--bs-body-color-rgb), 0.06);
    color: var(--bs-body-color);
}

.theme-button:disabled {
    opacity: 0.65;
}

.reply-composer__bar {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.reply-composer__meta {
    min-width: 0;
    flex: 1;
}

.reply-composer__cancel {
    color: rgba(var(--bs-body-color-rgb), 0.55);
    line-height: 1;
}

.reply-composer__cancel:hover {
    color: var(--bs-body-color);
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
