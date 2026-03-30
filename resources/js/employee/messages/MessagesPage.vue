<template>
    <div class="messages-page">
        <div
            class="messenger-shell"
            :class="{ 'is-mobile-users-open': showMobileUsersPanel }"
        >
            <aside
                class="contacts-panel"
                :class="{
                    'is-mobile-open': showMobileUsersPanel,
                    'is-mobile-users-open': showMobileUsersPanel,
                    'is-mobile-closing': mobileUsersPanelClosing,
                }"
            >
                <div class="contacts-panel__header">
                    <div>
                        <div class="contacts-panel__eyebrow">DOST-TAPI</div>
                        <h1>Messenger</h1>
                    </div>

                    <div class="contacts-panel__actions">
                        <button
                            type="button"
                            class="icon-chip contacts-panel__mobile-close"
                            aria-label="Close user list"
                            title="Close user list"
                            @click="closeMobileUsersPanel"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <button
                            type="button"
                            class="icon-chip"
                            aria-label="Create group chat"
                            title="Create group chat"
                        >
                            <i class="fa-solid fa-people-group"></i>
                        </button>
                    </div>
                </div>

                <div class="contacts-panel__search">
                    <div class="search-shell">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search Users"
                        />
                    </div>
                </div>

                <div class="contacts-list">
                    <button
                        v-for="user in visibleUsers"
                        :key="user.id"
                        type="button"
                        class="contact-card"
                        :class="{ 'is-active': Number(user.id) === Number(selectedUserId) }"
                        @click="selectUser(user)"
                    >
                        <span class="contact-card__avatar">
                            <img :src="user.profile" alt="profile" />
                            <span
                                class="contact-card__status-dot"
                                :class="user.is_active ? 'contact-card__status-dot--active' : 'contact-card__status-dot--inactive'"
                            ></span>
                        </span>

                        <span class="contact-card__body">
                            <span class="contact-card__name">{{ user.name }}</span>
                            <span class="contact-card__status">
                                {{ user.active_label }}
                            </span>
                        </span>

                        <span class="contact-card__meta">
                            <span v-if="user.unread_count > 0" class="unread-pill">
                                {{ user.unread_count }}
                            </span>
                        </span>
                    </button>

                    <div v-if="visibleUsers.length === 0" class="chat-empty mt-4">
                        <div class="chat-empty__icon">
                            <i class="fa-regular fa-comment-dots"></i>
                        </div>
                        <div class="fw-semibold">No conversations match</div>
                        <div class="text-white-50 small">Try a different search or filter.</div>
                    </div>
                </div>
            </aside>

            <section
                class="conversation-panel"
                :class="{ 'is-mobile-hidden': showMobileUsersPanel }"
            >
                <div class="conversation-panel__top">
                    <div class="conversation-user">
                        <span class="conversation-user__avatar">
                            <img :src="activeUserAvatar" alt="profile" />
                            <span
                                class="conversation-user__status-dot"
                                :class="activeUser?.is_active ? 'conversation-user__status-dot--active' : 'conversation-user__status-dot--inactive'"
                            ></span>
                        </span>
                        <div class="conversation-user__text text-truncate">
                            <h2 class="conversation-user__name">{{ activeUserName }}</h2>
                            <div class="conversation-user__status">
                                {{ activeUserStatus }}
                            </div>
                        </div>
                    </div>

                    <div class="conversation-actions">
                        <button
                            type="button"
                            class="conversation-info-btn conversation-info-btn--users"
                            aria-label="Open user list"
                            title="Open user list"
                            @click="openMobileUsersPanel"
                        >
                            <i class="fa-solid fa-user-group"></i>
                        </button>
                        <button type="button" class="conversation-info-btn" aria-label="Conversation info">
                            <i class="fa-solid fa-circle-info"></i>
                        </button>
                    </div>
                </div>

                <template v-if="pinnedMessages.length">
                    <button
                        type="button"
                        class="conversation-banner"
                        @click="togglePinnedMessagesPanel"
                    >
                        <div class="conversation-banner__pinned-summary">
                            <i class="fa-solid fa-thumbtack"></i>
                            <div class="conversation-banner__pinned-copy">
                                <div class="conversation-banner__title">Pinned messages</div>
                                <small class="conversation-banner__subtitle">
                                    {{ latestPinnedPreview }}
                                </small>
                            </div>
                        </div>
                        <div class="conversation-banner__pinned-count">
                            {{ pinnedMessages.length }}
                        </div>
                    </button>
                </template>

                <transition name="fade">
                    <div
                        v-if="showPinnedMessagesPanel"
                        class="pinned-modal-backdrop"
                        @click.self="showPinnedMessagesPanel = false"
                    >
                        <div class="pinned-modal">
                            <div class="pinned-modal__header">
                                <div>
                                    <div class="pinned-modal__title">Pinned messages</div>
                                    <small class="text-white-50">
                                        {{ pinnedMessages.length }} pinned
                                    </small>
                                </div>
                                <button
                                    type="button"
                                    class="pinned-modal__close"
                                    @click="showPinnedMessagesPanel = false"
                                    aria-label="Close pinned messages"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <div v-if="pinnedMessages.length" class="pinned-modal__list">
                                <div
                                    v-for="pin in pinnedMessages"
                                    :key="pin.message_id"
                                    class="pinned-modal__item"
                                >
                                    <button
                                        type="button"
                                        class="pinned-modal__item-body"
                                        @click="scrollToPinnedMessage(pin)"
                                    >
                                        <div class="pinned-modal__item-preview">
                                            {{ pin.preview }}
                                        </div>
                                        <small class="pinned-modal__item-date">
                                            Pinned {{ formatPinnedAt(pin.pinned_at || pin.created_at) }}
                                        </small>
                                    </button>
                                    <button
                                        type="button"
                                        class="pinned-modal__item-unpin"
                                        @click.stop="unpinPinnedMessage(pin)"
                                        aria-label="Unpin message"
                                        title="Unpin"
                                    >
                                        <i class="fa-solid fa-thumbtack-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-else class="pinned-modal__empty text-white-50">
                                Currently no pinned messages yet.
                            </div>
                        </div>
                    </div>
                </transition>

                <div class="conversation-panel__body" ref="conversationBody" @scroll.passive="handleConversationScroll">
                    <div v-if="loadingConversation" class="chat-loading">
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

                    <div v-if="loadingOlderConversation" class="chat-loading chat-loading--inline">
                        <span class="loader-dot"></span>
                        <div class="fw-semibold">Loading messages...</div>
                    </div>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="message-row"
                        :class="[
                            message.is_mine ? 'message-row--mine' : 'message-row--theirs',
                            message.is_unsent ? 'message-row--unsent' : '',
                        ]"
                        :data-message-id="message.id"
                        @click="selectMessage(message)"
                    >
                        <div class="message-bubble" :class="{ 'message-bubble--unsent': message.is_unsent }">
                            <div
                                class="message-bubble__floating-actions"
                                :class="{ 'is-open': activeMessageActionsId === message.id }"
                            >
                                <div v-if="message.is_mine && !message.is_unsent" class="bubble-action-group">
                                    <button
                                        type="button"
                                        class="bubble-action"
                                        :class="{ 'is-active': activeMessageActionsId === message.id }"
                                        @click.stop="toggleMessageActions(message)"
                                        title="More actions"
                                        :aria-label="`More actions for ${message.body || message.attachment?.name || 'message'}`"
                                        :aria-expanded="activeMessageActionsId === message.id"
                                    >
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div v-if="activeMessageActionsId === message.id" class="bubble-action-menu">
                                        <button
                                            v-if="message.is_mine && message.body"
                                            type="button"
                                            class="bubble-action bubble-action--menu"
                                            @click.stop="editMessage(message)"
                                            title="Edit message"
                                            :aria-label="`Edit message ${message.body}`"
                                        >
                                            <span class="bubble-action__icon">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </span>
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            v-if="message.is_mine"
                                            type="button"
                                            class="bubble-action bubble-action--menu bubble-action--danger"
                                            @click.stop="unsendMessage(message)"
                                            title="Unsend message"
                                            :aria-label="`Unsend message ${message.body || message.attachment?.name || 'message'}`"
                                        >
                                            <span class="bubble-action__icon">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </span>
                                            <span>Unsend</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="bubble-action bubble-action--menu"
                                            :class="{ 'is-active': Boolean(message.pinned_at) }"
                                            @click.stop="togglePinMessage(message)"
                                            :title="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                            :aria-label="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                        >
                                            <span class="bubble-action__icon">
                                                <i :class="message.pinned_at ? 'fa-solid fa-thumbtack-slash' : 'fa-solid fa-thumbtack'"></i>
                                            </span>
                                            <span>{{ message.pinned_at ? 'Unpin' : 'Pin' }}</span>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    v-else-if="!message.is_unsent"
                                    type="button"
                                    class="bubble-action"
                                    :class="{ 'is-active': Boolean(message.pinned_at) }"
                                    @click.stop="togglePinMessage(message)"
                                    :title="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                    :aria-label="message.pinned_at ? 'Unpin message' : 'Pin message'"
                                >
                                    <span class="bubble-action__icon">
                                        <i :class="message.pinned_at ? 'fa-solid fa-thumbtack-slash' : 'fa-solid fa-thumbtack'"></i>
                                    </span>
                                </button>
                                <button
                                    v-if="!message.is_unsent"
                                    type="button"
                                    class="bubble-action"
                                    @click.stop="startReply(message)"
                                >
                                    <i class="fa-solid fa-reply"></i>
                                </button>
                                <button
                                    v-if="!message.is_unsent"
                                    type="button"
                                    class="bubble-action"
                                    :class="{ 'is-active': selectedMessageId === message.id && showReactionPicker }"
                                    @click.stop="toggleReactionPicker(message)"
                                    :aria-pressed="selectedMessageId === message.id && showReactionPicker"
                                >
                                    <i class="fa-regular fa-face-smile"></i>
                                </button>
                                <button
                                    v-if="message.attachment"
                                    type="button"
                                    class="bubble-action"
                                    @click.stop="downloadAttachment(message.attachment)"
                                >
                                    <i class="fa-solid fa-download"></i>
                                </button>
                            </div>
                            <button
                                v-if="message.reply_preview"
                                type="button"
                                class="message-bubble__reply message-bubble__reply--link"
                                @click.stop="scrollToReplyMessage(message)"
                                :aria-label="`Jump to replied message for ${message.reply_preview}`"
                            >
                                <div class="message-bubble__reply-label">
                                    <i class="fa-solid fa-reply"></i>
                                    Replied to this message
                                </div>
                                <div>{{ message.reply_preview }}</div>
                            </button>

                                        <div
                                            v-if="message.body"
                                            class="message-bubble__text"
                                        >
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
                                    :class="message.is_mine ? 'message-pin-chip--mine' : 'message-pin-chip--theirs'"
                                    title="Pinned message"
                                >
                                    <span class="message-pin-chip__icon">
                                        <i class="fa-solid fa-thumbtack"></i>
                                    </span>
                                </div>

                                <div
                                    v-if="message.reaction"
                                    class="message-reaction-badge message-reaction-badge--floating"
                                    :class="message.is_mine ? 'message-reaction-badge--mine' : 'message-reaction-badge--theirs'"
                                    :title="getReactionEmoji(message.reaction)"
                                >
                                    <span class="message-reaction-badge__glyph">{{ getReactionEmoji(message.reaction) }}</span>
                                </div>

                                    <div
                                        v-if="message.attachment && message.attachment.type === 'image' && !message.is_unsent"
                                        class="message-bubble__attachment message-bubble__attachment--image"
                                    >
                                    <button
                                        type="button"
                                        class="message-bubble__image-link"
                                        @click.stop="openImageGallery(message.attachment)"
                                        :aria-label="`Open ${message.attachment.name || 'attachment'} in gallery`"
                                    >
                                        <img
                                            :src="message.attachment.url"
                                            :alt="message.attachment.name"
                                            @load="handleAttachmentImageLoad"
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
                                        <span class="message-bubble__attachment-name">{{ message.attachment.name }}</span>
                                        <small class="text-white-50">{{ formatFileSize(message.attachment.size) }}</small>
                                    </span>
                                    <span class="message-bubble__attachment-download">
                                        <i class="fa-solid fa-download"></i>
                                    </span>
                                </a>

                                <div class="message-bubble__time">
                                    {{ formatTime(message.created_at) }}
                                </div>
                                <div
                                    v-if="message.is_mine"
                                    class="message-bubble__status"
                                    :class="message.read_at ? 'message-bubble__status--seen' : 'message-bubble__status--sent'"
                                >
                                    {{ message.read_at ? `Seen at ${formatSeenAt(message.read_at)}` : 'Sent' }}
                                    <span v-if="message.edited_at" class="message-bubble__status-edit">· Edited</span>
                                </div>
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
                            <span class="typing-indicator__label">typing...</span>
                        </div>
                    </div>

                </div>

                <div
                    v-if="showReactionPicker && selectedMessageId"
                    class="reaction-overlay reaction-overlay--panel"
                    @click.self="clearReactionPicker"
                >
                    <div class="reaction-picker reaction-picker--centered" @click.stop>
                        <button
                            v-for="reaction in reactionOptions"
                            :key="reaction.key"
                            type="button"
                            class="reaction-picker__btn"
                            :title="reaction.label"
                            :aria-label="reaction.label"
                            @click.stop="setReaction(selectedMessage, reaction.key)"
                        >
                            <span class="reaction-picker__glyph">{{ reaction.emoji }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="replyTargetMessage" class="composer-reply">
                    <div class="composer-reply__meta">
                        <strong>Replying to {{ replyTargetMessage.is_mine ? 'you' : activeUserName }}</strong>
                        <button type="button" class="composer-reply__close" @click="clearReplyTarget">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="composer-reply__preview">{{ getMessageSnippet(replyTargetMessage) }}</div>
                </div>

                <div v-if="selectedAttachment" class="attachment-preview">
                    <div class="attachment-preview__meta">
                        <span v-if="selectedAttachmentPreviewType === 'image' && selectedAttachmentPreviewUrl" class="attachment-preview__thumb">
                            <img :src="selectedAttachmentPreviewUrl" :alt="selectedAttachment.name" />
                        </span>
                        <span v-else class="attachment-preview__icon">
                            <i class="fa-regular fa-file-lines"></i>
                        </span>
                        <div class="attachment-preview__body">
                            <div class="attachment-preview__name">{{ selectedAttachment.name }}</div>
                            <small class="text-white-50">{{ formatFileSize(selectedAttachment.size) }}</small>
                        </div>
                    </div>
                    <button type="button" class="attachment-preview__remove" @click="clearSelectedAttachment">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form class="composer" @submit.prevent="sendMessage">
                    <input
                        ref="attachmentInput"
                        type="file"
                        class="d-none"
                        :accept="attachmentAccept"
                        @change="handleAttachmentChange"
                    >
                    <button
                        type="button"
                        class="composer__button"
                        :class="{ 'is-active': showPinnedMessagesPanel }"
                        aria-label="Pinned messages"
                        title="View pinned messages"
                        @click.stop="togglePinnedMessagesPanel"
                    >
                        <i class="fa-solid fa-thumbtack"></i>
                    </button>
                    <button type="button" class="composer__button" aria-label="Attach file" @click="triggerAttachmentPicker('file')">
                        <i class="fa-regular fa-file-lines"></i>
                    </button>
                    <div class="composer__field">
                        <div class="composer__input-shell">
                            <textarea
                                ref="composerInput"
                                v-model="draftMessage"
                                class="composer__input"
                                rows="1"
                                placeholder="Aa"
                                :maxlength="messageCharacterLimit"
                                :disabled="!activeUser || sendingMessage"
                                @input="handleComposerInput"
                                @blur="handleComposerBlur"
                                @keydown.enter.exact.prevent="sendMessage"
                                @keydown.enter.shift.exact.stop
                            ></textarea>
                        </div>
                        <div class="composer__meta mt-2">
                            <small class="composer__hint">Shift+Enter for a new line</small>
                            <small
                                class="composer__counter"
                                :class="{ 'is-near-limit': messageCharactersRemaining <= 200 }"
                            >
                                {{ messageCharacterCount }}/{{ messageCharacterLimit }}
                            </small>
                        </div>
                    </div>
                    <button
                        v-if="showScrollToBottomButton"
                        type="button"
                        class="composer__scroll-bottom"
                        @click="scrollConversationToBottom"
                        title="Scroll to bottom"
                        aria-label="Scroll to bottom"
                    >
                        <i class="fa-solid fa-arrow-down"></i>
                    </button>
                    <button
                        type="submit"
                        class="composer__send"
                        :disabled="!activeUser || sendingMessage || (!draftMessage.trim() && !selectedAttachment)"
                    >
                        <i v-if="!sendingMessage" class="fa-regular fa-paper-plane"></i>
                        <span v-else class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    </button>
                </form>

                <transition name="fade">
                    <button
                        v-if="showScrollToBottomButton"
                        type="button"
                        class="message-scroll-bottom"
                        @click="scrollConversationToBottom"
                        title="Scroll to bottom"
                        aria-label="Scroll to bottom"
                    >
                        <i class="fa-solid fa-arrow-down"></i>
                    </button>
                </transition>
            </section>
        </div>
        <div ref="imageGalleryContainer" class="d-none"></div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'MessagesPage',
    props: {
        initialUsers: {
            type: Array,
            default: () => [],
        },
        authUser: {
            type: Object,
            required: true,
        },
        initialSelectedUserId: {
            type: [Number, String],
            default: null,
        },
        csrfToken: {
            type: String,
            default: '',
        },
    },
    data() {
        const selectedId = Number(this.initialSelectedUserId || this.initialUsers?.[0]?.id || 0);
        const normalizeUserId = (userId) => Number(userId);
        const getLastSeenStore = () => {
            try {
                return JSON.parse(localStorage.getItem('online-users-last-seen') || '{}');
            } catch (error) {
                return {};
            }
        };
        const getActivityState = (userId, latestAt = null) => {
            const normalizedUserId = normalizeUserId(userId);
            const storedLastSeenAt = getLastSeenStore()[normalizedUserId] ?? null;
            const isOnline = this.onlineUserIds?.includes(normalizedUserId) ?? false;

            if (isOnline) {
                return {
                    label: 'Online',
                    isActive: true,
                    lastSeenAt: storedLastSeenAt ? Number(storedLastSeenAt) : Date.now(),
                };
            }

            const fallbackSource = storedLastSeenAt || latestAt;

            if (!fallbackSource) {
                return {
                    label: 'Offline',
                    isActive: false,
                    lastSeenAt: null,
                };
            }

            const latestDate = new Date(fallbackSource);
            const diffMs = Date.now() - latestDate.getTime();

            if (Number.isNaN(diffMs) || diffMs < 0) {
                return {
                    label: 'Offline',
                    isActive: false,
                    lastSeenAt: null,
                };
            }

            const diffMinutes = Math.max(1, Math.floor(diffMs / 60_000));

            if (diffMinutes < 60) {
                return {
                    label: `Active ${diffMinutes} mins ago`,
                    isActive: false,
                    lastSeenAt: latestDate.getTime(),
                };
            }

            if (diffMinutes < 1440) {
                return {
                    label: `Active ${Math.floor(diffMinutes / 60)} hrs ago`,
                    isActive: false,
                    lastSeenAt: latestDate.getTime(),
                };
            }

            return {
                label: `Active ${Math.floor(diffMinutes / 1440)} days ago`,
                isActive: false,
                lastSeenAt: latestDate.getTime(),
            };
        };
        const withStatus = (user) => {
            const activityState = getActivityState(user.id, user.latest_at);

            return {
                ...user,
                id: normalizeUserId(user.id),
                active_label: user.active_label || activityState.label,
                is_active: user.is_active ?? activityState.isActive,
                last_seen_at: activityState.lastSeenAt,
            };
        };
        const token = localStorage.getItem('auth_token');

        if (token) {
            axios.defaults.headers.common.Authorization = `Bearer ${token}`;
            axios.defaults.withCredentials = true;
        }

        return {
            users: this.initialUsers.map(withStatus),
            selectedUserId: selectedId,
            searchQuery: '',
            messages: [],
            pinnedMessages: [],
            draftMessage: '',
            replyTargetMessage: null,
            selectedMessage: null,
            selectedMessageId: null,
            selectedAttachment: null,
            selectedAttachmentPreviewUrl: null,
            selectedAttachmentPreviewType: 'file',
            attachmentAccept: '.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt',
            attachmentMode: 'file',
            messageCharacterLimit: 2000,
            onlineUserIds: [],
            onlineUsersChannel: null,
            onlineUsersHereListener: null,
            onlineUsersJoiningListener: null,
            onlineUsersLeavingListener: null,
            onlineUsersUpdatedListener: null,
            sharedOnlineUsersListener: null,
            messageChannel: null,
            receiveSound: null,
            sendSound: null,
            typingIndicator: false,
            typingIndicatorTimer: null,
            typingStateActive: false,
            typingStopTimer: null,
            showPinnedMessagesPanel: false,
            showComposerEmojiPicker: false,
            showScrollToBottomButton: false,
            showMobileUsersPanel: false,
            loadingOlderConversation: false,
            conversationPage: 1,
            conversationLastPage: 1,
            conversationHasMore: true,
            showReactionPicker: false,
            reactionTargetId: null,
            activeMessageActionsId: null,
            imageGalleryInstance: null,
            activityRefreshTimer: null,
            loadingConversation: false,
            sendingMessage: false,
            conversationError: '',
            composerEmojiOptions: ['😀', '😂', '😍', '🥳', '👍', '🙏', '🔥', '🎉', '❤️', '😎'],
            reactionOptions: [
                { key: 'like', emoji: '👍', label: 'Like' },
                { key: 'love', emoji: '❤️', label: 'Love' },
                { key: 'haha', emoji: '😂', label: 'Haha' },
                { key: 'sad', emoji: '😢', label: 'Sad' },
                { key: 'angry', emoji: '😡', label: 'Angry' },
            ],
            presenceLifecycleBound: false,
            handlePresencePageHide: null,
            handlePresenceBeforeUnload: null,
        };
    },
    computed: {
        activeUser() {
            return this.users.find((user) => Number(user.id) === Number(this.selectedUserId)) || null;
        },
        activeUserName() {
            return this.activeUser?.name || 'Select a conversation';
        },
        activeUserAvatar() {
            return this.activeUser?.profile || `https://ui-avatars.com/api/?name=${encodeURIComponent(this.activeUserName)}&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true`;
        },
        activeUserStatus() {
            if (!this.activeUser) {
                return 'Choose a user on the left';
            }

            return this.activeUser.active_label || 'Active now';
        },
        visibleUsers() {
            const search = this.searchQuery.trim().toLowerCase();
            const filtered = [...this.users]
                .filter((user) => {
                    const haystack = `${user.name || ''} ${user.email || ''} ${user.preview || ''}`.toLowerCase();
                    const matchesSearch = !search || haystack.includes(search);
                    return matchesSearch;
                })
                .sort((a, b) => {
                    const aTime = a.latest_at ? new Date(a.latest_at).getTime() : 0;
                    const bTime = b.latest_at ? new Date(b.latest_at).getTime() : 0;
                    return bTime - aTime;
                });

            return filtered;
        },
        latestPinnedPreview() {
            return this.pinnedMessages?.[0]?.preview || '';
        },
        selectedAttachmentName() {
            return this.selectedAttachment?.name || '';
        },
        messageCharacterCount() {
            return this.draftMessage.length;
        },
        messageCharactersRemaining() {
            return Math.max(0, this.messageCharacterLimit - this.messageCharacterCount);
        },
    },
    mounted() {
        this.ensureAuthHeaders();
        this.receiveSound = new Audio('/sounds/receive.mp3');
        this.receiveSound.preload = 'auto';
        this.sendSound = new Audio('/sounds/sent.mp3');
        this.sendSound.preload = 'auto';
        this.hydrateOnlineUsersFromSharedState();
        this.bindSharedOnlineUsersListener();
        this.initializeOnlineUsers();
        this.bindPresenceLifecycleEvents();
        this.announcePresence('online');
        this.refreshUserActivityLabels();
        this.activityRefreshTimer = window.setInterval(() => {
            this.refreshUserActivityLabels();
        }, 60_000);

        if (this.activeUser) {
            this.loadConversation(this.activeUser.id, { page: 1 });
        }

        this.initializeDirectMessageListener();
    },
    beforeUnmount() {
        this.announcePresence('offline');
        this.unbindPresenceLifecycleEvents();
        this.unbindSharedOnlineUsersListener();

        this.teardownOnlineUsersListeners();
        this.onlineUsersChannel = null;

        if (this.messageChannel && this.authUser?.id) {
            window.Echo.leave(`direct-messages.${this.authUser.id}`);
            this.messageChannel = null;
        }

        this.clearTypingTimers();

        if (this.activityRefreshTimer) {
            window.clearInterval(this.activityRefreshTimer);
            this.activityRefreshTimer = null;
        }

        this.destroyImageGallery();
    },
    methods: {
        ensureAuthHeaders() {
            const token = localStorage.getItem('auth_token');

            if (token) {
                axios.defaults.headers.common.Authorization = `Bearer ${token}`;
                axios.defaults.withCredentials = true;

                if (window.Echo?.connector?.options?.auth) {
                    window.Echo.connector.options.auth.headers = {
                        ...(window.Echo.connector.options.auth.headers || {}),
                        Authorization: `Bearer ${token}`,
                    };
                }
            }
        },
        initializeDirectMessageListener() {
            if (this.messageChannel || !window.Echo || !this.authUser?.id) {
                return;
            }

            this.messageChannel = window.Echo.private(`direct-messages.${this.authUser.id}`)
                .listen('.direct-message.sent', (event) => {
                    const message = event?.message;
                    if (!message) return;

                    if (Number(message.sender_id || 0) !== Number(this.authUser?.id || 0)) {
                        this.playReceiveSound();
                    }

                    this.handleIncomingDirectMessage(message);
                })
                .listen('.direct-message.updated', (event) => {
                    const payload = event?.payload || {};
                    const message = payload.message || null;
                    if (!message) return;

                    this.handleIncomingDirectMessage(
                        message,
                        payload.pinned_messages || null,
                        payload.conversation_preview || null,
                    );
                })
                .listen('.direct-message.typing', (event) => {
                    const payload = event?.payload || {};
                    const activeUserId = Number(this.activeUser?.id || 0);
                    const senderId = Number(payload.sender_id || 0);

                    if (!payload.is_typing || !activeUserId || senderId !== activeUserId) {
                        this.typingIndicator = false;

                        if (this.typingIndicatorTimer) {
                            window.clearTimeout(this.typingIndicatorTimer);
                            this.typingIndicatorTimer = null;
                        }

                        return;
                    }

                    this.typingIndicator = true;

                    if (this.typingIndicatorTimer) {
                        window.clearTimeout(this.typingIndicatorTimer);
                    }

                    this.typingIndicatorTimer = window.setTimeout(() => {
                        this.typingIndicator = false;
                        this.typingIndicatorTimer = null;
                    }, 2500);

                    this.$nextTick(() => {
                        const bodyEl = this.$refs.conversationBody || this.$el.querySelector('.conversation-panel__body');
                        if (!bodyEl) {
                            return;
                        }

                        this.updateScrollToBottomButton(bodyEl);
                    });
                })
                .listen('.direct-message.seen', (event) => {
                    const payload = event?.payload || {};
                    const threadUserId = Number(payload.reader_id || payload.partner_id || 0);
                    const selectedUserId = Number(this.activeUser?.id || 0);

                    if (!threadUserId || selectedUserId !== threadUserId) {
                        return;
                    }

                    const readAt = payload.read_at || null;
                    const messageIds = new Set(
                        (payload.message_ids || []).map((id) => Number(id)),
                    );

                    if (!readAt || messageIds.size === 0) {
                        return;
                    }

                    this.applySeenReceipt(threadUserId, readAt, [...messageIds]);
                });
        },
        bindPresenceLifecycleEvents() {
            if (this.presenceLifecycleBound) {
                return;
            }

            this.handlePresencePageHide = () => {
                this.announcePresence('offline', true);
            };

            this.handlePresenceBeforeUnload = () => {
                this.announcePresence('offline', true);
            };

            window.addEventListener('pagehide', this.handlePresencePageHide);
            window.addEventListener('beforeunload', this.handlePresenceBeforeUnload);
            this.presenceLifecycleBound = true;
        },
        unbindPresenceLifecycleEvents() {
            if (!this.presenceLifecycleBound) {
                return;
            }

            if (this.handlePresencePageHide) {
                window.removeEventListener('pagehide', this.handlePresencePageHide);
            }

            if (this.handlePresenceBeforeUnload) {
                window.removeEventListener('beforeunload', this.handlePresenceBeforeUnload);
            }

            this.handlePresencePageHide = null;
            this.handlePresenceBeforeUnload = null;
            this.presenceLifecycleBound = false;
        },
        async announcePresence(status = 'online', useKeepAlive = false) {
            try {
                const token = localStorage.getItem('auth_token');
                const headers = {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                };

                if (token) {
                    headers.Authorization = `Bearer ${token}`;
                }

                await fetch('/api/presence', {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    keepalive: useKeepAlive,
                    body: JSON.stringify({ status }),
                });
            } catch (error) {
                console.error('Failed to announce presence:', error);
            }
        },
        playReceiveSound() {
            if (!this.receiveSound) return;

            this.receiveSound.currentTime = 0;
            const played = this.receiveSound.play();

            if (played && typeof played.catch === 'function') {
                played.catch(() => {});
            }
        },
        playSendSound() {
            if (!this.sendSound) return;

            this.sendSound.currentTime = 0;
            const played = this.sendSound.play();

            if (played && typeof played.catch === 'function') {
                played.catch(() => {});
            }
        },
        handleIncomingDirectMessage(message, pinnedMessages = null, conversationPreview = null) {
            if (!message?.id) {
                return;
            }

            const authUserId = Number(this.authUser?.id || 0);
            const senderId = Number(message.sender_id || 0);
            const recipientId = Number(message.recipient_id || 0);
            const partnerId = senderId === authUserId ? recipientId : senderId;
            const activeUserId = Number(this.activeUser?.id || 0);
            const isActiveConversation = activeUserId === partnerId;
            const targetUser = this.users.find((user) => Number(user.id) === partnerId);
            const snippet = this.getMessageSnippet(message);

            if (targetUser) {
                targetUser.latest_at = message.created_at || new Date().toISOString();
                targetUser.preview = snippet;
                targetUser.unread_count = senderId === authUserId
                    ? 0
                    : (isActiveConversation ? 0 : Number(targetUser.unread_count || 0) + 1);

                const activityState = this.getActivityState(targetUser.id, targetUser.latest_at);
                targetUser.active_label = activityState.label;
                targetUser.is_active = activityState.isActive;
                targetUser.last_seen_at = activityState.lastSeenAt;
            }

            if (isActiveConversation) {
                this.upsertLocalMessage({
                    ...message,
                    is_mine: senderId === authUserId,
                    read_at: senderId === authUserId ? null : message.read_at ?? null,
                });

                if (message.is_unsent) {
                    this.clearSelectedMessage();
                }

                if (Array.isArray(pinnedMessages)) {
                    this.pinnedMessages = pinnedMessages;
                }

                if (conversationPreview?.preview && targetUser) {
                    this.applyConversationPreview(targetUser, conversationPreview);
                }

                this.scrollConversationToBottom();

                if (senderId !== authUserId && this.isConversationPanelVisible()) {
                    this.markConversationSeen(partnerId);
                }
                return;
            }

            if (targetUser) {
                if (conversationPreview?.preview) {
                    this.applyConversationPreview(targetUser, conversationPreview);
                }
                this.syncUsersOnlineState();
            }
        },
        initializeOnlineUsers() {
            if (this.onlineUsersChannel || !window.Echo) {
                return;
            }

            this.hydrateOnlineUsersFromSharedState();

            this.onlineUsersHereListener = (users) => {
                this.onlineUserIds = users.map((user) => Number(user.id));
                this.saveLastSeen(this.onlineUserIds);
                this.syncUsersOnlineState();
            };

            this.onlineUsersJoiningListener = (user) => {
                const userId = Number(user.id);

                if (!this.onlineUserIds.includes(userId)) {
                    this.onlineUserIds.push(userId);
                }

                this.markUserSeen(userId);
                this.syncUsersOnlineState();
            };

            this.onlineUsersLeavingListener = (user) => {
                const userId = Number(user.id);

                this.onlineUserIds = this.onlineUserIds.filter((id) => id !== userId);
                this.markUserSeen(userId);
                this.syncUsersOnlineState();
            };

            this.onlineUsersUpdatedListener = (event) => {
                const payload = event?.payload || event || {};
                const presenceUser = payload.user || null;
                const status = payload.status || '';
                const userId = Number(presenceUser?.id || 0);

                if (!userId) {
                    return;
                }

                if (status === 'online') {
                    if (!this.onlineUserIds.includes(userId)) {
                        this.onlineUserIds.push(userId);
                    }
                } else if (status === 'offline') {
                    this.onlineUserIds = this.onlineUserIds.filter((id) => id !== userId);
                }

                this.markUserSeen(userId);
                this.syncUsersOnlineState();
            };

            this.onlineUsersChannel = window.Echo.join('online-users')
                .here(this.onlineUsersHereListener)
                .joining(this.onlineUsersJoiningListener)
                .leaving(this.onlineUsersLeavingListener);

            this.onlineUsersChannel.listen('.online-users.updated', this.onlineUsersUpdatedListener);
        },
        hydrateOnlineUsersFromSharedState() {
            const sharedIds = window.__onlineUsersPresence?.onlineUserIds || [];

            if (!Array.isArray(sharedIds) || sharedIds.length === 0) {
                return;
            }

            this.onlineUserIds = [...new Set(sharedIds.map((id) => Number(id)))];
            this.syncUsersOnlineState();
        },
        bindSharedOnlineUsersListener() {
            if (this.sharedOnlineUsersListener) {
                return;
            }

            this.sharedOnlineUsersListener = (event) => {
                const sharedIds = event?.detail?.onlineUserIds || [];

                if (!Array.isArray(sharedIds)) {
                    return;
                }

                this.onlineUserIds = [...new Set(sharedIds.map((id) => Number(id)))];
                this.syncUsersOnlineState();
            };

            window.addEventListener('online-users:updated', this.sharedOnlineUsersListener);
        },
        unbindSharedOnlineUsersListener() {
            if (!this.sharedOnlineUsersListener) {
                return;
            }

            window.removeEventListener('online-users:updated', this.sharedOnlineUsersListener);
            this.sharedOnlineUsersListener = null;
        },
        teardownOnlineUsersListeners() {
            if (!this.onlineUsersChannel) {
                return;
            }

            if (this.onlineUsersHereListener) {
                this.onlineUsersChannel.stopListening('pusher:subscription_succeeded', this.onlineUsersHereListener);
                this.onlineUsersHereListener = null;
            }

            if (this.onlineUsersJoiningListener) {
                this.onlineUsersChannel.stopListening('pusher:member_added', this.onlineUsersJoiningListener);
                this.onlineUsersJoiningListener = null;
            }

            if (this.onlineUsersLeavingListener) {
                this.onlineUsersChannel.stopListening('pusher:member_removed', this.onlineUsersLeavingListener);
                this.onlineUsersLeavingListener = null;
            }

            if (this.onlineUsersUpdatedListener) {
                this.onlineUsersChannel.stopListening('.online-users.updated', this.onlineUsersUpdatedListener);
                this.onlineUsersUpdatedListener = null;
            }

            this.onlineUsersChannel = null;
        },
        getLastSeenStore() {
            try {
                return JSON.parse(localStorage.getItem('online-users-last-seen') || '{}');
            } catch (error) {
                return {};
            }
        },
        saveLastSeen(userIds = []) {
            const store = this.getLastSeenStore();
            const now = Date.now();

            userIds.forEach((id) => {
                store[Number(id)] = now;
            });

            localStorage.setItem('online-users-last-seen', JSON.stringify(store));
        },
        markUserSeen(userId) {
            const store = this.getLastSeenStore();
            store[Number(userId)] = Date.now();
            localStorage.setItem('online-users-last-seen', JSON.stringify(store));
        },
        getLastSeen(userId) {
            const store = this.getLastSeenStore();
            return store[Number(userId)] ?? null;
        },
        syncUsersOnlineState() {
            this.users = this.users.map((user) => {
                const activityState = this.getActivityState(user.id, user.latest_at);

                return {
                    ...user,
                    is_active: activityState.isActive,
                    active_label: activityState.label,
                    last_seen_at: activityState.lastSeenAt,
                };
            });
        },
        selectUser(user) {
            if (!user || Number(user.id) === Number(this.selectedUserId)) {
                this.closeMobileUsersPanel();
                return;
            }

            this.closeMobileUsersPanel();
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.selectedUserId = Number(user.id);
            this.conversationError = '';
            this.resetConversationState();
            this.showPinnedMessagesPanel = false;
            this.showComposerEmojiPicker = false;
            this.clearSelectedMessage();
            this.clearReplyTarget();
            this.loadConversation(user.id, { page: 1 });
        },
        resetConversationState() {
            this.messages = [];
            this.loadingConversation = false;
            this.loadingOlderConversation = false;
            this.conversationPage = 1;
            this.conversationLastPage = 1;
            this.conversationHasMore = true;
            this.showScrollToBottomButton = false;
        },
        openMobileUsersPanel() {
            this.showMobileUsersPanel = true;
        },
        closeMobileUsersPanel() {
            this.showMobileUsersPanel = false;
        },
        toggleMobileUsersPanel() {
            this.showMobileUsersPanel = !this.showMobileUsersPanel;
        },
        async loadConversation(userId, { page = 1, preserveScroll = false } = {}) {
            this.ensureAuthHeaders();

            const user = this.users.find((item) => Number(item.id) === Number(userId));
            if (!user) return;

            this.clearTypingTimers();
            this.typingIndicator = false;
            const isOlderLoad = page > 1;
            const bodyEl = this.$refs.conversationBody;
            const previousScrollHeight = preserveScroll && bodyEl ? bodyEl.scrollHeight : 0;
            const previousScrollTop = preserveScroll && bodyEl ? bodyEl.scrollTop : 0;

            if (isOlderLoad) {
                this.loadingOlderConversation = true;
            } else {
                this.loadingConversation = true;
                this.loadingOlderConversation = false;
            }

            this.conversationError = '';
            if (page === 1) {
                this.messages = [];
                this.showPinnedMessagesPanel = false;
                this.showComposerEmojiPicker = false;
                this.showScrollToBottomButton = false;
                this.conversationPage = 1;
                this.conversationLastPage = 1;
                this.conversationHasMore = true;
            }
            this.clearSelectedMessage();

            try {
                const { data } = await axios.get(`/api/direct-messages/${userId}`, {
                    params: {
                        page,
                        per_page: 20,
                    },
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (Number(this.selectedUserId) !== Number(userId)) {
                    return;
                }

                const messages = Array.isArray(data.messages) ? data.messages : [];
                const pagination = data.pagination ?? {};
                this.conversationPage = pagination.current_page ?? page;
                this.conversationLastPage = pagination.last_page ?? page;
                this.conversationHasMore = Boolean(pagination.has_more);

                if (page === 1) {
                    this.messages = messages;
                } else if (messages.length > 0) {
                    const existingIds = new Set(this.messages.map((item) => Number(item.id)));
                    const olderMessages = messages.filter((message) => !existingIds.has(Number(message.id)));
                    this.messages = [...olderMessages, ...this.messages];
                }

                this.pinnedMessages = Array.isArray(data.pinned_messages) ? data.pinned_messages : [];
                user.unread_count = 0;
                user.preview = this.getMessageSnippet(this.messages[this.messages.length - 1] || null);
                const activityState = this.getActivityState(user.id, user.latest_at);
                user.active_label = activityState.label;
                user.is_active = activityState.isActive;
                user.last_seen_at = activityState.lastSeenAt;

                await axios.post(
                    `/api/direct-messages/${userId}/seen`,
                    {},
                    {
                        headers: {
                        Accept: 'application/json',
                        },
                    }
                );

                this.$nextTick(() => {
                    if (!bodyEl) return;

                    if (isOlderLoad && preserveScroll) {
                        const nextScrollHeight = bodyEl.scrollHeight;
                        bodyEl.scrollTop = nextScrollHeight - previousScrollHeight + previousScrollTop;
                        this.updateScrollToBottomButton(bodyEl);
                        return;
                    }

                    if (page === 1) {
                        window.requestAnimationFrame(() => {
                            this.scrollConversationToBottom();

                            window.requestAnimationFrame(() => {
                                this.scrollConversationToBottom();
                            });
                        });
                    }
                });
            } catch (error) {
                this.conversationError = error?.response?.data?.message || 'Please try again later.';
            } finally {
                this.loadingConversation = false;
                this.loadingOlderConversation = false;
            }
        },
        handleComposerInput() {
            this.resizeComposer();

            if (!this.activeUser) {
                return;
            }

            if (!this.draftMessage.trim()) {
                this.sendTypingState(false);
                return;
            }

            this.sendTypingState(true);
        },
        handleComposerBlur() {
            this.sendTypingState(false);
        },
        clearTypingTimers() {
            if (this.typingStopTimer) {
                window.clearTimeout(this.typingStopTimer);
                this.typingStopTimer = null;
            }

            if (this.typingIndicatorTimer) {
                window.clearTimeout(this.typingIndicatorTimer);
                this.typingIndicatorTimer = null;
            }

            this.typingStateActive = false;
        },
        sendTypingState(isTyping) {
            const activeUser = this.activeUser;
            if (!activeUser) {
                return;
            }

            if (isTyping) {
                if (this.typingStateActive) {
                    if (this.typingStopTimer) {
                        window.clearTimeout(this.typingStopTimer);
                    }

                    this.typingStopTimer = window.setTimeout(() => {
                        this.sendTypingState(false);
                    }, 1800);

                    return;
                }

                axios.post(
                    `/api/direct-messages/${activeUser.id}/typing`,
                    { is_typing: true },
                    {
                        headers: this.buildAuthHeaders(),
                    }
                ).catch((error) => {
                    console.error('Failed to send typing state:', error);
                });

                this.typingStateActive = true;

                if (this.typingStopTimer) {
                    window.clearTimeout(this.typingStopTimer);
                }

                this.typingStopTimer = window.setTimeout(() => {
                    this.sendTypingState(false);
                }, 1800);

                return;
            }

            if (!this.typingStateActive) {
                return;
            }

            if (this.typingStopTimer) {
                window.clearTimeout(this.typingStopTimer);
                this.typingStopTimer = null;
            }

            this.typingStateActive = false;

            axios.post(
                `/api/direct-messages/${activeUser.id}/typing`,
                { is_typing: false },
                {
                    headers: this.buildAuthHeaders(),
                }
            ).catch((error) => {
                console.error('Failed to clear typing state:', error);
            });
        },
        async sendMessage() {
            this.ensureAuthHeaders();

            const body = this.draftMessage.trim();
            if ((!body && !this.selectedAttachment) || !this.activeUser || this.sendingMessage) {
                return;
            }

            this.sendTypingState(false);
            this.sendingMessage = true;

            try {
                const formData = new FormData();
                formData.append('recipient_id', this.activeUser.id);

                if (body) {
                    formData.append('body', body);
                }

                if (this.replyTargetMessage?.id) {
                    formData.append('reply_to_id', this.replyTargetMessage.id);
                }

                if (this.selectedAttachment) {
                    formData.append('attachment', this.selectedAttachment);
                }

                const { data } = await axios.post('/api/direct-messages', formData, {
                    headers: this.buildAuthHeaders(),
                });

                const message = data?.message;
                this.draftMessage = '';

                if (message) {
                    this.upsertLocalMessage({
                        ...message,
                        is_mine: true,
                        read_at: null,
                    });
                    this.playSendSound();
                    this.updateContactPreview(message, true);
                    this.clearReplyTarget();
                    this.clearSelectedAttachment();
                    this.clearSelectedMessage();
                    this.showPinnedMessagesPanel = false;
                    this.showComposerEmojiPicker = false;
                    this.$nextTick(() => {
                        this.scrollConversationToBottom();
                    });
                } else {
                    await this.loadConversation(this.activeUser.id, { page: 1 });
                }
            } catch (error) {
                const message = error?.response?.data?.message || 'Unable to send message.';

                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Message not sent',
                        text: message,
                        confirmButtonColor: '#ff2a70',
                    });
                }
            } finally {
                this.sendingMessage = false;
                this.$nextTick(() => this.resizeComposer());
            }
        },
        handleAttachmentImageLoad() {
            if (!this.activeUser) {
                return;
            }
        },
        selectMessage(message) {
            this.selectedMessage = message || null;
            this.selectedMessageId = message?.id || null;
            this.showReactionPicker = false;
            this.reactionTargetId = null;
            this.activeMessageActionsId = null;
        },
        clearSelectedMessage() {
            this.selectedMessage = null;
            this.selectedMessageId = null;
            this.showReactionPicker = false;
            this.reactionTargetId = null;
            this.activeMessageActionsId = null;
        },
        async editMessage(message) {
            if (!message?.id || !message.is_mine || message.is_unsent) {
                return;
            }

            this.activeMessageActionsId = null;
            const currentBody = message.body || '';
            if (!currentBody.trim()) {
                return;
            }

            const nextBody = await this.promptForMessageEdit(currentBody);
            if (nextBody === null) {
                return;
            }

            const trimmedBody = nextBody.trim();
            if (!trimmedBody || trimmedBody === currentBody.trim()) {
                return;
            }

            try {
                const { data } = await axios.patch(
                    `/api/direct-messages/${message.id}`,
                    { body: trimmedBody },
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                if (data?.message) {
                    this.upsertLocalMessage(data.message);
                }

                if (data?.conversation_preview?.preview && this.activeUser) {
                    this.applyConversationPreview(this.activeUser, data.conversation_preview);
                }
            } catch (error) {
                const responseMessage = error?.response?.data?.message || 'Unable to edit message.';
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Edit failed',
                        text: responseMessage,
                    });
                }
            }
        },
        async unsendMessage(message) {
            if (!message?.id || !message.is_mine || message.is_unsent) {
                return;
            }

            this.activeMessageActionsId = null;
            const confirmed = await this.confirmUnsendMessage(message);
            if (!confirmed) {
                return;
            }

            try {
                const { data } = await axios.delete(`/api/direct-messages/${message.id}`, {
                    headers: this.buildAuthHeaders(),
                });

                if (data?.message) {
                    this.upsertLocalMessage({
                        ...data.message,
                        is_mine: true,
                    });
                }

                this.clearSelectedMessage();

                if (data?.conversation_preview?.preview && this.activeUser) {
                    this.applyConversationPreview(this.activeUser, data.conversation_preview);
                } else {
                    await this.loadConversation(this.activeUser.id, { page: 1 });
                }

                if (Array.isArray(data?.pinned_messages)) {
                    this.pinnedMessages = data.pinned_messages;
                }
            } catch (error) {
                const responseMessage = error?.response?.data?.message || 'Unable to unsend message.';
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Unsend failed',
                        text: responseMessage,
                    });
                }
            }
        },
        togglePinnedMessagesPanel() {
            this.showPinnedMessagesPanel = !this.showPinnedMessagesPanel;
            if (this.showPinnedMessagesPanel) {
                this.showComposerEmojiPicker = false;
            }
        },
        toggleComposerEmojiPicker() {
            this.showComposerEmojiPicker = !this.showComposerEmojiPicker;

            if (this.showComposerEmojiPicker) {
                this.showPinnedMessagesPanel = false;
                this.clearReactionPicker();
            }
        },
        closeComposerEmojiPicker() {
            this.showComposerEmojiPicker = false;
        },
        insertComposerEmoji(emoji) {
            if (!emoji) return;

            const textarea = this.$refs.composerInput;
            const current = this.draftMessage || '';

            if (!textarea) {
                this.draftMessage = `${current}${emoji}`;
                this.showComposerEmojiPicker = false;
                return;
            }

            const start = Number.isFinite(textarea.selectionStart) ? textarea.selectionStart : current.length;
            const end = Number.isFinite(textarea.selectionEnd) ? textarea.selectionEnd : current.length;

            this.draftMessage = `${current.slice(0, start)}${emoji}${current.slice(end)}`;
            this.showComposerEmojiPicker = false;

            this.$nextTick(() => {
                textarea.focus();
                const caret = start + emoji.length;
                textarea.setSelectionRange(caret, caret);
                this.resizeComposer();
            });
        },
        startReply(message) {
            if (!message || message.is_unsent) return;

            this.replyTargetMessage = message;
            this.clearReactionPicker();
            this.activeMessageActionsId = null;
        },
        clearReplyTarget() {
            this.replyTargetMessage = null;
        },
        triggerAttachmentPicker(mode = 'file') {
            this.attachmentMode = mode;
            this.attachmentAccept = mode === 'image'
                ? 'image/*'
                : '.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt';

            this.$nextTick(() => {
                const input = this.$refs.attachmentInput;
                if (input) {
                    input.click();
                }
            });
        },
        handleAttachmentChange(event) {
            const file = event?.target?.files?.[0] ?? null;
            if (!file) {
                this.clearSelectedAttachment();
                return;
            }

            const isImage = file.type?.startsWith('image/');
            if (this.attachmentMode === 'image' && !isImage) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Image required',
                        text: 'Please choose an image file for this action.',
                    });
                }
                event.target.value = '';
                return;
            }

            this.clearSelectedAttachment(false);
            this.selectedAttachment = file;
            this.selectedAttachmentPreviewType = isImage ? 'image' : 'file';
            this.selectedAttachmentPreviewUrl = isImage ? URL.createObjectURL(file) : null;
        },
        clearSelectedAttachment(resetInput = true) {
            if (this.selectedAttachmentPreviewUrl) {
                URL.revokeObjectURL(this.selectedAttachmentPreviewUrl);
                this.selectedAttachmentPreviewUrl = null;
            }

            this.selectedAttachment = null;
            this.selectedAttachmentPreviewType = 'file';
            this.attachmentMode = 'file';
            this.attachmentAccept = '.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt';

            if (resetInput) {
                const input = this.$refs.attachmentInput;
                if (input) {
                    input.value = '';
                }
            }
        },
        clearReactionPicker() {
            this.showReactionPicker = false;
            this.reactionTargetId = null;
        },
        toggleMessageActions(message) {
            if (!message?.id || message.is_unsent) {
                return;
            }

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.clearReactionPicker();
            this.activeMessageActionsId = this.activeMessageActionsId === message.id ? null : message.id;
        },
        toggleReactionPicker(message) {
            if (!message?.id || message.is_unsent) return;

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.reactionTargetId = message.id;
            this.activeMessageActionsId = null;
            this.showReactionPicker = this.showReactionPicker && this.reactionTargetId === message.id
                ? !this.showReactionPicker
                : true;
        },
        async togglePinMessage(message) {
            if (!message?.id || message.is_unsent) return;

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.clearReactionPicker();
            this.activeMessageActionsId = null;

            try {
                const { data } = await axios.patch(
                    `/api/direct-messages/${message.id}/pin`,
                    {
                        is_pinned: !message.pinned_at,
                    },
                    {
                        headers: this.buildAuthHeaders(),
                    }
                );

                if (data?.message) {
                    this.upsertLocalMessage(data.message);
                }

                if (Array.isArray(data?.pinned_messages)) {
                    this.pinnedMessages = data.pinned_messages;
                }

                if (!this.pinnedMessages.length) {
                    this.showPinnedMessagesPanel = false;
                }
            } catch (error) {
                console.error('Failed to toggle pin:', error);
            }
        },
        async unpinPinnedMessage(pin) {
            if (!pin?.message_id) return;

            const message = this.messages.find((item) => Number(item.id) === Number(pin.message_id))
                || { id: pin.message_id, pinned_at: pin.pinned_at || new Date().toISOString() };

            await this.togglePinMessage(message);
        },
        buildAuthHeaders() {
            const token = localStorage.getItem('auth_token');
            return token ? { Authorization: `Bearer ${token}` } : {};
        },
        upsertLocalMessage(message) {
            if (!message?.id) return;

            const existingIndex = this.messages.findIndex((item) => item.id === message.id);
            if (existingIndex === -1) {
                this.messages.push(message);
                return;
            }

            this.messages.splice(existingIndex, 1, {
                ...this.messages[existingIndex],
                ...message,
            });
        },
        removeLocalMessage(messageId) {
            const normalizedId = Number(messageId);
            const existingIndex = this.messages.findIndex((item) => Number(item.id) === normalizedId);

            if (existingIndex >= 0) {
                this.messages.splice(existingIndex, 1);
            }

            if (this.selectedMessageId && Number(this.selectedMessageId) === normalizedId) {
                this.clearSelectedMessage();
            }
        },
        async promptForMessageEdit(currentBody) {
            if (!window.Swal) {
                const fallback = window.prompt('Edit message', currentBody);
                return fallback;
            }

            const result = await window.Swal.fire({
                title: 'Edit message',
                input: 'textarea',
                inputValue: currentBody,
                inputAttributes: {
                    'aria-label': 'Edit message body',
                },
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#2f80ed',
                cancelButtonColor: '#6c757d',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Message cannot be empty.';
                    }

                    return null;
                },
            });

            if (!result.isConfirmed) {
                return null;
            }

            return result.value;
        },
        async confirmUnsendMessage(message) {
            if (!window.Swal) {
                return window.confirm('Unsend this message for everyone?');
            }

            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Unsend message?',
                text: 'This will remove the message for both sides.',
                showCancelButton: true,
                confirmButtonText: 'Unsend',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
            });

            return result.isConfirmed;
        },
        applyConversationPreview(user, conversationPreview) {
            if (!user || !conversationPreview) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(conversationPreview, 'latest_at')) {
                user.latest_at = conversationPreview.latest_at;
            }

            if (Object.prototype.hasOwnProperty.call(conversationPreview, 'preview')) {
                user.preview = conversationPreview.preview;
            }

            if (conversationPreview.latest_message?.id && Number(this.selectedUserId) === Number(user.id)) {
                this.upsertLocalMessage({
                    ...conversationPreview.latest_message,
                    is_mine: Number(conversationPreview.latest_message.sender_id) === Number(this.authUser?.id),
                });
            }

            this.syncUsersOnlineState();
        },
        isConversationPanelVisible() {
            if (typeof window === 'undefined') {
                return false;
            }

            const panel = this.$el?.querySelector('.conversation-panel');
            if (!panel) {
                return false;
            }

            const styles = window.getComputedStyle(panel);
            return styles.display !== 'none' && styles.visibility !== 'hidden' && panel.getClientRects().length > 0;
        },
        async markConversationSeen(userId = null) {
            const activeUser = this.activeUser;
            if (!activeUser) return;
            if (!this.isConversationPanelVisible()) return;

            const selectedUserId = Number(userId ?? activeUser.id);
            if (Number(activeUser.id) !== selectedUserId) {
                return;
            }

            if (!this.messages.some((message) => !message.is_mine && !message.read_at)) {
                return;
            }

            try {
                const { data } = await axios.post(
                    `/api/direct-messages/${selectedUserId}/seen`,
                    {},
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    }
                );

                const readAt = data?.read_at ?? null;
                const messageIds = new Set((data?.message_ids ?? []).map((id) => Number(id)));

                if (!readAt || messageIds.size === 0) return;

                this.messages = this.messages.map((message) => {
                    if (!messageIds.has(Number(message.id))) {
                        return message;
                    }

                    return {
                        ...message,
                        read_at: readAt,
                    };
                });
            } catch (error) {
                console.error('Failed to mark conversation as seen:', error);
            }
        },
        async scrollToPinnedMessage(pin) {
            if (!pin?.message_id) return;

            this.showPinnedMessagesPanel = false;
            this.selectedMessage = this.messages.find((message) => Number(message.id) === Number(pin.message_id)) || null;
            this.selectedMessageId = this.selectedMessage?.id || null;

            await this.scrollToMessageById(pin.message_id, {
                flashClass: 'message-row--highlighted',
            });
        },
        async scrollToReplyMessage(message) {
            const replyTargetId = message?.reply_to?.id || message?.reply_to_id;
            if (!replyTargetId) return;

            this.selectedMessage = this.messages.find((item) => Number(item.id) === Number(replyTargetId)) || null;
            this.selectedMessageId = this.selectedMessage?.id || null;

            await this.scrollToMessageById(replyTargetId, {
                flashClass: 'message-row--highlighted',
                shakeClass: 'message-row--reply-target',
            });
        },
        async scrollToMessageById(messageId, { flashClass = null, shakeClass = null } = {}) {
            if (!messageId) return;

            await this.$nextTick();

            const body = this.$el.querySelector('.conversation-panel__body');
            const target = this.$el.querySelector(`[data-message-id="${messageId}"]`);

            if (!body || !target) {
                return;
            }

            const bodyRect = body.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const nextTop = body.scrollTop + targetRect.top - bodyRect.top - body.clientHeight / 2 + target.clientHeight / 2;
            body.scrollTo({
                top: nextTop,
                behavior: 'smooth',
            });

            const timers = target.__messageTimers || (target.__messageTimers = {});

            if (timers.flash) {
                window.clearTimeout(timers.flash);
            }

            if (timers.shake) {
                window.clearTimeout(timers.shake);
            }

            if (flashClass) {
                target.classList.add(flashClass);
                timers.flash = window.setTimeout(() => target.classList.remove(flashClass), 900);
            }

            if (shakeClass) {
                timers.shake = window.setTimeout(() => {
                    target.classList.add(shakeClass);
                    window.setTimeout(() => target.classList.remove(shakeClass), 650);
                }, 420);
            } else if (flashClass) {
                timers.flash = window.setTimeout(() => target.classList.remove(flashClass), 900);
            }
        },
        async setReaction(message, reactionKey) {
            if (!message?.id || message.is_unsent) return;

            this.clearReactionPicker();
            const nextReaction = message.reaction === reactionKey ? null : reactionKey;

            try {
                const { data } = await axios.patch(
                    `/api/direct-messages/${message.id}/reaction`,
                    { reaction: nextReaction },
                    {
                        headers: this.buildAuthHeaders(),
                    }
                );

                if (data?.message) {
                    this.upsertLocalMessage(data.message);
                }
            } catch (error) {
                console.error('Failed to set reaction:', error);
            }
        },
        openImageGallery(attachment) {
            if (!attachment?.url) {
                return;
            }

            if (!window.lightGallery) {
                window.open(attachment.url, '_blank', 'noopener');
                return;
            }

            const galleryContainer = this.$refs.imageGalleryContainer;
            if (!galleryContainer) {
                window.open(attachment.url, '_blank', 'noopener');
                return;
            }

            this.destroyImageGallery();

            this.imageGalleryInstance = window.lightGallery(galleryContainer, {
                dynamic: true,
                dynamicEl: [
                    {
                        src: attachment.url,
                        thumb: attachment.url,
                        subHtml: attachment.name || 'Attachment',
                    },
                ],
                plugins: [window.lgThumbnail, window.lgZoom].filter(Boolean),
                licenseKey: '0000-0000-000-0000',
                speed: 300,
            });

            this.imageGalleryInstance.openGallery();
        },
        destroyImageGallery() {
            if (this.imageGalleryInstance) {
                this.imageGalleryInstance.destroy();
                this.imageGalleryInstance = null;
            }
        },
        getMessageSnippet(message) {
            if (!message) return 'Attachment';

            if (message.is_unsent) return 'Unsent Message';

            if (message.body) return message.body;

            if (message.attachment?.name) return message.attachment.name;

            return 'Attachment';
        },
        getReactionEmoji(reactionKey) {
            return this.reactionOptions.find((reaction) => reaction.key === reactionKey)?.emoji || '';
        },
        downloadAttachment(attachment) {
            if (!attachment?.url) return;

            const link = document.createElement('a');
            link.href = attachment.url;
            link.download = attachment.name || 'attachment';
            link.target = '_blank';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        },
        formatPinnedAt(timestamp) {
            if (!timestamp) return 'just now';

            try {
                return new Intl.DateTimeFormat([], {
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                }).format(new Date(timestamp));
            } catch (error) {
                return 'just now';
            }
        },
        formatFileSize(bytes) {
            const value = Number(bytes || 0);
            if (!value) return 'Unknown size';

            const units = ['B', 'KB', 'MB', 'GB'];
            let size = value;
            let unitIndex = 0;

            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex += 1;
            }

            return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
        },
        updateContactPreview(message, clearUnread = false) {
            const user = this.users.find((item) => Number(item.id) === Number(this.activeUser?.id));
            if (!user) return;

            user.latest_at = message.created_at || new Date().toISOString();
            user.preview = this.getMessageSnippet(message);
            user.unread_count = clearUnread ? 0 : Number(user.unread_count || 0);
            this.syncUsersOnlineState();
        },
        applySeenReceipt(partnerId, readAt, messageIds = []) {
            const normalizedPartnerId = Number(partnerId || 0);
            const normalizedReadAt = readAt ? new Date(readAt).toISOString() : null;

            if (!normalizedPartnerId || !normalizedReadAt) {
                return;
            }

            const normalizedMessageIds = new Set(
                Array.isArray(messageIds) ? messageIds.map((id) => Number(id)) : [],
            );

            if (normalizedMessageIds.size > 0) {
                this.messages = this.messages.map((message) => {
                    if (!normalizedMessageIds.has(Number(message.id))) {
                        return message;
                    }

                    return {
                        ...message,
                        read_at: normalizedReadAt,
                    };
                });
            }

        },
        refreshUserActivityLabels() {
            this.users.forEach((user) => {
                const activityState = this.getActivityState(user.id, user.latest_at);
                user.active_label = activityState.label;
                user.is_active = activityState.isActive;
                user.last_seen_at = activityState.lastSeenAt;
            });
        },
        getActivityState(userId, latestAt = null) {
            const normalizedUserId = Number(userId);
            const isOnline = this.onlineUserIds.includes(normalizedUserId);
            const lastSeenAt = this.getLastSeen(normalizedUserId);
            const fallbackAt = lastSeenAt || latestAt;

            if (isOnline) {
                return {
                    label: 'Online',
                    isActive: true,
                    lastSeenAt: lastSeenAt || Date.now(),
                };
            }

            if (!fallbackAt) {
                return {
                    label: 'Offline',
                    isActive: false,
                    lastSeenAt: null,
                };
            }

            const latestDate = new Date(fallbackAt);
            const diffMs = Date.now() - latestDate.getTime();

            if (Number.isNaN(diffMs) || diffMs < 0) {
                return {
                    label: 'Offline',
                    isActive: false,
                    lastSeenAt: null,
                };
            }

            const diffMinutes = Math.max(1, Math.floor(diffMs / 60_000));

            if (diffMinutes < 60) {
                return {
                    label: `Active ${diffMinutes} mins ago`,
                    isActive: false,
                    lastSeenAt: latestDate.getTime(),
                };
            }

            if (diffMinutes < 1440) {
                return {
                    label: `Active ${Math.floor(diffMinutes / 60)} hrs ago`,
                    isActive: false,
                    lastSeenAt: latestDate.getTime(),
                };
            }

            return {
                label: `Active ${Math.floor(diffMinutes / 1440)} days ago`,
                isActive: false,
                lastSeenAt: latestDate.getTime(),
            };
        },
        getActiveLabel(latestAt) {
            return this.getActivityState(this.activeUser?.id, latestAt).label;
        },
        scrollConversationToBottom() {
            this.$nextTick(() => {
                const body = this.$refs.conversationBody || this.$el.querySelector('.conversation-panel__body');
                if (!body) return;

                body.scrollTop = body.scrollHeight;
                this.showScrollToBottomButton = false;
            });
        },
        handleConversationScroll(event) {
            const body = event?.target;
            if (!body) return;

            this.updateScrollToBottomButton(body);

            if (
                this.loadingConversation ||
                this.loadingOlderConversation ||
                !this.activeUser ||
                !this.conversationHasMore ||
                this.conversationPage >= this.conversationLastPage
            ) {
                return;
            }

            if (body.scrollTop > 80) {
                return;
            }

            this.loadConversation(this.activeUser.id, {
                page: this.conversationPage + 1,
                preserveScroll: true,
            });
        },
        updateScrollToBottomButton(bodyEl) {
            if (!bodyEl) return;

            const distanceFromBottom = bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight;
            this.showScrollToBottomButton = distanceFromBottom > 220;
        },
        isConversationNearBottom(bodyEl, threshold = 220) {
            if (!bodyEl) return true;

            const distanceFromBottom = bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight;
            return distanceFromBottom <= threshold;
        },
        resizeComposer() {
            const textarea = this.$refs.composerInput;
            if (!textarea) return;

            textarea.style.height = 'auto';
            textarea.style.height = `${Math.min(textarea.scrollHeight, 140)}px`;
        },
        formatTime(value) {
            if (!value) return '';

            try {
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return '';
                }

                const diffMs = Date.now() - date.getTime();
                const oneDayMs = 24 * 60 * 60 * 1000;

                if (diffMs >= oneDayMs) {
                    const datePart = new Intl.DateTimeFormat([], {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    }).format(date);

                    const timePart = new Intl.DateTimeFormat([], {
                        hour: 'numeric',
                        minute: '2-digit',
                    }).format(date);

                    return `${datePart} ${timePart}`;
                }

                return new Intl.DateTimeFormat([], {
                    hour: 'numeric',
                    minute: '2-digit',
                }).format(date);
            } catch (error) {
                return '';
            }
        },
        formatSeenAt(value) {
            return this.formatTime(value);
        },
    },
};
</script>

