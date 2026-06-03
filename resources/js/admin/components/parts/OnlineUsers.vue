<template>
    <div class="dropdown position-relative">
        <a
            class="text-decoration-none position-relative d-inline-block"
            :class="{ 'online-users-trigger--employee': userRole === 'employee' }"
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
                <div class="online-users-menu__hero">
                    <div class="online-users-menu__topline">
                        <div class="online-users-menu__title-block">
                            <span class="online-users-menu__eyebrow">Team Presence</span>
                            <h6 class="mb-0 fw-semibold">Online Users</h6>
                            <div class="online-users-menu__subline">
                                <span class="online-users-menu__count-dot"></span>
                                <span>{{ onlineCount }} online</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm theme-button online-users-menu__messenger-btn"
                            @click.stop="openMessagesPage"
                        >
                            <i class="fa-regular fa-paper-plane"></i>
                            <span>Messenger</span>
                        </button>
                    </div>
                    <p class="online-users-menu__description mb-0">
                        See who is active right now and jump straight into a conversation.
                    </p>
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
                                    <span
                                        class="user-list__status-dot"
                                        :class="user.isOnline ? 'user-list__status-dot--online' : 'user-list__status-dot--offline'"
                                    ></span>
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
                                <span
                                    v-if="selectedUserState"
                                    class="user-list__status-dot"
                                    :class="selectedUserState?.isOnline ? 'user-list__status-dot--online' : 'user-list__status-dot--offline'"
                                ></span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ selectedUserState?.name }}</div>
                                <small class="theme-muted">
                                    {{ conversationStatusLabel }}
                                </small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm theme-button" @click.stop="openMessagesPage">
                                Open Messenger
                            </button>
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
                                v-if="!conversationHasMore || conversationPage >= conversationLastPage"
                                class="conversation-start-marker"
                            >
                                Your conversation starts here
                            </div>

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
                                class="message-row"
                                :class="[
                                    message.is_system
                                        ? 'message-row--system'
                                        : message.is_mine
                                            ? 'message-row--mine'
                                            : 'message-row--theirs',
                                    highlightedMessageId === message.id ? 'message-row--highlighted' : '',
                                    highlightedMessageId === message.id ? `message-row--pulse-${highlightPulseToken}` : '',
                                ]"
                                :data-message-id="message.id"
                            >
                                <div v-if="message.is_system" class="message-system-note">
                                    <div class="message-system-note__body">
                                        {{ formatSystemMessage(message) }}
                                    </div>
                                </div>
                                <div v-else class="message-bubble-shell">
                                    <div
                                        v-if="!message.is_unsent"
                                        class="message-actions"
                                        :class="{
                                            'is-open': activeReactionPickerId === message.id || activeMessageActionsId === message.id,
                                            'is-hidden-for-reaction': activeReactionPickerId === message.id,
                                        }"
                                    >
                                        <div v-if="message.is_mine && !message.is_unsent" class="message-action-group">
                                            <button
                                                type="button"
                                                class="message-action-button message-action-button--more"
                                                :class="{ 'is-active': activeMessageActionsId === message.id }"
                                                @click.stop="toggleMessageActions(message, $event)"
                                                title="More actions"
                                                :aria-label="`More actions for ${message.body || message.attachment?.name || 'message'}`"
                                                :aria-expanded="activeMessageActionsId === message.id"
                                            >
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                        </div>
                                        <button
                                            v-else-if="!message.is_unsent"
                                            type="button"
                                            class="message-action-button message-action-button--pin"
                                            :class="{ 'is-active': isMessagePinned(message.id) }"
                                            @click.stop="togglePinMessage(message)"
                                            :title="isMessagePinned(message.id) ? 'Unpin message' : 'Pin message'"
                                            :aria-label="isMessagePinned(message.id) ? 'Unpin message' : 'Pin message'"
                                            :disabled="!isMessagePinned(message.id) && pinnedMessages.length >= pinnedMessageLimit"
                                        >
                                            <span class="message-action-button__pin-icon">
                                                <i
                                                    v-if="isMessagePinned(message.id)"
                                                    class="fa-solid fa-thumbtack-slash"
                                                ></i>
                                                <i v-else class="fa-solid fa-thumbtack"></i>
                                            </span>
                                        </button>
                                        <button
                                            v-if="!message.is_unsent"
                                            type="button"
                                            class="message-action-button message-action-button--reply"
                                            @click="startReply(message)"
                                            title="Reply"
                                            :aria-label="`Reply to ${message.body || message.attachment?.name || 'message'}`"
                                        >
                                            <i class="fa-solid fa-reply"></i>
                                        </button>
                                        <button
                                            v-if="!message.is_unsent"
                                            type="button"
                                            class="message-action-button message-action-button--react"
                                            @click.stop="toggleReactionPicker(message)"
                                            title="React"
                                            :aria-label="`React to ${message.body || message.attachment?.name || 'message'}`"
                                        >
                                            <i class="fa-regular fa-face-smile"></i>
                                        </button>
                                    </div>
                                    <div class="message-bubble-stack">
                                    <div
                                        class="message-bubble"
                                        :class="message.is_mine ? 'message-bubble--mine' : 'message-bubble--theirs'"
                                    >
                                        <div
                                            v-if="message.reply_to_id && !message.is_unsent"
                                            class="reply-preview reply-preview--linked"
                                            role="button"
                                            tabindex="0"
                                            @click.stop="scrollToReplyMessage(message)"
                                            @keyup.enter.stop="scrollToReplyMessage(message)"
                                            :title="'Jump to replied message'"
                                        >
                                            <div class="reply-preview__header">
                                                <i class="fa-solid fa-reply"></i>
                                                <span>Replied to this message</span>
                                            </div>
                                            <div class="reply-preview__body">
                                                {{ getReplyPreview(message) }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="!message.is_unsent && isMessagePinned(message.id)"
                                            class="message-pin-chip"
                                            :class="message.is_mine ? 'message-pin-chip--mine' : 'message-pin-chip--theirs'"
                                        >
                                            <span class="message-pin-chip__dot"></span>
                                            <span class="message-pin-chip__icon">
                                                <i class="fa-solid fa-thumbtack"></i>
                                            </span>
                                        </div>
                                        <div
                                            v-if="message.attachment && !message.is_unsent"
                                            class="message-attachment"
                                            :class="message.attachment.type === 'image' ? 'message-attachment--image' : 'message-attachment--file'"
                                        >
                                            <div
                                                v-if="message.attachment.type === 'image'"
                                                class="message-attachment__image-wrap"
                                            >
                                                <button
                                                    type="button"
                                                    class="message-attachment__image-link"
                                                    @click.stop="openImageGallery(message.attachment)"
                                                    :aria-label="`Open ${message.attachment.name || 'attachment'} in gallery`"
                                                >
                                                    <img
                                                        :src="message.attachment.url"
                                                        :alt="message.attachment.name || 'Attachment'"
                                                        class="message-attachment__image"
                                                        @load="handleAttachmentMediaLoad"
                                                    />
                                                    <span class="message-attachment__image-overlay">
                                                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                                                    </span>
                                                </button>
                                                <a
                                                    :href="message.attachment.url"
                                                    :download="getAttachmentDownloadName(message.attachment)"
                                                    class="message-attachment__download-btn"
                                                    title="Download attachment"
                                                >
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                            </div>
                                            <div
                                                v-else
                                                class="message-attachment__file-wrap"
                                            >
                                                <a
                                                    :href="message.attachment.url"
                                                    :download="getAttachmentDownloadName(message.attachment)"
                                                    class="message-attachment__file-link"
                                                    title="Download attachment"
                                                >
                                                    <div class="message-attachment__file-icon">
                                                        <i :class="getAttachmentIconClass(message.attachment)"></i>
                                                    </div>
                                                    <div class="message-attachment__file-meta">
                                                        <div class="message-attachment__file-name">
                                                            {{ message.attachment.name || 'Attachment' }}
                                                        </div>
                                                        <small class="theme-muted">
                                                            {{ formatFileSize(message.attachment.size) }}
                                                        </small>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                        <div
                                            v-if="message.body && !message.is_unsent"
                                            class="message-bubble__body"
                                        >
                                            {{ message.body }}
                                        </div>
                                        <div
                                            v-if="message.is_unsent"
                                            class="message-bubble__body message-bubble__body--unsent"
                                        >
                                            Unsent Message
                                        </div>
                                        <div
                                            v-if="!message.is_unsent && message.reactions && message.reactions.length > 0"
                                            class="message-reaction-badges message-reaction-badges--floating"
                                            :class="
                                                message.is_mine
                                                    ? 'message-reaction-badges--mine'
                                                    : 'message-reaction-badges--theirs'
                                            "
                                            :title="formatReactionsTooltip(message.reactions)"
                                            @click="openReactionsModal(message.reactions)"
                                            @keydown.enter="openReactionsModal(message.reactions)"
                                            @keydown.space="openReactionsModal(message.reactions)"
                                            role="button"
                                            tabindex="0"
                                        >
                                            <span
                                                v-for="emoji in getUniqueReactionEmojis(message.reactions).slice(0, 3)"
                                                :key="emoji"
                                                class="message-reaction-badge__glyph"
                                            >
                                                {{ emoji }}
                                            </span>
                                            <span
                                                v-if="getUniqueReactionEmojis(message.reactions).length > 3"
                                                class="message-reaction-count"
                                            >
                                                +{{ getUniqueReactionEmojis(message.reactions).length - 3 }}
                                            </span>
                                        </div>
                                        <span
                                            v-else-if="!message.is_unsent && getReactionMeta(message)"
                                            class="message-reaction-badge message-reaction-badge--floating"
                                            :class="message.is_mine ? 'message-reaction-badge--mine' : 'message-reaction-badge--theirs'"
                                            :style="{
                                                color: getReactionMeta(message).color,
                                                backgroundColor: getReactionMeta(message).bg,
                                            }"
                                            :title="getReactionMeta(message).label"
                                        >
                                            <span class="message-reaction-badge__glyph">{{ getReactionMeta(message).glyph }}</span>
                                        </span>
                                        <div class="w-100">
                                            <div class="message-bubble__time">
                                                <span>{{ formatMessageTime(message.created_at) }}</span>
                                                <span
                                                    v-if="message.edited_at && !message.is_unsent"
                                                    class="message-bubble__time-edit"
                                                >
                                                    · Edited
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                        <div
                                            v-if="
                                                message.is_mine &&
                                                (shouldShowSeenReceipt(message) ||
                                                    !message.read_at)
                                            "
                                            class="message-bubble__status"
                                            :class="
                                                shouldShowSeenReceipt(message)
                                                    ? 'message-bubble__status--seen'
                                                    : 'message-bubble__status--sent'
                                            "
                                        >
                                            <template v-if="shouldShowSeenReceipt(message)">
                                                <span
                                                    class="message-bubble__seen-avatar"
                                                    :title="formatSeenReceiptTooltip(message.read_at)"
                                                    :aria-label="formatSeenReceiptTooltip(message.read_at)"
                                                >
                                                    <img
                                                        v-if="getSeenReceiptAvatar()"
                                                        :src="getSeenReceiptAvatar()"
                                                        :alt="`${selectedUserState?.name || 'User'} profile`"
                                                    />
                                                    <span v-else>
                                                        {{ selectedUserState?.name?.charAt(0)?.toUpperCase() || 'U' }}
                                                    </span>
                                                </span>
                                            </template>
                                            <template v-else>Sent</template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="typingIndicator"
                                class="typing-indicator message-panel__typing"
                            >
                                <span class="typing-indicator__dots">
                                    <span></span><span></span><span></span>
                                </span>
                                <span>typing...</span>
                            </div>

                        </div>
                    </div>

                    <transition name="fade">
                        <button
                            v-if="showScrollToBottomButton"
                            type="button"
                            class="btn btn-sm message-scroll-bottom"
                            @click="scrollConversationToBottom"
                            title="Scroll to bottom"
                        >
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                    </transition>

                    <form class="message-panel__footer" @submit.prevent="sendMessage">
                        <div
                            v-if="replyTargetMessage"
                            class="reply-composer mb-3"
                        >
                            <div class="reply-composer__bar">
                                <div class="reply-composer__meta">
                                <small class="reply-composer__label">Replying</small>
                                <div class="reply-composer__body">
                                        {{ getMessagePreview(replyTargetMessage) }}
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
                        <div
                            v-if="selectedAttachment"
                            class="attachment-preview mb-3"
                            :class="{ 'attachment-preview--sending': sendingMessage }"
                        >
                            <div class="attachment-preview__thumb">
                                <img
                                    v-if="selectedAttachmentIsImage"
                                    :src="selectedAttachmentPreviewUrl"
                                    :alt="selectedAttachment.name"
                                />
                                <div
                                    v-else
                                    class="attachment-preview__icon"
                                >
                                    <i :class="selectedAttachmentIconClass"></i>
                                </div>
                            </div>
                            <div class="attachment-preview__meta">
                                <div class="attachment-preview__name">
                                    {{ selectedAttachment.name }}
                                </div>
                                <small class="theme-muted">
                                    {{ formatFileSize(selectedAttachment.size) }}
                                </small>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-link attachment-preview__remove"
                                @click="clearSelectedAttachment"
                                title="Remove attachment"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <small v-if="attachmentError" class="text-danger mb-2 d-block">
                            {{ attachmentError }}
                        </small>
                        <div
                            v-if="sendingMessage"
                            class="message-panel__composer-status mb-2"
                            aria-live="polite"
                            aria-atomic="true"
                        >
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span>
                                {{ selectedAttachment ? 'Uploading attachment...' : 'Sending message...' }}
                            </span>
                        </div>
                        <textarea
                            v-model="messageDraft"
                            @input="handleMessageInput"
                            @blur="handleMessageBlur"
                            @keydown.enter.exact.prevent="sendMessage"
                            class="form-control message-input"
                            rows="3"
                            placeholder="Type a message..."
                            autocapitalize="off"
                            autocorrect="off"
                            spellcheck="false"
                            :disabled="sendingMessage || !selectedUser"
                            :maxlength="messageMaxChars"
                        ></textarea>
                        <div class="message-panel__composer-tools mt-3 mb-2">
                            <button
                                type="button"
                                class="message-panel__attach-btn"
                                @click="triggerAttachmentPicker"
                                :disabled="sendingMessage || !selectedUser"
                                title="Attach file"
                            >
                                <span class="message-panel__attach-btn-icon">
                                    <i class="fa-solid fa-paperclip"></i>
                                </span>
                                <span class="message-panel__attach-btn-text">
                                    Attach file
                                </span>
                            </button>
                            <button
                                type="button"
                                class="message-panel__pins-btn"
                                @click.stop="togglePinnedMessagesPanel"
                                :disabled="!selectedUser"
                                title="View pinned messages"
                            >
                                <span class="message-panel__pins-btn-icon">
                                    <i class="fa-solid fa-thumbtack"></i>
                                </span>
                                <span class="message-panel__pins-btn-text">
                                    View pins
                                </span>
                                <span
                                    v-if="pinnedMessages.length"
                                    class="message-panel__pins-btn-count"
                                >
                                    {{ pinnedMessages.length }}
                                </span>
                            </button>
                            <input
                                ref="attachmentInput"
                                type="file"
                                class="d-none"
                                :accept="attachmentAccept"
                                @change="handleAttachmentChange"
                            />
                        </div>
                        <small v-if="pinError" class="text-danger d-block mb-2">
                            {{ pinError }}
                        </small>
                        <transition name="fade">
                            <div
                                v-if="showPinLimitPopup"
                                class="pin-limit-popup"
                                role="status"
                                aria-live="polite"
                            >
                                You’ve reached the pin limit.
                            </div>
                        </transition>
                        <div class="message-panel__composer-bottom mt-3">
                            <small class="theme-muted message-panel__composer-count">
                                {{ messageDraftLength }}/{{ messageMaxChars }}
                            </small>
                            <button
                                type="submit"
                                class="btn px-4 theme-button"
                                :disabled="sendingMessage || (!messageDraft.trim() && !selectedAttachment) || isMessageDraftTooLong || !!attachmentError"
                              >
                                <span
                                    v-if="sendingMessage"
                                    class="spinner-border spinner-border-sm me-2 align-middle"
                                    role="status"
                                ></span>
                                  {{ sendingMessage ? (selectedAttachment ? "Uploading..." : "Sending...") : "Send" }}
                              </button>
                        </div>
                        <small v-if="isMessageDraftTooLong" class="text-danger mt-2 d-block">
                            Message is too long.
                        </small>
                    </form>
                    <transition name="fade">
                        <div
                            v-if="showPinnedMessagesPanel"
                            class="pinned-messages-panel pinned-messages-panel--floating"
                            @click.stop
                        >
                            <div class="pinned-messages-panel__header">
                                <div>
                                    <div class="pinned-messages-panel__title">
                                        Pinned messages
                                    </div>
                                    <small class="theme-muted">
                                        {{ pinnedMessages.length }}/{{ pinnedMessageLimit }} pinned
                                    </small>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-link p-0 pinned-messages-panel__close"
                                    @click="showPinnedMessagesPanel = false"
                                    title="Close pins"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div
                                v-if="pinnedMessages.length"
                                class="pinned-messages-panel__list"
                            >
                                <button
                                    v-for="pin in sortedPinnedMessages"
                                    :key="pin.message_id"
                                    type="button"
                                    class="pinned-messages-panel__item"
                                    @click="scrollToPinnedMessage(pin)"
                                >
                                    <div class="pinned-messages-panel__item-meta">
                                        <div class="pinned-messages-panel__item-preview">
                                            {{ pin.preview }}
                                        </div>
                                        <small class="theme-muted">
                                            Pinned {{ formatPinnedAt(pin.pinned_at || pin.created_at) }}
                                        </small>
                                    </div>
                                    <div class="pinned-messages-panel__item-actions">
                                        <div
                                            class="pinned-messages-panel__item-action pinned-messages-panel__item-action--unpin"
                                            role="button"
                                            tabindex="0"
                                            @click.stop="unpinPinnedMessage(pin)"
                                            @keyup.enter.stop="unpinPinnedMessage(pin)"
                                            title="Remove pin"
                                            aria-label="Remove pin"
                                        >
                                            <i class="fa-solid fa-thumbtack-slash"></i>
                                        </div>
                                        <div class="pinned-messages-panel__item-action">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </div>
                                    </div>
                                </button>
                            </div>
                            <div
                                v-else
                                class="pinned-messages-panel__empty theme-muted"
                            >
                                No pinned messages yet.
                            </div>
                        </div>
                    </transition>
                    <transition name="fade">
                        <div
                            v-if="activeReactionTargetMessage"
                            class="reaction-overlay reaction-overlay--panel"
                            @click.self="activeReactionPickerId = null"
                        >
                            <div class="reaction-picker reaction-picker--centered" @click.stop>
                                <button
                                    v-for="reaction in reactionOptions"
                                    :key="reaction.key"
                                    type="button"
                                    class="reaction-picker__btn"
                                    :style="{ color: reaction.color, backgroundColor: reaction.bg }"
                                    :title="reaction.label"
                                    :aria-label="reaction.label"
                                    @click="setReaction(activeReactionTargetMessage, reaction.key)"
                                >
                                    <span class="reaction-picker__glyph">{{ reaction.glyph }}</span>
                                </button>
                            </div>
                        </div>
                    </transition>
                    <teleport to="body">
                        <transition name="fade">
                            <div
                                v-if="activeMessageActionTargetMessage"
                                class="message-action-menu-layer"
                                @click="activeMessageActionsId = null"
                            >
                                <div
                                    class="message-action-menu message-action-menu--floating"
                                    :style="messageActionMenuStyle"
                                    @click.stop
                                >
                                    <button
                                        v-if="activeMessageActionTargetMessage.is_mine && activeMessageActionTargetMessage.body"
                                        type="button"
                                        class="message-action-menu__item message-action-menu__item--primary"
                                        @click.stop="editMessage(activeMessageActionTargetMessage)"
                                    >
                                        <span class="message-action-menu__icon">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </span>
                                        <span class="message-action-menu__content">
                                            <span class="message-action-menu__label">Edit</span>
                                            <small class="message-action-menu__hint">Edit message</small>
                                        </span>
                                    </button>
                                    <button
                                        v-if="activeMessageActionTargetMessage.is_mine"
                                        type="button"
                                        class="message-action-menu__item message-action-menu__item--danger"
                                        @click.stop="unsendMessage(activeMessageActionTargetMessage)"
                                    >
                                        <span class="message-action-menu__icon">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </span>
                                        <span class="message-action-menu__content">
                                            <span class="message-action-menu__label">Unsend</span>
                                            <small class="message-action-menu__hint">Remove for everyone</small>
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="message-action-menu__item"
                                        :class="{ 'is-active': isMessagePinned(activeMessageActionTargetMessage.id) }"
                                        @click.stop="togglePinMessage(activeMessageActionTargetMessage)"
                                        :disabled="!isMessagePinned(activeMessageActionTargetMessage.id) && pinnedMessages.length >= pinnedMessageLimit"
                                    >
                                        <span class="message-action-menu__icon">
                                            <i :class="isMessagePinned(activeMessageActionTargetMessage.id) ? 'fa-solid fa-thumbtack-slash' : 'fa-solid fa-thumbtack'"></i>
                                        </span>
                                        <span class="message-action-menu__content">
                                            <span class="message-action-menu__label">{{ isMessagePinned(activeMessageActionTargetMessage.id) ? 'Unpin' : 'Pin' }}</span>
                                            <small class="message-action-menu__hint">
                                                {{ isMessagePinned(activeMessageActionTargetMessage.id) ? 'Remove from pinned' : 'Keep it easy to find' }}
                                            </small>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </teleport>
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
                        <div
                            v-if="showDockTypingIndicator"
                            class="message-dock__typing-indicator"
                        >
                            <span class="message-dock__typing-dots">
                                <span></span><span></span><span></span>
                            </span>
                            <small class="message-dock__typing-text">typing...</small>
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
        <ReactionModal
            :is-open="showReactionsModal"
            :reactions="reactionsModalData"
            :current-user-id="userId"
            :reaction-options="reactionOptions.map((reaction) => ({ ...reaction, emoji: reaction.glyph }))"
            @close="closeReactionsModal"
        />
        <transition name="fade">
            <div
                v-if="messageActionModalVisible"
                class="message-action-modal-backdrop"
                @click.self="closeMessageActionModal"
            >
                <div
                    class="message-action-modal"
                    :class="{
                        'message-action-modal--edit':
                            messageActionModalMode === 'edit',
                        'message-action-modal--confirm':
                            messageActionModalMode === 'unsend',
                    }"
                    role="dialog"
                    aria-modal="true"
                    tabindex="-1"
                    @keydown.esc.prevent="closeMessageActionModal"
                >
                    <div
                        class="message-action-modal__header"
                        :class="{
                            'message-action-modal__header--edit':
                                messageActionModalMode === 'edit',
                            'message-action-modal__header--confirm':
                                messageActionModalMode === 'unsend',
                        }"
                    >
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge"
                                :class="
                                    messageActionModalMode === 'edit'
                                        ? 'message-action-modal__badge--edit'
                                        : 'message-action-modal__badge--danger'
                                "
                            >
                                <i
                                    :class="
                                        messageActionModalMode === 'edit'
                                            ? 'fa-regular fa-pen-to-square'
                                            : 'fa-regular fa-trash-can'
                                    "
                                ></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                {{
                                    messageActionModalMode === "edit"
                                        ? "Custom editor"
                                        : "Confirmation"
                                }}
                            </div>
                            <h3 class="message-action-modal__title">
                                {{
                                    messageActionModalMode === "edit"
                                        ? "Edit this message"
                                        : "Unsend this message?"
                                }}
                            </h3>
                            <p class="message-action-modal__subtitle">
                                {{
                                    messageActionModalMode === "edit"
                                        ? "Update the message text below and save when you are ready."
                                        : "This removes the message for everyone in this conversation."
                                }}
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

                    <div
                        class="message-action-modal__body"
                        :class="{
                            'message-action-modal__body--edit':
                                messageActionModalMode === 'edit',
                            'message-action-modal__body--confirm':
                                messageActionModalMode === 'unsend',
                        }"
                    >
                        <template v-if="messageActionModalMode === 'edit'">
                            <textarea
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
                            <div
                                class="message-action-modal__context"
                                :class="{
                                    'message-action-modal__context--confirm':
                                        messageActionModalMode === 'unsend',
                                }"
                            >
                                <div class="message-action-modal__context-label">
                                    Message to unsend
                                </div>
                                <div class="message-action-modal__preview">
                                    {{ messageActionModalMessagePreview }}
                                </div>
                            </div>
                        </template>

                        <p
                            v-if="messageActionModalError"
                            class="message-action-modal__error"
                        >
                            {{ messageActionModalError }}
                        </p>
                    </div>

                    <div
                        class="message-action-modal__footer"
                        :class="{
                            'message-action-modal__footer--edit':
                                messageActionModalMode === 'edit',
                            'message-action-modal__footer--confirm':
                                messageActionModalMode === 'unsend',
                        }"
                    >
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
                            :class="
                                messageActionModalMode === 'edit'
                                    ? 'message-action-modal__btn--primary'
                                    : 'message-action-modal__btn--danger'
                            "
                            :disabled="messageActionModalSaving || messageActionModalSubmitDisabled"
                            @click="submitMessageActionModal"
                        >
                            <span
                                v-if="messageActionModalSaving"
                                class="spinner-border spinner-border-sm"
                                aria-hidden="true"
                            ></span>
                            <span v-else>
                                {{
                                    messageActionModalMode === "edit"
                                        ? "Save changes"
                                        : "Unsend message"
                                }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
        <div ref="imageGalleryContainer" class="d-none"></div>
    </div>
</template>

<script>
import axios from "axios";
import ReactionModal from "../../../employee/messages/components/ReactionModal.vue";

export default {
    name: "OnlineUsers",
    components: {
        ReactionModal,
    },
    props: {
        userId: {
            type: Number,
            default: null,
        },
        userRole: {
            type: String,
            default: "employee",
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
            attachmentMaxSizeBytes: 5 * 1024 * 1024,
            allowedAttachmentExtensions: [
                "jpg",
                "jpeg",
                "png",
                "gif",
                "doc",
                "docx",
                "docs",
                "pdf",
                "xlsx",
                "txt",
            ],
            selectedAttachment: null,
            selectedAttachmentPreviewUrl: null,
            attachmentError: "",
            messageChannel: null,
            imageGalleryInstance: null,
            replyTargetMessage: null,
            receiveSound: null,
            sendSound: null,
            sharedOnlineUsersListener: null,
            reactionOptions: [
                {
                    key: "like",
                    label: "Like",
                    glyph: "👍",
                    color: "#1877f2",
                    bg: "rgba(24, 119, 242, 0.12)",
                },
                {
                    key: "number-one",
                    label: "One DOST",
                    glyph: "☝️",
                    color: "#ff9f1c",
                    bg: "rgba(255, 159, 28, 0.14)",
                },
                {
                    key: "love",
                    label: "Love",
                    glyph: "❤️",
                    color: "#f33f58",
                    bg: "rgba(243, 63, 88, 0.12)",
                },
                {
                    key: "haha",
                    label: "Haha",
                    glyph: "😆",
                    color: "#f7b125",
                    bg: "rgba(247, 177, 37, 0.14)",
                },
                {
                    key: "sad",
                    label: "Sad",
                    glyph: "😢",
                    color: "#5d6cff",
                    bg: "rgba(93, 108, 255, 0.12)",
                },
                {
                    key: "angry",
                    label: "Angry",
                    glyph: "😡",
                    color: "#f24d3d",
                    bg: "rgba(242, 77, 61, 0.12)",
                },
            ],
            typingIndicator: false,
            typingStateActive: false,
            typingStopTimer: null,
            typingIndicatorTimer: null,
            showScrollToBottomButton: false,
            highlightedMessageId: null,
            highlightPulseToken: 0,
            highlightMessageTimer: null,
            highlightAnimationTimer: null,
            pinnedMessages: [],
            showPinnedMessagesPanel: false,
            pinnedMessageLimit: 10,
            pinError: "",
            pinErrorTimer: null,
            showPinLimitPopup: false,
            pinLimitPopupTimer: null,
            activeReactionPickerId: null,
            activeMessageActionsId: null,
            messageActionMenuPosition: {
                top: 0,
                left: 0,
            },
            messageReactionsKey: `direct_message_reactions_${localStorage.getItem("auth_user_id") || "guest"}`,
            messageReactions: {},
            pinnedMessagesKey: null,
            dockStateKey: `message_dock_state_${localStorage.getItem("auth_user_id") || "guest"}`,
            showReactionsModal: false,
            reactionsModalData: [],
            messageActionModalVisible: false,
            messageActionModalMode: "edit",
            messageActionModalMessage: null,
            messageActionModalBody: "",
            messageActionModalError: "",
            messageActionModalSaving: false,
            isInitialized: false,
            pageLoadListener: null,
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
        conversationStatusLabel() {
            if (!this.selectedUserState) {
                return "";
            }

            if (this.showScrollToBottomButton) {
                return "New messages below";
            }

            if (this.typingIndicator) {
                return "typing...";
            }

            return this.selectedUserState.statusLabel;
        },
        showDockTypingIndicator() {
            return this.typingIndicator && !this.messagePanelOpen;
        },
        messageDraftLength() {
            return this.messageDraft.length;
        },
        isMessageDraftTooLong() {
            return this.messageDraftLength > this.messageMaxChars;
        },
        attachmentAccept() {
            return ".jpg,.jpeg,.png,.gif,.doc,.docx,.docs,.pdf,.xlsx,.txt";
        },
        selectedAttachmentIsImage() {
            if (!this.selectedAttachment) {
                return false;
            }

            const extension = this.getFileExtension(this.selectedAttachment.name);
            return ["jpg", "jpeg", "png", "gif"].includes(extension);
        },
        selectedAttachmentIconClass() {
            if (!this.selectedAttachment) {
                return "fa-solid fa-file";
            }

            const extension = this.getFileExtension(this.selectedAttachment.name);
            return this.getAttachmentIconClass({ extension });
        },
        activeReactionTargetMessage() {
            if (!this.activeReactionPickerId) {
                return null;
            }

            return (
                this.conversationMessages.find(
                    (message) => message.id === this.activeReactionPickerId,
                ) || null
            );
        },
        activeMessageActionTargetMessage() {
            if (!this.activeMessageActionsId) {
                return null;
            }

            return (
                this.conversationMessages.find(
                    (message) => message.id === this.activeMessageActionsId,
                ) || null
            );
        },
        messageActionMenuStyle() {
            return {
                top: `${this.messageActionMenuPosition.top}px`,
                left: `${this.messageActionMenuPosition.left}px`,
            };
        },
        unreadMessageCount() {
            if (this.messagePanelOpen && this.selectedUserState) {
                return 0;
            }

            return this.conversationMessages.filter(
                (message) => !message.is_mine && !message.read_at,
            ).length;
        },
        latestSeenReceiptMessageId() {
            for (let index = this.conversationMessages.length - 1; index >= 0; index -= 1) {
                const message = this.conversationMessages[index];

                if (message?.is_mine && message?.read_at) {
                    return Number(message.id);
                }
            }

            return null;
        },
        sortedPinnedMessages() {
            return [...this.pinnedMessages].sort((a, b) => {
                const aPinnedAt = new Date(a?.pinned_at || a?.created_at || 0).getTime();
                const bPinnedAt = new Date(b?.pinned_at || b?.created_at || 0).getTime();

                if (aPinnedAt !== bPinnedAt) {
                    return bPinnedAt - aPinnedAt;
                }

                return (b?.message_id || 0) - (a?.message_id || 0);
            });
        },
        messageActionModalMessagePreview() {
            return this.getMessageSnippet(this.messageActionModalMessage);
        },
        messageActionModalSubmitDisabled() {
            if (this.messageActionModalMode === "edit") {
                const trimmedBody = this.messageActionModalBody.trim();
                const originalBody = (this.messageActionModalMessage?.body || "").trim();
                return !trimmedBody || trimmedBody === originalBody;
            }

            return false;
        },
    },
    mounted() {
        this.hydrateOnlineUsersFromSharedState();
        this.bindSharedOnlineUsersListener();
        this.sendSound = new Audio("/sounds/sent.mp3");
        this.sendSound.preload = "auto";

        this.pageLoadListener = () => {
            this.initializeOnlineUsers();
        };

        if (document.readyState === "complete") {
            this.initializeOnlineUsers();
            return;
        }

        window.addEventListener("load", this.pageLoadListener, { once: true });
    },
    beforeUnmount() {
        this.unbindSharedOnlineUsersListener();

        if (this.pageLoadListener) {
            window.removeEventListener("load", this.pageLoadListener);
            this.pageLoadListener = null;
        }

        if (this.isInitialized) {
            this.leaveDirectMessageChannel();
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
            }
            this.clearTypingTimers();
            this.clearHighlightTimer();
            this.clearPinErrorTimer();
            this.clearPinLimitPopupTimer();
            this.clearSelectedAttachment(false);
            this.destroyImageGallery();
        }
    },
    methods: {
        initializeOnlineUsers() {
            if (this.isInitialized) {
                return;
            }

            this.isInitialized = true;

            this.hydrateOnlineUsersFromSharedState();

            const onlineUsersChannel = window.Echo.join("online-users")
                .here((users) => {
                    this.onlineUserIds = users.map((user) => Number(user.id));
                    this.saveLastSeen(this.onlineUserIds);
                    this.syncUsersOnlineState();
                })
                .joining((user) => {
                    const userId = Number(user.id);

                    if (!this.onlineUserIds.includes(userId)) {
                        this.onlineUserIds.push(userId);
                    }
                    this.markUserSeen(userId);
                    this.syncUsersOnlineState();
                })
                .leaving((user) => {
                    const userId = Number(user.id);

                    this.onlineUserIds = this.onlineUserIds.filter(
                        (id) => id !== userId,
                    );
                    this.markUserSeen(userId);
                    this.syncUsersOnlineState();
                });

            onlineUsersChannel.listen(".online-users.updated", (event) => {
                const payload = event?.payload || event || {};
                const presenceUser = payload.user || null;
                const status = payload.status || "";
                const userId = Number(presenceUser?.id || 0);

                if (!userId) {
                    return;
                }

                if (status === "online") {
                    if (!this.onlineUserIds.includes(userId)) {
                        this.onlineUserIds.push(userId);
                    }
                } else if (status === "offline") {
                    this.onlineUserIds = this.onlineUserIds.filter(
                        (id) => id !== userId,
                    );
                }

                this.markUserSeen(userId);
                this.syncUsersOnlineState();
            });

            this.loadUsers();
            this.restoreDockState();
            this.loadMessageReactions();
            this.statusInterval = setInterval(() => {
                this.syncUsersOnlineState();
            }, 60000);

            this.subscribeToDirectMessages();
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

            window.addEventListener("online-users:updated", this.sharedOnlineUsersListener);
        },
        unbindSharedOnlineUsersListener() {
            if (!this.sharedOnlineUsersListener) {
                return;
            }

            window.removeEventListener("online-users:updated", this.sharedOnlineUsersListener);
            this.sharedOnlineUsersListener = null;
        },
        async loadUsers() {
            this.loadingUsers = true;

            try {
                const { data } = await axios.get("/api/users", {
                    headers: this.token
                        ? { Authorization: `Bearer ${this.token}` }
                        : {},
                });

                this.users = data.map((user) => {
                    const activityState = this.getActivityState(user.id);

                    return {
                        ...user,
                        isSelf: this.userId ? user.id === this.userId : false,
                        isOnline: activityState.isOnline,
                        lastSeenAt: activityState.lastSeenAt,
                        statusLabel: activityState.label,
                    };
                })
                .filter((user) => !user.isSelf);
            } catch (error) {
                console.error("Failed to load online users list:", error);
                this.users = [];
            } finally {
                this.loadingUsers = false;
            }
        },
        syncUsersOnlineState() {
            this.users = this.users.map((user) => {
                const activityState = this.getActivityState(user.id);

                return {
                    ...user,
                    isOnline: activityState.isOnline,
                    lastSeenAt: activityState.lastSeenAt,
                    statusLabel: activityState.label,
                };
            });
        },
        async openMessageBox(user) {
            this.hideUsersDropdown();
            this.chatDockVisible = true;
            this.selectedUser = user;
            this.messagePanelOpen = true;
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.saveDockState();
            this.resetConversationState();
            this.messageDraft = "";
            this.replyTargetMessage = null;
            this.clearSelectedAttachment();
            this.attachmentError = "";
            this.activeReactionPickerId = null;
            this.activeMessageActionsId = null;
            this.clearHighlightTimer();
            this.highlightedMessageId = null;
            this.showPinnedMessagesPanel = false;
            this.pinError = "";
            this.showPinLimitPopup = false;
            this.clearPinLimitPopupTimer();
            this.pinnedMessages = [];
            await this.loadConversation({ page: 1, reset: true });
            this.scrollConversationToBottom();
            if (this.canMarkConversationSeen(user.id)) {
                this.markConversationSeen(user.id);
            }
        },
        closeMessageBox() {
            this.messagePanelOpen = false;
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.activeReactionPickerId = null;
            this.activeMessageActionsId = null;
            this.clearHighlightTimer();
            this.highlightedMessageId = null;
            this.showPinnedMessagesPanel = false;
            this.pinError = "";
            this.showPinLimitPopup = false;
            this.clearPinLimitPopupTimer();
            this.pinnedMessages = [];
            this.saveDockState();
            this.attachmentError = "";
        },
        openMessagesPage() {
            this.closeMessageBox();
            window.location.href = this.userRole === "admin" ? "/admin/messages" : "/employee/messages";
        },
        hideChatDock() {
            this.messagePanelOpen = false;
            this.chatDockVisible = false;
            this.selectedUser = null;
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.activeReactionPickerId = null;
            this.activeMessageActionsId = null;
            this.clearHighlightTimer();
            this.highlightedMessageId = null;
            this.showPinnedMessagesPanel = false;
            this.pinError = "";
            this.showPinLimitPopup = false;
            this.clearPinLimitPopupTimer();
            this.pinnedMessages = [];
            this.resetConversationState();
            this.messageDraft = "";
            this.replyTargetMessage = null;
            this.clearSelectedAttachment();
            this.attachmentError = "";
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
            this.clearSelectedAttachment();
            this.attachmentError = "";
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.saveDockState();
            this.showPinnedMessagesPanel = false;
            this.pinError = "";
            this.showPinLimitPopup = false;
            this.clearPinLimitPopupTimer();
            this.pinnedMessages = [];
            this.loadConversation({ page: 1, reset: true }).then(() => {
                this.scrollConversationToBottom();
                if (this.canMarkConversationSeen(this.selectedUserState?.id)) {
                    this.markConversationSeen(this.selectedUserState?.id);
                }
            });
        },
        resetConversationState() {
            this.conversationMessages = [];
            this.loadingConversation = false;
            this.loadingOlderConversation = false;
            this.conversationPage = 1;
            this.conversationLastPage = 1;
            this.conversationHasMore = true;
            this.showScrollToBottomButton = false;
            this.pinnedMessages = [];
            this.clearHighlightTimer();
            this.highlightedMessageId = null;
        },
        clearPinErrorTimer() {
            if (this.pinErrorTimer) {
                clearTimeout(this.pinErrorTimer);
                this.pinErrorTimer = null;
            }
        },
        clearPinLimitPopupTimer() {
            if (this.pinLimitPopupTimer) {
                clearTimeout(this.pinLimitPopupTimer);
                this.pinLimitPopupTimer = null;
            }
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

                const messages = Array.isArray(data.messages)
                    ? data.messages.map((message) =>
                          this.normalizeConversationMessage(message),
                      )
                    : [];
                const pagination = data.pagination ?? {};
                const pinnedMessages = data.pinned_messages ?? [];
                this.conversationPage = pagination.current_page ?? page;
                this.conversationLastPage = pagination.last_page ?? page;
                this.conversationHasMore = Boolean(pagination.has_more);
                this.pinnedMessages = Array.isArray(pinnedMessages) ? pinnedMessages : [];

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
                        this.updateScrollToBottomButton(bodyEl);
                        return;
                    }

                    if (page === 1 || isInitialLoad) {
                        bodyEl.scrollTop = bodyEl.scrollHeight;
                        this.showScrollToBottomButton = false;
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
            this.updateScrollToBottomButton(body);
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
            if ((!body && !this.selectedAttachment) || !activeUser) return;
            if (body.length > this.messageMaxChars) return;

            const selectedUserId = activeUser.id;
            this.sendingMessage = true;
            this.clearTypingTimers();
            this.typingIndicator = false;

            try {
                const formData = new FormData();
                formData.append("recipient_id", selectedUserId);
                formData.append("body", body);

                if (this.replyTargetMessage?.id) {
                    formData.append("reply_to_id", this.replyTargetMessage.id);
                }

                if (this.selectedAttachment) {
                    formData.append("attachment", this.selectedAttachment);
                }

                const { data } = await axios.post(
                    "/api/direct-messages",
                    formData,
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
                        read_at: null,
                    });
                    this.playSendSound();
                    this.messageDraft = "";
                    this.replyTargetMessage = null;
                    this.clearSelectedAttachment();
                    this.attachmentError = "";

                    this.$nextTick(() => {
                        const bodyEl = this.$el.querySelector(".message-panel__body");
                        if (bodyEl) {
                            bodyEl.scrollTop = bodyEl.scrollHeight;
                            this.showScrollToBottomButton = false;
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
                            this.applyServerMessageUpdate({
                                ...message,
                                is_mine: true,
                                read_at: null,
                            });

                            this.$nextTick(() => {
                                const bodyEl = this.$el.querySelector(".message-panel__body");
                                if (bodyEl) {
                                    bodyEl.scrollTop = bodyEl.scrollHeight;
                                    this.showScrollToBottomButton = false;
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
                    this.typingIndicator = false;
                    if (this.typingIndicatorTimer) {
                        clearTimeout(this.typingIndicatorTimer);
                        this.typingIndicatorTimer = null;
                    }

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

                    this.applyServerMessageUpdate({
                        ...message,
                        is_mine: false,
                    });
                    this.loadingConversation = false;

                    if (this.canMarkConversationSeen(partnerId)) {
                        this.markConversationSeen(partnerId);
                    }

                    this.scrollConversationToBottom();
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
                    const messageIds = new Set(payload.message_ids.map((id) => Number(id)));

                    this.conversationMessages = this.conversationMessages.map((message) => {
                        if (!messageIds.has(Number(message.id))) {
                            return message;
                        }

                        return {
                            ...message,
                            read_at: readAt,
                        };
                    });
                })
                .listen(".direct-message.updated", (event) => {
                    const payload = event?.payload || {};
                    const message = payload.message || null;
                    if (!message) return;

                    const partnerId =
                        message.sender_id === this.userId
                            ? message.recipient_id
                            : message.sender_id;

                    if (!this.selectedUserState || this.selectedUserState.id !== partnerId) {
                        return;
                    }

                    this.applyServerMessageUpdate(message, payload.pinned_messages || null);
                })
                .listen(".direct-message.typing", (event) => {
                    const payload = event?.payload;
                    if (!payload || !payload.is_typing) return;

                    if (!this.selectedUserState || this.selectedUserState.id !== payload.sender_id) {
                        return;
                    }

                    this.typingIndicator = true;
                    if (this.typingIndicatorTimer) {
                        clearTimeout(this.typingIndicatorTimer);
                    }

                    this.typingIndicatorTimer = setTimeout(() => {
                        this.typingIndicator = false;
                        this.typingIndicatorTimer = null;
                    }, 2500);

                    this.$nextTick(() => {
                        const bodyEl = this.$refs.conversationBody || this.$el.querySelector(".message-panel__body");
                        if (!bodyEl) {
                            return;
                        }

                        if (this.isConversationNearBottom(bodyEl, 100)) {
                            this.scrollConversationToBottom();
                            return;
                        }

                        this.updateScrollToBottomButton(bodyEl);
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
                    this.showScrollToBottomButton = false;
                }
            });
        },
        updateScrollToBottomButton(bodyEl) {
            if (!bodyEl) return;

            const distanceFromBottom =
                bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight;

            this.showScrollToBottomButton = distanceFromBottom > 220;
        },
        handleAttachmentMediaLoad() {
            const bodyEl = this.$refs.conversationBody || this.$el.querySelector(".message-panel__body");
            if (!bodyEl || !this.messagePanelOpen) {
                return;
            }

            if (!this.isConversationNearBottom(bodyEl)) {
                this.updateScrollToBottomButton(bodyEl);
                return;
            }

            this.scrollConversationToBottom();
        },
        isConversationNearBottom(bodyEl, threshold = 220) {
            if (!bodyEl) return true;

            const distanceFromBottom =
                bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight;

            return distanceFromBottom <= threshold;
        },
        handleInterfaceClick() {
            if (!this.canMarkConversationSeen()) {
                return;
            }

            this.activeReactionPickerId = null;
            this.showPinnedMessagesPanel = false;
            this.markConversationSeen();
        },
        handleMessageInput() {
            const activeUser = this.selectedUserState;
            if (!activeUser) return;

            if (!this.messageDraft.trim()) {
                this.sendTypingState(false);
                return;
            }

            this.sendTypingState(true);
        },
        handleMessageBlur() {
            this.sendTypingState(false);
        },
        clearTypingTimers() {
            if (this.typingStopTimer) {
                clearTimeout(this.typingStopTimer);
                this.typingStopTimer = null;
            }

            if (this.typingIndicatorTimer) {
                clearTimeout(this.typingIndicatorTimer);
                this.typingIndicatorTimer = null;
            }

            this.typingStateActive = false;
        },
        sendTypingState(isTyping) {
            const activeUser = this.selectedUserState;
            if (!activeUser) return;

            if (isTyping) {
                if (this.typingStateActive) {
                    if (this.typingStopTimer) {
                        clearTimeout(this.typingStopTimer);
                    }

                    this.typingStopTimer = setTimeout(() => {
                        this.sendTypingState(false);
                    }, 1800);

                    return;
                }

                axios.post(
                    `/api/direct-messages/${activeUser.id}/typing`,
                    { is_typing: true },
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                ).catch((error) => {
                    console.error("Failed to send typing state:", error);
                });

                this.typingStateActive = true;
                if (this.typingStopTimer) {
                    clearTimeout(this.typingStopTimer);
                }

                this.typingStopTimer = setTimeout(() => {
                    this.sendTypingState(false);
                }, 1800);

                return;
            }

            if (!this.typingStateActive) {
                return;
            }

            if (this.typingStopTimer) {
                clearTimeout(this.typingStopTimer);
                this.typingStopTimer = null;
            }

            this.typingStateActive = false;

            axios.post(
                `/api/direct-messages/${activeUser.id}/typing`,
                { is_typing: false },
                {
                    headers: this.token
                        ? { Authorization: `Bearer ${this.token}` }
                        : {},
                },
            ).catch((error) => {
                console.error("Failed to clear typing state:", error);
            });
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
                    this.receiveSound = new Audio("/sounds/receive.mp3");
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
        playSendSound() {
            try {
                if (!this.sendSound) {
                    this.sendSound = new Audio("/sounds/sent.mp3");
                    this.sendSound.preload = "auto";
                }

                this.sendSound.currentTime = 0;
                const played = this.sendSound.play();

                if (played && typeof played.catch === "function") {
                    played.catch(() => {});
                }
            } catch (error) {
                console.error("Failed to play send sound:", error);
            }
        },
        destroyImageGallery() {
            if (this.imageGalleryInstance) {
                this.imageGalleryInstance.destroy();
                this.imageGalleryInstance = null;
            }
        },
        loadMessageReactions() {
            try {
                this.messageReactions = JSON.parse(localStorage.getItem(this.messageReactionsKey) || "{}");
            } catch (error) {
                this.messageReactions = {};
            }
        },
        saveMessageReactions() {
            try {
                localStorage.setItem(this.messageReactionsKey, JSON.stringify(this.messageReactions));
            } catch (error) {
                console.error("Failed to save message reactions:", error);
            }
        },
        applyServerMessageUpdate(message, pinnedMessages = null) {
            if (!message?.id) {
                return;
            }

            const existingMessage = this.conversationMessages.find(
                (item) => Number(item.id) === Number(message.id),
            );

            const senderId = message?.sender_id ?? existingMessage?.sender_id ?? null;
            const recipientId = message?.recipient_id ?? existingMessage?.recipient_id ?? null;
            const isUnsent = Boolean(message?.is_unsent);

            const normalizedMessage = this.normalizeConversationMessage({
                ...existingMessage,
                ...message,
                sender_id: senderId,
                recipient_id: recipientId,
                body: isUnsent ? null : message?.body ?? existingMessage?.body ?? null,
                attachment: isUnsent ? null : message?.attachment ?? existingMessage?.attachment ?? null,
                edited_at: isUnsent ? null : message?.edited_at ?? existingMessage?.edited_at ?? null,
                pinned_at: isUnsent ? null : message?.pinned_at ?? existingMessage?.pinned_at ?? null,
                pinned_by_id: isUnsent ? null : message?.pinned_by_id ?? existingMessage?.pinned_by_id ?? null,
                is_pinned: isUnsent ? false : Boolean(message?.is_pinned ?? existingMessage?.is_pinned),
                reaction: isUnsent ? null : message?.reaction ?? existingMessage?.reaction ?? null,
                reactions: isUnsent
                    ? []
                    : Array.isArray(message?.reactions)
                        ? message.reactions
                        : Array.isArray(existingMessage?.reactions)
                            ? existingMessage.reactions
                            : [],
                is_mine: senderId !== null
                    ? Number(senderId) === Number(this.userId)
                    : Boolean(existingMessage?.is_mine),
            });

            this.upsertConversationMessage(normalizedMessage);

            if (Array.isArray(pinnedMessages)) {
                this.pinnedMessages = pinnedMessages;
            } else if (isUnsent) {
                this.pinnedMessages = this.pinnedMessages.filter(
                    (pin) => Number(pin?.message_id) !== Number(message.id),
                );
            }
        },
        getPinStorageKey(userId = null) {
            const currentUserId = this.userId || localStorage.getItem("auth_user_id") || "guest";
            const selectedUserId = userId ?? this.selectedUserState?.id ?? this.selectedUser?.id ?? null;

            if (!selectedUserId) {
                return null;
            }

            return `direct_message_pins_${[currentUserId, selectedUserId].sort().join("_")}`;
        },
        loadPinnedMessages(userId = null) {
            try {
                const key = this.getPinStorageKey(userId);
                this.pinnedMessagesKey = key;

                if (!key) {
                    this.pinnedMessages = [];
                    return;
                }

                const storedPins = JSON.parse(localStorage.getItem(key) || "[]");
                this.pinnedMessages = Array.isArray(storedPins)
                    ? storedPins.filter((pin) => pin?.message_id)
                    : [];
            } catch (error) {
                this.pinnedMessages = [];
            }
        },
        savePinnedMessages() {
            try {
                const key = this.pinnedMessagesKey || this.getPinStorageKey();
                if (!key) return;

                localStorage.setItem(key, JSON.stringify(this.pinnedMessages));
            } catch (error) {
                console.error("Failed to save pinned messages:", error);
            }
        },
        togglePinnedMessagesPanel() {
            if (!this.selectedUserState) {
                return;
            }

            this.showPinnedMessagesPanel = !this.showPinnedMessagesPanel;
        },
        toggleMessageActions(message, event = null) {
            if (!message?.id || message.is_unsent || message.is_system) {
                return;
            }

            this.activeReactionPickerId = null;

            if (this.activeMessageActionsId === message.id) {
                this.activeMessageActionsId = null;
                return;
            }

            this.positionMessageActionMenu(event?.currentTarget || event?.target);
            this.activeMessageActionsId = message.id;
        },
        positionMessageActionMenu(triggerElement) {
            if (!triggerElement?.getBoundingClientRect) {
                return;
            }

            const rect = triggerElement.getBoundingClientRect();
            const menuWidth = 200;
            const sidePadding = 16;
            const minLeft = sidePadding + menuWidth / 2;
            const maxLeft = Math.max(
                minLeft,
                window.innerWidth - sidePadding - menuWidth / 2,
            );
            const centeredLeft = rect.left + rect.width / 2;

            this.messageActionMenuPosition = {
                top: Math.max(12, rect.top - 10),
                left: Math.min(maxLeft, Math.max(minLeft, centeredLeft)),
            };
        },
        getMessageSnippet(message) {
            if (!message) {
                return "Attachment";
            }

            if (message.is_system) {
                return this.formatSystemMessage(message);
            }

            if (message.is_unsent) {
                return "Unsent Message";
            }

            if (message.body) {
                return message.body;
            }

            if (message.attachment?.name) {
                return message.attachment.name;
            }

            return "Attachment";
        },
        isMessagePinned(messageId) {
            return this.conversationMessages.some(
                (message) => message.id === messageId && Boolean(message.pinned_at),
            );
        },
        async togglePinMessage(message) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.clearPinErrorTimer();
            this.pinError = "";
            this.activeMessageActionsId = null;

            const isPinned = Boolean(message.pinned_at);

            try {
                const { data } = await axios.patch(
                    `/api/direct-messages/${message.id}/pin`,
                    {
                        is_pinned: !isPinned,
                    },
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                const updatedMessage = data?.message ?? null;
                const pinnedMessages = data?.pinned_messages ?? null;
                this.applyServerMessageUpdate(updatedMessage, pinnedMessages);
            } catch (error) {
                const status = error?.response?.status;
                const responseMessage = error?.response?.data?.message || "";

                if (status === 422) {
                    this.showPinLimitPopup = true;
                    this.clearPinLimitPopupTimer();
                    this.pinLimitPopupTimer = window.setTimeout(() => {
                        this.showPinLimitPopup = false;
                        this.pinLimitPopupTimer = null;
                    }, 2400);

                    this.pinError = responseMessage || `You’ve reached the pin limit of ${this.pinnedMessageLimit}.`;
                    this.clearPinErrorTimer();
                    this.pinErrorTimer = window.setTimeout(() => {
                        this.pinError = "";
                        this.pinErrorTimer = null;
                    }, 2200);
                    return;
                }

                console.error("Failed to update pin:", error);
            }
        },
        async scrollToPinnedMessage(pin) {
            if (!pin?.message_id) return;

            this.showPinnedMessagesPanel = false;
            await this.scrollToMessage(pin.message_id);
        },
        async unpinPinnedMessage(pin) {
            if (!pin?.message_id) return;

            const message = this.conversationMessages.find((item) => item.id === pin.message_id);
            if (!message) return;

            if (message.is_unsent || message.is_system) {
                return;
            }

            await this.togglePinMessage({
                ...message,
                pinned_at: message.pinned_at || pin.pinned_at || null,
            });
        },
        async editMessage(message) {
            if (!message?.id || !message.is_mine || message.is_unsent || message.is_system) {
                return;
            }

            this.activeMessageActionsId = null;
            if (!(message.body || "").trim()) {
                return;
            }

            this.openMessageActionModal("edit", message);
        },
        async unsendMessage(message) {
            if (!message?.id || !message.is_mine || message.is_unsent || message.is_system) {
                return;
            }

            this.activeMessageActionsId = null;
            this.openMessageActionModal("unsend", message);
        },
        openMessageActionModal(mode, message) {
            if (!message?.id) {
                return;
            }

            this.messageActionModalMode = mode === "unsend" ? "unsend" : "edit";
            this.messageActionModalMessage = message;
            this.messageActionModalBody = message.body || "";
            this.messageActionModalError = "";
            this.messageActionModalSaving = false;
            this.messageActionModalVisible = true;

            this.$nextTick(() => {
                const input = this.$refs.messageActionModalInput;
                if (this.messageActionModalMode === "edit" && input?.focus) {
                    input.focus();
                    if (typeof input.setSelectionRange === "function") {
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
            this.messageActionModalMode = "edit";
            this.messageActionModalMessage = null;
            this.messageActionModalBody = "";
            this.messageActionModalError = "";
            this.messageActionModalSaving = false;
        },
        async submitMessageActionModal() {
            if (!this.messageActionModalMessage?.id || this.messageActionModalSaving) {
                return;
            }

            if (this.messageActionModalMode === "edit") {
                const trimmedBody = this.messageActionModalBody.trim();
                const originalBody = (this.messageActionModalMessage.body || "").trim();

                if (!trimmedBody || trimmedBody === originalBody) {
                    return;
                }

                this.messageActionModalSaving = true;
                this.messageActionModalError = "";

                try {
                    const { data } = await axios.patch(
                        `/api/direct-messages/${this.messageActionModalMessage.id}`,
                        { body: trimmedBody },
                        {
                            headers: this.token
                                ? { Authorization: `Bearer ${this.token}` }
                                : {},
                        },
                    );

                    this.applyServerMessageUpdate(data?.message ?? null, data?.pinned_messages ?? null);
                    this.closeMessageActionModal(true);
                } catch (error) {
                    this.messageActionModalError =
                        error?.response?.data?.message || "Unable to edit message.";
                } finally {
                    this.messageActionModalSaving = false;
                }

                return;
            }

            this.messageActionModalSaving = true;
            this.messageActionModalError = "";

            try {
                const { data } = await axios.delete(
                    `/api/direct-messages/${this.messageActionModalMessage.id}`,
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                this.applyServerMessageUpdate(data?.message ?? null, data?.pinned_messages ?? null);
                this.closeMessageActionModal(true);
            } catch (error) {
                this.messageActionModalError =
                    error?.response?.data?.message || "Unable to unsend message.";
            } finally {
                this.messageActionModalSaving = false;
            }
        },
        getReactionMeta(message) {
            const reactionKey = message?.reaction ?? null;
            if (!reactionKey) {
                return null;
            }
            return this.reactionOptions.find((reaction) => reaction.key === reactionKey) || null;
        },
        getReactionEmoji(reactionKey) {
            return (
                this.reactionOptions.find((reaction) => reaction.key === reactionKey)?.glyph || ""
            );
        },
        getUniqueReactionEmojis(reactions) {
            if (!Array.isArray(reactions) || reactions.length === 0) {
                return [];
            }

            const uniqueReactions = new Set();
            reactions.forEach((reaction) => {
                if (reaction?.reaction) {
                    uniqueReactions.add(reaction.reaction);
                }
            });

            return Array.from(uniqueReactions).map((reactionKey) => this.getReactionEmoji(reactionKey));
        },
        formatReactionsTooltip(reactions) {
            if (!Array.isArray(reactions) || reactions.length === 0) {
                return "";
            }

            const grouped = {};
            reactions.forEach((reaction) => {
                if (reaction?.reaction && reaction?.user_name) {
                    if (!grouped[reaction.reaction]) {
                        grouped[reaction.reaction] = [];
                    }
                    grouped[reaction.reaction].push(reaction.user_name);
                }
            });

            return Object.entries(grouped)
                .map(([reactionKey, names]) => {
                    const emoji = this.getReactionEmoji(reactionKey);
                    return `${emoji}: ${names.join(", ")}`;
                })
                .join("\n");
        },
        openReactionsModal(reactions) {
            if (!Array.isArray(reactions) || reactions.length === 0) {
                return;
            }

            this.reactionsModalData = reactions;
            this.showReactionsModal = true;
        },
        closeReactionsModal() {
            this.showReactionsModal = false;
            this.reactionsModalData = [];
        },
        toggleReactionPicker(message) {
            if (message?.is_unsent || message?.is_system) {
                return;
            }

            this.activeMessageActionsId = null;
            this.activeReactionPickerId = this.activeReactionPickerId === message.id ? null : message.id;
        },
        async promptForMessageEdit(currentBody) {
            if (!window.Swal) {
                return window.prompt("Edit message", currentBody);
            }

            const result = await window.Swal.fire({
                title: "Edit message",
                input: "textarea",
                inputValue: currentBody,
                inputAttributes: {
                    "aria-label": "Edit message body",
                },
                showCancelButton: true,
                confirmButtonText: "Save",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#2f80ed",
                cancelButtonColor: "#6c757d",
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return "Message cannot be empty.";
                    }

                    return null;
                },
            });

            if (!result.isConfirmed) {
                return null;
            }

            return result.value;
        },
        async confirmUnsendMessage() {
            if (!window.Swal) {
                return window.confirm("Unsend this message for everyone?");
            }

            const result = await window.Swal.fire({
                icon: "warning",
                title: "Unsend message?",
                text: "This will remove the message for both sides.",
                showCancelButton: true,
                confirmButtonText: "Unsend",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
            });

            return result.isConfirmed;
        },
        async setReaction(message, reactionKey) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            const existingReaction = Array.isArray(message.reactions)
                ? message.reactions.find(
                      (reaction) => Number(reaction.user_id) === Number(this.userId || 0),
                  )?.reaction || null
                : message.reaction || null;
            const nextReaction = existingReaction === reactionKey ? null : reactionKey;

            try {
                const { data } = await axios.patch(
                    `/api/direct-messages/${message.id}/reaction`,
                    {
                        reaction: nextReaction,
                    },
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                this.applyServerMessageUpdate(data?.message ?? null);
                this.activeReactionPickerId = null;
            } catch (error) {
                console.error("Failed to update reaction:", error);
            }
        },
        openImageGallery(attachment) {
            if (!attachment?.url) {
                return;
            }

            if (!window.lightGallery) {
                window.open(attachment.url, "_blank", "noopener");
                return;
            }

            const galleryContainer = this.$refs.imageGalleryContainer;
            if (!galleryContainer) {
                window.open(attachment.url, "_blank", "noopener");
                return;
            }

            this.destroyImageGallery();

            this.imageGalleryInstance = window.lightGallery(galleryContainer, {
                dynamic: true,
                dynamicEl: [
                    {
                        src: attachment.url,
                        thumb: attachment.url,
                        subHtml: attachment.name || "Attachment",
                    },
                ],
                plugins: [window.lgThumbnail, window.lgZoom].filter(Boolean),
                licenseKey: "0000-0000-000-0000",
                speed: 300,
            });

            this.imageGalleryInstance.openGallery();
        },
        canMarkConversationSeen(userId = null) {
            const activeUser = this.selectedUserState;
            const selectedUserId = userId ?? activeUser?.id ?? null;

            if (!activeUser || !selectedUserId) {
                return false;
            }

            if (!this.chatDockVisible || !this.messagePanelOpen) {
                return false;
            }

            if (!this.isMessagePanelVisible()) {
                return false;
            }

            return Number(activeUser.id) === Number(selectedUserId);
        },
        isMessagePanelVisible() {
            if (typeof window === "undefined") {
                return false;
            }

            const panel = this.$el?.querySelector(".message-panel");
            if (!panel) {
                return false;
            }

            const styles = window.getComputedStyle(panel);
            return styles.display !== "none" && styles.visibility !== "hidden" && panel.getClientRects().length > 0;
        },
        async markConversationSeen(userId = null) {
            const activeUser = this.selectedUserState;
            if (!this.canMarkConversationSeen(userId)) return;
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
                const messageIds = new Set((data?.message_ids ?? []).map((id) => Number(id)));

                if (!readAt || messageIds.size === 0) return;

                this.conversationMessages = this.conversationMessages.map((message) => {
                    if (!messageIds.has(Number(message.id))) {
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
            if (!message || message.is_unsent || message.is_system) {
                return;
            }

            this.replyTargetMessage = this.normalizeReplyTarget(message);
            this.messageDraft = "";
        },
        cancelReply() {
            this.replyTargetMessage = null;
        },
        clearHighlightTimer() {
            if (this.highlightMessageTimer) {
                clearTimeout(this.highlightMessageTimer);
                this.highlightMessageTimer = null;
            }
        },
        async ensureMessageVisible(messageId) {
            if (!messageId) {
                return null;
            }

            let targetMessage = this.conversationMessages.find((item) => item.id === messageId) || null;
            let guard = 0;

            while (
                !targetMessage &&
                this.conversationHasMore &&
                this.conversationPage < this.conversationLastPage &&
                guard < 10
            ) {
                const nextPage = this.conversationPage + 1;
                await this.loadConversation({
                    page: nextPage,
                    preserveScroll: true,
                });
                targetMessage = this.conversationMessages.find((item) => item.id === messageId) || null;
                guard += 1;
            }

            return targetMessage;
        },
        async scrollToMessage(messageId) {
            if (!messageId) return;

            const targetMessage = await this.ensureMessageVisible(messageId);
            if (!targetMessage) {
                return;
            }

            await this.$nextTick();

            const bodyEl = this.$refs.conversationBody || this.$el.querySelector(".message-panel__body");
            let targetEl = bodyEl?.querySelector(`[data-message-id="${messageId}"]`)
                || this.$el.querySelector(`[data-message-id="${messageId}"]`);

            if (!targetEl) {
                await this.$nextTick();
                targetEl = bodyEl?.querySelector(`[data-message-id="${messageId}"]`)
                    || this.$el.querySelector(`[data-message-id="${messageId}"]`);
            }

            if (targetEl) {
                targetEl.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
            } else if (bodyEl) {
                bodyEl.scrollTop = bodyEl.scrollHeight;
            }

            await this.waitForMessageInView(targetEl, bodyEl);
            await this.pulseHighlightedMessage(messageId, targetEl);

            if (bodyEl) {
                this.updateScrollToBottomButton(bodyEl);
            }
        },
        async scrollToReplyMessage(message) {
            const replyId = message?.reply_to?.id || message?.reply_to_id || null;
            if (!replyId) return;

            await this.scrollToMessage(replyId);
        },
        async waitForMessageInView(targetEl, bodyEl, timeoutMs = 1400) {
            if (!targetEl) {
                return;
            }

            const startedAt = Date.now();

            while (Date.now() - startedAt < timeoutMs) {
                await this.$nextTick();

                if (this.isMessageInView(targetEl, bodyEl)) {
                    await new Promise((resolve) => setTimeout(resolve, 60));
                    if (this.isMessageInView(targetEl, bodyEl)) {
                        return;
                    }
                }

                await new Promise((resolve) => setTimeout(resolve, 40));
            }
        },
        isMessageInView(targetEl, bodyEl) {
            if (!targetEl) {
                return false;
            }

            const targetRect = targetEl.getBoundingClientRect();

            if (!bodyEl) {
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                return targetRect.top < viewportHeight && targetRect.bottom > 0;
            }

            const bodyRect = bodyEl.getBoundingClientRect();
            return targetRect.top < bodyRect.bottom && targetRect.bottom > bodyRect.top;
        },
        clearHighlightAnimationTimer() {
            if (this.highlightAnimationTimer) {
                clearTimeout(this.highlightAnimationTimer);
                this.highlightAnimationTimer = null;
            }
        },
        async pulseHighlightedMessage(messageId, targetEl = null) {
            if (!messageId) {
                return;
            }

            if (this.highlightedMessageId === messageId) {
                this.highlightedMessageId = null;
                await this.$nextTick();
                await new Promise((resolve) => requestAnimationFrame(resolve));
                await new Promise((resolve) => requestAnimationFrame(resolve));
            }

            this.highlightedMessageId = messageId;
            this.highlightPulseToken += 1;
            this.clearHighlightTimer();
            this.clearHighlightAnimationTimer();

            const bubbleEl = targetEl?.querySelector(".message-bubble")
                || this.$el.querySelector(`[data-message-id="${messageId}"] .message-bubble`);

            if (bubbleEl) {
                bubbleEl.style.animation = "none";
                bubbleEl.offsetHeight;
                await this.$nextTick();

                requestAnimationFrame(() => {
                    if (!bubbleEl.isConnected || this.highlightedMessageId !== messageId) {
                        return;
                    }

                    bubbleEl.style.animation = "messageShake 0.55s ease-in-out";
                    this.highlightAnimationTimer = setTimeout(() => {
                        if (bubbleEl.isConnected && this.highlightedMessageId === messageId) {
                            bubbleEl.style.animation = "";
                        }
                        this.highlightAnimationTimer = null;
                    }, 650);
                });
            }

            this.highlightMessageTimer = setTimeout(() => {
                this.highlightedMessageId = null;
                this.highlightMessageTimer = null;
            }, 2200);
        },
        getReplyPreview(message) {
            if (!message?.reply_to_id && !message?.reply_to && !message?.reply_preview) {
                if (message?.is_unsent) {
                    return "Unsent Message";
                }

                if (message?.body) {
                    return message.body;
                }

                if (message?.attachment?.name) {
                    return message.attachment.name;
                }

                return "Attachment";
            }

            if (message?.reply_preview) {
                return message.reply_preview;
            }

            const replyTarget = message.reply_to
                ? message.reply_to
                : this.conversationMessages.find((item) => item.id === message.reply_to_id);

            if (!replyTarget) return "Original message not available";

            if (replyTarget.is_unsent) {
                return "Unsent Message";
            }

            if (replyTarget.body) {
                return replyTarget.body;
            }

            if (replyTarget.attachment?.name) {
                return replyTarget.attachment.name;
            }

            return "Attachment";
        },
        getMessagePreview(message) {
            if (!message) {
                return "Attachment";
            }

            if (message.is_system) {
                return this.formatSystemMessage(message);
            }

            if (message.is_unsent) {
                return "Unsent Message";
            }

            if (message.body) {
                return message.body;
            }

            if (message.attachment?.name) {
                return message.attachment.name;
            }

            return "Attachment";
        },
        normalizeReplyTarget(message) {
            if (!message) {
                return null;
            }

            return {
                id: message.id,
                body: message.body || "",
                attachment: message.attachment
                    ? { ...message.attachment }
                    : null,
                sender_id: message.sender_id,
                recipient_id: message.recipient_id,
                created_at: message.created_at || null,
            };
        },
        normalizeConversationMessage(message) {
            if (!message) {
                return message;
            }

            const senderId =
                message.sender_id != null ? Number(message.sender_id) : null;
            const recipientId =
                message.recipient_id != null ? Number(message.recipient_id) : null;

            return {
                ...message,
                id: message.id != null ? Number(message.id) : message.id,
                sender_id: senderId,
                recipient_id: recipientId,
                is_mine:
                    senderId !== null
                        ? Number(senderId) === Number(this.userId || 0)
                        : Boolean(message.is_mine),
                is_system: Boolean(
                    message.is_system || message.message_type === "system",
                ),
                is_unsent: Boolean(message.is_unsent),
                reaction: message.is_unsent ? null : message.reaction || null,
                reactions: message.is_unsent
                    ? []
                    : Array.isArray(message.reactions)
                        ? message.reactions
                        : [],
            };
        },
        formatSystemMessage(message) {
            return String(message?.body || "").trim();
        },
        triggerAttachmentPicker() {
            const input = this.$refs.attachmentInput;
            if (input) {
                input.click();
            }
        },
        handleAttachmentChange(event) {
            const file = event?.target?.files?.[0] ?? null;
            this.attachmentError = "";

            if (!file) {
                this.clearSelectedAttachment();
                return;
            }

            const validationError = this.validateAttachment(file);
            if (validationError) {
                this.attachmentError = validationError;
                this.clearSelectedAttachment(false);
                if (event?.target) {
                    event.target.value = "";
                }
                return;
            }

            this.setSelectedAttachment(file);
        },
        setSelectedAttachment(file) {
            this.clearSelectedAttachment(false);

            if (!file) {
                return;
            }

            this.selectedAttachment = file;
            this.selectedAttachmentPreviewUrl = URL.createObjectURL(file);
        },
        clearSelectedAttachment(resetInput = true) {
            if (this.selectedAttachmentPreviewUrl) {
                URL.revokeObjectURL(this.selectedAttachmentPreviewUrl);
                this.selectedAttachmentPreviewUrl = null;
            }

            this.selectedAttachment = null;

            if (resetInput) {
                const input = this.$refs.attachmentInput;
                if (input) {
                    input.value = "";
                }
            }
        },
        validateAttachment(file) {
            if (!file) {
                return "";
            }

            if (file.size > this.attachmentMaxSizeBytes) {
                return "Attachment must not exceed 5 MB.";
            }

            const extension = this.getFileExtension(file.name);
            if (!this.allowedAttachmentExtensions.includes(extension)) {
                return "Allowed file types: JPG, JPEG, PNG, GIF, DOC, DOCX, DOCS, PDF, XLSX, TXT.";
            }

            return "";
        },
        getFileExtension(filename) {
            if (!filename || typeof filename !== "string") {
                return "";
            }

            const parts = filename.split(".");
            if (parts.length < 2) {
                return "";
            }

            return parts.pop().toLowerCase();
        },
        formatFileSize(sizeInBytes) {
            if (sizeInBytes === null || sizeInBytes === undefined) {
                return "";
            }

            const size = Number(sizeInBytes);
            if (Number.isNaN(size) || size < 0) {
                return "";
            }

            const units = ["B", "KB", "MB", "GB"];
            let value = size;
            let unitIndex = 0;

            while (value >= 1024 && unitIndex < units.length - 1) {
                value /= 1024;
                unitIndex += 1;
            }

            const precision = unitIndex === 0 ? 0 : 1;
            return `${value.toFixed(precision)} ${units[unitIndex]}`;
        },
        getAttachmentDownloadName(attachment) {
            if (!attachment) {
                return "attachment";
            }

            return attachment.name || attachment.path?.split("/").pop() || "attachment";
        },
        getAttachmentIconClass(attachment) {
            const extension = (attachment?.extension || "").toLowerCase();

            if (["jpg", "jpeg", "png", "gif"].includes(extension)) {
                return "fa-solid fa-image";
            }

            if (extension === "pdf") {
                return "fa-solid fa-file-pdf";
            }

            if (["doc", "docx", "docs"].includes(extension)) {
                return "fa-solid fa-file-word";
            }

            if (extension === "xlsx") {
                return "fa-solid fa-file-excel";
            }

            if (extension === "txt") {
                return "fa-solid fa-file-lines";
            }

            return "fa-solid fa-file";
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
                store[Number(id)] = now;
            });

            localStorage.setItem("online-users-last-seen", JSON.stringify(store));
        },
        markUserSeen(userId) {
            const store = this.getLastSeenStore();
            store[Number(userId)] = Date.now();
            localStorage.setItem("online-users-last-seen", JSON.stringify(store));
        },
        getLastSeen(userId) {
            const store = this.getLastSeenStore();
            return store[Number(userId)] ?? null;
        },
        getActivityState(userId) {
            const normalizedUserId = Number(userId);
            const isOnline = this.onlineUserIds.includes(normalizedUserId);

            if (isOnline) {
                return {
                    isOnline: true,
                    lastSeenAt: this.getLastSeen(normalizedUserId),
                    label: "Online",
                };
            }

            const lastSeenAt = this.getLastSeen(normalizedUserId);

            if (!lastSeenAt) {
                return {
                    isOnline: false,
                    lastSeenAt: null,
                    label: "Offline",
                };
            }

            const mins = Math.max(1, Math.floor((Date.now() - lastSeenAt) / 60000));

            if (mins < 60) {
                return {
                    isOnline: false,
                    lastSeenAt,
                    label: `Active ${mins} min ago`,
                };
            }

            if (mins < 1440) {
                return {
                    isOnline: false,
                    lastSeenAt,
                    label: `Active ${Math.floor(mins / 60)} hrs ago`,
                };
            }

            return {
                isOnline: false,
                lastSeenAt,
                label: `Active ${Math.floor(mins / 1440)} days ago`,
            };
        },
        getStatusLabel(userId) {
            return this.getActivityState(userId).label;
        },
        formatMessageTime(timestamp) {
            if (!timestamp) return "";

            const date = new Date(timestamp);
            if (Number.isNaN(date.getTime())) return "";

            const diffMs = Date.now() - date.getTime();
            const oneDayMs = 24 * 60 * 60 * 1000;

            if (diffMs >= oneDayMs) {
                const datePart = new Intl.DateTimeFormat(undefined, {
                    month: "short",
                    day: "numeric",
                    year: "numeric",
                }).format(date);

                const timePart = new Intl.DateTimeFormat(undefined, {
                    hour: "numeric",
                    minute: "2-digit",
                }).format(date);

                return `${datePart} ${timePart}`;
            }

            return new Intl.DateTimeFormat(undefined, {
                hour: "numeric",
                minute: "2-digit",
            }).format(date);
        },
        formatSeenAt(timestamp) {
            if (!timestamp) return "";

            const date = new Date(timestamp);
            return new Intl.DateTimeFormat(undefined, {
                hour: "numeric",
                minute: "2-digit",
            }).format(date);
        },
        formatSeenReceiptTooltip(value) {
            if (!value) return "";

            const seenAt = this.formatSeenAt(value);
            return seenAt ? `Seen at ${seenAt}` : "";
        },
        getSeenReceiptAvatar() {
            return this.selectedUserState?.profile || null;
        },
        shouldShowSeenReceipt(message) {
            if (!message?.is_mine || !message?.read_at) {
                return false;
            }

            return Number(message.id) === Number(this.latestSeenReceiptMessageId || 0);
        },
        formatPinnedAt(timestamp) {
            if (!timestamp) return "";

            const date = new Date(timestamp);
            return new Intl.DateTimeFormat(undefined, {
                month: "short",
                day: "numeric",
                year: "numeric",
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
    width: 45px;
    height: 45px;
    flex-shrink: 0;
}

.user-list__status-dot {
    position: absolute;
    right: -1px;
    bottom: -1px;
    width: 0.78rem;
    height: 0.78rem;
    border-radius: 50%;
    border: 2px solid var(--bs-body-bg);
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.06);
}

.user-list__status-dot--online {
    background: #22c55e;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.12);
}

.user-list__status-dot--offline {
    background: #9ca3af;
    box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.12);
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
    backdrop-filter: blur(12px);
}

.online-users-menu__hero {
    margin-bottom: 0.9rem;
    padding: 1rem 1rem 0.95rem;
    border: 1px solid rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 18px;
    background:
        radial-gradient(circle at top left, rgba(var(--bs-primary-rgb), 0.16), transparent 58%),
        linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-primary-rgb), 0.02));
}

.online-users-menu__topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 0.65rem;
}

.online-users-menu__title-block {
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.online-users-menu__eyebrow {
    display: inline-flex;
    align-items: center;
    margin-bottom: 0.45rem;
    color: rgba(var(--bs-primary-rgb), 0.9);
    font-size: 0.69rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.online-users-menu__title-block h6 {
    font-size: 1rem;
    letter-spacing: 0.01em;
}

.online-users-menu__subline {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.55rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.08);
    color: #15803d;
    font-size: 0.79rem;
    font-weight: 600;
}

.online-users-menu__count-dot {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.14);
    flex: 0 0 auto;
}

.online-users-menu__messenger-btn {
    align-self: flex-start;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.6rem 1rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.online-users-menu__description {
    color: var(--bs-secondary-color);
    font-size: 0.82rem;
    line-height: 1.5;
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
    min-height: 2.5rem;
    padding: 0 0.7rem;
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
    font-size: 0.82rem;
    margin-right: 0.55rem;
    flex-shrink: 0;
}

.search-shell__input {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    color: var(--bs-body-color);
    min-height: 2.1rem;
    padding-top: 0;
    padding-bottom: 0;
    padding-left: 0;
    padding-right: 0;
    min-width: 0;
    font-size: 0.9rem;
    letter-spacing: 0.01em;
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
    z-index: 900;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.message-panel {
    position: absolute;
    right: 0;
    bottom: 4.5rem;
    width: min(580px, calc(100vw - 1.5rem));
    height: min(82vh, 700px);
    max-height: min(82vh, 700px);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bs-border-color);
    color: var(--bs-body-color);
    z-index: 901;
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

.message-dock__typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.1rem;
    color: var(--bs-secondary-color);
}

.message-dock__typing-dots {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
}

.message-dock__typing-dots span {
    width: 0.28rem;
    height: 0.28rem;
    border-radius: 50%;
    background: currentColor;
    animation: dockTypingBounce 1s infinite ease-in-out;
}

.message-dock__typing-dots span:nth-child(2) {
    animation-delay: 0.12s;
}

.message-dock__typing-dots span:nth-child(3) {
    animation-delay: 0.24s;
}

.message-dock__typing-text {
    font-size: 0.72rem;
    line-height: 1;
    color: var(--bs-secondary-color);
}

@keyframes dockTypingBounce {
    0%, 80%, 100% {
        transform: translateY(0);
        opacity: 0.45;
    }
    40% {
        transform: translateY(-2px);
        opacity: 1;
    }
}

.typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    width: fit-content;
    padding: 0.4rem 0.65rem;
    border-radius: 999px;
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    font-size: 0.82rem;
    font-weight: 600;
}

.message-panel__typing {
    align-self: flex-start;
    margin-top: 0.2rem;
}

.typing-indicator__dots {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
}

.typing-indicator__dots span {
    width: 0.34rem;
    height: 0.34rem;
    border-radius: 50%;
    background: currentColor;
    animation: typingBounce 1s infinite ease-in-out;
}

.typing-indicator__dots span:nth-child(2) {
    animation-delay: 0.12s;
}

.typing-indicator__dots span:nth-child(3) {
    animation-delay: 0.24s;
}

@keyframes typingBounce {
    0%, 80%, 100% {
        transform: translateY(0);
        opacity: 0.45;
    }
    40% {
        transform: translateY(-2px);
        opacity: 1;
    }
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

.conversation-start-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 8px 0 16px;
    color: rgba(var(--bs-body-color-rgb), 0.58);
    font-size: 0.82rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.conversation-start-marker::before,
.conversation-start-marker::after {
    content: "";
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(var(--bs-body-color-rgb), 0.18));
}

.conversation-start-marker::before {
    margin-right: 12px;
}

.conversation-start-marker::after {
    margin-left: 12px;
    background: linear-gradient(90deg, rgba(var(--bs-body-color-rgb), 0.18), transparent);
}

.message-panel__older-loading {
    min-height: 3rem;
    padding: 0.5rem 0 0.75rem;
}

.message-panel__footer {
    border-top: 1px solid var(--bs-border-color);
    padding: 0.72rem 1rem 0.85rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    position: relative;
}

.message-panel__composer-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    min-height: 2rem;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-body-color);
    font-size: 0.86rem;
    font-weight: 600;
}

.message-scroll-bottom {
    position: absolute;
    right: 1rem;
    bottom: 6.25rem;
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    box-shadow: 0 12px 22px rgba(0, 0, 0, 0.18);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
}

.message-scroll-bottom:hover {
    background: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
}

.message-bubble {
    position: relative;
    max-width: 78%;
    min-width: 150px;
    padding: 0.75rem 0.9rem;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    word-break: break-word;
    transition: box-shadow 0.18s ease, transform 0.18s ease;
}

.message-row {
    display: flex;
    width: 100%;
    margin-bottom: 0.85rem;
}

.message-row:last-child {
    margin-bottom: 0;
}

.message-row--theirs {
    justify-content: flex-start;
}

.message-row--mine {
    justify-content: flex-end;
}

.message-row--system {
    justify-content: center;
}

.message-bubble-shell {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    max-width: 100%;
}

.message-bubble-stack {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    width: 100%;
    max-width: 100%;
}

.message-row--theirs .message-bubble-shell {
    flex-direction: row-reverse;
}

.message-row--mine .message-bubble-shell {
    flex-direction: row;
}

.message-row--theirs .message-bubble-stack {
    align-items: flex-start;
}

.message-system-note {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    max-width: min(100%, 32rem);
    margin: 0 auto;
    padding: 0.48rem 0.9rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.03);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.015);
    text-align: center;
}

.message-system-note__body {
    display: block;
    max-width: 100%;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.82rem;
    font-weight: 500;
    line-height: 1.25;
    white-space: normal;
    overflow-wrap: anywhere;
}

.message-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    opacity: 0;
    transform: translateY(0);
    pointer-events: none;
    transition: opacity 0.16s ease, transform 0.16s ease;
    position: relative;
    z-index: 80;
}

.message-row--theirs .message-actions {
    flex-direction: row-reverse;
}

.message-row:hover .message-actions,
.message-row:focus-within .message-actions,
.message-actions.is-open {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.message-actions.is-hidden-for-reaction {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.message-action-group {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.message-action-menu-layer {
    position: fixed;
    inset: 0;
    z-index: 2500;
    pointer-events: auto;
}

.message-action-menu {
    position: absolute;
    bottom: calc(100% + 8px);
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 180px;
    padding: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    background: rgba(36, 42, 51, 0.98);
    box-shadow: 0 18px 36px rgba(0, 0, 0, 0.34);
    z-index: 120;
    backdrop-filter: blur(10px);
}

.message-action-menu--floating {
    position: fixed;
    top: 0;
    left: 0;
    right: auto;
    bottom: auto;
    transform: translate(-50%, calc(-100% - 8px));
    z-index: 2501;
}

.message-action-menu::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: -7px;
    width: 14px;
    height: 14px;
    background: rgba(36, 42, 51, 0.98);
    transform: translateX(-50%) rotate(45deg);
    border-radius: 2px;
    z-index: -1;
}

.message-action-button {
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.14);
    transition: transform 0.16s ease, background-color 0.16s ease, color 0.16s ease;
}

.message-action-button:hover {
    transform: translateY(-1px);
    background: var(--bs-tertiary-bg);
}

.message-action-button--reply {
    font-size: 0.9rem;
}

.message-action-button--react {
    font-size: 0.95rem;
}

.message-action-button--more {
    font-size: 0.95rem;
}

.message-action-button--pin.is-active {
    background: rgba(13, 110, 253, 0.18) !important;
    color: #0d6efd;
    border-color: rgba(13, 110, 253, 0.36);
}

.message-action-button--pin.is-active:hover {
    background: rgba(13, 110, 253, 0.24);
    color: #0b5ed7;
    border-color: rgba(13, 110, 253, 0.44);
}

.message-action-button__pin-icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
    line-height: 1;
}

.message-action-button__pin-icon i:first-child {
    font-size: 0.74rem;
}

.message-action-button__pin-slash {
    position: absolute;
    inset: 0;
    font-size: 0.66rem;
    transform: rotate(40deg) translateY(-1px);
    transform-origin: center;
    color: currentColor;
    opacity: 0.95;
}

.message-action-menu__item {
    width: 100%;
    min-height: 3.25rem;
    border: 0;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.04);
    color: #f3f4f6;
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    padding: 0.72rem 0.9rem;
    font-size: 1rem;
    font-weight: 600;
    white-space: nowrap;
    text-align: left;
    transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
}

.message-action-menu__item:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.16);
}

.message-action-menu__item.is-active {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.message-action-menu__item--primary {
    background: linear-gradient(180deg, #3563f0 0%, #2d55da 100%);
    color: #fff;
}

.message-action-menu__item--primary:hover {
    background: linear-gradient(180deg, #3d6cfa 0%, #315ce7 100%);
    color: #fff;
}

.message-action-menu__item--danger {
    background: rgba(255, 255, 255, 0.06);
}

.message-action-menu__item--danger:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.message-action-menu__item:disabled {
    opacity: 0.58;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.message-action-menu__icon {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 24px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #f5f5f7;
}

.message-action-menu__icon i {
    font-size: 0.78rem;
}

.message-action-menu__item--primary .message-action-menu__icon {
    background: rgba(255, 255, 255, 0.14);
    border-color: rgba(255, 255, 255, 0.18);
}

.message-action-menu__item--danger .message-action-menu__icon {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

.message-action-menu__content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1px;
    min-width: 0;
}

.message-action-menu__label {
    line-height: 1.1;
}

.message-action-menu__hint {
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.15;
}

.message-action-menu__item--primary .message-action-menu__hint {
    color: rgba(255, 255, 255, 0.78);
}

.message-action-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.54);
    backdrop-filter: blur(6px);
}

.message-action-modal {
    width: min(100%, 520px);
    max-height: min(100vh - 40px, 680px);
    overflow: hidden;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: linear-gradient(180deg, rgb(30, 36, 45), rgb(23, 28, 36));
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.36);
    color: #fff;
}

.message-action-modal--edit,
.message-action-modal--confirm {
    width: min(100%, 780px);
    border-radius: 28px;
    border-color: rgba(118, 137, 175, 0.18);
    background:
        radial-gradient(circle at top right, rgba(73, 102, 173, 0.14), transparent 34%),
        linear-gradient(180deg, rgb(46, 52, 62), rgb(37, 42, 50));
    box-shadow: 0 28px 60px rgba(8, 15, 28, 0.42);
}

.message-action-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 24px 24px 18px;
}

.message-action-modal__header--edit,
.message-action-modal__header--confirm {
    padding: 26px 26px 22px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.message-action-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-action-modal__badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.message-action-modal__badge--edit {
    background: rgba(59, 130, 246, 0.18);
    color: #8ec5ff;
}

.message-action-modal__badge--danger {
    background: rgba(239, 68, 68, 0.18);
    color: #ff9b9b;
}

.message-action-modal__eyebrow {
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.58);
}

.message-action-modal__title {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
}

.message-action-modal__subtitle {
    margin: 0;
    color: rgba(255, 255, 255, 0.72);
    line-height: 1.55;
}

.message-action-modal__close {
    width: 46px;
    height: 46px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 46px;
    font-size: 1.15rem;
}

.message-action-modal__close:disabled {
    opacity: 0.55;
}

.message-action-modal__body {
    padding: 0 24px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.message-action-modal__body--edit,
.message-action-modal__body--confirm {
    padding: 24px 26px;
}

.message-action-modal__textarea {
    min-height: 212px;
    resize: vertical;
}

.message-action-modal__textarea,
.message-action-modal__preview {
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    padding: 14px 16px;
    outline: none;
}

.message-action-modal__textarea:focus {
    border-color: rgba(52, 102, 230, 0.88);
    box-shadow:
        0 0 0 3px rgba(45, 85, 218, 0.7),
        0 0 0 7px rgba(45, 85, 218, 0.18);
}

.message-action-modal__meta {
    display: flex;
    justify-content: flex-start;
}

.message-action-modal__count,
.message-action-modal__context-label {
    color: rgba(255, 255, 255, 0.58);
    font-size: 0.78rem;
}

.message-action-modal__context {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-action-modal__context--confirm {
    padding: 26px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(0, 0, 0, 0.06);
}

.message-action-modal__preview {
    line-height: 1.55;
    word-break: break-word;
    min-height: 82px;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
}

.message-action-modal__error {
    margin: 0;
    color: #ffb4b4;
    font-size: 0.88rem;
}

.message-action-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 0 24px 24px;
}

.message-action-modal__footer--edit,
.message-action-modal__footer--confirm {
    padding: 20px 26px 28px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
}

.message-action-modal__btn {
    min-width: 182px;
    min-height: 54px;
    border: 0;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 0 24px;
    font-size: 1rem;
    font-weight: 700;
}

.message-action-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}

.message-action-modal__btn--primary {
    background: linear-gradient(180deg, #3563f0 0%, #2d55da 100%);
    color: #fff;
}

.message-action-modal__btn--danger {
    background: linear-gradient(180deg, #e25264 0%, #cf3e52 100%);
    color: #fff;
}

.message-action-modal__btn:disabled {
    opacity: 0.6;
}

.reaction-picker {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.80rem 0.35rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: rgb(47, 53, 61);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
    position: relative;
    max-width: min(100% - 1.5rem, 22rem);
    flex-wrap: nowrap;
    overflow-x: auto;
    justify-content: center;
    z-index: 12;
    isolation: isolate;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.reaction-picker--centered {
    min-width: min(92%, 18rem);
    background: rgb(47, 53, 61);
}

.reaction-picker__btn {
    width: 2.35rem;
    height: 2.35rem;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--bs-body-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.16s ease, background-color 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
}

.reaction-picker__btn:hover {
    transform: translateY(-1px) scale(1.08);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
}

.reaction-picker__glyph {
    display: inline-block;
    font-size: 1.35rem;
    line-height: 1;
    animation: reactionPop 0.22s ease-out;
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
    font-size: 0.95rem;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
}

.message-reaction-badges {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 0.3rem 0.5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.message-reaction-badge--floating,
.message-reaction-badges--floating {
    position: absolute;
    bottom: -0.9rem;
    z-index: 4;
    box-shadow: 0 8px 14px rgba(0, 0, 0, 0.18);
    animation: reactionPop 0.22s ease-out;
}

.message-reaction-badge--theirs {
    right: -0.5rem;
}

.message-reaction-badge--mine {
    left: -0.5rem;
}

.message-reaction-badges--mine {
    left: -0.5rem;
}

.message-reaction-badges--theirs {
    right: -0.5rem;
}

.message-reaction-count {
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
}

.message-reaction-badges:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: scale(1.05);
}

.message-row--mine .message-action-button,
.message-row--mine .reaction-picker {
    background: rgba(255, 255, 255, 0.16);
    color: var(--bs-white);
    border-color: rgba(255, 255, 255, 0.18);
}

.message-row--mine .reaction-picker--centered {
    background: rgba(32, 37, 43, 0.94);
    border-color: rgba(255, 255, 255, 0.24);
}

.message-row--mine .message-action-button:hover,
.message-row--mine .reaction-picker__btn:hover {
    color: var(--bs-white);
}

.reaction-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(1px);
    z-index: 24;
}

.reaction-overlay--panel {
    pointer-events: auto;
}

.message-row--highlighted .message-bubble {
    box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.18), 0 14px 26px rgba(0, 0, 0, 0.16);
    transform: translateY(-1px);
    animation: messageShake 0.55s ease-in-out;
    will-change: transform;
}

[class*="message-row--pulse-"].message-row--highlighted .message-bubble {
    animation: messageShake 0.55s ease-in-out;
}

.message-bubble small {
    opacity: 1;
}

@keyframes messageShake {
    0% {
        transform: translateY(-1px) translateX(0);
    }
    15% {
        transform: translateY(-1px) translateX(-4px);
    }
    30% {
        transform: translateY(-1px) translateX(4px);
    }
    45% {
        transform: translateY(-1px) translateX(-3px);
    }
    60% {
        transform: translateY(-1px) translateX(3px);
    }
    75% {
        transform: translateY(-1px) translateX(-2px);
    }
    90% {
        transform: translateY(-1px) translateX(2px);
    }
    100% {
        transform: translateY(-1px) translateX(0);
    }
}

@keyframes reactionPop {
    0% {
        transform: scale(0.7);
        opacity: 0.2;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
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

.reply-preview--linked {
    cursor: pointer;
    transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
}

.reply-preview--linked:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
}

.reply-preview__header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.18rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(var(--bs-body-color-rgb), 0.7);
}

.reply-preview__header i {
    font-size: 0.7rem;
    color: var(--bs-primary);
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

.message-bubble__body {
    white-space: pre-wrap;
    word-break: break-word;
}

.message-bubble__body--unsent {
    font-style: italic;
    opacity: 0.78;
}

.message-bubble__time {
    margin-top: 6px;
    font-size: 0.72rem;
    text-align: right;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 4px;
    color: rgba(var(--bs-body-color-rgb), 0.72);
}

.message-bubble__time-edit {
    color: rgba(var(--bs-body-color-rgb), 0.74);
}

.message-bubble__status {
    margin-top: 6px;
    font-size: 0.72rem;
    text-align: right;
    display: flex;
    flex-direction: row;
    justify-content: flex-end;
    align-items: flex-end;
    gap: 4px;
    width: 100%;
    color: rgba(var(--bs-body-color-rgb), 0.52);
}

.message-bubble__status--sent {
    color: rgba(var(--bs-body-color-rgb), 0.52);
}

.message-bubble__status--seen {
    color: rgba(149, 255, 200, 0.92);
}

.message-bubble__seen-avatar {
    width: 1.05rem;
    height: 1.05rem;
    border-radius: 999px;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.12);
    color: var(--bs-body-color);
    font-size: 0.62rem;
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.14);
}

.message-bubble__seen-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 999px;
    object-fit: cover;
}

