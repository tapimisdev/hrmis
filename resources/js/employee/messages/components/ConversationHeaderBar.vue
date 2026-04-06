<template>
    <div class="conversation-panel__top">
        <div class="conversation-user">
            <span class="conversation-user__avatar">
                <img :src="activeUserAvatar" alt="profile" />
                <span
                    class="conversation-user__status-dot"
                    :class="{
                        'conversation-user__status-dot--active': isOnline,
                        'conversation-user__status-dot--inactive': !isOnline,
                    }"
                ></span>
            </span>
            <div class="conversation-user__text text-truncate">
                <div class="conversation-user__eyebrow">
                    {{ isGroup ? "Group conversation" : "Direct message" }}
                </div>
                <h2 class="conversation-user__name">{{ activeUserName }}</h2>
                <div class="conversation-user__status">{{ activeUserStatus }}</div>
            </div>
        </div>

        <div class="conversation-actions">
            <template v-if="isGroup">
                <button
                    type="button"
                    class="conversation-info-btn"
                    aria-label="Invite users"
                    title="Invite users"
                    @click="$emit('invite-members')"
                >
                    <i class="fa-solid fa-user-plus"></i>
                </button>
                <button
                    type="button"
                    class="conversation-info-btn"
                    aria-label="See members"
                    title="See members"
                    @click="$emit('show-members')"
                >
                    <i class="fa-solid fa-users"></i>
                </button>
                <button
                    type="button"
                    class="conversation-info-btn conversation-info-btn--danger"
                    aria-label="Leave group"
                    title="Leave group"
                    @click="$emit('leave-group')"
                >
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
                <button
                    type="button"
                    class="conversation-info-btn conversation-info-btn--info"
                    aria-label="Group info"
                    title="Group info"
                    @click="$emit('show-info')"
                >
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </template>
            <button
                v-else-if="activeUser"
                type="button"
                class="conversation-info-btn conversation-info-btn--info"
                aria-label="Conversation info"
                title="Conversation info"
                @click="$emit('show-info')"
            >
                <i class="fa-solid fa-circle-info"></i>
            </button>
            <button
                type="button"
                class="icon-chip contacts-panel__mobile-close"
                aria-label="Open user list"
                title="Open user list"
                @click="$emit('open-users-panel')"
            >
                <i class="fa-solid fa-user-group"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "ConversationHeaderBar",
    props: {
        activeUser: {
            type: Object,
            default: null,
        },
        activeUserName: {
            type: String,
            default: "Select a conversation",
        },
        activeUserAvatar: {
            type: String,
            default: "",
        },
        activeUserStatus: {
            type: String,
            default: "",
        },
        isOnline: {
            type: Boolean,
            default: false,
        },
        isGroup: {
            type: Boolean,
            default: false,
        },
    },
    emits: [
        "invite-members",
        "show-members",
        "leave-group",
        "show-info",
        "open-users-panel",
    ],
};
</script>
