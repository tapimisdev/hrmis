<template>
    <MessagesSidebar
        :show-mobile-users-panel="showMobileUsersPanel"
        :mobile-users-panel-closing="mobileUsersPanelClosing"
    >
        <template #header>
            <div class="contacts-panel__header-block">
                <div class="contacts-panel__header">
                    <div>
                        <div class="contacts-panel__eyebrow">Conversations</div>
                        <div class="contacts-panel__title-row">
                            <h2>Inbox</h2>
                            <span class="contacts-panel__count-badge">
                                {{ visibleUsers.length }}
                            </span>
                        </div>
                        <p class="contacts-panel__subtitle">
                            Direct and group chats synced with your HRIS workspace.
                        </p>
                    </div>

                    <div class="contacts-panel__actions">
                        <button
                            type="button"
                            class="icon-chip contacts-panel__mobile-close"
                            aria-label="Close user list"
                            title="Close user list"
                            @click="$emit('close-mobile-users-panel')"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <button
                            type="button"
                            class="icon-chip"
                            aria-label="Create group chat"
                            title="Create group chat"
                            @click="$emit('open-group-chat-modal')"
                        >
                            <i class="fa-solid fa-people-group"></i>
                        </button>
                        <button
                            v-if="groupChatRequestHistory.length"
                            type="button"
                            class="icon-chip"
                            aria-label="Request history"
                            title="Request history"
                            @click="$emit('open-group-chat-requests-modal')"
                        >
                            <i class="fa-solid fa-list-check"></i>
                            <span class="contacts-panel__action-badge">
                                {{ groupChatRequestHistory.length }}
                            </span>
                        </button>
                        <button
                            v-if="isAdmin"
                            type="button"
                            class="icon-chip"
                            aria-label="Pending approvals"
                            title="Pending approvals"
                            @click="$emit('open-approval-modal')"
                        >
                            <i class="fa-solid fa-user-check"></i>
                            <span
                                v-if="pendingGroupChatApprovals.length"
                                class="contacts-panel__action-badge"
                            >
                                {{ pendingGroupChatApprovals.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="contacts-panel__utility-row">
                    <a href="/employee/dashboard" class="contacts-panel__utility-link">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Dashboard</span>
                    </a>
                    <button
                        type="button"
                        class="contacts-panel__utility-badge"
                        @click="$emit('open-beta-info-modal')"
                    >
                        <i class="fa-solid fa-flask"></i>
                        <span>Beta</span>
                    </button>
                    <button
                        type="button"
                        class="contacts-panel__utility-badge"
                        @click="$emit('open-privacy-info-modal')"
                    >
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Privacy</span>
                    </button>
                </div>
            </div>
        </template>

        <template #search>
            <div class="contacts-panel__search">
                <div class="search-shell">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        :value="searchQuery"
                        type="text"
                        placeholder="Search conversations"
                        @input="$emit('update:search-query', $event.target.value)"
                    />
                </div>
            </div>
        </template>

        <template #list>
            <div class="contacts-list">
                <template v-if="showInitialPageSkeleton">
                    <div
                        v-for="index in 7"
                        :key="`contact-skeleton-${index}`"
                        class="contact-card contact-card--skeleton"
                    >
                        <span
                            class="contact-card__avatar contact-card__avatar--skeleton skeleton-shimmer"
                        ></span>
                        <span class="contact-card__body">
                            <span
                                class="contact-card__skeleton-line contact-card__skeleton-line--name skeleton-shimmer"
                            ></span>
                            <span
                                class="contact-card__skeleton-line contact-card__skeleton-line--preview skeleton-shimmer"
                            ></span>
                            <span
                                class="contact-card__skeleton-line contact-card__skeleton-line--status skeleton-shimmer"
                            ></span>
                        </span>
                        <span class="contact-card__meta">
                            <span class="contact-card__skeleton-time skeleton-shimmer"></span>
                        </span>
                    </div>
                </template>

                <template v-else>
                    <div
                        v-for="user in visibleUsers"
                        :key="user.conversation_key || user.id"
                        class="contact-card"
                        :class="{
                            'is-active': user.conversation_key === selectedConversationKey,
                        }"
                        role="button"
                        tabindex="0"
                        @click="$emit('select-user', user)"
                        @keydown.enter.prevent="$emit('select-user', user)"
                        @keydown.space.prevent="$emit('select-user', user)"
                    >
                        <span class="contact-card__avatar">
                            <img :src="user.profile" alt="profile" />
                            <span
                                class="contact-card__status-dot"
                                :class="
                                    isConversationOnline(user)
                                        ? 'contact-card__status-dot--active'
                                        : 'contact-card__status-dot--inactive'
                                "
                            ></span>
                        </span>

                        <span class="contact-card__body">
                            <span class="contact-card__name-row">
                                <span class="contact-card__name">{{ user.name }}</span>
                                <span
                                    v-if="user.unread_count > 0"
                                    class="unread-pill contact-card__name-unread"
                                >
                                    {{ formatUnreadCount(user.unread_count) }}
                                </span>
                            </span>
                            <span class="contact-card__preview">
                                {{ user.preview || getConversationStatusLabel(user) }}
                            </span>
                            <span class="contact-card__status">
                                <i
                                    v-if="user.conversation_type === 'group'"
                                    class="fa-solid fa-people-group me-1"
                                ></i>
                                {{ getConversationStatusLabel(user) }}
                            </span>
                        </span>

                        <span class="contact-card__meta">
                            <span class="contact-card__time">
                                {{ formatConversationTimestamp(user.latest_at) }}
                            </span>
                            <span class="contact-card__actions">
                                <button
                                    type="button"
                                    class="contact-card__more"
                                    :aria-expanded="contactActionMenuKey === user.conversation_key"
                                    aria-label="More conversation actions"
                                    @click.stop="$emit('toggle-contact-action-menu', user)"
                                >
                                    <i class="fa-solid fa-ellipsis"></i>
                                </button>
                                <transition name="fade">
                                    <div
                                        v-if="contactActionMenuKey === user.conversation_key"
                                        class="contact-card__menu"
                                        @click.stop
                                    >
                                        <button
                                            type="button"
                                            class="contact-card__menu-item contact-card__menu-item--danger"
                                            @click.stop="$emit('delete-conversation', user)"
                                        >
                                            <i class="fa-regular fa-trash-can"></i>
                                            <span>Delete messages</span>
                                        </button>
                                    </div>
                                </transition>
                            </span>
                        </span>
                    </div>

                    <div v-if="visibleUsers.length === 0" class="chat-empty mt-4">
                        <div class="chat-empty__icon">
                            <i class="fa-regular fa-comment-dots"></i>
                        </div>
                        <div class="fw-semibold">No conversations match</div>
                        <div class="text-white-50 small">
                            Try a different search or filter.
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </MessagesSidebar>
</template>

<script>
import MessagesSidebar from "./MessagesSidebar.vue";

export default {
    name: "ConversationsPanel",
    components: {
        MessagesSidebar,
    },
    props: {
        showMobileUsersPanel: {
            type: Boolean,
            default: false,
        },
        mobileUsersPanelClosing: {
            type: Boolean,
            default: false,
        },
        visibleUsers: {
            type: Array,
            default: () => [],
        },
        showInitialPageSkeleton: {
            type: Boolean,
            default: false,
        },
        selectedConversationKey: {
            type: String,
            default: null,
        },
        contactActionMenuKey: {
            type: String,
            default: null,
        },
        searchQuery: {
            type: String,
            default: "",
        },
        groupChatRequestHistory: {
            type: Array,
            default: () => [],
        },
        pendingGroupChatApprovals: {
            type: Array,
            default: () => [],
        },
        isAdmin: {
            type: Boolean,
            default: false,
        },
        formatUnreadCount: {
            type: Function,
            required: true,
        },
        getConversationStatusLabel: {
            type: Function,
            required: true,
        },
        formatConversationTimestamp: {
            type: Function,
            required: true,
        },
        isConversationOnline: {
            type: Function,
            required: true,
        },
    },
    emits: [
        "close-mobile-users-panel",
        "open-group-chat-modal",
        "open-group-chat-requests-modal",
        "open-approval-modal",
        "open-beta-info-modal",
        "open-privacy-info-modal",
        "update:search-query",
        "select-user",
        "toggle-contact-action-menu",
        "delete-conversation",
    ],
};
</script>