<style scoped lang="scss">
.messages-page {
    width: 100%;
    height: 100dvh;
    min-height: 100dvh;
    padding: 0;
    overflow: hidden;
}

.messenger-shell {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 0;
    width: 100%;
    height: 100dvh;
    min-height: 100dvh;
}

.contacts-panel,
.conversation-panel {
    height: 100dvh;
    min-height: 100dvh;
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
}

.contacts-panel {
    flex: 0 0 400px;
    width: 400px;
    display: flex;
    flex-direction: column;
    transition: 
      transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      visibility 0s linear 0.3s;
    background: linear-gradient(180deg, rgba(16, 16, 24, 0.98), rgba(26, 18, 34, 0.98));
    // border-radius: 26px;
}

.conversation-panel {
    flex: 1 1 0;
    min-width: 0;
    width: 100%;
    position: relative;
    z-index: 1;
    background:
        radial-gradient(circle at top left, rgba(255, 112, 180, 0.16), transparent 28%),
        radial-gradient(circle at bottom right, rgba(109, 40, 217, 0.12), transparent 30%),
        linear-gradient(180deg, rgba(24, 12, 28, 0.98), rgba(19, 10, 23, 0.98));
}

.contacts-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 18px 10px;
}

.contacts-panel__eyebrow,
.conversation-panel__eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.58);
    margin-bottom: 4px;
}

