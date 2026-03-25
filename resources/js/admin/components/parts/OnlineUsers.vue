<template>
    <div class="dropdown position-relative">
        <a
            class="text-decoration-none position-relative d-inline-block"
            href="#"
            ref="onlineUsersDropdownTrigger"
            id="onlineUsersDropdown"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            style="cursor: pointer"
        >
            <i
                class="fa-solid fa-user-group theme-icon"
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
            class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0 online-users-menu"
            aria-labelledby="onlineUsersDropdown"
            style="
                border-radius: 8px;
            "
            >
            <!-- Header -->
            <li class="px-4 py-3 border-bottom bg-body online-users-menu__header">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                    <h6 class="mb-0 fw-semibold text-uppercase">Online Users</h6>
                    <small class="theme-muted">{{ onlineCount }} online</small>
                </div>
                <div class="search-shell">
                    <i class="fa-solid fa-magnifying-glass search-shell__icon"></i>
                    <input
                        v-model="userSearch"
                        type="text"
                        class="form-control form-control-sm search-shell__input"
                        placeholder="Search users..."
                    />
                </div>
            </li>

            <!-- User list -->
            <div class="online-users-list">
                <li
                    v-if="loadingUsers"
                    class="text-center py-5 theme-muted"
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
                        class="text-center py-5 theme-muted"
                    >
                        <i class="fa-regular fa-user mb-2" style="font-size: 2rem"></i>
                        <p class="mb-0">No users found</p>
                    </li>

                    <li
                        v-else-if="filteredUsers.length === 0"
                        class="text-center py-5 theme-muted"
                    >
                        <i class="fa-solid fa-magnifying-glass mb-2" style="font-size: 1.5rem"></i>
                        <p class="mb-0">No matching users</p>
                    </li>

                <template v-else>
                    <li
                        v-for="user in filteredUsers"
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
                                </div>
                                <div class="flex-grow-1 mt-1">
                                    <div class="fw-semibold">{{ user.name }}</div>
                                    <small
                                        class="theme-muted"
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
            v-if="chatDockVisible"
            class="message-dock"
        >
            <transition name="fade">
                <div
                    v-if="messagePanelOpen"
                    class="message-panel"
                    @click="handleInterfaceClick"
                >
                    <div class="message-panel__header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-list">
                                <img
                                    v-if="selectedUserState?.profile"
                                    :src="selectedUserState.profile"
                                    class="rounded-circle"
                                    style="object-fit: cover"
                                />
                                <div
                                    v-else
                                    class="rounded-circle bg-success d-flex align-items-center justify-content-center"
                                    style="color: white"
                                >
                                    {{ selectedUserState?.name?.charAt(0)?.toUpperCase() }}
                                </div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ selectedUserState?.name }}</div>
                                <small class="theme-muted">{{ selectedUserState?.statusLabel }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm theme-button" @click="closeMessageBox">
                                Close
                            </button>
                        </div>
                    </div>

                    <div
                        class="message-panel__body"
                        ref="conversationBody"
                        @scroll.passive="handleConversationScroll"
                    >
                        <div
                            v-if="loadingConversation"
                            class="message-panel__loading"
                        >
                            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                            <div class="theme-muted mt-2">Loading messages...</div>
                        </div>

                        <div
                            v-else-if="conversationMessages.length === 0"
                            class="message-panel__empty theme-muted"
                        >
                            No messages yet. Start the conversation.
                        </div>

                        <div v-else class="message-panel__messages d-flex flex-column gap-2">
                            <div
                                v-if="loadingOlderConversation"
                                class="message-panel__older-loading"
                            >
                                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                <div class="theme-muted mt-2">Loading messages...</div>
                            </div>

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
                                <div class="w-100">
                                    <small>{{ formatMessageTime(message.created_at) }}</small>
                                    <div class="d-flex align-items-center gap-2">
                                        <small
                                            v-if="message.is_mine"
                                            class="message-status"
                                            :class="message.read_at ? 'message-status--seen' : 'message-status--sent'"
                                        >
                                            {{ message.read_at ? `Seen at ${formatSeenAt(message.read_at)}` : 'Sent' }}
                                        </small>
                                        <button
                                            type="button"
                                            class="btn p-0 text-decoration-none reply-action"
                                            @click="startReply(message)"
                                            title="Reply"
                                        >
                                            <i class="fa-solid fa-reply"></i>
                                        </button>
                                    </div>
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
                            class="form-control message-input"
                            rows="3"
                            placeholder="Type a message..."
                            autocapitalize="off"
                            autocorrect="off"
                            spellcheck="false"
                            :disabled="sendingMessage || !selectedUser"
                            :maxlength="messageMaxChars"
                        ></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="theme-muted">
                                {{ messageDraftLength }}/{{ messageMaxChars }}
                            </small>
                            <button
                                type="submit"
                                class="btn px-4 theme-button"
                                :disabled="sendingMessage || !messageDraft.trim() || isMessageDraftTooLong"
                            >
                                  {{ sendingMessage ? "Sending..." : "Send" }}
                              </button>
                        </div>
                        <small v-if="isMessageDraftTooLong" class="text-danger mt-2 d-block">
                            Message is too long.
                        </small>
                    </form>
                </div>
            </transition>

            <div class="message-dock__head">
                <button
                    type="button"
                    class="message-dock__toggle"
                    @click="toggleMessagePanel"
                    :title="messagePanelOpen ? 'Hide chat' : 'Open chat'"
                >
                    <div class="message-dock__avatar">
                        <img
                            v-if="selectedUserState?.profile"
                            :src="selectedUserState.profile"
                            class="rounded-circle"
                            style="object-fit: cover"
                        />
                        <div
                            v-else
                            class="rounded-circle bg-success d-flex align-items-center justify-content-center"
                            style="color: white"
                        >
                            {{ selectedUserState?.name?.charAt(0)?.toUpperCase() || 'DM' }}
                        </div>
                    </div>
                    <div class="message-dock__meta">
                        <div class="fw-semibold message-dock__name">
                            {{ selectedUserState?.name || 'Messages' }}
                        </div>
                    </div>
                    <span
                        v-if="unreadMessageCount > 0"
                        class="badge rounded-pill bg-danger message-dock__badge"
                    >
                        {{ unreadMessageCount }}
                    </span>
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-light message-dock__close"
                    @click.stop="hideChatDock"
                    title="Hide chat"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
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
            userSearch: "",
            statusInterval: null,
            messagePanelOpen: false,
            selectedUser: null,
            conversationMessages: [],
            loadingConversation: false,
            loadingOlderConversation: false,
            conversationPage: 1,
            conversationLastPage: 1,
            conversationHasMore: true,
            chatDockVisible: false,
            sendingMessage: false,
            messageDraft: "",
            messageMaxChars: 2000,
            messageChannel: null,
            replyTargetMessage: null,
            receiveSound: null,
            dockStateKey: `message_dock_state_${localStorage.getItem("auth_user_id") || "guest"}`,
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

                const aLastSeen = Number(a.lastSeenAt || 0);
                const bLastSeen = Number(b.lastSeenAt || 0);

                if (aLastSeen !== bLastSeen) {
                    return bLastSeen - aLastSeen;
                }

                return a.name.localeCompare(b.name);
            });
        },
        filteredUsers() {
            const query = this.userSearch.trim().toLowerCase();

            if (!query) {
                return this.sortedUsers;
            }

            return this.sortedUsers.filter((user) => {
                return (
                    user.name.toLowerCase().includes(query) ||
                    (user.statusLabel || "").toLowerCase().includes(query)
                );
            });
        },
        selectedUserState() {
            if (!this.selectedUser) {
                return null;
            }

            return (
                this.users.find((user) => user.id === this.selectedUser.id) ||
                this.selectedUser
            );
        },
        messageDraftLength() {
            return this.messageDraft.length;
        },
        isMessageDraftTooLong() {
            return this.messageDraftLength > this.messageMaxChars;
        },
        unreadMessageCount() {
            if (this.messagePanelOpen && this.selectedUserState) {
                return 0;
            }

            return this.conversationMessages.filter(
                (message) => !message.is_mine && !message.read_at,
            ).length;
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
        this.restoreDockState();
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
        async openMessageBox(user) {
            this.hideUsersDropdown();
            this.chatDockVisible = true;
            this.selectedUser = user;
            this.messagePanelOpen = true;
            this.saveDockState();
            this.resetConversationState();
            this.messageDraft = "";
            this.replyTargetMessage = null;
            await this.loadConversation({ page: 1, reset: true });
            this.scrollConversationToBottom();
            this.markConversationSeen(user.id);
        },
        closeMessageBox() {
            this.messagePanelOpen = false;
            this.saveDockState();
        },
        hideChatDock() {
            this.messagePanelOpen = false;
            this.chatDockVisible = false;
            this.selectedUser = null;
            this.resetConversationState();
            this.messageDraft = "";
            this.replyTargetMessage = null;
            this.clearDockState();
        },
        hideUsersDropdown() {
            try {
                const trigger = this.$refs.onlineUsersDropdownTrigger;
                const dropdown = trigger?.closest(".dropdown");
                const menu = this.$el.querySelector(".online-users-menu");

                trigger?.classList.remove("show");
                trigger?.setAttribute("aria-expanded", "false");
                menu?.classList.remove("show");
                dropdown?.classList.remove("show");

                const dropdownInstance =
                    window.bootstrap?.Dropdown?.getInstance?.(trigger) ||
                    window.bootstrap?.Dropdown?.getOrCreateInstance?.(trigger);

                dropdownInstance?.hide?.();
                trigger?.blur?.();
            } catch (error) {
                console.error("Failed to hide users dropdown:", error);
            }
        },
        toggleMessagePanel() {
            if (!this.selectedUserState) {
                return;
            }

            if (this.messagePanelOpen) {
                this.closeMessageBox();
                return;
            }

            this.messagePanelOpen = true;
            this.messageDraft = "";
            this.replyTargetMessage = null;
            this.saveDockState();
            this.loadConversation({ page: 1, reset: true }).then(() => {
                this.scrollConversationToBottom();
                this.markConversationSeen(this.selectedUserState?.id);
            });
        },
        resetConversationState() {
            this.conversationMessages = [];
            this.loadingConversation = false;
            this.loadingOlderConversation = false;
            this.conversationPage = 1;
            this.conversationLastPage = 1;
            this.conversationHasMore = true;
        },
        async loadConversation({
            page = 1,
            reset = false,
            preserveScroll = false,
        } = {}) {
            const activeUser = this.selectedUserState;
            if (!activeUser) return;

            const selectedUserId = activeUser.id;
            const isInitialLoad = page === 1 && reset;
            const isOlderLoad = page > 1;
            const bodyEl = this.$refs.conversationBody;
            const previousScrollHeight = preserveScroll && bodyEl ? bodyEl.scrollHeight : 0;
            const previousScrollTop = preserveScroll && bodyEl ? bodyEl.scrollTop : 0;

            if (isOlderLoad) {
                this.loadingOlderConversation = true;
            } else {
                this.loadingConversation = true;
            }

            try {
                const { data } = await axios.get(
                    `/api/direct-messages/${selectedUserId}`,
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                        params: {
                            page,
                            per_page: 20,
                        },
                    },
                );

                if (!this.selectedUserState || this.selectedUserState.id !== selectedUserId) {
                    return;
                }

                const messages = data.messages ?? [];
                const pagination = data.pagination ?? {};
                this.conversationPage = pagination.current_page ?? page;
                this.conversationLastPage = pagination.last_page ?? page;
                this.conversationHasMore = Boolean(pagination.has_more);

                if (reset || page === 1) {
                    this.conversationMessages = messages;
                } else if (messages.length > 0) {
                    const existingIds = new Set(this.conversationMessages.map((item) => item.id));
                    const olderMessages = messages.filter((message) => !existingIds.has(message.id));
                    this.conversationMessages = [...olderMessages, ...this.conversationMessages];
                }

                this.$nextTick(() => {
                    if (!bodyEl) return;

                    if (isOlderLoad && preserveScroll) {
                        const nextScrollHeight = bodyEl.scrollHeight;
                        bodyEl.scrollTop = nextScrollHeight - previousScrollHeight + previousScrollTop;
                        return;
                    }

                    if (page === 1 || isInitialLoad) {
                        bodyEl.scrollTop = bodyEl.scrollHeight;
                    }
                });
            } catch (error) {
                console.error("Failed to load conversation:", error);
            } finally {
                this.loadingConversation = false;
                this.loadingOlderConversation = false;
            }
        },
        handleConversationScroll(event) {
            const body = event?.target;
            if (!body || this.loadingConversation || this.loadingOlderConversation) return;
            if (!this.conversationHasMore || this.conversationPage >= this.conversationLastPage) return;

            if (body.scrollTop > 80) return;

            this.loadConversation({
                page: this.conversationPage + 1,
                preserveScroll: true,
            });
        },
        async sendMessage() {
            const body = this.messageDraft.trim();
            const activeUser = this.selectedUserState;
            if (!body || !activeUser) return;
            if (body.length > this.messageMaxChars) return;

            const selectedUserId = activeUser.id;
            this.sendingMessage = true;

            try {
                const { data } = await axios.post(
                    "/api/direct-messages",
                    {
                        recipient_id: selectedUserId,
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
                            this.selectedUserState &&
                            this.selectedUserState.id === partnerId
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

                    this.playReceiveSound();
                    this.chatDockVisible = true;

                    if (!this.messagePanelOpen) {
                        this.resetConversationState();
                        this.selectedUser = senderUser;
                        this.messagePanelOpen = false;
                        this.messageDraft = "";
                        this.replyTargetMessage = null;
                        this.saveDockState();
                        this.loadConversation({ page: 1, reset: true });
                        return;
                    }

                    if (!this.selectedUserState || this.selectedUserState.id !== partnerId) {
                        this.resetConversationState();
                        this.selectedUser = senderUser;
                        this.messagePanelOpen = true;
                        this.messageDraft = "";
                        this.saveDockState();
                        this.loadConversation({ page: 1, reset: true }).then(() => {
                            this.scrollConversationToBottom();
                        });
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
                })
                .listen(".direct-message.seen", (event) => {
                    const payload = event?.payload;
                    if (!payload || !Array.isArray(payload.message_ids)) return;

                    const threadUserId = payload.reader_id ?? payload.partner_id ?? null;

                    if (
                        !this.messagePanelOpen ||
                        !this.selectedUserState ||
                        this.selectedUserState.id !== threadUserId
                    ) {
                        return;
                    }

                    const readAt = payload.read_at ?? null;
                    const messageIds = new Set(payload.message_ids);

                    this.conversationMessages = this.conversationMessages.map((message) => {
                        if (!messageIds.has(message.id)) {
                            return message;
                        }

                        return {
                            ...message,
                            read_at: readAt,
                        };
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
        scrollConversationToBottom() {
            this.$nextTick(() => {
                const bodyEl = this.$refs.conversationBody || this.$el.querySelector(".message-panel__body");
                if (bodyEl) {
                    bodyEl.scrollTop = bodyEl.scrollHeight;
                }
            });
        },
        handleInterfaceClick() {
            this.markConversationSeen();
        },
        saveDockState() {
            try {
                if (!this.chatDockVisible || !this.selectedUser) {
                    return;
                }

                localStorage.setItem(
                    this.dockStateKey,
                    JSON.stringify({
                        chatDockVisible: true,
                        messagePanelOpen: this.messagePanelOpen,
                        selectedUser: {
                            id: this.selectedUser.id,
                            name: this.selectedUser.name,
                            profile: this.selectedUser.profile || null,
                        },
                    }),
                );
            } catch (error) {
                console.error("Failed to save dock state:", error);
            }
        },
        restoreDockState() {
            try {
                const rawState = localStorage.getItem(this.dockStateKey);
                if (!rawState) return;

                const state = JSON.parse(rawState);
                if (!state?.chatDockVisible || !state?.selectedUser?.id) return;

                this.chatDockVisible = true;
                this.messagePanelOpen = false;
                this.selectedUser = state.selectedUser;
            } catch (error) {
                console.error("Failed to restore dock state:", error);
            }
        },
        clearDockState() {
            try {
                localStorage.removeItem(this.dockStateKey);
            } catch (error) {
                console.error("Failed to clear dock state:", error);
            }
        },
        playReceiveSound() {
            try {
                if (!this.receiveSound) {
                    this.receiveSound = new Audio("/sounds/message.mp3");
                    this.receiveSound.preload = "auto";
                }

                this.receiveSound.currentTime = 0;
                const played = this.receiveSound.play();

                if (played && typeof played.catch === "function") {
                    played.catch(() => {});
                }
            } catch (error) {
                console.error("Failed to play receive sound:", error);
            }
        },
        async markConversationSeen(userId = null) {
            const activeUser = this.selectedUserState;
            if (!activeUser || !this.messagePanelOpen) return;
            if (!this.conversationMessages.some((message) => !message.is_mine && !message.read_at)) {
                return;
            }

            const selectedUserId = userId ?? activeUser.id;

            if (activeUser.id !== selectedUserId) {
                return;
            }

            try {
                const { data } = await axios.post(
                    `/api/direct-messages/${selectedUserId}/seen`,
                    {},
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                if (!this.selectedUser || this.selectedUser.id !== selectedUserId) {
                    return;
                }

                const readAt = data?.read_at ?? null;
                const messageIds = new Set(data?.message_ids ?? []);

                if (!readAt || messageIds.size === 0) return;

                this.conversationMessages = this.conversationMessages.map((message) => {
                    if (!messageIds.has(message.id)) {
                        return message;
                    }

                    return {
                        ...message,
                        read_at: readAt,
                    };
                });
            } catch (error) {
                console.error("Failed to mark conversation as seen:", error);
            }
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
        formatSeenAt(timestamp) {
            if (!timestamp) return "";

            const date = new Date(timestamp);
            return new Intl.DateTimeFormat(undefined, {
                hour: "numeric",
                minute: "2-digit",
            }).format(date);
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

.theme-icon {
    color: var(--bs-body-color);
}

.theme-muted {
    color: var(--bs-secondary-color);
}

.online-users-menu {
    width: max-content;
    min-width: 360px;
    max-width: calc(100vw - 1.5rem);
    overflow-x: hidden;
    z-index: 3000;
}

.online-users-menu__header {
    position: sticky;
    top: 0;
    z-index: 99999;
    background: var(--bs-body-bg);
}

.online-users-list {
    padding-top: 0.35rem;
    overflow: visible;
}

.search-shell {
    position: relative;
    display: flex;
    align-items: center;
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-secondary-bg);
    padding: 0.2rem 0.65rem;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background-color 0.15s ease;
}

.search-shell:focus-within {
    border-color: rgba(var(--bs-primary-rgb), 0.55);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.12);
    background: var(--bs-body-bg);
}

.search-shell__icon {
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
    margin-right: 0.55rem;
    flex-shrink: 0;
}

.search-shell__input {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    color: var(--bs-body-color);
    padding-left: 0;
    padding-right: 0;
    min-width: 0;
}

.search-shell__input::placeholder {
    color: var(--bs-secondary-color);
}

.message-input {
    text-transform: none !important;
}

.cursor-pointer {
    cursor: pointer;
}

.online-users-menu .dropdown-item {
    white-space: normal;
}

.online-users-menu .flex-grow-1 {
    min-width: 0;
}

.online-users-menu .fw-semibold,
.online-users-menu .theme-muted {
    word-break: break-word;
}

.message-dock {
    position: fixed;
    right: 2.1rem;
    bottom: 0.75rem;
    z-index: 1500;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.message-panel {
    position: absolute;
    right: 0;
    bottom: 4.5rem;
    width: min(410px, calc(100vw - 1.5rem));
    height: min(78vh, 640px);
    max-height: min(78vh, 640px);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bs-border-color);
    color: var(--bs-body-color);
}

.message-dock__head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    padding: 0.4rem 0.7rem 0.4rem 0.4rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
}

.message-dock__toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 0;
    background: transparent;
    color: inherit;
    padding: 0;
    text-align: left;
}

.message-dock__avatar {
    position: relative;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
}

.message-dock__avatar img,
.message-dock__avatar > div {
    width: 42px;
    height: 42px;
}

.message-dock__meta {
    min-width: 0;
}

.message-dock__name {
    max-width: 115px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.message-dock__badge {
    position: absolute;
    top: -0.35rem;
    left: 2.1rem;
    z-index: 2;
}

.message-dock__close {
    position: absolute;
    top: -0.35rem;
    right: -0.35rem;
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 999px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
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
    color: var(--bs-body-color);
}

.message-panel__header > .d-flex:first-child {
    min-width: 0;
}

.message-panel__header > .d-flex:last-child {
    flex-shrink: 0;
}

.message-panel__body {
    padding: 1rem 1.25rem;
    overflow-y: auto;
    flex: 1;
    flex-basis: 0;
    min-height: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    scrollbar-gutter: stable;
    overscroll-behavior: contain;
    background: linear-gradient(
        180deg,
        rgba(var(--bs-body-bg-rgb), 0.96) 0%,
        rgba(var(--bs-body-bg-rgb), 1) 100%
    );
    color: var(--bs-body-color);
}

.message-panel__messages {
    width: 100%;
    flex: 1;
    min-height: auto;
    padding: 0 0 10px 0;
    justify-content: flex-end;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}

.message-panel__loading,
.message-panel__empty,
.message-panel__older-loading {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.message-panel__loading {
    min-height: 0;
}

.message-panel__older-loading {
    min-height: 3rem;
    padding: 0.5rem 0 0.75rem;
}

.message-panel__footer {
    border-top: 1px solid var(--bs-border-color);
    padding: 1rem 1.25rem 1.25rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
}

.message-bubble {
    max-width: 78%;
    min-width: 150px;
    padding: 0.75rem 0.9rem;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    word-break: break-word;
}

.message-bubble small {
    opacity: 1;
}

.reply-preview,
.reply-composer {
    padding: 0.7rem 0.8rem;
    border-radius: 14px;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
    background: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
}

.reply-preview {
    margin-bottom: 0.6rem;
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
    color: rgba(var(--bs-body-color), 0.55);
}

.message-status {
    font-size: 11px;
    letter-spacing: 0.02em;
}

.message-status--sent {
    color: rgba(var(--bs-body-color), 0.55);
}

.message-status--seen {
  font-style: italic;
  font-size: 10px;
  position: relative;
  top: 0.5px;
  color: rgba(var(--bs-body-color), 1);
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
    background: var(--bs-tertiary-bg);
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

.message-input {
    color: var(--bs-body-color);
    background: var(--bs-body-bg);
    border-color: var(--bs-border-color);
}

.reply-composer__cancel:hover {
    color: var(--bs-body-color);
}

.dropdown-menu {
    max-height: 400px;
    overflow-y: auto;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-color: var(--bs-border-color);
}

.dropdown-menu::-webkit-scrollbar {
    width: 6px;
}

.dropdown-menu::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

@media (max-width: 576px) {
    .message-dock {
        right: 0.5rem;
        bottom: 0.5rem;
        gap: 0.35rem;
    }

    .message-dock__head {
        padding: 0.3rem 0.55rem 0.3rem 0.32rem;
        max-width: calc(100vw - 1rem);
    }

    .message-dock__avatar,
    .message-dock__avatar img,
    .message-dock__avatar > div {
        width: 36px;
        height: 36px;
    }

    .message-dock__name {
        max-width: 96px;
    }

    .message-panel {
        width: calc(100vw - 1rem);
        right: 0;
        left: auto;
        bottom: 4.15rem;
    }

    .message-panel__header {
        padding: 0.8rem 0.9rem;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .message-panel__header > .d-flex:first-child {
        flex: 1 1 auto;
    }

    .message-panel__header > .d-flex:last-child {
        width: 100%;
        justify-content: flex-end;
    }

    .message-panel__body,
    .message-panel__footer {
        padding-left: 0.9rem;
        padding-right: 0.9rem;
    }
}

[data-bs-theme="light"] {
  #onlineUsersDropdown i {
    color: var(--primary) !important;
  }
}
</style>