.message-attachment {
    width: 100%;
}

.message-attachment--image {
    margin-bottom: 0.35rem;
}

.message-attachment__image-wrap,
.message-attachment__file-wrap {
    position: relative;
}

.message-attachment__image-link {
    position: relative;
    display: block;
    border-radius: 14px;
    overflow: hidden;
    padding: 0;
    border: 0;
    background: transparent;
    width: 100%;
    cursor: zoom-in;
}

.message-attachment__image {
    display: block;
    width: 100%;
    max-width: 100%;
    height: auto;
    border-radius: 14px;
    object-fit: cover;
}

.message-attachment__image-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        180deg,
        rgba(15, 23, 42, 0.08),
        rgba(15, 23, 42, 0.18)
    );
    color: var(--bs-white);
    opacity: 0;
    transition: opacity 0.18s ease;
    font-size: 1.1rem;
}

.message-attachment__image-link:hover .message-attachment__image-overlay {
    opacity: 1;
}

.message-bubble--mine .message-attachment__image-overlay {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.03),
        rgba(255, 255, 255, 0.12)
    );
}

.message-attachment__download-btn {
    position: absolute;
    top: 0.55rem;
    right: 0.55rem;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
    background: rgba(var(--bs-body-bg-rgb), 0.9);
    color: var(--bs-body-color);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.14);
    backdrop-filter: blur(8px);
    transition: transform 0.18s ease, background-color 0.18s ease, color 0.18s ease;
    z-index: 2;
}

