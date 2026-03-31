<template>
    <div class="messages-page">
        <div class="messages-workspace">
            <div
                class="messenger-shell"
                :class="{ 'is-mobile-users-open': showMobileUsersPanel }"
            >
                <MessagesSidebar
                    :show-mobile-users-panel="showMobileUsersPanel"
                    :mobile-users-panel-closing="mobileUsersPanelClosing"
                >
                    <template #header>
                    <div class="contacts-panel__header">
                        <div>
                            <div class="contacts-panel__eyebrow">Conversations</div>
                            <div class="contacts-panel__title-row">
                                <h2>Inbox</h2>
                                <span class="contacts-panel__count-badge">{{ visibleUsers.length }}</span>
                            </div>
                            <p class="contacts-panel__subtitle">
                                Direct and group chats synced with your HRIS workspace.
                            </p>
                            <div class="contacts-panel__utility-row">
                                <a href="/employee/dashboard" class="contacts-panel__utility-link">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    <span>Dashboard</span>
                                </a>
                                <button
                                    type="button"
                                    class="contacts-panel__utility-badge"
                                    @click="showBetaInfoModal = true"
                                >
                                    <i class="fa-solid fa-flask"></i>
                                    <span>Beta</span>
                                </button>
                            </div>
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
                                @click="openGroupChatModal"
                            >
                                <i class="fa-solid fa-people-group"></i>
                            </button>
                            <button
                                v-if="groupChatRequestHistory.length"
                                type="button"
                                class="icon-chip"
                                aria-label="Request history"
                                title="Request history"
                                @click="showGroupChatRequestsModal = true"
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
                                @click="showApprovalModal = true"
                            >
                                <i class="fa-solid fa-user-check"></i>
                                <span v-if="pendingGroupChatApprovals.length" class="contacts-panel__action-badge">
                                    {{ pendingGroupChatApprovals.length }}
                                </span>
                            </button>
                        </div>
                    </div>
                    </template>

                    <template #search>
                    <div class="contacts-panel__search">
                        <div class="search-shell">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search conversations"
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
                                <span class="contact-card__avatar contact-card__avatar--skeleton skeleton-shimmer"></span>
                                <span class="contact-card__body">
                                    <span class="contact-card__skeleton-line contact-card__skeleton-line--name skeleton-shimmer"></span>
                                    <span class="contact-card__skeleton-line contact-card__skeleton-line--preview skeleton-shimmer"></span>
                                    <span class="contact-card__skeleton-line contact-card__skeleton-line--status skeleton-shimmer"></span>
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
                                :class="{ 'is-active': user.conversation_key === selectedConversationKey }"
                                role="button"
                                tabindex="0"
                                @click="selectUser(user)"
                                @keydown.enter.prevent="selectUser(user)"
                                @keydown.space.prevent="selectUser(user)"
                            >
                                <span class="contact-card__avatar">
                                    <img :src="user.profile" alt="profile" />
                                    <span
                                        class="contact-card__status-dot"
                                        :class="isConversationOnline(user) ? 'contact-card__status-dot--active' : 'contact-card__status-dot--inactive'"
                                    ></span>
                                </span>

                                <span class="contact-card__body">
                                    <span class="contact-card__name-row">
                                        <span class="contact-card__name">{{ user.name }}</span>
                                        <span v-if="user.unread_count > 0" class="unread-pill contact-card__name-unread">
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
                                            @click.stop="toggleContactActionMenu(user)"
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
                                                    @click.stop="openConversationDeleteModal(user)"
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
                        <div class="text-white-50 small">Try a different search or filter.</div>
                            </div>
                        </template>
                    </div>
                    </template>
                </MessagesSidebar>

                <ConversationWorkspace :show-mobile-users-panel="showMobileUsersPanel">
                    <template #header>
                <div class="conversation-panel__top">
                    <div class="conversation-user">
                        <span class="conversation-user__avatar">
                            <img :src="activeUserAvatar" alt="profile" />
                            <span
                                class="conversation-user__status-dot"
                                :class="isConversationOnline(activeUser) ? 'conversation-user__status-dot--active' : 'conversation-user__status-dot--inactive'"
                            ></span>
                        </span>
                        <div class="conversation-user__text text-truncate">
                            <div class="conversation-user__eyebrow">
                                {{ activeConversationIsGroup ? 'Group conversation' : 'Direct message' }}
                            </div>
                            <h2 class="conversation-user__name">{{ activeUserName }}</h2>
                            <div class="conversation-user__status">
                                {{ activeUserStatus }}
                            </div>
                        </div>
                    </div>

                    <div class="conversation-actions">
                        <template v-if="activeConversationIsGroup">
                            <button
                                type="button"
                                class="conversation-info-btn"
                                aria-label="Invite users"
                                title="Invite users"
                                @click="openInviteMembersModal"
                            >
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                            <button
                                type="button"
                                class="conversation-info-btn"
                                aria-label="See members"
                                title="See members"
                                @click="openGroupMembersModal"
                            >
                                <i class="fa-solid fa-users"></i>
                            </button>
                            <button
                                type="button"
                                class="conversation-info-btn conversation-info-btn--danger"
                                aria-label="Leave group"
                                title="Leave group"
                                @click="leaveActiveGroup"
                            >
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                            <button
                                type="button"
                                class="conversation-info-btn conversation-info-btn--info"
                                aria-label="Group info"
                                title="Group info"
                                @click="openConversationInfoModal"
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
                            @click="openConversationInfoModal"
                        >
                            <i class="fa-solid fa-circle-info"></i>
                        </button>
                        <button
                            type="button"
                            class="icon-chip contacts-panel__mobile-close"
                            aria-label="Open user list"
                            title="Open user list"
                            @click="openMobileUsersPanel"
                        >
                            <i class="fa-solid fa-user-group"></i>
                        </button>
                    </div>
                </div>
                    </template>

                    <template #pinned-banner>
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
                    </template>

                    <template #pinned-panel>
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
                    </template>

                    <template #body>
                <div class="conversation-panel__body" ref="conversationBody" @scroll.passive="handleConversationScroll">
                    <div v-if="showInitialPageSkeleton" class="chat-skeleton">
                        <div class="chat-skeleton__date skeleton-shimmer"></div>
                        <div
                            v-for="index in 6"
                            :key="`message-skeleton-${index}`"
                            class="chat-skeleton__row"
                            :class="index % 2 === 0 ? 'chat-skeleton__row--mine' : 'chat-skeleton__row--theirs'"
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

                    <div v-if="loadingOlderConversation" class="chat-loading chat-loading--inline">
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
                        @click="message.is_system ? null : selectMessage(message)"
                    >
                        <div v-if="message.is_system" class="message-system-note">
                            {{ message.body }}
                        </div>
                        <div v-else class="message-bubble" :class="{ 'message-bubble--unsent': message.is_unsent }">
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
                                            v-if="activeConversationIsGroup && !message.is_mine"
                                            class="message-bubble__sender"
                                        >
                                            {{ message.sender_name || 'User' }}
                                        </div>
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
                            <span class="typing-indicator__label">{{ typingIndicatorLabel }}</span>
                        </div>
                    </div>

                </div>

                <div v-if="replyTargetMessage" class="composer-reply">
                    <div class="composer-reply__meta">
                        <strong>Replying to {{ replyTargetLabel }}</strong>
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
                    <button
                        type="button"
                        class="composer__button"
                        ref="composerEmojiButton"
                        aria-label="Insert emoji"
                        @click="toggleComposerEmojiPicker"
                    >
                        <i class="fa-regular fa-face-smile"></i>
                    </button>
                    <button
                        type="button"
                        class="composer__button"
                        aria-label="Attach file"
                        @click="triggerAttachmentPicker('file')"
                    >
                        <i class="fa-regular fa-file-lines"></i>
                    </button>
                    <div class="composer__field">
                        <div class="composer__input-shell">
                            <textarea
                                ref="composerInput"
                                v-model="draftMessage"
                                class="composer__input"
                                rows="1"
                                :placeholder="activeConversationIsGroup ? 'Message the group' : 'Aa'"
                                :maxlength="messageCharacterLimit"
                                :disabled="!activeUser || sendingMessage"
                                @input="handleComposerInput"
                                @blur="handleComposerBlur"
                                @focus="captureComposerSelection"
                                @click="captureComposerSelection"
                                @keyup="captureComposerSelection"
                                @select="captureComposerSelection"
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

                    <transition name="fade">
                        <div
                            v-if="showComposerEmojiPicker"
                            ref="composerEmojiOverlay"
                            class="composer-emoji-overlay"
                            @click.stop
                        >
                            <div class="composer-emoji-picker">
                                <button
                                    v-for="emoji in composerEmojiOptions"
                                    :key="emoji"
                                    type="button"
                                    class="composer-emoji-picker__btn"
                                    @pointerdown.prevent.stop="insertComposerEmoji(emoji)"
                                >
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>
                    </transition>
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
                    </template>
                </ConversationWorkspace>
            </div>
        </div>

        <transition name="fade">
            <div
                v-if="showBetaInfoModal"
                class="message-action-modal-backdrop"
                @click.self="showBetaInfoModal = false"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-flask"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Beta release</div>
                            <h3 class="message-action-modal__title">About the Messages beta</h3>
                            <p class="message-action-modal__subtitle">
                                This module is aimed at giving HRIS users one built-in space for direct messages,
                                group coordination, approvals, and quick internal communication without leaving the portal.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="showBetaInfoModal = false"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div class="message-action-modal__context">
                            <div class="message-action-modal__context-label">What this beta is for</div>
                            <div class="message-action-modal__preview message-action-modal__preview--stacked">
                                <p>The goal is to make messaging feel native to the HRIS Portal instead of a separate tool.</p>
                                <p>It is designed for employee-to-employee chat, team group chats, coordination with admins, and faster in-system updates tied to daily HR workflows.</p>
                            </div>
                        </div>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--primary"
                            @click="showBetaInfoModal = false"
                        >
                            Understood
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showGroupChatRequestsModal"
                class="message-action-modal-backdrop"
                @click.self="showGroupChatRequestsModal = false"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Request history</div>
                            <h3 class="message-action-modal__title">Group chat request updates</h3>
                            <p class="message-action-modal__subtitle">
                                Review all of your approved and rejected group chat requests.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="showGroupChatRequestsModal = false"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div v-if="groupChatRequestHistory.length" class="group-chat-approval-list">
                            <div
                                v-for="request in groupChatRequestHistory"
                                :key="`group-chat-request-${request.id}`"
                                class="group-chat-approval-card"
                            >
                                <div class="group-chat-approval-card__topline">
                                    <div class="group-chat-approval-card__heading">
                                        <div class="group-chat-approval-card__title">{{ request.name }}</div>
                                        <div class="group-chat-approval-card__meta">
                                            {{ request.approval_status === 'approved' ? 'Approved' : 'Rejected' }}
                                            · Bctioned By {{ request.processed_by?.name || 'Admin' }}
                                            <span v-if="request.processed_at || request.created_at">
                                                • {{ formatConversationTimestamp(request.processed_at || request.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="group-chat-approval-card__top-actions">
                                        <span
                                            class="group-chat-request-status"
                                            :class="`group-chat-request-status--${request.approval_status}`"
                                        >
                                            {{ formatRequestStatus(request.approval_status) }}
                                        </span>
                                        <button
                                            v-if="request.approval_status === 'approved'"
                                            type="button"
                                            class="group-chat-approval-card__open-btn"
                                            aria-label="Open messages"
                                            title="Open messages"
                                            @click="openApprovedRequestConversation(request)"
                                        >
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="(request.members || []).length" class="group-chat-approval-card__members">
                                    <button
                                        type="button"
                                        class="group-chat-approval-card__member-trigger"
                                        :aria-expanded="activeGroupRequestTooltipId === request.id"
                                        :aria-label="`Show members for ${request.name}`"
                                        @click.stop="toggleGroupRequestTooltip(request.id)"
                                    >
                                        <span class="group-chat-approval-card__member-stack">
                                            <span
                                                v-for="member in getRequestMemberPreview(request.members)"
                                                :key="`request-member-${request.id}-${member.id || member.name}`"
                                                class="group-chat-approval-card__member-avatar"
                                            >
                                                <img :src="getMemberProfile(member)" :alt="member.name || 'User'">
                                            </span>
                                            <span
                                                v-if="request.members.length > 10"
                                                class="group-chat-approval-card__member-avatar group-chat-approval-card__member-avatar--more"
                                            >
                                                +{{ request.members.length - 10 }}
                                            </span>
                                        </span>
                                    </button>

                                    <transition name="fade">
                                        <div
                                            v-if="activeGroupRequestTooltipId === request.id"
                                            class="group-chat-approval-card__tooltip"
                                            @click.stop
                                        >
                                            <div class="group-chat-approval-card__tooltip-list">
                                                <span
                                                    v-for="member in request.members"
                                                    :key="`request-member-name-${request.id}-${member.id || member.name}`"
                                                    class="group-chat-approval-card__tooltip-chip"
                                                >
                                                    {{ member.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                                <div
                                    v-if="request.approval_status === 'rejected' && request.rejection_reason"
                                    class="group-chat-request-note"
                                >
                                    {{ request.rejection_reason }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-white-50">
                            No approved or rejected group chat requests yet.
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="conversationDeleteModalVisible"
                class="message-action-modal-backdrop"
                @click.self="closeConversationDeleteModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--danger">
                                <i class="fa-regular fa-trash-can"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Delete for you</div>
                            <h3 class="message-action-modal__title">Delete your copy of this chat?</h3>
                            <p class="message-action-modal__subtitle">
                                This clears the messages only for you. Other participants will still be able to see them.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="conversationDeleteSubmitting"
                            @click="closeConversationDeleteModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div class="message-action-modal__context">
                            <div class="message-action-modal__context-label">Conversation</div>
                            <div class="message-action-modal__preview">
                                {{ contactActionTarget?.name || 'This chat' }}
                            </div>
                        </div>

                        <p v-if="conversationDeleteError" class="message-action-modal__error">
                            {{ conversationDeleteError }}
                        </p>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="conversationDeleteSubmitting"
                            @click="closeConversationDeleteModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--danger"
                            :disabled="conversationDeleteSubmitting || !contactActionTarget"
                            @click="confirmDeleteConversationMessages"
                        >
                            <span v-if="conversationDeleteSubmitting" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span v-else>Delete for me</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showGroupChatModal"
                class="message-action-modal-backdrop"
                @click.self="closeGroupChatModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-people-group"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Create group chat</div>
                            <h3 class="message-action-modal__title">Start a shared conversation</h3>
                            <p class="message-action-modal__subtitle">
                                {{ isAdmin
                                    ? 'Admins can create a group chat immediately.'
                                    : 'Your request will be sent to admins for approval first.' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="groupChatSubmitting"
                            @click="closeGroupChatModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div class="mb-3">
                            <label class="form-label text-white-50 small">Group name</label>
                            <input
                                v-model="groupChatForm.name"
                                type="text"
                                class="message-action-modal__textarea"
                                style="min-height: 52px;"
                                maxlength="120"
                                placeholder="Enter group name"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white-50 small">Members</label>
                            <div class="mb-3">
                                <input
                                    v-model="groupChatUserSearch"
                                    type="text"
                                    class="message-action-modal__input"
                                    placeholder="Search user to add"
                                >
                            </div>
                            <div class="group-chat-member-list">
                                <label
                                    v-for="user in filteredGroupChatUsers"
                                    :key="user.id"
                                    class="group-chat-member-item"
                                >
                                    <input
                                        :checked="groupChatForm.member_ids.includes(user.id)"
                                        type="checkbox"
                                        @change="toggleGroupChatMember(user.id)"
                                    >
                                    <img :src="user.profile" :alt="user.name">
                                    <span>{{ user.name }}</span>
                                </label>
                            </div>
                            <div v-if="filteredGroupChatUsers.length === 0" class="text-white-50 small mt-2">
                                No users match your search.
                            </div>
                        </div>

                        <p v-if="groupChatError" class="message-action-modal__error">
                            {{ groupChatError }}
                        </p>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="groupChatSubmitting"
                            @click="closeGroupChatModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--primary"
                            :disabled="groupChatSubmitting || !canSubmitGroupChat"
                            @click="submitGroupChat"
                        >
                            <span v-if="groupChatSubmitting" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span v-else>Create</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showGroupInfoModal"
                class="message-action-modal-backdrop"
                @click.self="closeGroupInfoModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                {{ activeConversationIsGroup ? 'Group info' : 'Conversation info' }}
                            </div>
                            <h3 class="message-action-modal__title">Edit {{ activeUserName }}</h3>
                            <p class="message-action-modal__subtitle">
                                {{ activeConversationIsGroup
                                    ? 'Update the group name, photo, your nickname, and browse shared media.'
                                    : 'Set a nickname for this conversation and browse shared media.' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="groupInfoSubmitting"
                            @click="closeGroupInfoModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div
                        class="message-action-modal__body"
                        ref="conversationInfoBody"
                        @scroll.passive="handleConversationInfoScroll"
                    >
                        <div class="group-info-editor">
                            <div class="group-info-editor__avatar">
                                <img :src="groupInfoPhotoPreview || activeUserAvatar" :alt="activeUserName">
                                <button
                                    v-if="activeConversationIsGroup"
                                    type="button"
                                    class="message-action-modal__btn message-action-modal__btn--ghost"
                                    :disabled="groupInfoSubmitting"
                                    @click="$refs.groupInfoPhotoInput?.click()"
                                >
                                    Change photo
                                </button>
                                <input
                                    v-if="activeConversationIsGroup"
                                    ref="groupInfoPhotoInput"
                                    type="file"
                                    class="d-none"
                                    accept="image/*"
                                    @change="handleGroupInfoPhotoChange"
                                >
                            </div>

                            <div v-if="activeConversationIsGroup" class="mb-3">
                                <label class="form-label text-white-50 small">Group name</label>
                                <input
                                    v-model="groupInfoForm.name"
                                    type="text"
                                    class="message-action-modal__input"
                                    maxlength="120"
                                    placeholder="Enter group name"
                                >
                            </div>

                            <div v-else class="mb-3">
                                <label class="form-label text-white-50 small">Name</label>
                                <div class="message-action-modal__preview">
                                    {{ activeUser?.actual_name || activeUserName }}
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-white-50 small">
                                    {{ activeConversationIsGroup ? 'Your nickname' : 'Nickname' }}
                                </label>
                                <input
                                    v-model="groupInfoForm.nickname"
                                    type="text"
                                    class="message-action-modal__input"
                                    maxlength="120"
                                    :placeholder="activeConversationIsGroup
                                        ? 'Set your nickname in this group'
                                        : 'Set a nickname for this conversation'"
                                >
                            </div>
                        </div>

                        <div class="conversation-info-media">
                            <div class="conversation-info-media__header">
                                <div>
                                    <div class="message-action-modal__context-label">Shared media</div>
                                    <div class="text-white-50 small">Images and files from this conversation.</div>
                                </div>
                                <div
                                    v-if="conversationInfoMediaLoading && conversationInfoMediaItems.length"
                                    class="text-white-50 small"
                                >
                                    Loading...
                                </div>
                            </div>

                            <div class="conversation-info-media__tabs">
                                <button
                                    type="button"
                                    class="conversation-info-media__tab"
                                    :class="{ 'is-active': conversationInfoTab === 'media' }"
                                    @click="conversationInfoTab = 'media'"
                                >
                                    Media
                                </button>
                                <button
                                    type="button"
                                    class="conversation-info-media__tab"
                                    :class="{ 'is-active': conversationInfoTab === 'files' }"
                                    @click="conversationInfoTab = 'files'"
                                >
                                    Files
                                </button>
                            </div>

                            <div
                                v-if="conversationInfoTab === 'media' && conversationInfoImageItems.length"
                                class="conversation-info-media__section"
                            >
                                <div class="conversation-info-media__grid">
                                    <button
                                        v-for="item in conversationInfoImageItems"
                                        :key="`image-${item.message_id}`"
                                        type="button"
                                        class="conversation-info-media__image"
                                        @click="openImageGallery(item.attachment)"
                                    >
                                        <img :src="item.attachment.url" :alt="item.attachment.name || 'Image'">
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="conversationInfoTab === 'files' && conversationInfoFileItems.length"
                                class="conversation-info-media__section"
                            >
                                <button
                                    v-for="item in conversationInfoFileItems"
                                    :key="`file-${item.message_id}`"
                                    type="button"
                                    class="conversation-info-media__file"
                                    @click="downloadAttachment(item.attachment)"
                                >
                                    <span class="conversation-info-media__file-icon">
                                        <i class="fa-regular fa-file-lines"></i>
                                    </span>
                                    <span class="conversation-info-media__file-body">
                                        <span class="conversation-info-media__file-name">{{ item.attachment.name }}</span>
                                        <small class="text-white-50">
                                            {{ formatFileSize(item.attachment.size) }} · {{ formatTime(item.created_at) }}
                                        </small>
                                    </span>
                                </button>
                            </div>

                            <div
                                v-if="conversationInfoMediaLoaded && !activeConversationInfoItems.length && !conversationInfoMediaLoading"
                                class="text-white-50 small"
                            >
                                {{ conversationInfoTab === 'files' ? 'No shared files yet.' : 'No shared media yet.' }}
                            </div>

                            <div
                                v-if="conversationInfoMediaLoading && !conversationInfoMediaItems.length"
                                class="chat-loading chat-loading--inline"
                            >
                                <span class="loader-dot"></span>
                                <div class="fw-semibold">Loading shared media...</div>
                            </div>

                            <div
                                v-if="conversationInfoMediaLoaded && conversationInfoMediaHasMore && !conversationInfoMediaLoading"
                                class="conversation-info-media__hint"
                            >
                                Scroll to load more
                            </div>
                        </div>

                        <p v-if="groupInfoError" class="message-action-modal__error">
                            {{ groupInfoError }}
                        </p>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="groupInfoSubmitting"
                            @click="closeGroupInfoModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--primary"
                            :disabled="groupInfoSubmitting || !canSubmitGroupInfo"
                            @click="submitConversationInfo"
                        >
                            <span v-if="groupInfoSubmitting" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span v-else>Save changes</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showGroupMembersModal"
                class="message-action-modal-backdrop"
                @click.self="closeGroupMembersModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Group members</div>
                            <h3 class="message-action-modal__title">{{ activeUserName }}</h3>
                            <p class="message-action-modal__subtitle">
                                {{ activeGroupMembers.length }} members in this chat.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="closeGroupMembersModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div v-if="activeGroupMembers.length" class="group-chat-member-list">
                            <div
                                v-for="member in activeGroupMembers"
                                :key="member.id"
                                class="group-chat-member-item group-chat-member-item--static"
                            >
                                <img :src="getMemberProfile(member)" :alt="member.display_name || member.name">
                                <div class="group-chat-member-item__content">
                                    <div class="group-chat-member-item__headline">
                                        <span>{{ member.display_name || member.name }}</span>
                                        <small v-if="Number(member.id) === Number(authUser?.id || 0)" class="text-white-50">(You)</small>
                                    </div>
                                    <small v-if="member.nickname && member.nickname !== member.name" class="text-white-50">
                                        {{ member.name }}
                                    </small>
                                    <small v-if="member.added_by_name" class="text-white-50">Added by {{ member.added_by_name }}</small>
                                </div>
                                <div v-if="member.joined_at" class="group-chat-member-item__date">
                                    Added {{ formatGroupMemberDate(member.joined_at) }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-white-50">
                            No members found for this group.
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showInviteMembersModal"
                class="message-action-modal-backdrop"
                @click.self="closeInviteMembersModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Invite members</div>
                            <h3 class="message-action-modal__title">Add users to {{ activeUserName }}</h3>
                            <p class="message-action-modal__subtitle">
                                Invite more people into this group conversation.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="groupInviteSubmitting"
                            @click="closeInviteMembersModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div class="mb-3">
                            <input
                                v-model="groupInviteSearch"
                                type="text"
                                class="message-action-modal__input"
                                placeholder="Search user to invite"
                            >
                        </div>
                        <div class="group-chat-member-list">
                            <label
                                v-for="user in filteredInvitableUsers"
                                :key="user.id"
                                class="group-chat-member-item"
                            >
                                <input
                                    :checked="groupInviteMemberIds.includes(user.id)"
                                    type="checkbox"
                                    @change="toggleInviteMember(user.id)"
                                >
                                <img :src="user.profile" :alt="user.name">
                                <span>{{ user.name }}</span>
                            </label>
                        </div>
                        <div v-if="filteredInvitableUsers.length === 0" class="text-white-50 small mt-2">
                            No more users available to invite.
                        </div>
                        <p v-if="groupInviteError" class="message-action-modal__error">
                            {{ groupInviteError }}
                        </p>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="groupInviteSubmitting"
                            @click="closeInviteMembersModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--primary"
                            :disabled="groupInviteSubmitting || groupInviteMemberIds.length === 0"
                            @click="submitInviteMembers"
                        >
                            <span v-if="groupInviteSubmitting" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span v-else>Invite</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showLeaveGroupModal"
                class="message-action-modal-backdrop"
                @click.self="closeLeaveGroupModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--danger">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Leave group</div>
                            <h3 class="message-action-modal__title">Leave {{ activeUserName }}?</h3>
                            <p class="message-action-modal__subtitle">
                                You will stop receiving messages from this group unless someone invites you again.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="leaveGroupSubmitting"
                            @click="closeLeaveGroupModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div class="message-action-modal__context">
                            <div class="message-action-modal__context-label">Group</div>
                            <div class="message-action-modal__preview">
                                {{ activeUserName }}
                            </div>
                        </div>
                        <p v-if="leaveGroupError" class="message-action-modal__error">
                            {{ leaveGroupError }}
                        </p>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="leaveGroupSubmitting"
                            @click="closeLeaveGroupModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--danger"
                            :disabled="leaveGroupSubmitting"
                            @click="confirmLeaveActiveGroup"
                        >
                            <span v-if="leaveGroupSubmitting" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span v-else>Leave group</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showApprovalModal"
                class="message-action-modal-backdrop"
                @click.self="showApprovalModal = false"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div class="message-action-modal__badge message-action-modal__badge--edit">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Admin approvals</div>
                            <h3 class="message-action-modal__title">Pending group chat requests</h3>
                            <p class="message-action-modal__subtitle">
                                Review and approve employee-created group chats.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="showApprovalModal = false"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div v-if="pendingGroupChatApprovals.length" class="group-chat-approval-list">
                            <div
                                v-for="request in pendingGroupChatApprovals"
                                :key="request.id"
                                class="group-chat-approval-card"
                            >
                                <div class="group-chat-approval-card__title">{{ request.name }}</div>
                                <div class="group-chat-approval-card__meta">
                                    Requested by {{ request.creator?.name || 'User' }}
                                </div>
                                <div class="group-chat-approval-card__members">
                                    {{ request.members.map((member) => member.name).join(', ') }}
                                </div>
                                <div class="group-chat-approval-card__actions">
                                    <button
                                        type="button"
                                        class="message-action-modal__btn message-action-modal__btn--ghost"
                                        @click="rejectGroupChatRequest(request)"
                                    >
                                        Reject
                                    </button>
                                    <button
                                        type="button"
                                        class="message-action-modal__btn message-action-modal__btn--primary"
                                        @click="approveGroupChatRequest(request)"
                                    >
                                        Approve
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-white-50">
                            No pending group chat requests right now.
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="showReactionPicker && selectedMessage"
                class="reaction-modal-backdrop"
                @click.self="clearReactionPicker"
            >
                <div
                    class="reaction-modal"
                    role="dialog"
                    aria-modal="true"
                    tabindex="-1"
                    @keydown.esc.prevent="clearReactionPicker"
                >
                    <div class="reaction-modal__header">
                        <div>
                            <div class="reaction-modal__eyebrow">Emoji reaction</div>
                            <h3 class="reaction-modal__title">React to this message</h3>
                            <p class="reaction-modal__subtitle">
                                {{ reactionTargetPreview }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="reaction-modal__close"
                            @click="clearReactionPicker"
                            aria-label="Close reaction picker"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="reaction-modal__body">
                        <button
                            v-for="reaction in reactionOptions"
                            :key="reaction.key"
                            type="button"
                            class="reaction-modal__option"
                            :class="{ 'is-active': selectedMessage?.reaction === reaction.key }"
                            :title="reaction.label"
                            :aria-label="reaction.label"
                            @click="setReaction(selectedMessage, reaction.key)"
                        >
                            <span class="reaction-modal__emoji">{{ reaction.emoji }}</span>
                            <span class="reaction-modal__label">{{ reaction.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="messageActionModalVisible"
                class="message-action-modal-backdrop"
                @click.self="closeMessageActionModal"
            >
                <div
                    class="message-action-modal"
                    role="dialog"
                    aria-modal="true"
                    tabindex="-1"
                    @keydown.esc.prevent="closeMessageActionModal"
                >
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge"
                                :class="messageActionModalMode === 'edit'
                                    ? 'message-action-modal__badge--edit'
                                    : 'message-action-modal__badge--danger'"
                            >
                                <i :class="messageActionModalMode === 'edit'
                                    ? 'fa-regular fa-pen-to-square'
                                    : 'fa-regular fa-trash-can'"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                {{ messageActionModalMode === 'edit' ? 'Custom editor' : 'Confirmation' }}
                            </div>
                            <h3 class="message-action-modal__title">
                                {{ messageActionModalMode === 'edit' ? 'Edit this message' : 'Unsend this message?' }}
                            </h3>
                            <p class="message-action-modal__subtitle">
                                {{ messageActionModalMode === 'edit'
                                    ? 'Update the message text below and save when you are ready.'
                                    : 'This removes the message for everyone in this conversation.' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="messageActionModalSaving"
                            @click="closeMessageActionModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <template v-if="messageActionModalMode === 'edit'">
                            <textarea
                                id="edit-message-body"
                                ref="messageActionModalInput"
                                v-model="messageActionModalBody"
                                class="message-action-modal__textarea"
                                rows="6"
                                :disabled="messageActionModalSaving"
                                maxlength="2000"
                                @keydown.ctrl.enter.prevent="submitMessageActionModal"
                                @keydown.meta.enter.prevent="submitMessageActionModal"
                            ></textarea>
                            <div class="message-action-modal__meta">
                                <small class="message-action-modal__count">
                                    {{ messageActionModalBody.length }}/2000
                                </small>
                            </div>
                        </template>

                        <template v-else>
                            <div class="message-action-modal__context">
                                <div class="message-action-modal__context-label">Message to unsend</div>
                                <div class="message-action-modal__preview">
                                    {{ messageActionModalMessagePreview }}
                                </div>
                            </div>
                        </template>

                        <p v-if="messageActionModalError" class="message-action-modal__error">
                            {{ messageActionModalError }}
                        </p>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="messageActionModalSaving"
                            @click="closeMessageActionModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="message-action-modal__btn"
                            :class="messageActionModalMode === 'edit'
                                ? 'message-action-modal__btn--primary'
                                : 'message-action-modal__btn--danger'"
                            :disabled="messageActionModalSaving || messageActionModalSubmitDisabled"
                            @click="submitMessageActionModal"
                        >
                            <span v-if="messageActionModalSaving" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span v-else>
                                {{ messageActionModalMode === 'edit' ? 'Save changes' : 'Unsend message' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
        <transition name="fade">
            <div
                v-if="alertModalVisible"
                class="message-action-modal-backdrop"
                @click.self="closeAlertModal"
            >
                <div class="message-action-modal" role="dialog" aria-modal="true">
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge"
                                :class="alertModalType === 'success'
                                    ? 'message-action-modal__badge--edit'
                                    : 'message-action-modal__badge--danger'"
                            >
                                <i :class="alertModalType === 'success'
                                    ? 'fa-solid fa-check'
                                    : 'fa-solid fa-triangle-exclamation'"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">Notice</div>
                            <h3 class="message-action-modal__title">{{ alertModalTitle }}</h3>
                            <p class="message-action-modal__subtitle">{{ alertModalMessage }}</p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="closeAlertModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn"
                            :class="alertModalType === 'success'
                                ? 'message-action-modal__btn--primary'
                                : 'message-action-modal__btn--danger'"
                            @click="closeAlertModal"
                        >
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition-group name="toast" tag="div" class="messages-toast-stack">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="messages-toast"
                :class="`messages-toast--${notification.type}`"
                role="status"
                aria-live="polite"
            >
                <div class="messages-toast__icon">
                    <i :class="getNotificationIcon(notification.type)"></i>
                </div>
                <div class="messages-toast__content">
                    <div class="messages-toast__title">{{ notification.title }}</div>
                    <div class="messages-toast__message">{{ notification.message }}</div>
                </div>
                <button
                    type="button"
                    class="messages-toast__close"
                    aria-label="Dismiss notification"
                    @click="dismissNotification(notification.id)"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </transition-group>
        <div ref="imageGalleryContainer" class="d-none"></div>
    </div>
</template>

<script>
import axios from 'axios';
import ConversationWorkspace from './components/ConversationWorkspace.vue';
import MessagesSidebar from './components/MessagesSidebar.vue';

export default {
    name: 'MessagesPage',
    components: {
        ConversationWorkspace,
        MessagesSidebar,
    },
    props: {
        initialUsers: {
            type: Array,
            default: () => [],
        },
        initialAvailableUsers: {
            type: Array,
            default: () => [],
        },
        initialPendingGroupChatApprovals: {
            type: Array,
            default: () => [],
        },
        initialGroupChatRequestHistory: {
            type: Array,
            default: () => [],
        },
        authUser: {
            type: Object,
            required: true,
        },
        initialSelectedConversationKey: {
            type: String,
            default: null,
        },
        csrfToken: {
            type: String,
            default: '',
        },
    },
    data() {
        const selectedConversationKey = this.initialSelectedConversationKey || this.initialUsers?.[0]?.conversation_key || null;
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

            const fallbackSource = storedLastSeenAt;

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
            const isGroup = user.conversation_type === 'group';
            const activityState = isGroup
                ? this.getGroupActivityState(user)
                : getActivityState(user.id, user.latest_at);

            return {
                ...user,
                id: normalizeUserId(user.id),
                conversation_key: user.conversation_key || `direct:${normalizeUserId(user.id)}`,
                conversation_type: user.conversation_type || 'direct',
                member_ids: Array.isArray(user.member_ids) ? user.member_ids.map((id) => Number(id)) : [],
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
            availableUsers: this.initialAvailableUsers.map((user) => ({
                ...user,
                id: Number(user.id),
            })),
            pendingGroupChatApprovals: this.initialPendingGroupChatApprovals.map((request) => ({
                ...request,
                id: Number(request.id),
            })),
            groupChatRequestHistory: this.initialGroupChatRequestHistory.map((request) => ({
                ...request,
                id: Number(request.id),
            })),
            selectedConversationKey,
            searchQuery: '',
            messages: [],
            pinnedMessages: [],
            draftMessage: '',
            replyTargetMessage: null,
            selectedMessage: null,
            selectedMessageId: null,
            messageActionModalVisible: false,
            messageActionModalMode: 'edit',
            messageActionModalMessage: null,
            messageActionModalBody: '',
            messageActionModalError: '',
            messageActionModalSaving: false,
            alertModalVisible: false,
            alertModalType: 'error',
            alertModalTitle: '',
            alertModalMessage: '',
            selectedAttachment: null,
            selectedAttachmentPreviewUrl: null,
            selectedAttachmentPreviewType: 'file',
            attachmentAccept: '.jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt',
            attachmentMode: 'file',
            messageCharacterLimit: 2000,
            onlineUserIds: [],
            onlineUsersResolved: false,
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
            typingIndicatorSenderName: '',
            typingIndicatorTimer: null,
            typingStateActive: false,
            typingStopTimer: null,
            pendingImageAutoScrollCount: 0,
            showPinnedMessagesPanel: false,
            showComposerEmojiPicker: false,
            composerSelectionStart: 0,
            composerSelectionEnd: 0,
            showScrollToBottomButton: false,
            showMobileUsersPanel: false,
            mobileUsersPanelClosing: false,
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
                { key: 'number-one', emoji: '☝️', label: 'One DOST' },
                { key: 'love', emoji: '❤️', label: 'Love' },
                { key: 'haha', emoji: '😂', label: 'Haha' },
                { key: 'sad', emoji: '😢', label: 'Sad' },
                { key: 'angry', emoji: '😡', label: 'Angry' },
            ],
            presenceLifecycleBound: false,
            handlePresencePageHide: null,
            handlePresenceBeforeUnload: null,
            showGroupChatModal: false,
            groupChatSubmitting: false,
            groupChatError: '',
            groupChatForm: {
                name: '',
                member_ids: [],
            },
            groupChatUserSearch: '',
            showGroupInfoModal: false,
            groupInfoSubmitting: false,
            groupInfoError: '',
            groupInfoPhotoPreview: '',
            groupInfoForm: {
                name: '',
                nickname: '',
                photo: null,
            },
            conversationInfoMediaItems: [],
            conversationInfoMediaPage: 1,
            conversationInfoMediaHasMore: true,
            conversationInfoMediaLoading: false,
            conversationInfoMediaLoaded: false,
            conversationInfoTab: 'media',
            showGroupMembersModal: false,
            showInviteMembersModal: false,
            groupInviteSubmitting: false,
            groupInviteError: '',
            groupInviteSearch: '',
            groupInviteMemberIds: [],
            showBetaInfoModal: false,
            showGroupChatRequestsModal: false,
            activeGroupRequestTooltipId: null,
            showLeaveGroupModal: false,
            leaveGroupSubmitting: false,
            leaveGroupError: '',
            showApprovalModal: false,
            contactActionMenuKey: null,
            contactActionTarget: null,
            conversationDeleteModalVisible: false,
            conversationDeleteSubmitting: false,
            conversationDeleteError: '',
            now: new Date(),
            nowTimer: null,
            notifications: [],
            notificationIdCounter: 0,
            showInitialCacheLoader: false,
            didHydrateFromCache: false,
            cachePersistTimer: null,
            cacheVersion: 'v1',
        };
    },
        computed: {
        greetingLabel() {
            const hour = this.now.getHours();

            if (hour < 12) {
                return 'Good morning,';
            }

            if (hour < 18) {
                return 'Good afternoon,';
            }

            return 'Good evening,';
        },
        currentDateBadge() {
            return new Intl.DateTimeFormat('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            }).format(this.now);
        },
        currentTimeBadge() {
            return new Intl.DateTimeFormat('en-US', {
                hour: 'numeric',
                minute: '2-digit',
            }).format(this.now);
        },
        totalUnreadCount() {
            return this.users.reduce((total, user) => total + Number(user.unread_count || 0), 0);
        },
        showInitialPageSkeleton() {
            return this.loadingConversation && !this.didHydrateFromCache;
        },
        typingIndicatorLabel() {
            if (!this.activeConversationIsGroup) {
                return 'typing...';
            }

            const senderName = String(this.typingIndicatorSenderName || '').trim();
            return senderName ? `${senderName} is typing...` : 'Someone is typing...';
        },
        activeUser() {
            return this.users.find((user) => user.conversation_key === this.selectedConversationKey) || null;
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

            return this.getConversationStatusLabel(this.activeUser);
        },
        activeGroupMembers() {
            if (!this.activeConversationIsGroup) {
                return [];
            }

            const members = Array.isArray(this.activeUser?.members) ? this.activeUser.members : [];
            return members.map((member) => ({
                ...member,
                id: Number(member.id),
            }));
        },
        activeGroupSelfMember() {
            const authUserId = Number(this.authUser?.id || 0);
            return this.activeGroupMembers.find((member) => Number(member.id) === authUserId) || null;
        },
        replyTargetLabel() {
            if (!this.replyTargetMessage) {
                return 'you';
            }

            if (this.replyTargetMessage.is_mine) {
                return 'you';
            }

            if (this.activeConversationIsGroup) {
                return this.replyTargetMessage.sender_name || 'User';
            }

            return this.activeUserName;
        },
        activeConversationIsGroup() {
            return this.activeUser?.conversation_type === 'group';
        },
        isAdmin() {
            return Boolean(this.authUser?.is_admin);
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
        messageActionModalMessagePreview() {
            return this.messageActionModalMessage?.body
                || this.messageActionModalMessage?.attachment?.name
                || 'This message will be removed.';
        },
        messageActionModalSubmitDisabled() {
            if (this.messageActionModalMode !== 'edit') {
                return false;
            }

            const body = this.messageActionModalBody.trim();
            const original = this.messageActionModalMessage?.body || '';

            return !body || body === original.trim();
        },
        reactionTargetPreview() {
            const preview = this.getMessageSnippet(this.selectedMessage);

            if (!preview) {
                return 'Pick an emoji to react.';
            }

            return preview.length > 96 ? `${preview.slice(0, 93)}...` : preview;
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
        canSubmitGroupChat() {
            return this.groupChatForm.name.trim() !== '' && this.groupChatForm.member_ids.length >= 2;
        },
        canSubmitGroupInfo() {
            if (!this.activeUser) {
                return false;
            }

            if (this.activeConversationIsGroup) {
                return this.groupInfoForm.name.trim() !== '';
            }

            return true;
        },
        conversationInfoImageItems() {
            return this.conversationInfoMediaItems.filter((item) => item?.attachment?.type === 'image');
        },
        conversationInfoFileItems() {
            return this.conversationInfoMediaItems.filter((item) => item?.attachment?.type !== 'image');
        },
        activeConversationInfoItems() {
            return this.conversationInfoTab === 'files'
                ? this.conversationInfoFileItems
                : this.conversationInfoImageItems;
        },
        filteredInvitableUsers() {
            const search = this.groupInviteSearch.trim().toLowerCase();
            const memberIds = new Set(
                (Array.isArray(this.activeUser?.member_ids) ? this.activeUser.member_ids : [])
                    .map((id) => Number(id)),
            );

            return this.availableUsers.filter((user) => {
                if (memberIds.has(Number(user.id))) {
                    return false;
                }

                const haystack = `${user.name || ''} ${user.email || ''}`.toLowerCase();
                return !search || haystack.includes(search);
            });
        },
        filteredGroupChatUsers() {
            const search = this.groupChatUserSearch.trim().toLowerCase();

            return this.availableUsers.filter((user) => {
                if (!search) {
                    return true;
                }

                const haystack = `${user.name || ''} ${user.email || ''}`.toLowerCase();
                return haystack.includes(search);
            });
        },
    },
    mounted() {
        this.ensureAuthHeaders();
        const hydratedFromCache = this.hydrateMessagesCache();
        this.didHydrateFromCache = hydratedFromCache;
        this.showInitialCacheLoader = false;
        this.nowTimer = window.setInterval(() => {
            this.now = new Date();
        }, 60_000);
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
            this.loadConversation(this.activeUser, {
                page: 1,
                preserveVisibleState: hydratedFromCache,
            });
        } else {
            this.didHydrateFromCache = true;
        }

        this.initializeDirectMessageListener();
        window.addEventListener('click', this.handleGlobalClick);
    },
    beforeUnmount() {
        if (this.nowTimer) {
            window.clearInterval(this.nowTimer);
            this.nowTimer = null;
        }

        if (this.cachePersistTimer) {
            window.clearTimeout(this.cachePersistTimer);
            this.cachePersistTimer = null;
        }

        this.notifications.forEach((notification) => {
            if (notification.timeout) {
                window.clearTimeout(notification.timeout);
            }
        });
        this.notifications = [];

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

        window.removeEventListener('click', this.handleGlobalClick);
        this.destroyImageGallery();
    },
    methods: {
        handleGlobalClick(event) {
            this.contactActionMenuKey = null;

            if (!this.showComposerEmojiPicker) {
                return;
            }

            const overlay = this.$refs.composerEmojiOverlay;
            const button = this.$refs.composerEmojiButton;
            const target = event?.target || null;

            if (overlay?.contains?.(target) || button?.contains?.(target)) {
                return;
            }

            this.closeComposerEmojiPicker();
        },
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
        getMessagesCacheKey() {
            const authUserId = Number(this.authUser?.id || 0);
            return authUserId ? `employee-messages-cache:${this.cacheVersion}:${authUserId}` : '';
        },
        hydrateMessagesCache() {
            const cacheKey = this.getMessagesCacheKey();

            if (!cacheKey) {
                return false;
            }

            try {
                const raw = localStorage.getItem(cacheKey);
                if (!raw) {
                    return false;
                }

                const parsed = JSON.parse(raw);
                const cachedUsers = Array.isArray(parsed?.users) ? parsed.users : [];
                const cachedSelectedConversationKey = parsed?.selectedConversationKey || null;
                const cachedConversation = parsed?.activeConversation || null;

                if (cachedUsers.length) {
                    const userMap = new Map(cachedUsers.map((user) => [user.conversation_key, user]));
                    this.users = this.users.map((user) => {
                        const cachedUser = userMap.get(user.conversation_key);
                        return cachedUser ? { ...user, ...cachedUser } : user;
                    });
                }

                if (cachedSelectedConversationKey) {
                    const exists = this.users.some((user) => user.conversation_key === cachedSelectedConversationKey);
                    if (exists) {
                        this.selectedConversationKey = cachedSelectedConversationKey;
                    }
                }

                if (
                    cachedConversation
                    && cachedConversation.conversation_key === this.selectedConversationKey
                ) {
                    this.messages = Array.isArray(cachedConversation.messages)
                        ? cachedConversation.messages.map((message) => this.normalizeMessage(message))
                        : [];
                    this.pinnedMessages = Array.isArray(cachedConversation.pinnedMessages)
                        ? cachedConversation.pinnedMessages
                        : [];
                    this.conversationPage = Number(cachedConversation.conversationPage || 1);
                    this.conversationLastPage = Number(cachedConversation.conversationLastPage || 1);
                    this.conversationHasMore = Boolean(cachedConversation.conversationHasMore);
                }

                return Boolean(cachedUsers.length || (cachedConversation && this.messages.length));
            } catch (error) {
                return false;
            }
        },
        scheduleMessagesCachePersist() {
            if (this.cachePersistTimer) {
                window.clearTimeout(this.cachePersistTimer);
            }

            this.cachePersistTimer = window.setTimeout(() => {
                this.persistMessagesCache();
            }, 180);
        },
        persistMessagesCache() {
            const cacheKey = this.getMessagesCacheKey();

            if (!cacheKey) {
                return;
            }

            try {
                const activeConversation = this.activeUser?.conversation_key
                    ? {
                        conversation_key: this.activeUser.conversation_key,
                        messages: this.messages.slice(-40),
                        pinnedMessages: this.pinnedMessages.slice(0, 10),
                        conversationPage: this.conversationPage,
                        conversationLastPage: this.conversationLastPage,
                        conversationHasMore: this.conversationHasMore,
                    }
                    : null;

                localStorage.setItem(cacheKey, JSON.stringify({
                    savedAt: Date.now(),
                    selectedConversationKey: this.selectedConversationKey,
                    users: this.users.slice(0, 40).map((user) => ({
                        conversation_key: user.conversation_key,
                        preview: user.preview || '',
                        latest_at: user.latest_at || null,
                        unread_count: Number(user.unread_count || 0),
                        is_unread: Boolean(user.is_unread),
                        active_label: user.active_label || '',
                        is_active: Boolean(user.is_active),
                        last_seen_at: user.last_seen_at || null,
                    })),
                    activeConversation,
                }));
            } catch (error) {
                // Ignore cache write failures.
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
                        this.typingIndicatorSenderName = '';

                        if (this.typingIndicatorTimer) {
                            window.clearTimeout(this.typingIndicatorTimer);
                            this.typingIndicatorTimer = null;
                        }

                        return;
                    }

                    this.typingIndicator = true;
                    this.typingIndicatorSenderName = '';

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

                        if (this.isConversationNearBottom(bodyEl)) {
                            this.scrollConversationToBottom();
                            return;
                        }

                        this.updateScrollToBottomButton(bodyEl);
                    });
                })
                .listen('.group-chat.typing', (event) => {
                    const payload = event?.payload || {};
                    const activeGroupId = this.activeConversationIsGroup ? Number(this.activeUser?.id || 0) : 0;
                    const senderId = Number(payload.sender_id || 0);
                    const groupChatId = Number(payload.group_chat_id || 0);

                    if (
                        !payload.is_typing ||
                        !activeGroupId ||
                        groupChatId !== activeGroupId ||
                        senderId === Number(this.authUser?.id || 0)
                    ) {
                        this.typingIndicator = false;
                        this.typingIndicatorSenderName = '';

                        if (this.typingIndicatorTimer) {
                            window.clearTimeout(this.typingIndicatorTimer);
                            this.typingIndicatorTimer = null;
                        }

                        return;
                    }

                    this.typingIndicator = true;
                    this.typingIndicatorSenderName = String(payload.sender_name || '').trim();

                    if (this.typingIndicatorTimer) {
                        window.clearTimeout(this.typingIndicatorTimer);
                    }

                    this.typingIndicatorTimer = window.setTimeout(() => {
                        this.typingIndicator = false;
                        this.typingIndicatorSenderName = '';
                        this.typingIndicatorTimer = null;
                    }, 2500);

                    this.$nextTick(() => {
                        const bodyEl = this.$refs.conversationBody || this.$el.querySelector('.conversation-panel__body');
                        if (!bodyEl) {
                            return;
                        }

                        if (this.isConversationNearBottom(bodyEl)) {
                            this.scrollConversationToBottom();
                            return;
                        }

                        this.updateScrollToBottomButton(bodyEl);
                    });
                })
                .listen('.direct-message.seen', (event) => {
                    const payload = event?.payload || {};
                    const threadUserId = Number(payload.reader_id || payload.partner_id || 0);
                    const selectedUserId = this.activeConversationIsGroup ? 0 : Number(this.activeUser?.id || 0);

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
                })
                .listen('.group-chat.created', (event) => {
                    if (!event?.conversation) {
                        return;
                    }

                    this.upsertConversation({
                        ...event.conversation,
                        conversation_type: 'group',
                    });
                })
                .listen('.group-chat.message-sent', (event) => {
                    if (!event?.message || !event?.conversation) {
                        return;
                    }

                    this.handleIncomingGroupMessage(event.message, event.conversation);
                })
                .listen('.group-chat.message-updated', (event) => {
                    if (!event?.message || !event?.conversation) {
                        return;
                    }

                    this.handleIncomingGroupMessage(
                        event.message,
                        event.conversation,
                        event.pinned_messages || null,
                    );
                })
                .listen('.group-chat.updated', (event) => {
                    this.handleGroupConversationUpdated(event);
                })
                .listen('.group-chat.request-updated', (event) => {
                    if (!event?.request) {
                        return;
                    }

                    if (!this.isAdmin) {
                        if (['approved', 'rejected'].includes(event.action) && Number(event.request?.creator?.id || 0) === Number(this.authUser?.id || 0)) {
                            this.upsertGroupChatRequestHistory(event.request);
                        }

                        if (event.action === 'approved' && Number(event.request?.creator?.id || 0) === Number(this.authUser?.id || 0)) {
                            this.notify({
                                type: 'success',
                                title: 'Group chat approved',
                                message: `"${event.request?.name || 'Your group chat'}" is now available in your messages.`,
                                duration: 4200,
                            });
                        }

                        if (event.action === 'rejected' && Number(event.request?.creator?.id || 0) === Number(this.authUser?.id || 0)) {
                            this.notify({
                                type: 'error',
                                title: 'Group chat request declined',
                                message: `"${event.request?.name || 'Your group chat'}" was declined by an admin.`,
                                duration: 4200,
                            });
                        }

                        return;
                    }

                    this.handlePendingGroupChatRequestUpdate(event.action, event.request);
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
            const activeConversationKey = this.activeUser?.conversation_key || null;
            const targetConversationKey = `direct:${partnerId}`;
            const isActiveConversation = activeConversationKey === targetConversationKey;
            const targetUser = this.users.find((user) => user.conversation_key === targetConversationKey);
            const snippet = this.getMessageSnippet(message);
            const hasExistingMessage = this.messages.some(
                (item) => Number(item.id) === Number(message.id),
            );

            if (targetUser) {
                targetUser.latest_at = message.created_at || new Date().toISOString();
                targetUser.preview = snippet;
                targetUser.unread_count = senderId === authUserId
                    ? 0
                    : (isActiveConversation ? 0 : Number(targetUser.unread_count || 0) + 1);
                targetUser.is_unread = Number(targetUser.unread_count || 0) > 0;
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

                if (!hasExistingMessage) {
                    if (message?.attachment?.type === 'image') {
                        this.pendingImageAutoScrollCount += 1;
                    }
                    this.scrollConversationToBottom();
                }

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
        handleIncomingGroupMessage(message, conversation, pinnedMessages = null) {
            const conversationKey = conversation?.conversation_key || `group:${conversation?.id || message?.group_chat_id}`;
            const isActiveConversation = this.selectedConversationKey === conversationKey;
            const hasExistingMessage = this.messages.some((item) => Number(item.id) === Number(message.id));
            const authUserId = Number(this.authUser?.id || 0);
            const senderId = Number(message?.sender_id || 0);

            this.upsertConversation({
                ...conversation,
                conversation_type: 'group',
            });

            const targetConversation = this.users.find((user) => user.conversation_key === conversationKey);
            if (targetConversation) {
                targetConversation.latest_at = message?.created_at || new Date().toISOString();
                targetConversation.preview = this.getMessageSnippet(message);
                targetConversation.unread_count = senderId === authUserId
                    ? 0
                    : (isActiveConversation ? 0 : Number(targetConversation.unread_count || 0) + 1);
                targetConversation.is_unread = Number(targetConversation.unread_count || 0) > 0;
            }

            if (!isActiveConversation) {
                return;
            }

            this.upsertLocalMessage(message);

            if (message.is_unsent) {
                this.clearSelectedMessage();
            }

            if (Array.isArray(pinnedMessages)) {
                this.pinnedMessages = pinnedMessages;
                if (!this.pinnedMessages.length) {
                    this.showPinnedMessagesPanel = false;
                }
            }

            if (!hasExistingMessage) {
                if (message?.attachment?.type === 'image') {
                    this.pendingImageAutoScrollCount += 1;
                }
                this.scrollConversationToBottom();
            }

            if (senderId !== authUserId && this.isConversationPanelVisible()) {
                this.markGroupConversationSeen();
            }
        },
        handleGroupConversationUpdated(event) {
            const conversation = event?.conversation || null;
            const removedUserId = Number(event?.removed_user_id || 0);
            const authUserId = Number(this.authUser?.id || 0);

            if (removedUserId && removedUserId === authUserId) {
                const conversationKey = conversation?.conversation_key || `group:${conversation?.id || this.activeUser?.id || 0}`;
                this.removeConversation(conversationKey);
                return;
            }

            if (conversation?.conversation_key) {
                this.upsertConversation({
                    ...conversation,
                    conversation_type: 'group',
                });
            }
        },
        handlePendingGroupChatRequestUpdate(action, request) {
            const normalizedRequest = {
                ...request,
                id: Number(request.id),
            };
            const existingIndex = this.pendingGroupChatApprovals.findIndex((item) => Number(item.id) === Number(normalizedRequest.id));

            if (action === 'created') {
                if (existingIndex === -1) {
                    this.pendingGroupChatApprovals.unshift(normalizedRequest);
                    return;
                }

                this.pendingGroupChatApprovals.splice(existingIndex, 1, {
                    ...this.pendingGroupChatApprovals[existingIndex],
                    ...normalizedRequest,
                });
                return;
            }

            if (existingIndex >= 0) {
                this.pendingGroupChatApprovals.splice(existingIndex, 1);
            }
        },
        upsertGroupChatRequestHistory(request) {
            if (!request?.id) {
                return;
            }

            const normalizedRequest = {
                ...request,
                id: Number(request.id),
            };
            const existingIndex = this.groupChatRequestHistory.findIndex((item) => Number(item.id) === Number(normalizedRequest.id));

            if (existingIndex >= 0) {
                this.groupChatRequestHistory.splice(existingIndex, 1, {
                    ...this.groupChatRequestHistory[existingIndex],
                    ...normalizedRequest,
                });
            } else {
                this.groupChatRequestHistory.unshift(normalizedRequest);
            }

            this.groupChatRequestHistory = [...this.groupChatRequestHistory].sort((left, right) => {
                const leftAt = new Date(left.processed_at || left.created_at || 0).getTime();
                const rightAt = new Date(right.processed_at || right.created_at || 0).getTime();

                return rightAt - leftAt;
            });
        },
        formatRequestStatus(status) {
            const normalizedStatus = String(status || '').toLowerCase();

            if (normalizedStatus === 'approved') {
                return 'Approved';
            }

            if (normalizedStatus === 'rejected') {
                return 'Rejected';
            }

            return 'Pending';
        },
        openApprovedRequestConversation(request) {
            if (!request?.id) {
                return;
            }

            const targetConversationKey = `group:${Number(request.id)}`;
            const targetConversation = this.users.find((user) => user.conversation_key === targetConversationKey);

            if (!targetConversation) {
                this.notify({
                    type: 'error',
                    title: 'Conversation unavailable',
                    message: 'This approved group chat is not ready in your current conversation list yet.',
                    duration: 3600,
                });
                return;
            }

            this.showGroupChatRequestsModal = false;
            this.selectUser(targetConversation);
        },
        initializeOnlineUsers() {
            if (this.onlineUsersChannel || !window.Echo) {
                return;
            }

            this.hydrateOnlineUsersFromSharedState();

            this.onlineUsersHereListener = (users) => {
                this.onlineUserIds = users.map((user) => Number(user.id));
                this.onlineUsersResolved = true;
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
            this.onlineUsersResolved = true;
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
                this.onlineUsersResolved = true;
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
                if (user.conversation_type === 'group') {
                    const activityState = this.getGroupActivityState(user);

                    return {
                        ...user,
                        is_active: activityState.isActive,
                        active_label: activityState.label,
                        last_seen_at: activityState.lastSeenAt,
                    };
                }

                if (!this.onlineUsersResolved && user.is_active) {
                    return user;
                }

                const activityState = this.getActivityState(user.id, user.latest_at);

                return {
                    ...user,
                    is_active: activityState.isActive,
                    active_label: activityState.label,
                    last_seen_at: activityState.lastSeenAt,
                };
            });
        },
        getGroupActivityState(user) {
            const memberIds = Array.isArray(user?.member_ids) ? user.member_ids.map((id) => Number(id)) : [];
            const authUserId = Number(this.authUser?.id || 0);
            const otherMemberIds = memberIds.filter((id) => id && id !== authUserId);
            const onlineUserIds = Array.isArray(this.onlineUserIds) ? this.onlineUserIds : [];
            const onlineOthers = otherMemberIds.filter((id) => onlineUserIds.includes(id));
            const totalMembers = Number(user?.member_count || memberIds.length || 0);

            if (onlineOthers.length > 0) {
                return {
                    label: onlineOthers.length === 1 ? '1 member online' : `${onlineOthers.length} members online`,
                    isActive: true,
                    lastSeenAt: Date.now(),
                };
            }

            return {
                label: `${totalMembers} members`,
                isActive: false,
                lastSeenAt: null,
            };
        },
        isConversationOnline(user) {
            if (!user) {
                return false;
            }

            if (user.conversation_type === 'group') {
                return this.getGroupActivityState(user).isActive;
            }

            const onlineUserIds = Array.isArray(this.onlineUserIds) ? this.onlineUserIds : [];
            return onlineUserIds.includes(Number(user.id)) || Boolean(user.is_active);
        },
        getConversationStatusLabel(user) {
            if (!user) {
                return 'Offline';
            }

            if (user.conversation_type === 'group') {
                return this.getGroupActivityState(user).label;
            }

            if (this.isConversationOnline(user)) {
                return 'Online';
            }

            return this.getActivityState(user.id).label;
        },
        getMemberProfile(member) {
            if (member?.profile) {
                return member.profile;
            }

            const name = member?.name || 'User';
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true`;
        },
        getRequestMemberPreview(members = []) {
            return members.slice(0, 10);
        },
        toggleGroupRequestTooltip(requestId) {
            this.activeGroupRequestTooltipId = this.activeGroupRequestTooltipId === requestId ? null : requestId;
        },
        selectUser(user) {
            this.contactActionMenuKey = null;
            if (!user || user.conversation_key === this.selectedConversationKey) {
                this.closeMobileUsersPanel();
                return;
            }

            this.closeMobileUsersPanel();
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.selectedConversationKey = user.conversation_key;
            this.conversationError = '';
            this.resetConversationState();
            this.showPinnedMessagesPanel = false;
            this.showComposerEmojiPicker = false;
            this.clearSelectedMessage();
            this.clearReplyTarget();
            this.clearSelectedAttachment();
            this.scheduleMessagesCachePersist();
            this.loadConversation(user, { page: 1 });
        },
        resetConversationState() {
            this.messages = [];
            this.loadingConversation = false;
            this.loadingOlderConversation = false;
            this.conversationPage = 1;
            this.conversationLastPage = 1;
            this.conversationHasMore = true;
            this.showScrollToBottomButton = false;
            this.showGroupInfoModal = false;
            this.showGroupMembersModal = false;
            this.showInviteMembersModal = false;
            this.showLeaveGroupModal = false;
            this.resetConversationInfoMedia();
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
        toggleContactActionMenu(user) {
            const conversationKey = user?.conversation_key || null;

            if (!conversationKey) {
                return;
            }

            this.contactActionMenuKey = this.contactActionMenuKey === conversationKey
                ? null
                : conversationKey;
        },
        openConversationDeleteModal(user) {
            if (!user?.conversation_key) {
                return;
            }

            this.contactActionTarget = user;
            this.conversationDeleteError = '';
            this.conversationDeleteModalVisible = true;
            this.contactActionMenuKey = null;
        },
        closeConversationDeleteModal() {
            if (this.conversationDeleteSubmitting) {
                return;
            }

            this.conversationDeleteModalVisible = false;
            this.conversationDeleteError = '';
            this.contactActionTarget = null;
        },
        applyConversationClear(targetConversation, conversationPreview = null) {
            if (!targetConversation?.conversation_key) {
                return;
            }

            const preview = conversationPreview?.preview
                || (targetConversation.conversation_type === 'group'
                    ? 'Group chat is ready'
                    : 'Start a conversation');
            const latestAt = Object.prototype.hasOwnProperty.call(conversationPreview || {}, 'latest_at')
                ? conversationPreview.latest_at
                : null;

            const targetUser = this.users.find((item) => item.conversation_key === targetConversation.conversation_key);

            if (targetUser) {
                targetUser.preview = preview;
                targetUser.latest_at = latestAt;
                targetUser.unread_count = 0;
                targetUser.is_unread = false;
            }

            if (this.selectedConversationKey === targetConversation.conversation_key) {
                this.messages = [];
                this.pinnedMessages = [];
                this.typingIndicator = false;
                this.typingIndicatorSenderName = '';
                this.clearSelectedMessage();
                this.clearReplyTarget();
                this.showPinnedMessagesPanel = false;
                this.showComposerEmojiPicker = false;
            }

            this.scheduleMessagesCachePersist();
        },
        async confirmDeleteConversationMessages() {
            if (!this.contactActionTarget?.id || this.conversationDeleteSubmitting) {
                return;
            }

            this.conversationDeleteSubmitting = true;
            this.conversationDeleteError = '';

            try {
                const target = this.contactActionTarget;
                const endpoint = target.conversation_type === 'group'
                    ? `/api/group-chats/${target.id}/messages`
                    : `/api/direct-messages/conversation/${target.id}`;
                const { data } = await axios.delete(endpoint, {
                    headers: this.buildAuthHeaders(),
                });

                this.applyConversationClear(target, data?.conversation_preview || null);
                this.conversationDeleteSubmitting = false;
                this.closeConversationDeleteModal();
            } catch (error) {
                this.conversationDeleteError = error?.response?.data?.message || 'Unable to delete your copy of this conversation.';
                this.conversationDeleteSubmitting = false;
            }
        },
        async loadConversation(conversationOrId, { page = 1, preserveScroll = false, preserveVisibleState = false } = {}) {
            this.ensureAuthHeaders();

            const user = typeof conversationOrId === 'object'
                ? conversationOrId
                : this.users.find((item) => Number(item.id) === Number(conversationOrId));
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
                if (!preserveVisibleState) {
                    this.messages = [];
                }
                this.showPinnedMessagesPanel = false;
                this.showComposerEmojiPicker = false;
                this.showScrollToBottomButton = false;
                this.conversationPage = 1;
                this.conversationLastPage = 1;
                this.conversationHasMore = true;
                if (!preserveVisibleState) {
                    this.pinnedMessages = [];
                }
            }
            this.clearSelectedMessage();

            try {
                const url = user.conversation_type === 'group'
                    ? `/api/group-chats/${user.id}`
                    : `/api/direct-messages/${user.id}`;
                const { data } = await axios.get(url, {
                    params: {
                        page,
                        per_page: 20,
                    },
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (this.selectedConversationKey !== user.conversation_key) {
                    return;
                }

                const messages = Array.isArray(data.messages)
                    ? data.messages.map((message) => this.normalizeMessage(message))
                    : [];
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
                user.is_unread = false;
                user.preview = this.messages.length > 0
                    ? this.getMessageSnippet(this.messages[this.messages.length - 1] || null)
                    : (user.conversation_type === 'group' ? 'Group chat is ready' : 'Start a conversation');
                if (user.conversation_type === 'group') {
                    user.members = Array.isArray(data?.conversation?.members) ? data.conversation.members : [];
                    const activityState = this.getGroupActivityState({
                        ...user,
                        member_count: Number(user.member_count || data?.conversation?.member_count || 0),
                        member_ids: Array.isArray(data?.conversation?.member_ids) ? data.conversation.member_ids : user.member_ids,
                    });
                    user.member_count = Number(user.member_count || data?.conversation?.member_count || 0);
                    user.member_ids = Array.isArray(data?.conversation?.member_ids)
                        ? data.conversation.member_ids.map((id) => Number(id))
                        : (Array.isArray(user.member_ids) ? user.member_ids : []);
                    if (data?.conversation?.conversation_key) {
                        this.upsertConversation({
                            ...data.conversation,
                            conversation_type: 'group',
                        });
                    }
                    user.active_label = activityState.label;
                    user.is_active = activityState.isActive;
                    user.last_seen_at = activityState.lastSeenAt;
                    await this.markGroupConversationSeen();
                } else {
                    if (this.onlineUsersResolved || !user.is_active) {
                        const activityState = this.getActivityState(user.id);
                        user.active_label = activityState.label;
                        user.is_active = activityState.isActive;
                        user.last_seen_at = activityState.lastSeenAt;
                    }

                    await axios.post(
                        `/api/direct-messages/${user.id}/seen`,
                        {},
                        {
                            headers: {
                            Accept: 'application/json',
                            },
                        }
                    );
                }

                this.showInitialCacheLoader = false;
                this.scheduleMessagesCachePersist();

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
                this.showInitialCacheLoader = false;
            } finally {
                this.loadingConversation = false;
                this.loadingOlderConversation = false;
            }
        },
        handleComposerInput() {
            this.captureComposerSelection();
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
            this.captureComposerSelection();
            this.sendTypingState(false);
        },
        captureComposerSelection() {
            const textarea = this.$refs.composerInput;
            if (!textarea) {
                return;
            }

            const nextStart = Number.isFinite(textarea.selectionStart)
                ? textarea.selectionStart
                : this.draftMessage.length;
            const nextEnd = Number.isFinite(textarea.selectionEnd)
                ? textarea.selectionEnd
                : nextStart;

            this.composerSelectionStart = nextStart;
            this.composerSelectionEnd = nextEnd;
        },
        openGroupMembersModal() {
            if (!this.activeConversationIsGroup) {
                return;
            }

            this.showGroupMembersModal = true;
        },
        openConversationInfoModal() {
            if (!this.activeUser) {
                return;
            }

            this.groupInfoError = '';
            this.groupInfoPhotoPreview = '';
            this.groupInfoForm = {
                name: this.activeConversationIsGroup
                    ? (this.activeUser.name || '')
                    : (this.activeUser.actual_name || this.activeUser.name || ''),
                nickname: this.activeConversationIsGroup
                    ? (this.activeGroupSelfMember?.nickname || '')
                    : (this.activeUser.nickname || ''),
                photo: null,
            };
            this.showGroupInfoModal = true;
            this.resetConversationInfoMedia();
            this.loadConversationInfoMedia({ page: 1 });
        },
        closeGroupInfoModal() {
            if (this.groupInfoSubmitting) {
                return;
            }

            this.showGroupInfoModal = false;
            this.groupInfoError = '';
            this.clearGroupInfoPhotoSelection();
            this.resetConversationInfoMedia();
        },
        resetConversationInfoMedia() {
            this.conversationInfoMediaItems = [];
            this.conversationInfoMediaPage = 1;
            this.conversationInfoMediaHasMore = true;
            this.conversationInfoMediaLoading = false;
            this.conversationInfoMediaLoaded = false;
            this.conversationInfoTab = 'media';
        },
        handleConversationInfoScroll(event) {
            const body = event?.target;
            if (!body || this.conversationInfoMediaLoading || !this.conversationInfoMediaHasMore) {
                return;
            }

            const distanceFromBottom = body.scrollHeight - body.scrollTop - body.clientHeight;
            if (distanceFromBottom > 140) {
                return;
            }

            this.loadConversationInfoMedia({
                page: this.conversationInfoMediaPage + 1,
                append: true,
            });
        },
        async loadConversationInfoMedia({ page = 1, append = false } = {}) {
            if (!this.activeUser || this.conversationInfoMediaLoading) {
                return;
            }

            this.conversationInfoMediaLoading = true;

            try {
                const endpoint = this.activeConversationIsGroup
                    ? `/api/group-chats/${this.activeUser.id}/media`
                    : `/api/direct-messages/${this.activeUser.id}/media`;
                const { data } = await axios.get(endpoint, {
                    params: {
                        page,
                        per_page: 24,
                    },
                    headers: this.buildAuthHeaders(),
                });
                const items = Array.isArray(data?.items)
                    ? data.items.filter((item) => item?.attachment?.url)
                    : [];
                const existingIds = new Set(this.conversationInfoMediaItems.map((item) => Number(item.message_id)));
                const normalizedItems = items
                    .map((item) => ({
                        ...item,
                        message_id: Number(item.message_id),
                    }))
                    .filter((item) => !append || !existingIds.has(Number(item.message_id)));

                this.conversationInfoMediaItems = append
                    ? [...this.conversationInfoMediaItems, ...normalizedItems]
                    : normalizedItems;
                this.conversationInfoMediaPage = Number(data?.pagination?.current_page || page);
                this.conversationInfoMediaHasMore = Boolean(data?.pagination?.has_more);
            } catch (error) {
                console.error('Failed to load shared media:', error);
                this.conversationInfoMediaHasMore = false;
            } finally {
                this.conversationInfoMediaLoading = false;
                this.conversationInfoMediaLoaded = true;
            }
        },
        closeGroupMembersModal() {
            this.showGroupMembersModal = false;
        },
        handleGroupInfoPhotoChange(event) {
            const file = event?.target?.files?.[0] ?? null;

            if (!file) {
                this.clearGroupInfoPhotoSelection(false);
                return;
            }

            if (!file.type?.startsWith('image/')) {
                this.groupInfoError = 'Please choose an image file for the group photo.';
                this.clearGroupInfoPhotoSelection();
                return;
            }

            this.groupInfoError = '';
            this.clearGroupInfoPhotoSelection(false);
            this.groupInfoForm.photo = file;
            this.groupInfoPhotoPreview = URL.createObjectURL(file);
        },
        clearGroupInfoPhotoSelection(resetInput = true) {
            if (this.groupInfoPhotoPreview) {
                URL.revokeObjectURL(this.groupInfoPhotoPreview);
                this.groupInfoPhotoPreview = '';
            }

            this.groupInfoForm.photo = null;

            if (resetInput) {
                const input = this.$refs.groupInfoPhotoInput;
                if (input) {
                    input.value = '';
                }
            }
        },
        async submitConversationInfo() {
            if (!this.activeUser?.id || !this.canSubmitGroupInfo || this.groupInfoSubmitting) {
                return;
            }

            this.groupInfoSubmitting = true;
            this.groupInfoError = '';

            try {
                let data = null;

                if (this.activeConversationIsGroup) {
                    const formData = new FormData();
                    formData.append('name', this.groupInfoForm.name.trim());

                    if (this.groupInfoForm.nickname.trim()) {
                        formData.append('nickname', this.groupInfoForm.nickname.trim());
                    }

                    if (this.groupInfoForm.photo) {
                        formData.append('photo', this.groupInfoForm.photo);
                    }

                    ({ data } = await axios.post(
                        `/api/group-chats/${this.activeUser.id}/settings`,
                        formData,
                        {
                            headers: this.buildAuthHeaders(),
                        },
                    ));
                } else {
                    ({ data } = await axios.post(
                        `/api/direct-messages/${this.activeUser.id}/info`,
                        {
                            nickname: this.groupInfoForm.nickname.trim() || null,
                        },
                        {
                            headers: this.buildAuthHeaders(),
                        },
                    ));
                }

                const selectedConversation = this.activeUser;

                if (data?.conversation) {
                    this.upsertConversation(this.activeConversationIsGroup
                        ? {
                            ...data.conversation,
                            members: Array.isArray(data?.members) ? data.members : data.conversation.members,
                        }
                        : data.conversation);
                }

                this.groupInfoSubmitting = false;
                this.closeGroupInfoModal();

                if (selectedConversation && this.activeConversationIsGroup) {
                    await this.loadConversation(
                        {
                            ...selectedConversation,
                            ...(data?.conversation || {}),
                            members: Array.isArray(data?.members) ? data.members : selectedConversation.members,
                        },
                        { page: 1 },
                    );
                }
            } catch (error) {
                this.groupInfoError = error?.response?.data?.message || 'Unable to update conversation info.';
            } finally {
                this.groupInfoSubmitting = false;
            }
        },
        openInviteMembersModal() {
            if (!this.activeConversationIsGroup) {
                return;
            }

            this.groupInviteError = '';
            this.groupInviteSearch = '';
            this.groupInviteMemberIds = [];
            this.showInviteMembersModal = true;
        },
        closeInviteMembersModal() {
            if (this.groupInviteSubmitting) {
                return;
            }

            this.showInviteMembersModal = false;
            this.groupInviteError = '';
            this.groupInviteSearch = '';
            this.groupInviteMemberIds = [];
        },
        openLeaveGroupModal() {
            if (!this.activeConversationIsGroup) {
                return;
            }

            this.leaveGroupError = '';
            this.showLeaveGroupModal = true;
        },
        closeLeaveGroupModal() {
            if (this.leaveGroupSubmitting) {
                return;
            }

            this.showLeaveGroupModal = false;
            this.leaveGroupError = '';
        },
        toggleInviteMember(userId) {
            const normalizedId = Number(userId);

            if (this.groupInviteMemberIds.includes(normalizedId)) {
                this.groupInviteMemberIds = this.groupInviteMemberIds.filter((id) => id !== normalizedId);
                return;
            }

            this.groupInviteMemberIds = [...this.groupInviteMemberIds, normalizedId];
        },
        async submitInviteMembers() {
            if (!this.activeConversationIsGroup || !this.activeUser?.id || this.groupInviteMemberIds.length === 0 || this.groupInviteSubmitting) {
                return;
            }

            this.groupInviteSubmitting = true;
            this.groupInviteError = '';

            try {
                const { data } = await axios.post(
                    `/api/group-chats/${this.activeUser.id}/members`,
                    {
                        member_ids: this.groupInviteMemberIds,
                    },
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                if (data?.conversation) {
                    this.upsertConversation(data.conversation);
                }

                this.groupInviteSubmitting = false;
                this.closeInviteMembersModal();
            } catch (error) {
                this.groupInviteError = error?.response?.data?.message || 'Unable to invite members.';
            } finally {
                this.groupInviteSubmitting = false;
            }
        },
        async leaveActiveGroup() {
            this.openLeaveGroupModal();
        },
        async confirmLeaveActiveGroup() {
            if (!this.activeConversationIsGroup || !this.activeUser?.id) {
                return;
            }

            this.leaveGroupSubmitting = true;
            this.leaveGroupError = '';

            try {
                const { data } = await axios.post(
                    `/api/group-chats/${this.activeUser.id}/leave`,
                    {},
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                this.closeGroupMembersModal();
                this.closeInviteMembersModal();
                this.closeLeaveGroupModal();
                this.removeConversation(data?.conversation_key || this.activeUser.conversation_key);
            } catch (error) {
                this.leaveGroupError = error?.response?.data?.message || 'Unable to leave group chat.';
            } finally {
                this.leaveGroupSubmitting = false;
            }
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

            this.typingIndicator = false;
            this.typingIndicatorSenderName = '';
            this.typingStateActive = false;
        },
        sendTypingState(isTyping) {
            const activeUser = this.activeUser;
            if (!activeUser) {
                return;
            }

            const endpoint = this.activeConversationIsGroup
                ? `/api/group-chats/${activeUser.id}/typing`
                : `/api/direct-messages/${activeUser.id}/typing`;

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
                    endpoint,
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
                endpoint,
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
                let data = null;

                if (this.activeConversationIsGroup) {
                    const formData = new FormData();

                    if (body) {
                        formData.append('body', body);
                    }

                    if (this.replyTargetMessage?.id) {
                        formData.append('reply_to_id', this.replyTargetMessage.id);
                    }

                    if (this.selectedAttachment) {
                        formData.append('attachment', this.selectedAttachment);
                    }

                    ({ data } = await axios.post(
                        `/api/group-chats/${this.activeUser.id}/messages`,
                        formData,
                        {
                            headers: this.buildAuthHeaders(),
                        },
                    ));
                } else {
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

                    ({ data } = await axios.post('/api/direct-messages', formData, {
                        headers: this.buildAuthHeaders(),
                    }));
                }

                const message = data?.message;
                this.draftMessage = '';
                this.composerSelectionStart = 0;
                this.composerSelectionEnd = 0;

                if (message) {
                    this.upsertLocalMessage({
                        ...message,
                        is_mine: true,
                        read_at: null,
                    });
                    if (message?.attachment?.type === 'image') {
                        this.pendingImageAutoScrollCount += 1;
                    }
                    this.playSendSound();
                    this.updateContactPreview(message, true);
                    this.clearReplyTarget();
                    this.clearSelectedAttachment();
                    this.clearSelectedMessage();
                    this.showPinnedMessagesPanel = false;
                    this.showComposerEmojiPicker = false;
                    this.$nextTick(() => {
                        const textarea = this.$refs.composerInput;
                        if (textarea) {
                            textarea.focus();
                            textarea.value = this.draftMessage;
                            textarea.setSelectionRange(0, 0);
                        }
                        this.resizeComposer();
                        this.scrollConversationToBottom();
                    });
                    if (data?.conversation) {
                        this.upsertConversation(data.conversation);
                    }
                } else {
                    await this.loadConversation(this.activeUser, { page: 1 });
                    this.$nextTick(() => {
                        const textarea = this.$refs.composerInput;
                        if (textarea) {
                            textarea.focus();
                            textarea.value = this.draftMessage;
                            textarea.setSelectionRange(0, 0);
                        }
                        this.resizeComposer();
                    });
                }
            } catch (error) {
                const message = error?.response?.data?.message || 'Unable to send message.';
                this.notify({
                    type: 'error',
                    title: 'Message not sent',
                    message,
                });
            } finally {
                this.sendingMessage = false;
                this.$nextTick(() => this.resizeComposer());
            }
        },
        handleAttachmentImageLoad() {
            if (!this.activeUser) {
                return;
            }

            if (this.pendingImageAutoScrollCount > 0) {
                this.pendingImageAutoScrollCount -= 1;
                this.$nextTick(() => {
                    this.scrollConversationToBottom();
                    window.requestAnimationFrame(() => {
                        this.scrollConversationToBottom();
                    });
                });
                return;
            }

            if (!this.showScrollToBottomButton) {
                this.$nextTick(() => {
                    this.scrollConversationToBottom();
                });
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
            if (!message?.id || !message.is_mine || message.is_unsent || message.is_system) {
                return;
            }

            const currentBody = message.body || '';
            if (!currentBody.trim()) {
                return;
            }

            this.openMessageActionModal(message, 'edit');
        },
        async unsendMessage(message) {
            if (!message?.id || !message.is_mine || message.is_unsent || message.is_system) {
                return;
            }

            this.openMessageActionModal(message, 'delete');
        },
        openMessageActionModal(message, mode = 'edit') {
            this.activeMessageActionsId = null;
            this.clearReactionPicker();
            this.messageActionModalMode = mode === 'delete' ? 'delete' : 'edit';
            this.messageActionModalMessage = message || null;
            this.messageActionModalBody = message?.body || '';
            this.messageActionModalError = '';
            this.messageActionModalSaving = false;
            this.messageActionModalVisible = true;

            this.$nextTick(() => {
                const input = this.$refs.messageActionModalInput;

                if (this.messageActionModalMode === 'edit' && input?.focus) {
                    input.focus();

                    if (
                        this.messageActionModalMode === 'edit'
                        && typeof input.setSelectionRange === 'function'
                    ) {
                        const length = input.value?.length || 0;
                        input.setSelectionRange(length, length);
                    }
                }
            });
        },
        closeMessageActionModal(force = false) {
            if (this.messageActionModalSaving && !force) {
                return;
            }

            this.messageActionModalVisible = false;
            this.messageActionModalMode = 'edit';
            this.messageActionModalMessage = null;
            this.messageActionModalBody = '';
            this.messageActionModalError = '';
            this.messageActionModalSaving = false;
        },
        openAlertModal({ type = 'error', title = 'Notice', message = '' } = {}) {
            this.alertModalType = type === 'success' ? 'success' : 'error';
            this.alertModalTitle = title || 'Notice';
            this.alertModalMessage = message || '';
            this.alertModalVisible = true;
        },
        closeAlertModal() {
            this.alertModalVisible = false;
            this.alertModalType = 'error';
            this.alertModalTitle = '';
            this.alertModalMessage = '';
        },
        notify({ type = 'info', title = 'Notice', message = '', duration = 3600 } = {}) {
            const id = `toast-${Date.now()}-${this.notificationIdCounter += 1}`;
            const timeout = window.setTimeout(() => {
                this.dismissNotification(id);
            }, duration);

            this.notifications.push({
                id,
                type: ['success', 'error', 'info'].includes(type) ? type : 'info',
                title,
                message,
                timeout,
            });
        },
        dismissNotification(notificationId) {
            const index = this.notifications.findIndex((item) => item.id === notificationId);

            if (index === -1) {
                return;
            }

            const notification = this.notifications[index];

            if (notification.timeout) {
                window.clearTimeout(notification.timeout);
            }

            this.notifications.splice(index, 1);
        },
        getNotificationIcon(type) {
            if (type === 'success') {
                return 'fa-solid fa-check';
            }

            if (type === 'error') {
                return 'fa-solid fa-triangle-exclamation';
            }

            return 'fa-solid fa-circle-info';
        },
        async submitMessageActionModal() {
            if (!this.messageActionModalMessage?.id || this.messageActionModalSaving) {
                return;
            }

            if (this.messageActionModalMode === 'edit') {
                const trimmedBody = this.messageActionModalBody.trim();
                const originalBody = (this.messageActionModalMessage.body || '').trim();

                if (!trimmedBody || trimmedBody === originalBody) {
                    return;
                }

                this.messageActionModalSaving = true;
                this.messageActionModalError = '';

                try {
                    const endpoint = this.activeConversationIsGroup
                        ? `/api/group-messages/${this.messageActionModalMessage.id}`
                        : `/api/direct-messages/${this.messageActionModalMessage.id}`;
                    const { data } = await axios.patch(
                        endpoint,
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

                    this.closeMessageActionModal(true);
                } catch (error) {
                    this.messageActionModalError = error?.response?.data?.message || 'Unable to edit message.';
                } finally {
                    this.messageActionModalSaving = false;
                }

                return;
            }

            this.messageActionModalSaving = true;
            this.messageActionModalError = '';

            try {
                const endpoint = this.activeConversationIsGroup
                    ? `/api/group-messages/${this.messageActionModalMessage.id}`
                    : `/api/direct-messages/${this.messageActionModalMessage.id}`;
                const { data } = await axios.delete(endpoint, {
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
                    await this.loadConversation(this.activeUser, { page: 1 });
                }

                if (Array.isArray(data?.pinned_messages)) {
                    this.pinnedMessages = data.pinned_messages;
                }

                this.closeMessageActionModal(true);
            } catch (error) {
                this.messageActionModalError = error?.response?.data?.message || 'Unable to unsend message.';
            } finally {
                this.messageActionModalSaving = false;
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
            const current = textarea?.value ?? this.draftMessage ?? '';

            if (!textarea) {
                this.draftMessage = `${current}${emoji}`;
                this.showComposerEmojiPicker = false;
                return;
            }

            const start = Number.isFinite(this.composerSelectionStart)
                ? this.composerSelectionStart
                : current.length;
            const end = Number.isFinite(this.composerSelectionEnd)
                ? this.composerSelectionEnd
                : start;

            const nextMessage = `${current.slice(0, start)}${emoji}${current.slice(end)}`;
            this.draftMessage = nextMessage;
            textarea.value = nextMessage;
            this.showComposerEmojiPicker = false;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));

            this.$nextTick(() => {
                textarea.focus();
                const caret = start + emoji.length;
                textarea.setSelectionRange(caret, caret);
                this.composerSelectionStart = caret;
                this.composerSelectionEnd = caret;
                this.resizeComposer();
            });
        },
        startReply(message) {
            if (!message || message.is_unsent || message.is_system) return;

            this.replyTargetMessage = message;
            this.clearReactionPicker();
            this.activeMessageActionsId = null;
            this.$nextTick(() => {
                const textarea = this.$refs.composerInput;
                if (!textarea) {
                    return;
                }

                textarea.focus();
                const caret = this.draftMessage.length;
                if (typeof textarea.setSelectionRange === 'function') {
                    textarea.setSelectionRange(caret, caret);
                }
                this.resizeComposer();
            });
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
                this.notify({
                    type: 'error',
                    title: 'Image required',
                    message: 'Please choose an image file for this action.',
                    duration: 3200,
                });
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
            if (!message?.id || message.is_unsent || message.is_system) {
                return;
            }

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.clearReactionPicker();
            this.activeMessageActionsId = this.activeMessageActionsId === message.id ? null : message.id;
        },
        toggleReactionPicker(message) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.reactionTargetId = message.id;
            this.activeMessageActionsId = null;
            this.showReactionPicker = this.showReactionPicker && this.reactionTargetId === message.id
                ? !this.showReactionPicker
                : true;
        },
        async togglePinMessage(message) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.clearReactionPicker();
            this.activeMessageActionsId = null;

            try {
                const endpoint = this.activeConversationIsGroup
                    ? `/api/group-messages/${message.id}/pin`
                    : `/api/direct-messages/${message.id}/pin`;
                const { data } = await axios.patch(
                    endpoint,
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
        normalizeMessage(message) {
            if (!message) {
                return message;
            }

            const authUserId = Number(this.authUser?.id || 0);
            const senderId = Number(message.sender_id || 0);

            return {
                ...message,
                id: Number(message.id),
                sender_id: senderId || null,
                recipient_id: message.recipient_id != null ? Number(message.recipient_id) : null,
                group_chat_id: message.group_chat_id != null ? Number(message.group_chat_id) : null,
                is_mine: senderId === authUserId,
                is_system: Boolean(message.is_system || message.message_type === 'system'),
                is_unsent: Boolean(message.is_unsent),
            };
        },
        openGroupChatModal() {
            this.groupChatError = '';
            this.groupChatUserSearch = '';
            this.groupChatForm = {
                name: '',
                member_ids: [],
            };
            this.showGroupChatModal = true;
        },
        closeGroupChatModal() {
            if (this.groupChatSubmitting) {
                return;
            }

            this.showGroupChatModal = false;
            this.groupChatError = '';
            this.groupChatUserSearch = '';
        },
        toggleGroupChatMember(userId) {
            const normalizedId = Number(userId);

            if (this.groupChatForm.member_ids.includes(normalizedId)) {
                this.groupChatForm.member_ids = this.groupChatForm.member_ids.filter((id) => id !== normalizedId);
                return;
            }

            this.groupChatForm.member_ids = [...this.groupChatForm.member_ids, normalizedId];
        },
        async submitGroupChat() {
            if (!this.canSubmitGroupChat || this.groupChatSubmitting) {
                return;
            }

            this.groupChatSubmitting = true;
            this.groupChatError = '';

            try {
                const { data } = await axios.post(
                    '/api/group-chats',
                    {
                        name: this.groupChatForm.name.trim(),
                        member_ids: this.groupChatForm.member_ids,
                    },
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                if (data?.conversation) {
                    this.upsertConversation(data.conversation);
                    this.selectedConversationKey = data.conversation.conversation_key;
                    this.resetConversationState();
                    await this.loadConversation(data.conversation, { page: 1 });
                }

                this.showGroupChatModal = false;
                this.groupChatError = '';
                this.notify({
                    type: 'success',
                    title: 'Group chat',
                    message: data?.message || 'Saved successfully.',
                    duration: 3000,
                });
            } catch (error) {
                this.groupChatError = error?.response?.data?.message || 'Unable to create group chat.';
            } finally {
                this.groupChatSubmitting = false;
            }
        },
        async approveGroupChatRequest(request) {
            if (!request?.id) {
                return;
            }

            try {
                const { data } = await axios.post(
                    `/api/group-chats/${request.id}/approve`,
                    {},
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                this.pendingGroupChatApprovals = this.pendingGroupChatApprovals.filter((item) => item.id !== request.id);

                if (data?.conversation) {
                    this.upsertConversation(data.conversation);
                }
            } catch (error) {
                console.error('Failed to approve group chat request:', error);
            }
        },
        async rejectGroupChatRequest(request) {
            if (!request?.id) {
                return;
            }

            try {
                await axios.post(
                    `/api/group-chats/${request.id}/reject`,
                    {},
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                this.pendingGroupChatApprovals = this.pendingGroupChatApprovals.filter((item) => item.id !== request.id);
            } catch (error) {
                console.error('Failed to reject group chat request:', error);
            }
        },
        upsertConversation(conversation) {
            if (!conversation?.conversation_key) {
                return;
            }

            const existingConversation = this.users.find((item) => item.conversation_key === conversation.conversation_key) || null;
            const incomingUnreadCount = Number(conversation.unread_count || 0);
            const existingUnreadCount = Number(existingConversation?.unread_count || 0);
            const shouldPreserveUnreadCount = Boolean(
                existingConversation
                && existingUnreadCount > 0
                && incomingUnreadCount === 0
                && this.selectedConversationKey !== conversation.conversation_key,
            );

            const nextConversation = {
                ...conversation,
                id: Number(conversation.id),
                conversation_key: conversation.conversation_key,
                conversation_type: conversation.conversation_type || 'direct',
                member_ids: Array.isArray(conversation.member_ids) ? conversation.member_ids.map((id) => Number(id)) : [],
                members: Array.isArray(conversation.members) ? conversation.members.map((member) => ({
                    ...member,
                    id: Number(member.id),
                })) : [],
                actual_name: conversation.actual_name || existingConversation?.actual_name || conversation.name,
                nickname: Object.prototype.hasOwnProperty.call(conversation, 'nickname')
                    ? conversation.nickname
                    : (existingConversation?.nickname || null),
                unread_count: shouldPreserveUnreadCount ? existingUnreadCount : incomingUnreadCount,
                is_unread: shouldPreserveUnreadCount ? true : Boolean(incomingUnreadCount > 0 || conversation.is_unread),
                active_label: conversation.conversation_type === 'group'
                    ? (conversation.active_label || `${Number(conversation.member_count || 0)} members`)
                    : conversation.active_label,
            };

            if (nextConversation.conversation_type === 'direct') {
                const nickname = String(nextConversation.nickname || '').trim();
                const actualName = String(nextConversation.actual_name || nextConversation.name || 'User').trim();
                nextConversation.name = nickname || actualName || 'User';
            }

            const existingIndex = this.users.findIndex((item) => item.conversation_key === nextConversation.conversation_key);

            if (existingIndex === -1) {
                this.users.push(nextConversation);
                this.scheduleMessagesCachePersist();
                return;
            }

            this.users.splice(existingIndex, 1, {
                ...this.users[existingIndex],
                ...nextConversation,
            });
            this.scheduleMessagesCachePersist();
        },
        upsertLocalMessage(message) {
            if (!message?.id) return;

            const normalizedMessage = this.normalizeMessage(message);

            const existingIndex = this.messages.findIndex((item) => Number(item.id) === Number(normalizedMessage.id));
            if (existingIndex === -1) {
                this.messages.push(normalizedMessage);
                this.scheduleMessagesCachePersist();
                return;
            }

            this.messages.splice(existingIndex, 1, {
                ...this.messages[existingIndex],
                ...normalizedMessage,
            });
            this.scheduleMessagesCachePersist();
        },
        removeConversation(conversationKey) {
            const index = this.users.findIndex((item) => item.conversation_key === conversationKey);

            if (index >= 0) {
                this.users.splice(index, 1);
            }

            if (this.selectedConversationKey === conversationKey) {
                const nextConversation = this.users[0] || null;
                this.selectedConversationKey = nextConversation?.conversation_key || null;
                this.resetConversationState();

                if (nextConversation) {
                    this.loadConversation(nextConversation, { page: 1 });
                }
            }

            this.scheduleMessagesCachePersist();
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

            this.scheduleMessagesCachePersist();
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

            if (conversationPreview.latest_message?.id && this.selectedConversationKey === user.conversation_key) {
                this.upsertLocalMessage({
                    ...conversationPreview.latest_message,
                    is_mine: Number(conversationPreview.latest_message.sender_id) === Number(this.authUser?.id),
                });
            }

            this.syncUsersOnlineState();
            this.scheduleMessagesCachePersist();
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
            if (this.activeConversationIsGroup) return;
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
        async markGroupConversationSeen() {
            const activeUser = this.activeUser;
            if (!activeUser || !this.activeConversationIsGroup) return;
            if (!this.isConversationPanelVisible()) return;

            if (!this.messages.some((message) => !message.is_mine && !message.is_system)) {
                return;
            }

            try {
                await axios.post(
                    `/api/group-chats/${activeUser.id}/seen`,
                    {},
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    }
                );

                const targetConversation = this.users.find((user) => user.conversation_key === activeUser.conversation_key);
                if (targetConversation) {
                    targetConversation.unread_count = 0;
                    targetConversation.is_unread = false;
                }
            } catch (error) {
                console.error('Failed to mark group conversation as seen:', error);
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
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.clearReactionPicker();
            const nextReaction = message.reaction === reactionKey ? null : reactionKey;

            try {
                const endpoint = this.activeConversationIsGroup
                    ? `/api/group-messages/${message.id}/reaction`
                    : `/api/direct-messages/${message.id}/reaction`;
                const { data } = await axios.patch(
                    endpoint,
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
            if (!message) return '';

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
            const user = this.users.find((item) => item.conversation_key === this.activeUser?.conversation_key);
            if (!user) return;

            user.latest_at = message.created_at || new Date().toISOString();
            user.preview = this.getMessageSnippet(message);
            user.unread_count = clearUnread ? 0 : Number(user.unread_count || 0);
            user.is_unread = Number(user.unread_count || 0) > 0;
            this.scheduleMessagesCachePersist();
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
                const activityState = user.conversation_type === 'group'
                    ? this.getGroupActivityState(user)
                    : this.getActivityState(user.id);
                user.active_label = activityState.label;
                user.is_active = activityState.isActive;
                user.last_seen_at = activityState.lastSeenAt;
            });
        },
        getActivityState(userId, latestAt = null) {
            const normalizedUserId = Number(userId);
            const isOnline = this.onlineUserIds.includes(normalizedUserId);
            const lastSeenAt = this.getLastSeen(normalizedUserId);
            const fallbackAt = lastSeenAt;

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
            return this.getActivityState(this.activeUser?.id).label;
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

            this.loadConversation(this.activeUser, {
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
        formatConversationTimestamp(value) {
            if (!value) {
                return '';
            }

            try {
                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return '';
                }

                const now = this.now;
                const isSameDay = date.toDateString() === now.toDateString();

                if (isSameDay) {
                    return new Intl.DateTimeFormat('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                    }).format(date);
                }

                const yesterday = new Date(now);
                yesterday.setDate(now.getDate() - 1);

                if (date.toDateString() === yesterday.toDateString()) {
                    return 'Yesterday';
                }

                return new Intl.DateTimeFormat('en-US', {
                    month: 'short',
                    day: 'numeric',
                }).format(date);
            } catch (error) {
                return '';
            }
        },
        formatUnreadCount(value) {
            const count = Number(value || 0);
            if (count <= 0) {
                return '';
            }

            return count >= 10 ? '10+' : String(count);
        },
        formatGroupMemberDate(value) {
            if (!value) {
                return '';
            }

            try {
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return '';
                }

                return new Intl.DateTimeFormat([], {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                }).format(date);
            } catch (error) {
                return '';
            }
        },
    },
};
</script>

<style scoped lang="scss">
.messages-page {
    width: 100%;
    min-height: 100dvh;
    padding: 26px 24px;
    overflow: hidden;
    background:
        radial-gradient(circle at top left, rgba(31, 91, 255, 0.15), transparent 24%),
        radial-gradient(circle at top right, rgba(99, 102, 241, 0.08), transparent 22%),
        linear-gradient(180deg, #22272e 0%, #1e2329 100%);
    color: #f3f6fb;
}

.messages-workspace {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    height: calc(100dvh - 52px);
    min-height: calc(100dvh - 52px);
}

.conversation-user__eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-size: 0.72rem;
    color: rgba(214, 222, 235, 0.56);
}

.messenger-shell {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 18px;
    width: 100%;
    flex: 1;
    min-height: 0;
}

.contacts-panel,
.conversation-panel {
    min-height: 0;
    border: 1px solid rgba(255, 255, 255, 0.07);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.16);
    border-radius: 28px;
}

.contacts-panel {
    flex: 0 0 clamp(320px, 28vw, 370px);
    width: clamp(320px, 28vw, 370px);
    display: flex;
    flex-direction: column;
    transition: 
      transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      visibility 0s linear 0.3s;
    background: linear-gradient(180deg, rgba(52, 58, 66, 0.96), rgba(43, 49, 56, 0.98));
}

.conversation-panel {
    flex: 1 1 0;
    min-width: 0;
    width: 100%;
    position: relative;
    z-index: 1;
    background:
        radial-gradient(circle at top right, rgba(33, 91, 246, 0.14), transparent 24%),
        linear-gradient(180deg, rgba(49, 55, 63, 0.96), rgba(40, 45, 52, 0.98));
}

.contacts-panel__header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 12px;
    padding: 18px 18px 10px;
}

.contacts-panel__header > div:first-child {
    min-width: 0;
}

.contacts-panel__header h1,
.contacts-panel__header h2 {
    margin: 0;
    font-size: clamp(1.4rem, 2vw, 1.8rem);
    font-weight: 800;
    color: #f3f6fb;
}

.contacts-panel__title-row {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.contacts-panel__count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
    font-size: 0.76rem;
    font-weight: 800;
}

.contacts-panel__subtitle {
    margin: 8px 0 0;
    color: rgba(214, 222, 235, 0.64);
    font-size: 0.9rem;
}

.contacts-panel__utility-row {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 10px;
    margin-top: 14px;
    min-width: 0;
}

.contacts-panel__utility-link,
.contacts-panel__utility-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.contacts-panel__utility-link {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.05);
    color: #f3f6fb;
}

.contacts-panel__utility-link:hover {
    transform: translateY(-1px);
    background: rgba(28, 88, 246, 0.14);
    border-color: rgba(28, 88, 246, 0.24);
}

.contacts-panel__utility-badge {
    border: 0;
    background: linear-gradient(135deg, rgba(28, 88, 246, 0.24), rgba(67, 122, 255, 0.18));
    color: #dfe9ff;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    box-shadow: inset 0 0 0 1px rgba(142, 181, 255, 0.16);
}

.contacts-panel__utility-badge:hover {
    transform: translateY(-1px);
    box-shadow: inset 0 0 0 1px rgba(142, 181, 255, 0.24), 0 10px 24px rgba(28, 88, 246, 0.18);
}

.contacts-panel__actions,
.conversation-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
    position: relative;
    z-index: 1;
}

.icon-chip.contacts-panel__mobile-close {
    display: none;
}

.icon-chip {
    width: 44px;
    height: 44px;
    position: relative;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    color: #dce5f4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.18s ease, background 0.18s ease;
}

.icon-chip:hover {
    transform: translateY(-1px);
    background: rgba(28, 88, 246, 0.18);
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
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.06);
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
    color: #f3f6fb;
    padding: 13px 0;
    font-size: 1rem;
}

.search-shell input::placeholder {
    color: rgba(214, 222, 235, 0.46);
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 12px 12px 12px;
    min-height: 0;
    position: relative;
    z-index: 1;
}

.contact-card {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 0;
    border-radius: 22px;
    padding: 14px 16px;
    margin-bottom: 10px;
    background: rgba(255, 255, 255, 0.03);
    color: #f3f6fb;
    border: 1px solid rgba(255, 255, 255, 0.05);
    text-align: left;
    transition: background 0.18s ease, transform 0.18s ease;
    position: relative;
    cursor: pointer;
    z-index: 1;
}

.contact-card:hover {
    background: rgba(255, 255, 255, 0.06);
    transform: translateY(-1px);
}

.contact-card--skeleton {
    cursor: default;
    pointer-events: none;
}

.contact-card__avatar--skeleton {
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
}

.contact-card__skeleton-line,
.contact-card__skeleton-time,
.chat-skeleton__date,
.chat-skeleton__bubble {
    display: block;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
}

.contact-card__skeleton-line--name {
    width: 68%;
    height: 14px;
}

.contact-card__skeleton-line--preview {
    width: 88%;
    height: 11px;
    margin-top: 10px;
}

.contact-card__skeleton-line--status {
    width: 46%;
    height: 10px;
    margin-top: 10px;
}

.contact-card__skeleton-time {
    width: 48px;
    height: 10px;
}

.contact-card.is-active {
    background: linear-gradient(135deg, rgba(28, 88, 246, 0.96), rgba(19, 67, 201, 0.98));
    box-shadow: 0 14px 30px rgba(13, 59, 176, 0.28), inset 0 0 0 1px rgba(255, 255, 255, 0.08);
    z-index: 2;
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

.contact-card__name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.contact-card__name-unread {
    flex: 0 0 auto;
}

.contact-card__preview {
    display: block;
    font-size: 0.88rem;
    color: rgba(214, 222, 235, 0.78);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-card__status {
    display: block;
    font-size: 0.76rem;
    color: rgba(214, 222, 235, 0.54);
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
    color: rgba(214, 222, 235, 0.62);
    position: relative;
}

.contact-card__time {
    font-size: 0.74rem;
    font-weight: 700;
}

.contact-card__actions {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
}

.contact-card__more {
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(214, 222, 235, 0.78);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
}

.contact-card__more:hover {
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    transform: translateY(-1px);
}

.contact-card__menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 180px;
    padding: 8px;
    border-radius: 16px;
    background: rgba(35, 40, 47, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 18px 36px rgba(0, 0, 0, 0.32);
    z-index: 999;
}

.contact-card__menu-item {
    width: 100%;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    background: transparent;
    color: #f3f6fb;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-align: left;
    transition: background 0.18s ease, color 0.18s ease;
    white-space: nowrap;
    flex-wrap: nowrap;
}

.contact-card__menu-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.contact-card__menu-item--danger {
    color: #ffb0be;
}

.contact-card__menu-item--danger:hover {
    background: rgba(255, 96, 122, 0.12);
    color: #ffd6de;
}

.unread-pill {
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: #1c58f6;
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
}

.conversation-panel__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
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
    color: #f3f6fb;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-user__status {
    display: block;
    color: rgba(214, 222, 235, 0.68);
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
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.06);
    color: #dce5f4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.18s ease, filter 0.18s ease;
}

.conversation-info-btn:hover {
    transform: translateY(-1px);
    background: rgba(28, 88, 246, 0.18);
}

.conversation-info-btn--info {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.conversation-info-btn--danger {
    background: rgba(220, 53, 69, 0.16);
    color: #ffb3be;
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
    z-index: 2000;
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

.message-action-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(7, 5, 10, 0.72);
    backdrop-filter: blur(10px);
}

.message-action-modal {
    width: min(92vw, 620px);
    max-height: min(88vh, 760px);
    display: flex;
    flex-direction: column;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgba(27, 16, 31, 0.98), rgba(14, 9, 18, 0.99));
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.48);
    overflow: hidden;
}

.message-action-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.message-action-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-action-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.message-action-modal__badge--edit {
    background: rgba(91, 170, 255, 0.16);
    color: #8fc2ff;
}

.message-action-modal__badge--danger {
    background: rgba(255, 73, 122, 0.14);
    color: #ff8dab;
}

.message-action-modal__eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.55);
    margin-bottom: 6px;
}

.message-action-modal__title {
    margin: 0;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
}

.message-action-modal__subtitle {
    margin: 0;
    color: rgba(255, 255, 255, 0.68);
    line-height: 1.55;
}

.message-action-modal__close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
}

.message-action-modal__close:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.message-action-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px 22px 18px;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
}

.message-action-modal__context {
    margin-bottom: 16px;
}

.message-action-modal__context-label {
    margin-bottom: 8px;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.58);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.message-action-modal__copy {
    margin: 0 0 14px;
    color: rgba(255, 255, 255, 0.82);
    line-height: 1.6;
}

.message-action-modal__label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.84rem;
    color: rgba(255, 255, 255, 0.64);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.message-action-modal__input,
.message-action-modal__textarea {
    width: 100%;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    padding: 14px 16px;
    outline: none;
    line-height: 1.6;
}

.message-action-modal__input {
    height: 54px;
}

.message-action-modal__textarea {
    min-height: 168px;
    resize: vertical;
}

.message-action-modal__input:focus,
.message-action-modal__textarea:focus {
    border-color: rgba(91, 170, 255, 0.55);
    box-shadow: 0 0 0 4px rgba(91, 170, 255, 0.12);
}

.message-action-modal__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 10px;
}

.message-action-modal__hint,
.message-action-modal__count {
    color: rgba(255, 255, 255, 0.55);
}

.message-action-modal__preview {
    padding: 14px 16px;
    max-height: min(42vh, 420px);
    overflow-y: auto;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.92);
    line-height: 1.55;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.message-action-modal__warning {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
    color: #ffb4c9;
    font-size: 0.92rem;
}

.message-action-modal__error {
    margin: 14px 0 0;
    color: #ff8bab;
    font-size: 0.92rem;
}

.message-action-modal__footer {
    display: flex;
    flex: 0 0 auto;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px 22px;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01)),
        linear-gradient(180deg, rgba(49, 55, 63, 0.96), rgba(37, 42, 49, 0.99));
    position: relative;
    z-index: 1;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.message-action-modal__btn {
    min-width: 146px;
    height: 44px;
    padding: 0 18px;
    border: 0;
    border-radius: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.message-action-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

.message-action-modal__btn--primary {
    background: linear-gradient(135deg, #5f8bff, #4b63ff);
    color: #fff;
}

.message-action-modal__btn--danger {
    background: linear-gradient(135deg, #ff6e98, #ff3d6f);
    color: #fff;
}

.message-action-modal__btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.group-info-editor {
    display: grid;
    gap: 16px;
}

.group-info-editor__avatar {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.group-info-editor__avatar img {
    width: 96px;
    height: 96px;
    border-radius: 24px;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22);
}

.conversation-info-media {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: grid;
    gap: 16px;
}

.conversation-info-media__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.conversation-info-media__section {
    display: grid;
    gap: 12px;
}

.conversation-info-media__tabs {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.conversation-info-media__tab {
    min-width: 84px;
    height: 38px;
    padding: 0 16px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.7);
    font-weight: 700;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
}

.conversation-info-media__tab:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.conversation-info-media__tab.is-active {
    background: linear-gradient(135deg, #5f8bff, #4b63ff);
    color: #fff;
}

.conversation-info-media__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
}

.conversation-info-media__image {
    border: 0;
    padding: 0;
    border-radius: 16px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.06);
    aspect-ratio: 1 / 1;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
}

.conversation-info-media__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.conversation-info-media__file {
    width: 100%;
    border: 0;
    border-radius: 16px;
    padding: 12px 14px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
}

.conversation-info-media__file-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(95, 139, 255, 0.16);
    color: #aecdff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
}

.conversation-info-media__file-body {
    min-width: 0;
    display: grid;
    gap: 2px;
}

.conversation-info-media__file-name {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 700;
}

.conversation-info-media__hint {
    text-align: center;
    color: rgba(255, 255, 255, 0.52);
    font-size: 0.82rem;
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

.chat-skeleton {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 8px 0 22px;
}

.chat-skeleton__date {
    width: 148px;
    height: 12px;
    margin: 0 auto;
}

.chat-skeleton__row {
    display: flex;
}

.chat-skeleton__row--mine {
    justify-content: flex-end;
}

.chat-skeleton__row--theirs {
    justify-content: flex-start;
}

.chat-skeleton__bubble {
    width: min(72%, 420px);
    height: 56px;
    border-radius: 20px;
}

.chat-skeleton__row:nth-child(3) .chat-skeleton__bubble {
    width: min(54%, 300px);
}

.chat-skeleton__row:nth-child(4) .chat-skeleton__bubble {
    width: min(64%, 360px);
}

.chat-skeleton__row:nth-child(5) .chat-skeleton__bubble {
    width: min(46%, 240px);
}

.chat-skeleton__row:nth-child(6) .chat-skeleton__bubble {
    width: min(70%, 390px);
}

.skeleton-shimmer {
    position: relative;
    overflow: hidden;
}

.skeleton-shimmer::after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12), transparent);
    animation: skeletonShimmer 1.25s ease-in-out infinite;
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

.message-row--system {
    justify-content: center;
}

.message-system-note {
    max-width: min(100%, 420px);
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.76);
    font-size: 0.84rem;
    text-align: center;
    letter-spacing: 0.02em;
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

.reaction-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(7, 5, 10, 0.72);
    backdrop-filter: blur(10px);
}

.reaction-modal {
    width: min(92vw, 420px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgb(43, 49, 58), rgb(31, 36, 44));
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.48);
    overflow: hidden;
}

.reaction-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.reaction-modal__eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.55);
    margin-bottom: 8px;
}

.reaction-modal__title {
    margin: 0;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
}

.reaction-modal__subtitle {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.68);
    line-height: 1.55;
    word-break: break-word;
}

.reaction-modal__close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
}

.reaction-modal__body {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
    gap: 12px;
    padding: 20px 22px 22px;
}

.reaction-modal__option {
    min-height: 88px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 18px;
    background: rgb(57, 63, 73);
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 0.16s ease, background 0.16s ease, border-color 0.16s ease;
}

.reaction-modal__option:hover {
    transform: translateY(-1px);
    background: rgb(68, 75, 86);
}

.reaction-modal__option.is-active {
    background: linear-gradient(135deg, rgba(255, 77, 136, 0.22), rgba(184, 61, 230, 0.22));
    border-color: rgba(255, 140, 196, 0.48);
}

.reaction-modal__emoji {
    font-size: 1.6rem;
    line-height: 1;
}

.reaction-modal__label {
    font-size: 0.9rem;
    font-weight: 700;
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
    width: 1.05rem;
    height: 1.05rem;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    transform-origin: center;
    pointer-events: none;
}

.message-row--mine .message-pin-chip--floating {
    left: -6px;
    top: -4px;
    right: auto;
    transform: rotate(-45deg);
}

.message-row--theirs .message-pin-chip--floating {
    right: -6px;
    top: -4px;
    left: auto;
    transform: rotate(45deg);
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
    background: rgb(39, 44, 51);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    z-index: 30;
    isolation: isolate;
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

.composer__button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
}

.contacts-panel__action-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #ff5d7d;
    color: #fff;
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.message-bubble__sender {
    margin-bottom: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #ffb7d8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.group-chat-member-list,
.group-chat-approval-list {
    display: grid;
    gap: 10px;
    padding-bottom: 4px;
}

.group-chat-member-item,
.group-chat-approval-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
}

.group-chat-member-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    cursor: pointer;
    position: relative;
}

.group-chat-member-item--static {
    cursor: default;
}

.group-chat-member-item img {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
}

.group-chat-member-item__content {
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding-right: 110px;
}

.group-chat-member-item__headline {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.group-chat-member-item__date {
    position: absolute;
    right: 12px;
    bottom: 10px;
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.56);
    text-align: right;
}

.group-chat-approval-card {
    position: relative;
    padding: 16px;
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.025)),
        rgba(11, 15, 26, 0.88);
    box-shadow: 0 18px 38px rgba(4, 8, 20, 0.24);
}

.group-chat-approval-card__title {
    font-weight: 700;
    font-size: 1rem;
    color: #f7fbff;
}

.group-chat-approval-card__heading {
    min-width: 0;
}

.group-chat-approval-card__topline {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.group-chat-approval-card__meta,
.group-chat-approval-card__members {
    margin-top: 4px;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.9rem;
}

.group-chat-approval-card__meta {
    line-height: 1.45;
}

.group-chat-approval-card__top-actions {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
}

.group-chat-approval-card__open-btn {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid rgba(104, 149, 253, 0.22);
    background: rgba(104, 149, 253, 0.14);
    color: #dbe7ff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
    transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.group-chat-approval-card__open-btn:hover {
    transform: translateY(-1px);
    background: rgba(104, 149, 253, 0.22);
    border-color: rgba(104, 149, 253, 0.34);
}

.group-chat-approval-card__members {
    position: relative;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.group-chat-approval-card__member-trigger {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
}

.group-chat-approval-card__member-stack {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    padding-left: 6px;
}

.group-chat-approval-card__member-avatar {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    overflow: hidden;
    border: 2px solid rgba(16, 18, 28, 0.9);
    margin-left: -6px;
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 6px 16px rgba(5, 8, 20, 0.22);
}

.group-chat-approval-card__member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.group-chat-approval-card__member-avatar--more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 800;
    color: #f7fbff;
    background: rgba(104, 149, 253, 0.32);
}

.group-chat-approval-card__tooltip {
    position: absolute;
    top: 50%;
    left: calc(100% + 14px);
    transform: translateY(-50%);
    z-index: 8;
    width: min(260px, calc(100vw - 48px));
    max-height: 220px;
    padding: 10px;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(15, 18, 30, 0.96);
    box-shadow: 0 18px 44px rgba(4, 7, 18, 0.34);
    backdrop-filter: blur(18px);
}

.group-chat-approval-card__tooltip-list {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    overflow-y: auto;
    overflow-x: hidden;
    max-height: 200px;
    padding-right: 4px;
    scrollbar-width: thin;
}

.group-chat-approval-card__tooltip-chip {
    display: block;
    width: 100%;
    padding: 0.55rem 0.75rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.06);
    color: #f5f8ff;
    font-size: 0.84rem;
    line-height: 1.35;
    white-space: normal;
    word-break: break-word;
}

.group-chat-request-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.group-chat-request-status--approved {
    background: rgba(76, 198, 121, 0.16);
    border-color: rgba(76, 198, 121, 0.28);
    color: #b8f0c8;
}

.group-chat-request-status--rejected {
    background: rgba(255, 108, 138, 0.16);
    border-color: rgba(255, 108, 138, 0.28);
    color: #ffc0d0;
}

.group-chat-request-note {
    margin-top: 14px;
    padding: 0.8rem 0.9rem;
    border-radius: 16px;
    background: rgba(255, 108, 138, 0.08);
    border: 1px solid rgba(255, 108, 138, 0.14);
    color: #ffd2dd;
    font-size: 0.86rem;
    line-height: 1.5;
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
    left: 12px;
    bottom: calc(100% + 14px);
    transform: none;
    z-index: 60;
    pointer-events: auto;
    isolation: isolate;
}

.composer-emoji-picker {
    display: grid;
    grid-template-columns: repeat(5, 40px);
    gap: 8px;
    padding: 12px;
    border-radius: 24px;
    background: rgb(35, 40, 47);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 18px 34px rgba(0, 0, 0, 0.3);
    width: max-content;
    max-width: min(84vw, 248px);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.composer-emoji-picker__btn {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgb(58, 64, 74);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: transform 0.15s ease, background 0.15s ease;
}

.composer-emoji-picker__btn:hover {
    transform: translateY(-1px) scale(1.06);
    background: rgb(76, 84, 96);
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

@keyframes skeletonShimmer {
    100% {
        transform: translateX(100%);
    }
}

.conversation-banner {
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    color: rgba(214, 222, 235, 0.82);
}

.conversation-banner__title {
    color: #f3f6fb;
}

.conversation-banner__subtitle {
    color: rgba(214, 222, 235, 0.66);
}

.conversation-banner__pinned-count {
    background: rgba(28, 88, 246, 0.16);
}

.pinned-modal-backdrop,
.message-action-modal-backdrop,
.reaction-modal-backdrop {
    background: rgba(12, 16, 23, 0.56);
    backdrop-filter: blur(14px);
}

.pinned-modal,
.message-action-modal,
.reaction-modal {
    border-color: rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(circle at top right, rgba(28, 88, 246, 0.12), transparent 26%),
        linear-gradient(180deg, rgba(49, 55, 63, 0.98), rgba(37, 42, 49, 0.99));
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.32);
}

.pinned-modal__header,
.message-action-modal__header,
.reaction-modal__header,
.message-action-modal__footer {
    border-color: rgba(255, 255, 255, 0.07);
}

.pinned-modal__title,
.message-action-modal__title,
.reaction-modal__title {
    color: #f3f6fb;
}

.pinned-modal__item-body,
.message-action-modal__input,
.message-action-modal__textarea,
.message-action-modal__preview,
.reaction-modal__option,
.group-chat-member-item,
.group-chat-approval-card,
.conversation-info-media__file {
    background: rgb(56, 62, 72);
    border-color: rgba(255, 255, 255, 0.06);
    color: #f3f6fb;
}

.pinned-modal__item-body:hover,
.reaction-modal__option:hover,
.group-chat-member-item:hover {
    background: rgb(67, 74, 85);
}

.pinned-modal__close,
.pinned-modal__item-unpin,
.message-action-modal__close,
.reaction-modal__close {
    background: rgba(255, 255, 255, 0.06);
    color: #f3f6fb;
}

.pinned-modal__item-unpin:hover,
.reaction-modal__option.is-active {
    background: rgba(28, 88, 246, 0.16);
}

.message-action-modal__badge--edit {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.message-action-modal__badge--danger {
    background: rgba(220, 53, 69, 0.16);
    color: #ffb3be;
}

.message-action-modal__eyebrow,
.reaction-modal__eyebrow,
.message-action-modal__context-label,
.message-action-modal__label,
.message-action-modal__hint,
.message-action-modal__count,
.pinned-modal__item-date,
.reaction-modal__subtitle,
.message-action-modal__subtitle {
    color: rgba(214, 222, 235, 0.62);
}

.message-action-modal__input:focus,
.message-action-modal__textarea:focus {
    border-color: rgba(28, 88, 246, 0.55);
    box-shadow: 0 0 0 4px rgba(28, 88, 246, 0.14);
}

.message-action-modal__preview,
.message-action-modal__context,
.conversation-info-media__file {
    border: 1px solid rgba(255, 255, 255, 0.06);
    padding: 20px;
}

.message-action-modal__preview--stacked {
    display: flex;
    flex-direction: column;
    gap: 10px;
    white-space: normal;
}

.message-action-modal__preview--stacked p {
    margin: 0;
    line-height: 1.6;
}

.message-action-modal__warning,
.message-action-modal__error {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.18);
    color: #ffd7dd;
}

.message-action-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
    color: #f3f6fb;
}

.message-action-modal__btn--primary {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
}

.message-action-modal__btn--danger {
    background: linear-gradient(135deg, #d64c61, #ba364d);
}

.messages-toast-stack {
    position: fixed;
    top: 26px;
    right: 26px;
    z-index: 120;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
}

.messages-toast {
    width: min(380px, calc(100vw - 32px));
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: start;
    gap: 12px;
    padding: 14px 14px 14px 12px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(circle at top right, rgba(28, 88, 246, 0.1), transparent 32%),
        linear-gradient(180deg, rgba(52, 58, 66, 0.98), rgba(40, 45, 52, 0.99));
    color: #f3f6fb;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.24);
    pointer-events: auto;
}

.messages-toast--success {
    border-color: rgba(108, 201, 143, 0.22);
}

.messages-toast--error {
    border-color: rgba(220, 53, 69, 0.22);
}

.messages-toast__icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(28, 88, 246, 0.16);
    color: #8eb5ff;
}

.messages-toast--success .messages-toast__icon {
    background: rgba(108, 201, 143, 0.14);
    color: #9ce2b3;
}

.messages-toast--error .messages-toast__icon {
    background: rgba(220, 53, 69, 0.14);
    color: #ffb3be;
}

.messages-toast__title {
    font-size: 0.96rem;
    font-weight: 800;
    line-height: 1.2;
}

.messages-toast__message {
    margin-top: 4px;
    color: rgba(214, 222, 235, 0.76);
    line-height: 1.45;
    font-size: 0.88rem;
}

.messages-toast__close {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    color: #f3f6fb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.24s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px) translateX(12px);
}

.conversation-panel__body {
    padding: 22px 24px 18px;
    background:
        radial-gradient(circle at 15% 15%, rgba(28, 88, 246, 0.08), transparent 18%),
        radial-gradient(circle at 85% 25%, rgba(99, 102, 241, 0.06), transparent 20%),
        linear-gradient(180deg, rgba(43, 49, 57, 0.98), rgba(37, 42, 49, 0.98));
}

.chat-empty,
.chat-loading {
    color: rgba(214, 222, 235, 0.76);
}

.conversation-start-marker {
    color: rgba(214, 222, 235, 0.48);
}

.chat-empty__icon {
    background: rgba(255, 255, 255, 0.06);
    color: #8eb5ff;
}

.message-system-note {
    background: rgba(255, 255, 255, 0.06);
    color: rgba(214, 222, 235, 0.7);
}

.message-row--highlighted .message-bubble {
    box-shadow: 0 0 0 2px rgba(28, 88, 246, 0.24), 0 16px 30px rgba(0, 0, 0, 0.18);
}

.message-bubble {
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.08);
    color: #f3f6fb;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.message-row--mine .message-bubble {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
}

.message-row--theirs .message-bubble {
    background: rgba(255, 255, 255, 0.06);
}

.message-bubble__reply {
    background: rgba(255, 255, 255, 0.06);
}

.message-bubble__reply-label,
.message-bubble__time,
.message-bubble__status,
.message-bubble__status-edit {
    color: rgba(214, 222, 235, 0.68);
}

.message-bubble__attachment {
    background: rgba(255, 255, 255, 0.06);
}

.typing-indicator__dots span {
    background: #8eb5ff;
}

.bubble-action-menu {
    background: rgba(35, 40, 47, 0.98);
}

.bubble-action {
    background: rgba(45, 51, 59, 0.9);
    color: #f3f6fb;
}

.bubble-action:hover,
.bubble-action.is-active {
    background: rgba(28, 88, 246, 0.9);
}

.message-bubble__sender,
.message-pin-chip__icon,
.attachment-preview__icon {
    color: #a7c2ff;
}

.message-bubble__status--seen {
    color: #8ad7a6;
}

.message-scroll-bottom,
.composer__button.is-active,
.composer__send {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
}

.message-scroll-bottom {
    border-radius: 18px;
}

.message-reaction-badge {
    background: rgba(255, 255, 255, 0.1);
}

.composer {
    background: rgb(39, 44, 51);
    border-top: 1px solid rgba(255, 255, 255, 0.07);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.composer__button,
.composer__input-shell,
.composer-reply,
.attachment-preview,
.composer-reply__close,
.attachment-preview__remove,
.attachment-preview__thumb {
    background: rgba(255, 255, 255, 0.05);
}

.composer__button {
    border-radius: 16px;
    color: #dce5f4;
}

.composer__button:hover {
    background: rgba(28, 88, 246, 0.16);
}

.composer__button.is-active {
    box-shadow: 0 12px 24px rgba(28, 88, 246, 0.24);
}

.composer__input-shell {
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.composer__input,
.attachment-preview__name {
    color: #f3f6fb;
}

.composer__input::placeholder,
.composer__hint,
.composer__counter,
.composer-reply__preview {
    color: rgba(214, 222, 235, 0.5);
}

.composer__counter.is-near-limit {
    color: #9db9ff;
}

.composer__send {
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(28, 88, 246, 0.28);
}

.composer-emoji-picker {
    background: rgb(35, 40, 47);
}

.composer-emoji-picker__btn {
    background: rgb(58, 64, 74);
}

.composer-emoji-picker__btn:hover {
    background: rgb(67, 74, 85);
}

.loader-dot {
    background: #1c58f6;
    box-shadow: 0 0 0 0 rgba(28, 88, 246, 0.35);
}

@keyframes messageFlash {
    0% {
        box-shadow: 0 0 0 0 rgba(28, 88, 246, 0.45), 0 16px 30px rgba(0, 0, 0, 0.18);
    }
    100% {
        box-shadow: 0 0 0 12px rgba(28, 88, 246, 0), 0 16px 30px rgba(0, 0, 0, 0.18);
    }
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(28, 88, 246, 0.35);
    }
    70% {
        box-shadow: 0 0 0 14px rgba(28, 88, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(28, 88, 246, 0);
    }
}

@media (max-width: 1399.98px) {
    .messages-page {
        padding: 22px 20px;
    }

    .messages-workspace {
        height: calc(100dvh - 44px);
        min-height: calc(100dvh - 44px);
    }

    .messenger-shell {
        gap: 16px;
    }

    .contacts-panel {
        flex-basis: clamp(300px, 29vw, 340px);
        width: clamp(300px, 29vw, 340px);
    }

    .conversation-panel__top {
        padding: 16px 18px;
    }
}

@media (max-width: 991.98px) {
    .messages-page {
        padding: 16px;
    }

    .messages-workspace {
        height: calc(100dvh - 32px);
        min-height: calc(100dvh - 32px);
    }

    .messenger-shell {
        gap: 14px;
    }

    .contacts-panel,
    .conversation-panel {
        min-height: 0;
    }

    .contacts-panel {
        flex-basis: clamp(280px, 38vw, 320px);
        width: clamp(280px, 38vw, 320px);
    }

    .contacts-panel__header {
        padding: 16px 16px 10px;
    }

    .contacts-panel__search {
        padding: 4px 16px 12px;
    }

    .contacts-panel__utility-row {
        flex-wrap: wrap;
    }

    .contacts-panel__utility-link,
    .contacts-panel__utility-badge {
        min-height: 36px;
        padding: 0 12px;
        font-size: 0.78rem;
    }

    .contact-card {
        padding: 12px 14px;
        border-radius: 18px;
    }

    .contact-card__avatar {
        width: 50px;
        height: 50px;
        flex-basis: 50px;
    }

    .conversation-panel__top {
        padding: 16px;
    }

    .conversation-actions {
        gap: 8px;
    }

    .conversation-info-btn {
        width: 40px;
        height: 40px;
        flex-basis: 40px;
    }

    .conversation-panel__body {
        padding: 18px 18px 16px;
    }

    .composer {
        padding: 14px;
    }
}

@media (max-width: 767.98px) {
    .messages-page {
        padding: 10px;
        min-height: 100dvh;
    }

    .messages-workspace {
        gap: 12px;
        height: calc(100dvh - 20px);
        min-height: calc(100dvh - 20px);
    }

    .contacts-panel__utility-row {
        gap: 8px;
        flex-wrap: nowrap;
    }

    .contacts-panel__utility-link,
    .contacts-panel__utility-badge {
        min-height: 36px;
        padding: 0 12px;
        font-size: 0.76rem;
    }

    .group-chat-approval-card__topline {
        flex-direction: column;
        align-items: flex-start;
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

    .conversation-actions .icon-chip.contacts-panel__mobile-close {
        display: inline-flex;
    }

    .icon-chip.contacts-panel__mobile-close {
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
