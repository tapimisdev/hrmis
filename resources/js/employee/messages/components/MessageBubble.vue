<template>
    <div class="message-row" :class="{ 'message-row--mine': isMine, 'message-row--theirs': !isMine, 'message-row--system': isSystem }">
        <div v-if="!isSystem" class="message-bubble-wrap">
            <div class="message-bubble" :data-message-id="message.id">
                <!-- Reply target -->
                <button
                    v-if="message.reply_to || message.reply_to_id"
                    type="button"
                    class="message-bubble__reply message-bubble__reply--link"
                    @click="$emit('scroll-to-reply', message)"
                    :aria-label="`Jump to replied message for ${getMessageSnippet(message.reply_to || {})}`"
                >
                    <div class="message-bubble__reply-label">
                        <i class="fa-solid fa-reply"></i>
                        Replied to this message
                    </div>
                    <div>
                        {{ getReplyPreview(message) }}
                    </div>
                </button>

                <!-- Sender name for groups -->
                <div v-if="isGroup && !isMine" class="message-bubble__sender">
                    {{ message.sender_name || "User" }}
                </div>

                <!-- Message body -->
                <div v-if="message.body" class="message-bubble__text">
                    {{ message.body }}
                </div>
                <div v-if="message.is_unsent" class="message-bubble__text message-bubble__text--unsent">
                    Unsent Message
                </div>

                <!-- Pin indicator -->
                <div
                    v-if="message.pinned_at"
                    class="message-pin-chip message-pin-chip--floating"
                    :class="{
                        'message-pin-chip--mine': isMine,
                        'message-pin-chip--theirs': !isMine,
                    }"
                    title="Pinned message"
                >
                    <span class="message-pin-chip__icon">
                        <i class="fa-solid fa-thumbtack"></i>
                    </span>
                </div>

                <!-- Reaction badges (multiple) -->
                <button
                    v-if="displayReactions.length > 0"
                    type="button"
                    class="message-reaction-badges message-reaction-badges--floating"
                    :class="{
                        'message-reaction-badges--mine': isMine,
                        'message-reaction-badges--theirs': !isMine,
                    }"
                    @click="$emit('open-reactions', message)"
                    :title="reactionsTooltip"
                >
                    <span
                        v-for="emoji in displayReactions.slice(0, 3)"
                        :key="emoji"
                        class="message-reaction-badge__glyph"
                    >
                        {{ emoji }}
                    </span>
                    <span v-if="displayReactions.length > 3" class="message-reaction-count">
                        +{{ displayReactions.length - 3 }}
                    </span>
                </button>

                <!-- Image attachment -->
                <div
                    v-if="message.attachment && message.attachment.type === 'image' && !message.is_unsent"
                    class="message-bubble__attachment message-bubble__attachment--image"
                >
                    <button
                        type="button"
                        class="message-bubble__image-link"
                        @click.stop="$emit('open-image', message.attachment)"
                    >
                        <img :src="message.attachment.url" :alt="message.attachment.name" />
                        <div class="message-bubble__attachment-overlay">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                    </button>
                </div>

                <!-- File attachment -->
                <a
                    v-else-if="message.attachment && !message.is_unsent"
                    :href="message.attachment.url"
                    :download="message.attachment.name"
                    class="message-bubble__attachment message-bubble__attachment--file"
                    target="_blank"
                    rel="noopener"
                    @click.stop
                >
                    <i class="fa-regular fa-file-lines"></i>
                    <div class="message-bubble__attachment-meta">
                        <div class="message-bubble__attachment-name">
                            {{ message.attachment.name }}
                        </div>
                        <small class="text-white-50">
                            {{ formatFileSize(message.attachment.size) }}
                        </small>
                    </div>
                </a>

                <!-- Time and status -->
                <div class="message-bubble__time">
                    {{ formatTime(message.created_at) }}
                    <span v-if="message.is_edited" class="message-bubble__status-edit">(edited)</span>
                </div>

                <!-- Seen receipts -->
                <div v-if="isMine && shouldShowSeenReceipt" class="message-bubble__status message-bubble__status--seen">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div v-else-if="isMine && message.read_at" class="message-bubble__status message-bubble__status--seen">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>
        </div>
        <div v-else class="message-system-note">{{ message.body }}</div>
    </div>