.message-attachment__download-btn:hover {
    color: var(--bs-primary);
    background: var(--bs-body-bg);
    transform: translateY(-1px);
}

.message-bubble--mine .message-attachment__download-btn {
    border-color: rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.18);
    color: var(--bs-white);
}

.message-bubble--mine .message-attachment__download-btn:hover {
    background: rgba(255, 255, 255, 0.24);
    color: var(--bs-white);
}

.message-attachment__file-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    padding: 0.65rem 0.75rem;
    border-radius: 14px;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
    background: rgba(var(--bs-body-color-rgb), 0.03);
}

.message-attachment__file-link:hover {
    color: inherit;
    background: rgba(var(--bs-body-color-rgb), 0.06);
}

.message-attachment__file-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    font-size: 1.1rem;
}

.message-attachment__file-meta {
    min-width: 0;
    flex: 1;
}

.message-attachment__file-name {
    font-weight: 600;
    word-break: break-word;
}

.message-bubble--mine .message-attachment__file-link {
    border-color: rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.14);
    color: var(--bs-white);
}

.message-bubble--mine .message-attachment__file-link:hover {
    background: rgba(255, 255, 255, 0.2);
}

.message-bubble--mine .message-attachment__file-icon {
    background: rgba(255, 255, 255, 0.16);
    color: var(--bs-white);
}

