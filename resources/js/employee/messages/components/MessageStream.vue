<template>
    <div
        class="conversation-panel__body"
        ref="conversationBody"
        @scroll.passive="$emit('scroll', $event)"
    >
        <div v-if="showInitialPageSkeleton" class="chat-skeleton">
            <div class="chat-skeleton__date skeleton-shimmer"></div>
            <div
                v-for="index in 6"
                :key="`message-skeleton-${index}`"
                class="chat-skeleton__row"
                :class="
                    index % 2 === 0
                        ? 'chat-skeleton__row--mine'
                        : 'chat-skeleton__row--theirs'
                "
            >
                <div class="chat-skeleton__bubble skeleton-shimmer"></div>
            </div>
        </div>

        <div v-else-if="loadingConversation" class="chat-loading">
            <span class="loader-dot"></span>
            <div class="fw-semibold">Loading messages...</div>
        </div>

        <div v-else-if="!activeUser" class="chat-empty">
            <div class="chat-empty__icon">
                <i class="fa-regular fa-comments"></i>
            </div>
            <div class="fw-semibold">Choose a chat on the left</div>
        </div>

        <div v-else-if="conversationError" class="chat-empty">
            <div class="chat-empty__icon">
                <i class="fa-regular fa-triangle-exclamation"></i>
            </div>
            <div class="fw-semibold">Conversation unavailable</div>
            <div class="text-white-50 small">{{ conversationError }}</div>
        </div>

        <div v-else-if="messages.length === 0" class="chat-empty">
            <div class="chat-empty__icon">
                <i class="fa-regular fa-message"></i>
            </div>
            <div class="fw-semibold">No messages yet</div>
            <div class="text-white-50 small">
                Send the first message to start the conversation.
            </div>
        </div>

        <div v-else class="message-stream">
            <div
                v-if="!conversationHasMore || conversationPage >= conversationLastPage"
                class="conversation-start-marker mt-4 mb-4"
            >
                Your conversation starts here
            </div>

            <div
                v-if="loadingOlderConversation"
                class="chat-loading chat-loading--inline"
            >
                <span class="loader-dot"></span>
                <div class="fw-semibold">Loading messages...</div>
            </div>

            <div
                v-for="message in messages"
                :key="message.id"
                class="message-row"
                :class="[
                    message.is_system ? 'message-row--system' : '',
                    message.is_mine ? 'message-row--mine' : 'message-row--theirs',
                    message.is_unsent ? 'message-row--unsent' : '',
                ]"
                :data-message-id="message.id"
                @click="message.is_system ? null : $emit('select-message', message)"
            >
                <div v-if="message.is_system" class="message-system-note">
                    {{ message.body }}
                </div>
                <div v-else class="message-bubble-wrap">
                    <div
                        class="message-bubble"
                        :class="{ 'message-bubble--unsent': message.is_unsent }"
                    >
                        <div
                            class="message-bubble__floating-actions"
                            :class="{ 'is-open': activeMessageActionsId === message.id }"
                        >
                            <div
                                v-if="message.is_mine && !message.is_unsent"
                                class="bubble-action-group"
                            >
                                <button
                                    type="button"
                                    class="bubble-action"
                                    :class="{ 'is-active': activeMessageActionsId === message.id }"
                                    @click.stop="$emit('toggle-message-actions', message)"
                                    title="More actions"
                                    :aria-label="`More actions for ${message.body || message.attachment?.name || 'message'}`"
                                    :aria-expanded="activeMessageActionsId === message.id"
                                >
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div
                                    v-if="activeMessageActionsId === message.id"
                                    class="bubble-action-menu"
                                >
                                    <button
                                        v-if="message.is_mine && message.body"
                                        type="button"
                                        class="bubble-action bubble-action--menu bubble-action--menu-primary"
                                        @click.stop="$emit('edit-message', message)"
                                        title="Edit message"
                                        :aria-label="`Edit message ${message.body}`"
                                    >
                                        <span class="bubble-action__icon">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </span>
                                        <span class="bubble-action__content">
                                            <span class="bubble-action__label">Edit</span>
                                            <small class="bubble-action__hint">Edit message</small>
                                        </span>
                                    </button>
                                    <button
                                        v-if="message.is_mine"
                                        type="button"
                                        class="bubble-action bubble-action--menu bubble-action--danger"
                                        @click.stop="$emit('unsend-message', message)"
                                        title="Unsend message"
                                        :aria-label="`Unsend message ${message.body || message.attachment?.name || 'message'}`"
                                    >
                                        <span class="bubble-action__icon">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </span>
                                        <span class="bubble-action__content">
                                            <span class="bubble-action__label">Unsend</span>
                                            <small class="bubble-action__hint">Remove for everyone</small>
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="bubble-action bubble-action--menu"
                                        :class="{ 'is-active': Boolean(message.pinned_at) }"
                                        @click.stop="$emit('toggle-pin-message', message)"
                                        :title="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                        :aria-label="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                    >
                                        <span class="bubble-action__icon">
                                            <i
                                                :class="
                                                    message.pinned_at
                                                        ? 'fa-solid fa-thumbtack-slash'
                                                        : 'fa-solid fa-thumbtack'
                                                "
                                            ></i>
                                        </span>
                                        <span class="bubble-action__content">
                                            <span class="bubble-action__label">
                                                {{ message.pinned_at ? "Unpin" : "Pin" }}
                                            </span>
                                            <small class="bubble-action__hint">
                                                {{
                                                    message.pinned_at
                                                        ? "Remove from pinned"
                                                        : "Keep it easy to find"
                                                }}
                                            </small>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <button
                                v-else-if="!message.is_unsent"
                                type="button"
                                class="bubble-action"
                                :class="{ 'is-active': Boolean(message.pinned_at) }"
                                @click.stop="$emit('toggle-pin-message', message)"
                                :title="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                :aria-label="message.pinned_at ? 'Unpin message' : 'Pin message'"
                            >
                                <span class="bubble-action__icon">
                                    <i
                                        :class="
                                            message.pinned_at
                                                ? 'fa-solid fa-thumbtack-slash'
                                                : 'fa-solid fa-thumbtack'
                                        "
                                    ></i>
                                </span>
                            </button>
                            <button
                                v-if="!message.is_unsent"
                                type="button"
                                class="bubble-action"
                                @click.stop="$emit('start-reply', message)"
                            >
                                <i class="fa-solid fa-reply"></i>
                            </button>
                            <button
                                v-if="!message.is_unsent"
                                type="button"
                                class="bubble-action"
                                :class="{
                                    'is-active':
                                        selectedMessageId === message.id && showReactionPicker,
                                }"
                                @click.stop="$emit('toggle-reaction-picker', message)"
                                :aria-pressed="
                                    selectedMessageId === message.id && showReactionPicker
                                "
                            >
                                <i class="fa-regular fa-face-smile"></i>
                            </button>
                            <button
                                v-if="message.attachment"
                                type="button"
                                class="bubble-action"
                                @click.stop="$emit('download-attachment', message.attachment)"
                            >
                                <i class="fa-solid fa-download"></i>
                            </button>
                        </div>

                        <button
                            v-if="message.reply_preview"
                            type="button"
                            class="message-bubble__reply message-bubble__reply--link"
                            @click.stop="$emit('scroll-to-reply-message', message)"
                            :aria-label="`Jump to replied message for ${message.reply_preview}`"
                        >
                            <div class="message-bubble__reply-label">
                                <i class="fa-solid fa-reply"></i>
                                Replied to this message
                            </div>
                            <div>{{ message.reply_preview }}</div>
                        </button>

                        <div
                            v-if="activeConversationIsGroup && !message.is_mine"
                            class="message-bubble__sender"
                        >
                            {{ message.sender_name || "User" }}
                        </div>

                        <div v-if="message.body" class="message-bubble__text">
                            {{ message.body }}
                        </div>
                        <div
                            v-if="message.is_unsent"
                            class="message-bubble__text message-bubble__text--unsent"
                        >
                            Unsent Message
                        </div>

                        <div
                            v-if="message.pinned_at"
                            class="message-pin-chip message-pin-chip--floating"
                            :class="
                                message.is_mine
                                    ? 'message-pin-chip--mine'
                                    : 'message-pin-chip--theirs'
                            "
                            title="Pinned message"
                        >
                            <span class="message-pin-chip__icon">
                                <i class="fa-solid fa-thumbtack"></i>
                            </span>
                        </div>

                        <div
                            v-if="
                                !message.is_unsent &&
                                message.reactions &&
                                message.reactions.length > 0
                            "
                            class="message-reaction-badges message-reaction-badges--floating"
                            :class="
                                message.is_mine
                                    ? 'message-reaction-badges--mine'
                                    : 'message-reaction-badges--theirs'
                            "
                            :title="formatReactionsTooltipText(message.reactions)"
                            role="button"
                            tabindex="0"
                            @click="$emit('open-reactions-modal', message.reactions)"
                            @keydown.enter.prevent="
                                $emit('open-reactions-modal', message.reactions)
                            "
                            @keydown.space.prevent="
                                $emit('open-reactions-modal', message.reactions)
                            "
                        >
                            <span
                                v-for="emoji in getUniqueReactionEmojiList(message.reactions).slice(0, 3)"
                                :key="emoji"
                                class="message-reaction-badge__glyph"
                            >
                                {{ emoji }}
                            </span>
                            <span
                                v-if="getUniqueReactionEmojiList(message.reactions).length > 3"
                                class="message-reaction-count"
                            >
                                +{{ getUniqueReactionEmojiList(message.reactions).length - 3 }}
                            </span>
                        </div>
                        <div
                            v-else-if="!message.is_unsent && message.reaction"
                            class="message-reaction-badge message-reaction-badge--floating"
                            :class="
                                message.is_mine
                                    ? 'message-reaction-badge--mine'
                                    : 'message-reaction-badge--theirs'
                            "
                            :title="getReactionEmojiValue(message.reaction)"
                        >
                            <span class="message-reaction-badge__glyph">
                                {{ getReactionEmojiValue(message.reaction) }}
                            </span>
                        </div>

                        <div
                            v-if="
                                message.attachment &&
                                message.attachment.type === 'image' &&
                                !message.is_unsent
                            "
                            class="message-bubble__attachment message-bubble__attachment--image"
                        >
                            <button
                                type="button"
                                class="message-bubble__image-link"
                                @click.stop="$emit('open-image-gallery', message.attachment)"
                                :aria-label="`Open ${message.attachment.name || 'attachment'} in gallery`"
                            >
                                <img
                                    :src="message.attachment.url"
                                    :alt="message.attachment.name"
                                    @load="$emit('attachment-image-load')"
                                />
                                <span class="message-bubble__attachment-overlay">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </span>
                            </button>
                        </div>

                        <a
                            v-else-if="message.attachment && !message.is_unsent"
                            class="message-bubble__attachment message-bubble__attachment--file"
                            :href="message.attachment.url"
                            target="_blank"
                            rel="noopener"
                            :download="message.attachment.name"
                        >
                            <span class="message-bubble__attachment-icon">
                                <i class="fa-regular fa-file-lines"></i>
                            </span>
                            <span class="message-bubble__attachment-meta">
                                <span class="message-bubble__attachment-name">
                                    {{ message.attachment.name }}
                                </span>
                                <small class="text-white-50">
                                    {{ formatFileSizeText(message.attachment.size) }}
                                </small>
                            </span>
                            <span class="message-bubble__attachment-download">
                                <i class="fa-solid fa-download"></i>
                            </span>
                        </a>

                        <div class="message-bubble__time">
                            <span>{{ formatTimeText(message.created_at) }}</span>
                            <span
                                v-if="message.edited_at"
                                class="message-bubble__time-edit"
                            >
                                · Edited
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="message.is_mine && (showSeenReceipt(message) || !message.read_at)"
                        class="message-bubble__status"
                        :class="
                            showSeenReceipt(message)
                                ? 'message-bubble__status--seen'
                                : 'message-bubble__status--sent'
                        "
                    >
                        <template v-if="showSeenReceipt(message)">
                            <template v-if="activeConversationIsGroup">
                                <button
                                    type="button"
                                    class="message-bubble__seen-group"
                                    :title="formatGroupSeenReceiptTooltipText(message)"
                                    :aria-label="formatGroupSeenReceiptTooltipText(message)"
                                    @click.stop="$emit('open-seen-by-modal', message)"
                                >
                                    <span
                                        v-for="user in getSeenReceiptPreviewUsersList(message)"
                                        :key="`seen-preview-${message.id}-${user.id}`"
                                        class="message-bubble__seen-avatar"
                                    >
                                        <img
                                            :src="getMemberProfileUrl(user)"
                                            :alt="`${getSeenMemberLabel(user)} profile`"
                                        />
                                    </span>
                                    <span
                                        v-if="getSeenReceiptOverflowValue(message) > 0"
                                        class="message-bubble__seen-overflow"
                                    >
                                        +{{ getSeenReceiptOverflowValue(message) }}
                                    </span>
                                </button>
                            </template>
                            <template v-else>
                                <span
                                    class="message-bubble__seen-avatar"
                                    :title="formatSeenReceiptTooltipText(message.read_at)"
                                    :aria-label="formatSeenReceiptTooltipText(message.read_at)"
                                >
                                    <img
                                        :src="getSeenReceiptAvatarUrl()"
                                        :alt="`${activeUserName} profile`"
                                    />
                                </span>
                            </template>
                        </template>
                        <template v-else>Sent</template>
                    </div>
                </div>
            </div>

            <div
                v-if="typingIndicator && activeUser"
                class="message-row message-row--theirs message-row--typing"
            >
                <div class="message-bubble message-bubble--typing">
                    <span class="typing-indicator__dots" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </span>
                    <span class="typing-indicator__label">{{ typingIndicatorLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    <transition name="fade">
        <button
            v-if="showScrollToBottomButton"
            type="button"
            class="message-scroll-bottom"
            @click="$emit('scroll-to-bottom')"
            title="Scroll to bottom"
            aria-label="Scroll to bottom"
        >
            <i class="fa-solid fa-arrow-down"></i>
        </button>
    </transition>
</template>

<script>
export default {
    name: "MessageStream",
    props: {
        showInitialPageSkeleton: {
            type: Boolean,
            default: false,
        },
        loadingConversation: {
            type: Boolean,
            default: false,
        },
        activeUser: {
            type: Object,
            default: null,
        },
        activeUserName: {
            type: String,
            default: "",
        },
        conversationError: {
            type: String,
            default: "",
        },
        messages: {
            type: Array,
            default: () => [],
        },
        conversationHasMore: {
            type: Boolean,
            default: true,
        },
        conversationPage: {
            type: Number,
            default: 1,
        },
        conversationLastPage: {
            type: Number,
            default: 1,
        },
        loadingOlderConversation: {
            type: Boolean,
            default: false,
        },
        activeConversationIsGroup: {
            type: Boolean,
            default: false,
        },
        activeMessageActionsId: {
            type: [Number, String],
            default: null,
        },
        selectedMessageId: {
            type: [Number, String],
            default: null,
        },
        showReactionPicker: {
            type: Boolean,
            default: false,
        },
        typingIndicator: {
            type: Boolean,
            default: false,
        },
        typingIndicatorLabel: {
            type: String,
            default: "typing...",
        },
        showScrollToBottomButton: {
            type: Boolean,
            default: false,
        },
        formatTime: {
            type: Function,
            default: null,
        },
        formatFileSize: {
            type: Function,
            default: null,
        },
        formatReactionsTooltip: {
            type: Function,
            default: null,
        },
        getUniqueReactionEmojis: {
            type: Function,
            default: null,
        },
        getReactionEmoji: {
            type: Function,
            default: null,
        },
        shouldShowSeenReceipt: {
            type: Function,
            default: null,
        },
        formatGroupSeenReceiptTooltip: {
            type: Function,
            default: null,
        },
        formatSeenReceiptTooltip: {
            type: Function,
            default: null,
        },
        getSeenReceiptPreviewUsers: {
            type: Function,
            default: null,
        },
        getSeenReceiptOverflowCount: {
            type: Function,
            default: null,
        },
        getMemberProfile: {
            type: Function,
            default: null,
        },
        getSeenMemberName: {
            type: Function,
            default: null,
        },
        getSeenReceiptAvatar: {
            type: Function,
            default: null,
        },
    },
    emits: [
        "scroll",
        "select-message",
        "toggle-message-actions",
        "edit-message",
        "unsend-message",
        "toggle-pin-message",
        "start-reply",
        "toggle-reaction-picker",
        "download-attachment",
        "scroll-to-reply-message",
        "open-reactions-modal",
        "attachment-image-load",
        "open-image-gallery",
        "open-seen-by-modal",
        "scroll-to-bottom",
    ],
    methods: {
        getBodyElement() {
            return this.$refs.conversationBody || null;
        },
        formatTimeText(value) {
            return this.formatTime ? this.formatTime(value) : "";
        },
        formatFileSizeText(value) {
            return this.formatFileSize ? this.formatFileSize(value) : "";
        },
        formatReactionsTooltipText(reactions) {
            return this.formatReactionsTooltip
                ? this.formatReactionsTooltip(reactions)
                : "";
        },
        getUniqueReactionEmojiList(reactions) {
            return this.getUniqueReactionEmojis
                ? this.getUniqueReactionEmojis(reactions)
                : [];
        },
        getReactionEmojiValue(reactionKey) {
            return this.getReactionEmoji ? this.getReactionEmoji(reactionKey) : "";
        },
        showSeenReceipt(message) {
            return this.shouldShowSeenReceipt
                ? this.shouldShowSeenReceipt(message)
                : false;
        },
        formatGroupSeenReceiptTooltipText(message) {
            return this.formatGroupSeenReceiptTooltip
                ? this.formatGroupSeenReceiptTooltip(message)
                : "";
        },
        formatSeenReceiptTooltipText(readAt) {
            return this.formatSeenReceiptTooltip
                ? this.formatSeenReceiptTooltip(readAt)
                : "";
        },
        getSeenReceiptPreviewUsersList(message) {
            return this.getSeenReceiptPreviewUsers
                ? this.getSeenReceiptPreviewUsers(message)
                : [];
        },
        getSeenReceiptOverflowValue(message) {
            return this.getSeenReceiptOverflowCount
                ? this.getSeenReceiptOverflowCount(message)
                : 0;
        },
        getMemberProfileUrl(user) {
            return this.getMemberProfile ? this.getMemberProfile(user) : "";
        },
        getSeenMemberLabel(user) {
            return this.getSeenMemberName ? this.getSeenMemberName(user) : "User";
        },
        getSeenReceiptAvatarUrl() {
            return this.getSeenReceiptAvatar ? this.getSeenReceiptAvatar() : "";
        },
    },
};
</script>