.contacts-panel__header h1 {
    margin: 0;
    font-size: clamp(1.6rem, 2vw, 2.25rem);
    font-weight: 800;
    color: #f2f2f4;
}

.contacts-panel__actions,
.conversation-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contacts-panel__mobile-close,
.conversation-info-btn--users {
    display: none;
}

.icon-chip {
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: #f4f4f4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.18s ease, background 0.18s ease;
}

.icon-chip:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.16);
}

.contacts-panel__search {
    padding: 4px 18px 12px;
}

.search-shell {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 999px;
    padding: 0 16px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.04);
}

.search-shell i {
    color: rgba(255, 255, 255, 0.64);
    font-size: 1rem;
}

.search-shell input {
    flex: 1;
    background: transparent;
    border: 0;
    outline: none;
    color: #fff;
    padding: 13px 0;
    font-size: 1rem;
}

.search-shell input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 10px 10px 10px;
    min-height: 0;
}

.contact-card {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 0;
    border-radius: 18px;
    padding: 12px 14px;
    margin-bottom: 8px;
    background: rgba(255, 255, 255, 0.03);
    color: #fff;
    text-align: left;
    transition: background 0.18s ease, transform 0.18s ease;
}

.contact-card:hover {
    background: rgba(255, 255, 255, 0.07);
    transform: translateY(-1px);
}