.message-bubble--mine .message-attachment__file-name,
.message-bubble--mine .message-attachment__file-link .theme-muted {
    color: rgba(255, 255, 255, 0.9) !important;
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

.message-panel__composer-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    font-size: 10px;
    margin-bottom: 0.35rem !important;
}

.message-panel__attach-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
    min-height: 2.45rem;
    padding: 0.46rem 0.8rem 0.46rem 0.65rem;
    border-radius: 999px;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
    background: linear-gradient(
        180deg,
        rgba(var(--bs-body-bg-rgb), 0.98),
        rgba(var(--bs-body-bg-rgb), 0.88)
    );
    color: var(--bs-body-color);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
}

.message-panel__pins-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
    min-height: 2.45rem;
    padding: 0.46rem 0.78rem 0.46rem 0.64rem;
    border-radius: 999px;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
    background: linear-gradient(
        180deg,
        rgba(var(--bs-body-bg-rgb), 0.98),
        rgba(var(--bs-body-bg-rgb), 0.88)
    );
    color: var(--bs-body-color);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
}

.message-panel__pins-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(var(--bs-primary-rgb), 0.22);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    background: linear-gradient(
        180deg,
        rgba(var(--bs-primary-rgb), 0.08),
        rgba(var(--bs-body-bg-rgb), 0.94)
    );
    color: var(--bs-body-color);
}