</template>

<script>
export default {
    name: "MessageBubble",
    props: {
        message: {
            type: Object,
            required: true,
        },
        isGroup: {
            type: Boolean,
            default: false,
        },
        isMine: {
            type: Boolean,
            default: false,
        },
        isSystem: {
            type: Boolean,
            default: false,
        },
        shouldShowSeenReceipt: {
            type: Boolean,
            default: false,
        },
        reactionOptions: {
            type: Array,
            default: () => [
                { key: "like", emoji: "👍", label: "Like" },
                { key: "number-one", emoji: "☝️", label: "One DOST" },
                { key: "love", emoji: "❤️", label: "Love" },
                { key: "haha", emoji: "😂", label: "Haha" },
                { key: "sad", emoji: "😢", label: "Sad" },
                { key: "angry", emoji: "😡", label: "Angry" },
            ],
        },
    },
    emits: ["scroll-to-reply", "open-image", "select", "delete", "edit", "pin", "set-reaction", "open-reactions"],
    computed: {
        displayReactions() {
            if (this.message.is_unsent) {
                return [];
            }
            // Support both single reaction (DM) and multiple reactions (group)
            if (this.message.reactions && Array.isArray(this.message.reactions)) {
                // Group chats: array of reactions
                const emojis = new Set();
                this.message.reactions.forEach((r) => {
                    emojis.add(r.reaction);
                });
                return Array.from(emojis);
            } else if (this.message.reaction) {
                // DMs: single reaction
                return [this.getReactionEmoji(this.message.reaction)];
            }
            return [];
        },
        reactionsTooltip() {
            if (this.message.is_unsent) {
                return "";
            }
            if (this.message.reactions && Array.isArray(this.message.reactions)) {
                const grouped = {};
                this.message.reactions.forEach((r) => {
                    if (!grouped[r.reaction]) grouped[r.reaction] = [];
                    grouped[r.reaction].push(r.user_name);
                });
                return Object.entries(grouped)
                    .map(([emoji, names]) => `${emoji}: ${names.join(", ")}`)
                    .join("\n");
            }
            return "";
        },
    },
    methods: {
        getMessageSnippet(msg) {
            if (!msg) return "";
            if (msg.is_unsent) return "Unsent Message";
            if (msg.body) return msg.body;
            if (msg.attachment?.name) return msg.attachment.name;
            return "Attachment";
        },
        getReplyPreview(msg) {
            if (!msg.reply_to) return "Previous message";
            return this.getMessageSnippet(msg.reply_to);
        },
        getReactionEmoji(reactionKey) {
            return (
                this.reactionOptions.find((reaction) => reaction.key === reactionKey)?.emoji ||
                ""
            );
        },
        formatTime(value) {
            if (!value) return "";
            try {
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return "";
                }
                return new Intl.DateTimeFormat([], {
                    hour: "numeric",
                    minute: "2-digit",
                }).format(date);
            } catch (error) {
                return "";
            }
        },
        formatFileSize(bytes) {
            const value = Number(bytes || 0);
            if (!value) return "Unknown size";
            const units = ["B", "KB", "MB", "GB"];
            let size = value;
            let unitIndex = 0;
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex += 1;
            }
            return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
        },
    },
};
</script>

<style scoped lang="scss">
.message-reaction-badges {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background-color: var(--bs-secondary-bg, #f8f9fa);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;

    &:hover {
        background-color: var(--bs-tertiary-bg, #e9ecef);
        transform: scale(1.05);
    }

    &--floating {
        position: absolute;
        bottom: -12px;
        font-size: 14px;
        z-index: 1;

        &.message-reaction-badges--mine {
            right: 0;
        }

        &.message-reaction-badges--theirs {
            left: 0;
        }
    }
}

.message-reaction-badge__glyph {
    font-size: 16px;
    line-height: 1;
}

.message-reaction-count {
    font-size: 12px;
    font-weight: 600;
    color: var(--bs-body-color, #000);
    margin-left: 2px;
}
</style>