.contact-card.is-active {
    background: linear-gradient(135deg, rgba(112, 35, 146, 0.9), rgba(53, 25, 66, 0.98));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
}

.contact-card__avatar {
    position: relative;
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
}

.contact-card__avatar img,
.conversation-user__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.contact-card__status-dot,
.conversation-user__status-dot {
    position: absolute;
    right: 2px;
    bottom: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid rgba(20, 20, 22, 0.96);
    background: #43d17b;
}

.contact-card__status-dot--inactive {
    background: #7f8796;
}

.contact-card__status-dot--active {
    background: #43d17b;
}

.conversation-user__status-dot--inactive {
    background: #7f8796;
}

.conversation-user__status-dot--active {
    background: #43d17b;
}

.contact-card__body {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    overflow: hidden;
}

.contact-card__name {
    display: block;
    font-size: 1.04rem;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-card__preview {
    display: none;
}

.contact-card__status {
    display: block;
    font-size: 0.84rem;
    color: rgba(255, 255, 255, 0.62);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-card.is-active .contact-card__status {
    color: rgba(255, 255, 255, 0.82);
}

.contact-card__meta {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.62);
}

.unread-pill {
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: linear-gradient(135deg, #ff3b85, #ff1f65);
    color: #fff;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.74rem;
}

.conversation-panel {
    display: flex;
    flex-direction: column;
    border-top-left-radius: 28px;
    border-top-right-radius: 28px;
}

.conversation-panel__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
}

.conversation-user {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.conversation-user__text {
    min-width: 0;
}

.conversation-user__avatar {
    position: relative;
    width: 54px;
    height: 54px;
    flex: 0 0 54px;
}

.conversation-user__name {
    display: block;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-user__status {
    display: block;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.92rem;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-actions .icon-chip {
    display: none;
}

.conversation-info-btn {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff43b4, #ff1f8f);
    color: #1e1121;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.18s ease, filter 0.18s ease;
}

.conversation-info-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.03);
}

.pin-chip {
    border: 0;
    border-radius: 999px;
    padding: 10px 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    font-weight: 700;
}

.pin-chip i {
    color: #ff69bf;
}

.pin-chip__count {
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 105, 191, 0.2);
    color: #ffb4df;
    font-size: 0.75rem;
}

.conversation-banner {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.82);
    text-align: left;
    cursor: pointer;
    position: relative;
    border-left: 0;
    border-right: 0;
    border-top: 0;
}