.message-panel__pins-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
}

.message-panel__pins-btn-icon {
    width: 1.65rem;
    height: 1.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    font-size: 0.8rem;
}

.message-panel__pins-btn-text {
    font-weight: 600;
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.message-panel__pins-btn-count {
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    font-size: 0.72rem;
    font-weight: 700;
}

.pinned-messages-panel {
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
    border-radius: 16px;
    background: var(--bs-tertiary-bg);
    padding: 0.8rem;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
}

.pinned-messages-panel--floating {
    position: absolute;
    right: 0.85rem;
    bottom: 5.9rem;
    width: min(380px, calc(100% - 1.5rem));
    max-height: min(42vh, 320px);
    overflow: hidden;
    z-index: 12;
    backdrop-filter: blur(10px);
}

.pinned-messages-panel__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.65rem;
}

.pinned-messages-panel__title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

.pinned-messages-panel__close {
    color: rgba(var(--bs-body-color-rgb), 0.7);
}

.pinned-messages-panel__list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 220px;
    overflow-y: auto;
    padding-right: 0.15rem;
}

.pinned-messages-panel__item {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
    border-radius: 14px;
    background: rgba(var(--bs-body-bg-rgb), 0.9);
    color: var(--bs-body-color);
    padding: 0.7rem 0.8rem;
    text-align: left;
    transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease;
}