.conversation-banner__title {
    font-weight: 800;
    color: #fff;
}

.conversation-banner__pinned-summary {
    display: inline-flex;
    align-items: center;
    gap: 20px;
    min-width: 0;
}

.conversation-banner__pinned-copy {
    min-width: 0;
}

.conversation-banner__subtitle {
    display: block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: rgba(255, 255, 255, 0.72);
}

.conversation-banner__pinned-count {
    flex: 0 0 auto;
    min-width: 34px;
    height: 34px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-weight: 800;
}

.pinned-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    background: rgba(7, 5, 10, 0.7);
    backdrop-filter: blur(8px);
}

.pinned-modal {
    width: min(92vw, 720px);
    max-height: min(88vh, 820px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgba(24, 14, 30, 0.98), rgba(14, 9, 18, 0.98));
    box-shadow: 0 28px 70px rgba(0, 0, 0, 0.4);
}

.pinned-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.pinned-modal__title {
    font-weight: 800;
    color: #fff;
    font-size: 1.05rem;
}

.pinned-modal__close {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pinned-modal__list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 16px 18px 18px;
    overflow-y: auto;
}

.pinned-modal__item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
}

.pinned-modal__item-body {
    flex: 1;
    border: 0;
    border-radius: 16px;
    padding: 14px 16px 12px;
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    text-align: left;
    transition: background 0.16s ease, transform 0.16s ease;
}

.pinned-modal__item-body:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

.pinned-modal__item-preview {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pinned-modal__item-date {
    display: block;
    margin-top: 6px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.78rem;
}

.pinned-modal__item-unpin {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #ff7ab7;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
}

.pinned-modal__item-unpin:hover {
    background: rgba(255, 77, 136, 0.16);
}

.pinned-modal__empty {
    padding: 18px 20px 22px;
}

.conversation-panel__body {
    position: relative;
    flex: 1;
    overflow-y: auto;
    overflow-x: clip;
    padding: 18px 22px 18px;
    background:
        radial-gradient(circle at 20% 20%, rgba(255, 102, 179, 0.08), transparent 18%),
        radial-gradient(circle at 80% 30%, rgba(138, 92, 255, 0.08), transparent 20%),
        linear-gradient(180deg, rgba(25, 15, 31, 0.98), rgba(18, 10, 24, 0.98));
}

.message-stream {
    width: 100%;
    min-width: 0;
    overflow: hidden;
    padding-bottom: 20px;
}

.chat-empty,
.chat-loading {
    position: relative;
    z-index: 1;
    min-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
    color: rgba(255, 255, 255, 0.76);
    text-align: center;
    padding: 40px 20px;
}

.chat-loading--inline {
    min-height: 0;
    padding: 12px 0 18px;
}

.conversation-start-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 8px 0 16px;
    color: rgba(255, 255, 255, 0.58);
    font-size: 0.82rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.conversation-start-marker::before,
.conversation-start-marker::after {
    content: "";
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18));
}