.pinned-messages-panel__item:hover {
    transform: translateY(-1px);
    border-color: rgba(var(--bs-primary-rgb), 0.18);
    background: rgba(var(--bs-primary-rgb), 0.06);
}

.pinned-messages-panel__item-meta {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.pinned-messages-panel__item-preview {
    font-size: 0.9rem;
    font-weight: 600;
    word-break: break-word;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.pinned-messages-panel__item-action {
    flex-shrink: 0;
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    font-size: 0.78rem;
}

.pinned-messages-panel__item-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.pinned-messages-panel__item-action--unpin {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

.pinned-messages-panel__item-action--unpin:hover {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.16);
}

.pinned-messages-panel__empty {
    padding: 0.6rem 0.2rem 0.1rem;
    font-size: 0.92rem;
}

.pin-limit-popup {
    position: absolute;
    right: 1rem;
    bottom: 6.8rem;
    padding: 0.55rem 0.8rem;
    border-radius: 999px;
    background: rgba(220, 53, 69, 0.96);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 600;
    box-shadow: 0 12px 24px rgba(220, 53, 69, 0.28);
    z-index: 13;
}

.message-pin-chip {
    position: absolute;
    top: -0.4rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    border-radius: 0;
    color: #dc3545;
    z-index: 3;
    pointer-events: none;
}

.message-pin-chip--theirs {
    right: -0.15rem;
    transform: rotate(45deg);
}

.message-pin-chip--mine {
    left: -0.15rem;
    transform: rotate(-45deg);
}


.message-pin-chip__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #dc3545;
    font-size: 0.66rem;
    line-height: 1;

    i {
      font-size: 15px;
    }
}

.message-panel__attach-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(var(--bs-primary-rgb), 0.22);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    background: linear-gradient(
        180deg,
        rgba(var(--bs-primary-rgb), 0.08),
        rgba(var(--bs-body-bg-rgb), 0.94)
    );
    color: var(--bs-body-color);
}