.conversation-start-marker::before {
    margin-right: 12px;
}

.conversation-start-marker::after {
    margin-left: 12px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.18), transparent);
}

.chat-empty__icon {
    width: 78px;
    height: 78px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: grid;
    place-items: center;
    font-size: 2rem;
    color: #fff;
    margin-bottom: 6px;
}

.message-stream {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.message-row {
    position: relative;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    width: 100%;
    min-width: 0;
}

.message-row--mine {
    justify-content: flex-end;
}

.message-row--theirs {
    justify-content: flex-start;
}

.message-row--typing {
    margin-top: 2px;
}

.message-row--highlighted .message-bubble {
    box-shadow: 0 0 0 2px rgba(255, 77, 136, 0.22), 0 16px 30px rgba(0, 0, 0, 0.18);
    transform: translateY(-1px);
    animation: messageFlash 0.8s ease-in-out;
}

.message-row--reply-target .message-bubble {
    animation: messageShake 0.65s ease-in-out;
}

.message-bubble {
    position: relative;
    min-width: 0;
    max-width: min(76%, 640px);
    border-radius: 18px;
    padding: 12px 14px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.12);
}

.message-bubble__text {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.message-bubble__text--unsent {
    font-style: italic;
    opacity: 0.78;
}

.message-row--mine .message-bubble {
    background: linear-gradient(135deg, #ff4d88, #b83de6);
    border-bottom-right-radius: 6px;
}

.message-row--theirs .message-bubble {
    background: rgba(255, 255, 255, 0.08);
    border-bottom-left-radius: 6px;
}

.message-bubble--unsent {
    opacity: 0.94;
}

.message-bubble--typing {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 12px 16px;
}

.typing-indicator__dots {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.typing-indicator__dots span {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #ff86ca;
    animation: typingBounce 1s infinite ease-in-out;
}

.typing-indicator__dots span:nth-child(2) {
    animation-delay: 0.15s;
}

.typing-indicator__dots span:nth-child(3) {
    animation-delay: 0.3s;
}

.typing-indicator__label {
    color: rgba(255, 255, 255, 0.9);
    white-space: nowrap;
}

.message-bubble__floating-actions {
    position: absolute;
    top: 50%;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transform: translateY(-50%);
    z-index: 80;
    pointer-events: auto;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.16s ease, visibility 0.16s ease;
}

.message-row:hover .message-bubble__floating-actions {
    opacity: 1;
    visibility: visible;
}

.message-bubble__floating-actions.is-open {
    opacity: 1;
    visibility: visible;
}

.message-row--mine .message-bubble__floating-actions {
    right: calc(100% + 10px);
}

.message-row--theirs .message-bubble__floating-actions {
    left: calc(100% + 10px);
    flex-direction: row-reverse;
}

.bubble-action-group {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.bubble-action-menu {
    position: absolute;
    bottom: calc(100% + 8px);
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 148px;
    padding: 8px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(24, 20, 30, 0.96);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.28);
    z-index: 120;
}

.bubble-action {
    width: 38px;
    height: 38px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 50%;
    background: rgba(32, 24, 38, 0.86);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.22);
    transition: transform 0.16s ease, background 0.16s ease;
}

.bubble-action:hover {
    transform: translateY(-1px) scale(1.05);
    background: rgba(255, 73, 156, 0.95);
}

.bubble-action--danger:hover {
    background: rgba(220, 53, 69, 0.96);
}

.bubble-action.is-active {
    background: rgba(255, 73, 156, 0.95);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.3);
}

.bubble-action--menu {
    width: 100%;
    min-height: 36px;
    border-radius: 10px;
    justify-content: flex-start;
    padding: 0 10px 0 8px;
    gap: 10px;
    font-size: 0.95rem;
    white-space: nowrap;
    font-weight: 600;
    border: 0;
    background: transparent;
}

.bubble-action__icon {
    width: 22px;
    height: 22px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 22px;
    color: #f5f5f7;
}

.bubble-action__icon i {
    font-size: 0.74rem;
}

.bubble-action--danger .bubble-action__icon {
    color: #fff;
}

.reaction-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    pointer-events: none;
}

.reaction-overlay--panel {
    pointer-events: auto;
}

.reaction-picker {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 0.7rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(28, 18, 34, 0.95);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.24);
    flex-wrap: wrap;
    max-width: min(100%, 18rem);
    overflow: hidden;
    z-index: 11;
}

.reaction-picker--centered {
    pointer-events: auto;
    min-width: min(92%, 16rem);
}

.reaction-picker__btn {
    width: 2.2rem;
    height: 2.2rem;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.16s ease, background-color 0.16s ease, color 0.16s ease;
}

.reaction-picker__btn:hover {
    transform: translateY(-1px) scale(1.08);
    background: rgba(255, 255, 255, 0.08);
}

.reaction-picker__glyph {
    display: inline-block;
    font-size: 1.15rem;
    line-height: 1;
}

.message-bubble__reply {
    padding: 8px 10px;
    margin-bottom: 8px;
    border-left: 3px solid rgba(255, 255, 255, 0.35);
    background: rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    font-size: 0.84rem;
    color: rgba(255, 255, 255, 0.86);
}

.message-bubble__reply--link {
    width: 100%;
    border: 0;
    appearance: none;
    -webkit-appearance: none;
    font: inherit;
    color: inherit;
    display: block;
    text-align: left;
    cursor: pointer;
}

.message-bubble__reply--link:hover {
    background: rgba(0, 0, 0, 0.14);
}

.message-bubble__reply-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.68);
    margin-bottom: 6px;
}

.message-bubble__attachment {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.18);
    color: #fff;
    text-decoration: none;
}

.message-bubble__attachment--file {
    align-items: center;
}

.message-bubble__attachment--image {
    position: relative;
    display: block;
    overflow: hidden;
    padding: 0;
    border-radius: 16px;
}

.message-bubble__image-link {
    position: relative;
    display: block;
    width: 100%;
    border: 0;
    padding: 0;
    background: transparent;
    cursor: zoom-in;
}

.message-bubble__attachment--image img {
    display: block;
    width: 100%;
    height: auto;
    max-width: 100%;
    object-fit: cover;
}

.message-bubble__attachment-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    min-width: 0;
}

.message-bubble__attachment-name {
    display: block;
    line-height: 1.1;
}

.message-bubble__attachment-download {
    margin-left: auto;
    flex: 0 0 auto;
    color: rgba(255, 255, 255, 0.9);
}

.message-bubble__attachment-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.18));
    color: #fff;
    opacity: 0;
    transition: opacity 0.18s ease;
    font-size: 1.1rem;
}

.message-bubble__image-link:hover .message-bubble__attachment-overlay {
    opacity: 1;
}

.message-bubble__time {
    margin-top: 6px;
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.72);
    text-align: right;
}

.message-bubble__status {
    margin-top: 2px;
    font-size: 0.72rem;
    text-align: right;
}

.message-bubble__status--sent {
    color: rgba(255, 255, 255, 0.52);
}

.message-bubble__status--seen {
    color: rgba(149, 255, 200, 0.92);
}

.message-bubble__status-edit {
    color: rgba(255, 255, 255, 0.74);
    margin-left: 3px;
}

.message-pin-chip--floating,
.message-reaction-badge--floating {
    position: absolute;
    z-index: 4;
}

.message-pin-chip--floating {
    top: -8px;
    left: -8px;
    width: 1.05rem;
    height: 1.05rem;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    transform: rotate(-45deg);
    transform-origin: center;
    pointer-events: none;
}

.message-reaction-badge--floating {
    bottom: -0.9rem;
}

.message-row--mine .message-reaction-badge--floating {
    left: -0.5rem;
}

.message-row--theirs .message-reaction-badge--floating {
    right: -0.5rem;
}

.message-pin-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.05rem;
    height: 1.05rem;
    border-radius: 999px;
}

.message-pin-chip__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ff5d7d;
    font-size: 0.72rem;
    line-height: 1;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.2));
}

.message-reaction-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.6rem;
    min-height: 1.6rem;
    padding: 0.2rem 0.45rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
}

.message-reaction-badge--floating {
    min-width: 1.6rem;
    min-height: 1.6rem;
}

.message-reaction-badge__glyph {
    display: inline-block;
    line-height: 1;
    font-size: 1rem;
}

.message-scroll-bottom {
    position: absolute;
    right: 50px;
    bottom: 140px;
    width: 52px;
    height: 52px;
    border: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff4d88, #b83de6);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    z-index: 6;
}

.message-scroll-bottom:hover {
    transform: translateY(-1px);
}

.composer {
    display: flex;
    align-items: start;
    gap: 12px;
    padding: 14px 16px 16px;
    background: rgba(12, 8, 17, 0.92);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
}

.composer__button {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #ff86ca;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.16s ease, background 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
}

.composer__button:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.14);
}

.composer__button.is-active {
    background: linear-gradient(135deg, #ff4d88, #b83de6);
    color: #fff;
    box-shadow: 0 12px 24px rgba(255, 77, 136, 0.22);
}

.composer__field {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.composer__input-shell {
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    padding: 0 14px 0 16px;
    border: 1px solid rgba(255, 255, 255, 0.04);
}

.composer__input {
    width: 100%;
    min-height: 52px;
    max-height: 140px;
    resize: none;
    border: 0;
    outline: none;
    background: transparent;
    color: #fff;
    padding: 14px 0;
    line-height: 1.4;
}

.composer__input::placeholder {
    color: rgba(255, 255, 255, 0.55);
}

.composer__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 6px;
}

.composer__hint,
.composer__counter {
    text-transform: uppercase;
    font-size: 0.64rem;
    font-style: italic !important;
    line-height: 1;
    color: rgba(255, 255, 255, 0.54);
    user-select: none;
}

.composer__counter.is-near-limit {
    color: #ff9ebf;
}

.composer__send {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff4d88, #b83de6);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 30px rgba(255, 42, 112, 0.28);
}

.composer__scroll-bottom {
    display: none;
}

.composer-reply,
.attachment-preview {
    margin: 0 16px 10px;
    padding: 12px 14px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.attachment-preview {
    display: flex;
    align-items: center;
    gap: 12px;
}

.composer-reply__meta,
.attachment-preview__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-width: 0;
}

.attachment-preview__meta {
    flex: 1;
}

.composer-reply__preview {
    margin-top: 6px;
    color: rgba(255, 255, 255, 0.84);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.composer-reply__close,
.attachment-preview__remove {
    width: 30px;
    height: 30px;
    flex: 0 0 30px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
}

.attachment-preview__body {
    min-width: 0;
    flex: 1;
}

.attachment-preview__name {
    color: #fff;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-preview__icon {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ff86ca;
}

.attachment-preview__thumb {
    width: 54px;
    height: 54px;
    flex: 0 0 54px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.08);
}

.attachment-preview__thumb img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.composer__send:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
}

.composer-emoji-overlay {
    position: absolute;
    left: 50%;
    bottom: calc(100% + 14px);
    transform: translateX(-50%);
    z-index: 20;
    pointer-events: auto;
}

.composer-emoji-picker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 999px;
    background: rgba(18, 12, 24, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 18px 34px rgba(0, 0, 0, 0.3);
    flex-wrap: wrap;
    max-width: min(84vw, 420px);
}

.composer-emoji-picker__btn {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: transform 0.15s ease, background 0.15s ease;
}

.composer-emoji-picker__btn:hover {
    transform: translateY(-1px) scale(1.06);
    background: rgba(255, 77, 136, 0.16);
}

.loader-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #ff4d88;
    box-shadow: 0 0 0 0 rgba(255, 77, 136, 0.35);
    animation: pulse 1.5s infinite;
}

@keyframes typingBounce {
    0%,
    80%,
    100% {
        transform: translateY(0);
        opacity: 0.45;
    }
    40% {
        transform: translateY(-4px);
        opacity: 1;
    }
}

@keyframes messageFlash {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 77, 136, 0.45), 0 16px 30px rgba(0, 0, 0, 0.18);
    }
    100% {
        box-shadow: 0 0 0 12px rgba(255, 77, 136, 0), 0 16px 30px rgba(0, 0, 0, 0.18);
    }
}

@keyframes messageShake {
    0%,
    100% {
        transform: translateX(0);
    }
    20% {
        transform: translateX(-4px);
    }
    40% {
        transform: translateX(4px);
    }
    60% {
        transform: translateX(-3px);
    }
    80% {
        transform: translateX(3px);
    }
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 77, 136, 0.35);
    }
    70% {
        box-shadow: 0 0 0 14px rgba(255, 77, 136, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 77, 136, 0);
    }
}

@media (max-width: 1199.98px) {
    .messenger-shell {
        flex-direction: column;
        height: 100dvh;
        min-height: 100dvh;
    }

    .contacts-panel,
    .conversation-panel {
        min-height: 0;
        height: auto;
        width: 100%;
        flex: 1 1 auto;
    }

    .contacts-panel {
        flex: 0 0 auto;
        max-height: 34dvh;
    }

    .conversation-panel {
        flex: 1 1 auto;
        min-height: 0;
    }
}

@media (max-width: 767.98px) {
    .messages-page {
        padding: 8px;
        height: 100dvh;
        min-height: 100dvh;
    }

    .messenger-shell {
        gap: 8px;
        position: relative;
    }

    .messenger-shell.is-mobile-users-open {
        overflow: hidden;
    }

    .contacts-panel {
        display: flex;
        position: absolute;
        top: 0;
        left: 100%;
        height: 100dvh;
        width: 100%;
        padding: 16px 0 0 8px;
        z-index: 1000;
        min-height: 0;
        max-height: none;
        border-radius: 0;
        box-shadow: none;
        overflow: hidden;
        transform: translateX(0);
        background-color: rgba(0, 0, 0, 0.8);
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        will-change: transform;
        transition:
            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            visibility 0s linear 0.3s;
    }

    .messenger-shell.is-mobile-users-open .contacts-panel {
        display: flex;
    }

    .contacts-panel.is-mobile-open {
        display: flex;
        z-index: 1000;
        left: 100% !important;
        transform: translateX(-100%);
        opacity: 1;
        pointer-events: auto;
        visibility: visible;
        transition: all ease 0.3s;
        transition:
            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            visibility 0s linear 0s;
    }

    .contacts-panel.is-mobile-users-open {
        display: flex;
        z-index: 1000;
        left: 100%;
        transform: translateX(-100%);
        opacity: 1;
        pointer-events: auto;
        visibility: visible;
        transition:
            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            visibility 0s linear 0s;
    }

    .contacts-panel.is-mobile-closing {
        left: 100%;
        transform: translateX(0);
        opacity: 0;
        pointer-events: none;
        visibility: visible;
        transition:
            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            visibility 0s linear 0s;
    }

    .conversation-panel {
        border-radius: 20px;
        min-height: 0;
    }

    .conversation-panel.is-mobile-hidden {
        display: none;
    }

    .messenger-shell.is-mobile-users-open .conversation-panel {
        display: none;
    }

    .contacts-panel__header {
        padding: 14px 14px 8px;
    }

    .contacts-panel__search {
        padding: 4px 14px 10px;
    }

    .search-shell {
        gap: 10px;
        padding: 0 14px;
    }

    .search-shell input {
        padding: 11px 0;
        font-size: 0.95rem;
    }

    .contacts-list {
        padding: 0 8px 8px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .contact-card {
        gap: 12px;
        padding: 10px 12px;
        margin-bottom: 7px;
        border-radius: 16px;
    }

    .contact-card__avatar {
        width: 46px;
        height: 46px;
        flex-basis: 46px;
    }

    .contact-card__name {
        font-size: 0.97rem;
    }

    .contact-card__status {
        font-size: 0.76rem;
    }

    .contact-card__meta {
        align-items: flex-start;
        gap: 6px;
    }

    .unread-pill {
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        font-size: 0.7rem;
    }

    .conversation-panel__top {
        padding: 14px 14px 12px;
    }

    .conversation-user {
        gap: 10px;
    }

    .conversation-info-btn--users {
        display: inline-flex;
    }

    .contacts-panel__mobile-close {
        display: inline-flex;
    }

    .conversation-user__avatar {
        width: 44px;
        height: 44px;
        flex-basis: 44px;
    }

    .conversation-user__name {
        font-size: 1rem;
    }

    .conversation-user__status {
        font-size: 0.8rem;
    }

    .conversation-actions .icon-chip {
        display: inline-flex;
        width: 40px;
        height: 40px;
        flex-basis: 40px;
    }

    .conversation-info-btn {
        width: 40px;
        height: 40px;
        flex-basis: 40px;
    }

    .conversation-panel__top,
    .conversation-banner,
    .composer {
        padding-left: 12px;
        padding-right: 12px;
    }

    .conversation-panel__body {
        padding: 14px 12px 14px;
    }

    .conversation-banner {
        gap: 12px;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .conversation-banner__pinned-summary {
        gap: 12px;
    }

    .conversation-banner__subtitle {
        font-size: 0.8rem;
    }

    .message-stream {
        gap: 14px;
    }

    .message-row {
        gap: 8px;
    }

    .message-bubble {
        max-width: 92%;
        padding: 10px 12px;
        border-radius: 16px;
    }

    .message-bubble__time,
    .message-bubble__status {
        font-size: 0.68rem;
    }

    .message-bubble__floating-actions {
        gap: 6px;
    }

    .bubble-action {
        width: 34px;
        height: 34px;
    }

    .message-scroll-bottom {
        display: none;
    }

    .composer__scroll-bottom {
        display: inline-flex;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff4d88, #b83de6);
        color: #fff;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
        order: 2;
        padding-top: 10px;
        padding-bottom: 10px;
        margin-top: 4px;
        margin-bottom: 4px;
    }

    .composer__scroll-bottom:hover {
        transform: translateY(-1px);
    }

    .message-scroll-bottom {
        right: 16px;
        bottom: 114px;
        width: 46px;
        height: 46px;
    }

    .composer {
        gap: 8px;
        padding-top: 20px;
        padding-bottom: 22px;
        flex-wrap: wrap;
    }

    .composer__field {
        min-width: 100%;
        order: 1;
    }

    .composer__button {
        width: 38px;
        height: 38px;
        flex-basis: 38px;
        order: 2;
        padding-top: 10px;
        padding-bottom: 10px;
        margin-top: 4px;
        margin-bottom: 4px;
    }

    .composer__send {
        width: 38px;
        height: 38px;
        flex-basis: 38px;
        order: 3;
        margin-left: auto;
        padding-top: 10px;
        padding-bottom: 10px;
        margin-top: 4px;
        margin-bottom: 4px;
    }

    .composer__input-shell {
        padding-left: 14px;
        padding-right: 12px;
    }

    .composer__input {
        min-height: 48px;
        max-height: 120px;
        font-size: 0.95rem;
    }

    .composer__meta {
        margin: 10px 10px;
        padding: 0 2px;
        gap: 8px;
    }

    .composer-reply,
    .attachment-preview {
        margin-left: 12px;
        margin-right: 12px;
        margin-bottom: 8px;
        padding: 10px 12px;
    }

    .attachment-preview__thumb {
        width: 46px;
        height: 46px;
        flex-basis: 46px;
    }

    .conversation-start-marker {
        font-size: 0.72rem;
        margin: 6px 0 12px;
    }

    .conversation-start-marker::before {
        margin-right: 10px;
    }

    .conversation-start-marker::after {
        margin-left: 10px;
    }
}
</style>