.message-panel__attach-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
}

.message-panel__attach-btn-icon {
    width: 1.65rem;
    height: 1.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    flex-shrink: 0;
}

.message-panel__attach-btn-text {
    font-weight: 600;
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.attachment-preview {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.6rem 0.75rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    background: var(--bs-secondary-bg);
}

.attachment-preview--sending {
    position: relative;
    overflow: hidden;
}

.attachment-preview--sending::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(var(--bs-body-bg-rgb), 0.4);
    backdrop-filter: blur(1px);
    pointer-events: none;
}

.attachment-preview__thumb {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: rgba(var(--bs-body-color-rgb), 0.06);
    display: flex;
    align-items: center;
    justify-content: center;
}

.attachment-preview__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0;
}

.attachment-preview__icon {
    color: var(--bs-secondary-color);
    font-size: 1.35rem;
}

.attachment-preview__meta {
    min-width: 0;
    flex: 1;
}

.attachment-preview__name {
    font-weight: 600;
    word-break: break-word;
}

.attachment-preview__remove {
    color: var(--bs-secondary-color);
    text-decoration: none;
    flex-shrink: 0;
}

.attachment-preview__remove:hover {
    color: var(--bs-body-color);
}

.message-panel__composer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.message-panel__composer-count {
    line-height: 1;
}

.reply-composer__cancel:hover {
    color: var(--bs-body-color);
}

.dropdown-menu {
    max-height: 600px;
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
    .online-users-menu {
        min-width: min(360px, calc(100vw - 1rem));
    }

    .online-users-menu__header {
        padding-left: 0.9rem !important;
        padding-right: 0.9rem !important;
    }

    .online-users-menu__hero {
        padding: 0.9rem;
        border-radius: 16px;
    }

    .message-dock {
        right: 0.5rem;
        bottom: 0.5rem;
        gap: 0.35rem;
    }

    .message-dock__head {
        display: none;
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
        bottom: 10px;
        height: 95vh;
        max-height: 95vh;
    }

    .message-panel__header {
        padding: 0.8rem 0.9rem 10px;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .message-panel__header > .d-flex:first-child {
        flex: 1 1 auto;
    }

    .message-panel__header > .d-flex:last-child {
        width: 100%;
        justify-content: center;
        padding-bottom: 10px;
    }

    .message-panel__body,
    .message-panel__footer {
        padding-left: 0.9rem;
        padding-right: 0.9rem;
    }

    .message-panel__composer-tools,
    .message-panel__composer-bottom {
        width: 100%;
    }

    .message-panel__composer-tools {
        justify-content: center;
    }

    .message-panel__composer-status {
        font-size: 0.82rem;
    }

    .message-panel__composer-bottom {
        align-items: stretch;
    }

    .message-panel__composer-bottom .theme-muted {
        width: 100%;
    }

    .message-panel__composer-bottom .theme-button {
        width: 100%;
    }

    .message-panel__attach-btn {
        width: auto;
        justify-content: center;
        padding-left: 0.72rem;
        padding-right: 0.72rem;
    }

    .message-panel__pins-btn {
        width: auto;
        justify-content: center;
        padding-left: 0.72rem;
        padding-right: 0.72rem;
    }

    .message-panel__attach-btn-text {
        display: none;
    }

    .message-panel__pins-btn-text {
        display: none;
    }

    .message-panel__composer-count {
        width: 100%;
        text-align: center;
    }

    .pinned-messages-panel--floating {
        right: 0.5rem;
        left: 0.5rem;
        width: auto;
        bottom: 5.6rem;
        max-height: min(38vh, 280px);
    }

    .message-pin-chip {
        top: -0.55rem;
        width: 1.08rem;
        height: 1.08rem;
    }

    .message-actions {
        opacity: 1;
        transform: none;
        pointer-events: auto;
    }

    .reaction-picker {
        flex-wrap: wrap;
        justify-content: center;
        max-width: calc(100vw - 1.5rem);
    }
}

[data-bs-theme="light"] {
  #onlineUsersDropdown i {
    color: var(--primary) !important;
  }
}

#onlineUsersDropdown.online-users-trigger--employee i {
  color: var(--bs-white) !important;
}
</style>
