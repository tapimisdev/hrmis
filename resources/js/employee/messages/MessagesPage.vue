<template>
    <div class="messages-page">
        <div class="messages-workspace">
            <div
                class="messenger-shell"
                :class="{ 'is-mobile-users-open': showMobileUsersPanel }"
            >
                <ConversationsPanel
                    :show-mobile-users-panel="showMobileUsersPanel"
                    :mobile-users-panel-closing="mobileUsersPanelClosing"
                    :visible-users="visibleUsers"
                    :show-initial-page-skeleton="showInitialPageSkeleton"
                    :selected-conversation-key="selectedConversationKey"
                    :contact-action-menu-key="contactActionMenuKey"
                    :search-query="searchQuery"
                    :group-chat-request-history="groupChatRequestHistory"
                    :pending-group-chat-approvals="pendingGroupChatApprovals"
                    :is-admin="isAdmin"
                    :format-unread-count="formatUnreadCount"
                    :get-conversation-status-label="getConversationStatusLabel"
                    :format-conversation-timestamp="
                        formatConversationTimestamp
                    "
                    :is-conversation-online="isConversationOnline"
                    @close-mobile-users-panel="closeMobileUsersPanel"
                    @open-group-chat-modal="openGroupChatModal"
                    @open-group-chat-requests-modal="
                        showGroupChatRequestsModal = true
                    "
                    @open-approval-modal="showApprovalModal = true"
                    @open-beta-info-modal="showBetaInfoModal = true"
                    @open-privacy-info-modal="showPrivacyInfoModal = true"
                    @update:search-query="searchQuery = $event"
                    @select-user="selectUser"
                    @toggle-contact-action-menu="toggleContactActionMenu"
                    @delete-conversation="openConversationDeleteModal"
                />

                <ConversationWorkspace
                    :show-mobile-users-panel="showMobileUsersPanel"
                >
                    <template #header>
                        <ConversationHeaderBar
                            :active-user="activeUser"
                            :active-user-name="activeUserName"
                            :active-user-avatar="activeUserAvatar"
                            :active-user-status="activeUserStatus"
                            :is-online="isConversationOnline(activeUser)"
                            :is-group="activeConversationIsGroup"
                            @invite-members="openInviteMembersModal"
                            @show-members="openGroupMembersModal"
                            @leave-group="leaveActiveGroup"
                            @show-info="openConversationInfoModal"
                            @open-users-panel="openMobileUsersPanel"
                        />
                    </template>

                    <template #pinned-banner>
                        <template v-if="pinnedMessages.length">
                            <button
                                type="button"
                                class="conversation-banner"
                                @click="togglePinnedMessagesPanel"
                            >
                                <div
                                    class="conversation-banner__pinned-summary"
                                >
                                    <i class="fa-solid fa-thumbtack"></i>
                                    <div
                                        class="conversation-banner__pinned-copy"
                                    >
                                        <div class="conversation-banner__title">
                                            Pinned messages
                                        </div>
                                        <small
                                            class="conversation-banner__subtitle"
                                        >
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
                        <teleport to="body">
                            <PinnedMessagesPanel
                                :show="showPinnedMessagesPanel"
                                :pinned-messages="pinnedMessages"
                                @close="showPinnedMessagesPanel = false"
                                @scroll-to="scrollToPinnedMessage"
                                @unpin="unpinPinnedMessage"
                            />
                        </teleport>
                    </template>

                    <template #body>
                        <MessageStream
                            ref="messageStream"
                            :show-initial-page-skeleton="showInitialPageSkeleton"
                            :loading-conversation="loadingConversation"
                            :active-user="activeUser"
                            :active-user-name="activeUserName"
                            :conversation-error="conversationError"
                            :messages="messages"
                            :conversation-has-more="conversationHasMore"
                            :conversation-page="conversationPage"
                            :conversation-last-page="conversationLastPage"
                            :loading-older-conversation="
                                loadingOlderConversation
                            "
                            :active-conversation-is-group="
                                activeConversationIsGroup
                            "
                            :active-message-actions-id="
                                activeMessageActionsId
                            "
                            :selected-message-id="selectedMessageId"
                            :show-reaction-picker="showReactionPicker"
                            :typing-indicator="typingIndicator"
                            :typing-indicator-label="typingIndicatorLabel"
                            :show-scroll-to-bottom-button="
                                showScrollToBottomButton
                            "
                            :format-time="formatTime"
                            :format-file-size="formatFileSize"
                            :format-reactions-tooltip="
                                formatReactionsTooltip
                            "
                            :get-unique-reaction-emojis="
                                getUniqueReactionEmojis
                            "
                            :get-reaction-emoji="getReactionEmoji"
                            :should-show-seen-receipt="
                                shouldShowSeenReceipt
                            "
                            :format-group-seen-receipt-tooltip="
                                formatGroupSeenReceiptTooltip
                            "
                            :format-seen-receipt-tooltip="
                                formatSeenReceiptTooltip
                            "
                            :get-seen-receipt-preview-users="
                                getSeenReceiptPreviewUsers
                            "
                            :get-seen-receipt-overflow-count="
                                getSeenReceiptOverflowCount
                            "
                            :get-member-profile="getMemberProfile"
                            :get-seen-member-name="getSeenMemberName"
                            :get-seen-receipt-avatar="
                                getSeenReceiptAvatar
                            "
                            @scroll="handleConversationScroll"
                            @select-message="selectMessage"
                            @toggle-message-actions="toggleMessageActions"
                            @edit-message="editMessage"
                            @unsend-message="unsendMessage"
                            @toggle-pin-message="togglePinMessage"
                            @start-reply="startReply"
                            @toggle-reaction-picker="
                                toggleReactionPicker
                            "
                            @download-attachment="downloadAttachment"
                            @scroll-to-reply-message="
                                scrollToReplyMessage
                            "
                            @open-reactions-modal="openReactionsModal"
                            @attachment-image-load="
                                handleAttachmentImageLoad
                            "
                            @open-image-gallery="openImageGallery"
                            @open-seen-by-modal="openSeenByModal"
                            @scroll-to-bottom="
                                scrollConversationToBottom
                            "
                        />

                        <ComposerArea
                            ref="composerArea"
                            :reply-target-message="replyTargetMessage"
                            :reply-target-label="replyTargetLabel"
                            :get-message-snippet="getMessageSnippet"
                            :selected-attachment="selectedAttachment"
                            :selected-attachment-preview-url="
                                selectedAttachmentPreviewUrl
                            "
                            :selected-attachment-preview-type="
                                selectedAttachmentPreviewType
                            "
                            :format-file-size="formatFileSize"
                            :attachment-accept="attachmentAccept"
                            :show-pinned-messages-panel="
                                showPinnedMessagesPanel
                            "
                            :show-composer-emoji-picker="
                                showComposerEmojiPicker
                            "
                            :composer-emoji-options="
                                composerEmojiOptions
                            "
                            :active-user="activeUser"
                            :sending-message="sendingMessage"
                            :draft-message="draftMessage"
                            :active-conversation-is-group="
                                activeConversationIsGroup
                            "
                            :message-character-limit="
                                messageCharacterLimit
                            "
                            :message-character-count="
                                messageCharacterCount
                            "
                            :message-characters-remaining="
                                messageCharactersRemaining
                            "
                            :show-scroll-to-bottom-button="
                                showScrollToBottomButton
                            "
                            @send-message="sendMessage"
                            @clear-reply-target="clearReplyTarget"
                            @clear-selected-attachment="
                                clearSelectedAttachment
                            "
                            @toggle-pinned-messages-panel="
                                togglePinnedMessagesPanel
                            "
                            @toggle-composer-emoji-picker="
                                toggleComposerEmojiPicker
                            "
                            @trigger-attachment-picker="
                                triggerAttachmentPicker
                            "
                            @update:draft-message="draftMessage = $event"
                            @composer-input="handleComposerInput"
                            @composer-blur="handleComposerBlur"
                            @capture-selection="captureComposerSelection"
                            @scroll-to-bottom="
                                scrollConversationToBottom
                            "
                            @insert-composer-emoji="
                                insertComposerEmoji
                            "
                            @attachment-change="handleAttachmentChange"
                        />
                    </template>
                </ConversationWorkspace>
            </div>
        </div>

        <InfoNoticeModal
            :is-open="showBetaInfoModal"
            icon-class="fa-solid fa-flask"
            eyebrow="Beta release"
            title="About the BETA release"
            subtitle="This module is aimed at giving HRIS users one built-in space for direct messages, group coordination, approvals, and quick internal communication without leaving the portal."
            context-label="What this beta is for"
            :paragraphs="[
                'The goal is to make messaging feel native to the HRIS Portal instead of a separate tool.',
                'It is designed for employee-to-employee chat, team group chats, coordination with admins, and faster in-system updates tied to daily HR workflows.',
            ]"
            @close="showBetaInfoModal = false"
        />

        <InfoNoticeModal
            :is-open="showPrivacyInfoModal"
            icon-class="fa-solid fa-shield-halved"
            eyebrow="Privacy notice"
            title="About message privacy"
            subtitle="Your conversations are handled with privacy controls designed to protect exchanged messages inside the portal."
            context-label="How your messages are protected"
            :paragraphs="[
                'All exchanged messages are encrypted on the server and stored in a form that is not readable to the human eye.',
                'To help protect privacy over time, messages older than 3 months are included in a scheduled permanent deletion process.',
                'Once they pass the 3-month retention window, those older messages are permanently removed from the system.',
            ]"
            @close="showPrivacyInfoModal = false"
        />

        <transition name="fade">
            <div
                v-if="showGroupChatRequestsModal"
                class="message-action-modal-backdrop"
                @click.self="closeGroupChatRequestsModal"
            >
                <div
                    class="message-action-modal"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge message-action-modal__badge--edit"
                            >
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                Request history
                            </div>
                            <h3 class="message-action-modal__title">
                                Group chat request updates
                            </h3>
                            <p class="message-action-modal__subtitle">
                                Review your pending, approved, and rejected
                                group chat requests.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="closeGroupChatRequestsModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div
                            v-if="selectedHistoryMembersRequest"
                            class="approval-members-view"
                        >
                            <button
                                type="button"
                                class="approval-members-view__back"
                                @click="closeHistoryMembersView"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                                Go back
                            </button>

                            <div class="approval-members-view__list">
                                <div
                                    v-for="member in selectedHistoryMembersRequest.members || []"
                                    :key="`history-member-${selectedHistoryMembersRequest.id}-${member.id || member.name}`"
                                    class="approval-members-view__item"
                                >
                                    <img
                                        :src="getMemberProfile(member)"
                                        :alt="member.display_name || member.name"
                                    />
                                    <div class="approval-members-view__content">
                                        <div
                                            class="approval-members-view__name"
                                        >
                                            {{
                                                member.display_name ||
                                                member.name
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                member.nickname &&
                                                member.nickname !== member.name
                                            "
                                            class="approval-members-view__meta"
                                        >
                                            {{ member.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else-if="groupChatRequestHistory.length"
                            class="group-chat-approval-list"
                        >
                            <div
                                v-for="request in groupChatRequestHistory"
                                :key="`group-chat-request-${request.id}`"
                                class="group-chat-approval-card"
                            >
                                <div class="group-chat-approval-card__topline">
                                    <div
                                        class="group-chat-approval-card__heading"
                                    >
                                        <div
                                            class="group-chat-approval-card__title"
                                        >
                                            {{ request.name }}
                                        </div>
                                        <div
                                            class="group-chat-approval-card__meta"
                                        >
                                            <span>
                                                {{
                                                    formatRequestStatus(
                                                        request.approval_status,
                                                    )
                                                }}
                                                <template
                                                    v-if="
                                                        request.approval_status !==
                                                        'pending'
                                                    "
                                                >
                                                    · Actioned by
                                                    {{
                                                        request.processed_by
                                                            ?.name || "Admin"
                                                    }}
                                                </template>
                                            </span>
                                            <span
                                                v-if="
                                                    request.processed_at ||
                                                    request.created_at
                                                "
                                                class="group-chat-approval-card__meta-date"
                                            >
                                                {{
                                                    formatSeenDateTime(
                                                        request.processed_at ||
                                                            request.created_at,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        class="group-chat-approval-card__top-actions"
                                    >
                                        <span
                                            class="group-chat-request-status"
                                            :class="`group-chat-request-status--${request.approval_status}`"
                                        >
                                            {{
                                                formatRequestStatus(
                                                    request.approval_status,
                                                )
                                            }}
                                        </span>
                                        <button
                                            v-if="
                                                request.approval_status ===
                                                'approved'
                                            "
                                            type="button"
                                            class="group-chat-approval-card__open-btn"
                                            aria-label="Open messages"
                                            title="Open messages"
                                            @click="
                                                openApprovedRequestConversation(
                                                    request,
                                                )
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-arrow-up-right-from-square"
                                            ></i>
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        request.approval_status === 'pending'
                                    "
                                    class="group-chat-approval-card__actions"
                                >
                                    <button
                                        type="button"
                                        class="message-action-modal__btn message-action-modal__btn--ghost"
                                        :disabled="
                                            cancelGroupChatRequestSubmitting &&
                                            Number(
                                                selectedCancelGroupChatRequest
                                                    ?.id || 0,
                                            ) === Number(request.id)
                                        "
                                        @click="
                                            promptCancelGroupChatRequest(
                                                request,
                                            )
                                        "
                                    >
                                        Cancel
                                    </button>
                                </div>
                                <div
                                    v-if="(request.members || []).length"
                                    class="group-chat-approval-card__members"
                                >
                                    <button
                                        type="button"
                                        class="group-chat-approval-card__member-trigger"
                                        :aria-label="`Show members for ${request.name}`"
                                        @click.stop="openHistoryMembersView(request)"
                                    >
                                        <span
                                            class="group-chat-approval-card__member-stack"
                                        >
                                            <span
                                                v-for="member in getRequestMemberPreview(
                                                    request.members,
                                                )"
                                                :key="`request-member-${request.id}-${member.id || member.name}`"
                                                class="group-chat-approval-card__member-avatar"
                                            >
                                                <img
                                                    :src="
                                                        getMemberProfile(member)
                                                    "
                                                    :alt="member.name || 'User'"
                                                />
                                            </span>
                                            <span
                                                v-if="
                                                    getRequestMemberOverflowCount(
                                                        request.members,
                                                    ) > 0
                                                "
                                                class="group-chat-approval-card__member-avatar group-chat-approval-card__member-avatar--more"
                                            >
                                                +{{
                                                    getRequestMemberOverflowCount(
                                                        request.members,
                                                    )
                                                }}
                                            </span>
                                        </span>
                                        <span
                                            class="group-chat-approval-card__member-summary"
                                        >
                                            {{ request.members.length }}
                                            members
                                        </span>
                                    </button>
                                </div>
                                <div
                                    v-if="
                                        request.approval_status ===
                                            'rejected' &&
                                        request.rejection_reason
                                    "
                                    class="group-chat-request-note"
                                >
                                    {{ request.rejection_reason }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-white-50">
                            No group chat requests yet.
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <ConversationDeleteModal
            :is-open="conversationDeleteModalVisible"
            :conversation-name="contactActionTarget?.name || ''"
            :submitting="conversationDeleteSubmitting"
            :error="conversationDeleteError"
            @close="closeConversationDeleteModal"
            @confirm="confirmDeleteConversationMessages"
        />

        <GroupChatModal
            :is-open="showGroupChatModal"
            :available-users="availableUsers"
            :is-admin="isAdmin"
            :is-submitting="groupChatSubmitting"
            :error="groupChatError"
            :pending-request-count="pendingGroupChatRequestCount"
            :request-limit="groupChatRequestLimit"
            @close="closeGroupChatModal"
            @submit="submitGroupChat"
        />

        <GroupInfoModal
            :is-open="showGroupInfoModal"
            :title="activeUserName"
            :avatar="activeUserAvatar"
            :display-name="activeUser?.actual_name || activeUserName"
            :initial-form="groupInfoForm"
            :is-group="activeConversationIsGroup"
            :media-items="conversationInfoImageItems.concat(conversationInfoFileItems)"
            :media-loading="conversationInfoMediaLoading"
            :media-loaded="conversationInfoMediaLoaded"
            :has-more="conversationInfoMediaHasMore"
            :is-submitting="groupInfoSubmitting"
            :error="groupInfoError"
            :initial-active-tab="groupInfoModalRestoreActiveTab"
            :restore-scroll-top="groupInfoModalRestoreScrollTop"
            @close="closeGroupInfoModal"
            @submit="submitConversationInfo"
            @scroll="loadConversationInfoMedia"
            @photo-change="handleGroupInfoPhotoChange"
            @open-gallery="openImageGallery"
            @download="downloadAttachment"
        />

        <LeaveGroupModal
            :is-open="showLeaveGroupModal"
            :group-name="activeUserName"
            :is-submitting="leaveGroupSubmitting"
            :error="leaveGroupError"
            @close="closeLeaveGroupModal"
            @confirm="confirmLeaveActiveGroup"
        />

        <GroupMembersModal
            :is-open="showGroupMembersModal"
            :members="groupMembersModalMembers"
            :group-name="groupMembersModalTitle"
            :current-user-id="authUser?.id"
            @close="closeGroupMembersModal"
        />

        <InviteMembersModal
            :is-open="showInviteMembersModal"
            :group-name="activeUserName"
            :available-users="filteredInvitableUsers"
            :is-submitting="groupInviteSubmitting"
            :error="groupInviteError"
            @close="closeInviteMembersModal"
            @submit="submitInviteMembers"
        />

        <ReactionModal
            :is-open="showReactionsModal"
            :reactions="reactionsModalData"
            :current-user-id="authUser?.id"
            :reaction-options="reactionOptions"
            @close="closeReactionsModal"
        />

        <transition name="fade">
            <div
                v-if="showApprovalModal"
                class="message-action-modal-backdrop"
                @click.self="closeApprovalModal"
            >
                <div
                    class="message-action-modal"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge message-action-modal__badge--edit"
                            >
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                Admin approvals
                            </div>
                            <h3 class="message-action-modal__title">
                                {{
                                    selectedApprovalMembersRequest
                                        ? selectedApprovalMembersRequest.name
                                        : "Pending group chat requests"
                                }}
                            </h3>
                            <p class="message-action-modal__subtitle">
                                {{
                                    selectedApprovalMembersRequest
                                        ? "Review all members included in this requested group chat."
                                        : "Review and approve employee-created group chats."
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="closeApprovalModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div
                            v-if="selectedApprovalMembersRequest"
                            class="approval-members-view"
                        >
                            <button
                                type="button"
                                class="approval-members-view__back"
                                @click="closeApprovalMembersView"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                                Go back
                            </button>

                            <div class="approval-members-view__list">
                                <div
                                    v-for="member in selectedApprovalMembersRequest.members || []"
                                    :key="`approval-member-${selectedApprovalMembersRequest.id}-${member.id || member.name}`"
                                    class="approval-members-view__item"
                                >
                                    <img
                                        :src="getMemberProfile(member)"
                                        :alt="member.display_name || member.name"
                                    />
                                    <div class="approval-members-view__content">
                                        <div
                                            class="approval-members-view__name"
                                        >
                                            {{
                                                member.display_name ||
                                                member.name
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                member.nickname &&
                                                member.nickname !== member.name
                                            "
                                            class="approval-members-view__meta"
                                        >
                                            {{ member.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else-if="pendingGroupChatApprovals.length"
                            class="group-chat-approval-list"
                        >
                            <div
                                v-for="request in pendingGroupChatApprovals"
                                :key="request.id"
                                class="group-chat-approval-card"
                            >
                                <div class="group-chat-approval-card__title">
                                    {{ request.name }}
                                </div>
                                <div class="group-chat-approval-card__members">
                                    <button
                                        type="button"
                                        class="group-chat-approval-card__member-trigger"
                                        :aria-label="`Show members for ${request.name}`"
                                        @click="
                                            openRequestMembersModal(request)
                                        "
                                    >
                                        <span
                                            class="group-chat-approval-card__member-stack"
                                        >
                                            <span
                                                v-for="member in getRequestMemberPreview(
                                                    request.members,
                                                )"
                                                :key="`pending-request-member-${request.id}-${member.id || member.name}`"
                                                class="group-chat-approval-card__member-avatar"
                                            >
                                                <img
                                                    :src="
                                                        getMemberProfile(member)
                                                    "
                                                    :alt="member.name || 'User'"
                                                />
                                            </span>
                                            <span
                                                v-if="
                                                    getRequestMemberOverflowCount(
                                                        request.members,
                                                    ) > 0
                                                "
                                                class="group-chat-approval-card__member-avatar group-chat-approval-card__member-avatar--more"
                                            >
                                                +{{
                                                    getRequestMemberOverflowCount(
                                                        request.members,
                                                    )
                                                }}
                                            </span>
                                        </span>
                                        <span
                                            class="group-chat-approval-card__member-summary"
                                        >
                                            {{ request.members.length }}
                                            members
                                        </span>
                                    </button>
                                </div>
                                <div class="group-chat-approval-card__meta">
                                    Requested by
                                    {{ request.creator?.name || "User" }}
                                </div>
                                <div
                                    v-if="request.created_at"
                                    class="group-chat-approval-card__timestamp"
                                >
                                    Requested
                                    {{
                                        formatSeenDateTime(
                                            request.created_at,
                                        )
                                    }}
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
                                        @click="
                                            approveGroupChatRequest(request)
                                        "
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

        <ReactionPickerModal
            :is-open="showReactionPicker"
            :selected-message="selectedMessage"
            :reaction-options="reactionOptions"
            :current-user-id="authUser?.id"
            @close="clearReactionPicker"
            @set-reaction="setReaction"
        />

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
                                @keydown.ctrl.enter.prevent="
                                    submitMessageActionModal
                                "
                                @keydown.meta.enter.prevent="
                                    submitMessageActionModal
                                "
                            ></textarea>
                            <div class="message-action-modal__meta">
                                <small class="message-action-modal__count">
                                    {{ messageActionModalBody.length }}/2000
                                </small>
                            </div>
                        </template>

                        <template v-else>
                            <div class="message-action-modal__context">
                                <div
                                    class="message-action-modal__context-label"
                                >
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
                            :class="
                                messageActionModalMode === 'edit'
                                    ? 'message-action-modal__btn--primary'
                                    : 'message-action-modal__btn--danger'
                            "
                            :disabled="
                                messageActionModalSaving ||
                                messageActionModalSubmitDisabled
                            "
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
        <AlertModal
            :is-open="alertModalVisible"
            :type="alertModalType"
            :title="alertModalTitle"
            :message="alertModalMessage"
            @close="closeAlertModal"
        />

        <transition name="fade">
            <div
                v-if="cancelGroupChatRequestModalVisible"
                class="message-action-modal-backdrop"
                @click.self="closeCancelGroupChatRequestModal"
            >
                <div
                    class="message-action-modal"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge message-action-modal__badge--danger"
                            >
                                <i class="fa-regular fa-trash-can"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                Confirmation
                            </div>
                            <h3 class="message-action-modal__title">
                                Cancel group chat request?
                            </h3>
                            <p class="message-action-modal__subtitle">
                                This will remove the pending request and admins
                                will no longer be able to approve it.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            :disabled="cancelGroupChatRequestSubmitting"
                            @click="closeCancelGroupChatRequestModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body">
                        <div class="message-action-modal__context">
                            <div class="message-action-modal__context-label">
                                Request to cancel
                            </div>
                            <div class="message-action-modal__preview">
                                {{
                                    selectedCancelGroupChatRequest?.name ||
                                    "Pending group chat request"
                                }}
                            </div>
                        </div>
                    </div>

                    <div class="message-action-modal__footer">
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--ghost"
                            :disabled="cancelGroupChatRequestSubmitting"
                            @click="closeCancelGroupChatRequestModal"
                        >
                            Keep request
                        </button>
                        <button
                            type="button"
                            class="message-action-modal__btn message-action-modal__btn--danger"
                            :disabled="cancelGroupChatRequestSubmitting"
                            @click="confirmCancelGroupChatRequest"
                        >
                            <span
                                v-if="cancelGroupChatRequestSubmitting"
                                class="spinner-border spinner-border-sm"
                                aria-hidden="true"
                            ></span>
                            <span v-else>Cancel request</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <NotificationStack
            :notifications="notifications"
            :get-notification-icon="getNotificationIcon"
            @dismiss="dismissNotification"
        />
        <div ref="imageGalleryContainer" class="d-none"></div>
    </div>
</template>

<script>
import axios from "axios";
import AlertModal from "./components/AlertModal.vue";
import ComposerArea from "./components/ComposerArea.vue";
import ConversationDeleteModal from "./components/ConversationDeleteModal.vue";
import ConversationHeaderBar from "./components/ConversationHeaderBar.vue";
import ConversationWorkspace from "./components/ConversationWorkspace.vue";
import ConversationsPanel from "./components/ConversationsPanel.vue";
import MessageStream from "./components/MessageStream.vue";
import ReactionPickerModal from "./components/ReactionPickerModal.vue";
import ReactionModal from "./components/ReactionModal.vue";
import SeenByModal from "./components/SeenByModal.vue";
import GroupMembersModal from "./components/GroupMembersModal.vue";
import InviteMembersModal from "./components/InviteMembersModal.vue";
import GroupChatModal from "./components/GroupChatModal.vue";
import GroupInfoModal from "./components/GroupInfoModal.vue";
import InfoNoticeModal from "./components/InfoNoticeModal.vue";
import LeaveGroupModal from "./components/LeaveGroupModal.vue";
import NotificationStack from "./components/NotificationStack.vue";
import PinnedMessagesPanel from "./components/PinnedMessagesPanel.vue";

export default {
    name: "MessagesPage",
    components: {
        AlertModal,
        ComposerArea,
        ConversationDeleteModal,
        ConversationHeaderBar,
        ConversationWorkspace,
        ConversationsPanel,
        MessageStream,
        ReactionPickerModal,
        ReactionModal,
        SeenByModal,
        GroupMembersModal,
        InviteMembersModal,
        GroupChatModal,
        GroupInfoModal,
        InfoNoticeModal,
        LeaveGroupModal,
        NotificationStack,
        PinnedMessagesPanel,
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
        messagesBaseUrl: {
            type: String,
            default: "",
        },
        csrfToken: {
            type: String,
            default: "",
        },
    },
    data() {
        const selectedConversationKey =
            this.initialSelectedConversationKey ||
            this.initialUsers?.[0]?.conversation_key ||
            null;
        const normalizeUserId = (userId) => Number(userId);
        const getLastSeenStore = () => {
            try {
                return JSON.parse(
                    localStorage.getItem("online-users-last-seen") || "{}",
                );
            } catch (error) {
                return {};
            }
        };
        const getActivityState = (userId, latestAt = null) => {
            const normalizedUserId = normalizeUserId(userId);
            const storedLastSeenAt =
                getLastSeenStore()[normalizedUserId] ?? null;
            const isOnline =
                this.onlineUserIds?.includes(normalizedUserId) ?? false;

            if (isOnline) {
                return {
                    label: "Online",
                    isActive: true,
                    lastSeenAt: storedLastSeenAt
                        ? Number(storedLastSeenAt)
                        : Date.now(),
                };
            }

            const fallbackSource = storedLastSeenAt;

            if (!fallbackSource) {
                return {
                    label: "Offline",
                    isActive: false,
                    lastSeenAt: null,
                };
            }

            const latestDate = new Date(fallbackSource);
            const diffMs = Date.now() - latestDate.getTime();

            if (Number.isNaN(diffMs) || diffMs < 0) {
                return {
                    label: "Offline",
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
            const isGroup = user.conversation_type === "group";
            const activityState = isGroup
                ? this.getGroupActivityState(user)
                : getActivityState(user.id, user.latest_at);

            return {
                ...user,
                id: normalizeUserId(user.id),
                conversation_key:
                    user.conversation_key ||
                    `direct:${normalizeUserId(user.id)}`,
                conversation_token: user.conversation_token || null,
                conversation_type: user.conversation_type || "direct",
                member_ids: Array.isArray(user.member_ids)
                    ? user.member_ids.map((id) => Number(id))
                    : [],
                active_label: user.active_label || activityState.label,
                is_active: user.is_active ?? activityState.isActive,
                last_seen_at: activityState.lastSeenAt,
            };
        };
        const token = localStorage.getItem("auth_token");

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
            pendingGroupChatApprovals:
                this.initialPendingGroupChatApprovals.map((request) => ({
                    ...request,
                    id: Number(request.id),
                })),
            groupChatRequestHistory: this.initialGroupChatRequestHistory.map(
                (request) => ({
                    ...request,
                    id: Number(request.id),
                }),
            ),
            selectedConversationKey,
            searchQuery: "",
            messages: [],
            pinnedMessages: [],
            draftMessage: "",
            replyTargetMessage: null,
            selectedMessage: null,
            selectedMessageId: null,
            messageActionModalVisible: false,
            messageActionModalMode: "edit",
            messageActionModalMessage: null,
            messageActionModalBody: "",
            messageActionModalError: "",
            messageActionModalSaving: false,
            alertModalVisible: false,
            alertModalType: "error",
            alertModalTitle: "",
            alertModalMessage: "",
            selectedAttachment: null,
            selectedAttachmentPreviewUrl: null,
            selectedAttachmentPreviewType: "file",
            attachmentAccept: ".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt",
            attachmentMode: "file",
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
            typingIndicatorSenderName: "",
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
            conversationError: "",
            composerEmojiOptions: [
                "😀",
                "😂",
                "😍",
                "🥳",
                "👍",
                "🙏",
                "🔥",
                "🎉",
                "❤️",
                "😎",
            ],
            reactionOptions: [
                { key: "like", emoji: "👍", label: "Like" },
                { key: "number-one", emoji: "☝️", label: "One DOST" },
                { key: "love", emoji: "❤️", label: "Love" },
                { key: "haha", emoji: "😂", label: "Haha" },
                { key: "sad", emoji: "😢", label: "Sad" },
                { key: "angry", emoji: "😡", label: "Angry" },
            ],
            presenceLifecycleBound: false,
            handlePresencePageHide: null,
            handlePresenceBeforeUnload: null,
            showGroupChatModal: false,
            groupChatSubmitting: false,
            groupChatError: "",
            groupChatRequestLimit: 5,
            cancelGroupChatRequestModalVisible: false,
            cancelGroupChatRequestSubmitting: false,
            selectedCancelGroupChatRequest: null,
            groupChatForm: {
                name: "",
                member_ids: [],
            },
            showGroupInfoModal: false,
            reopenGroupInfoModalAfterGallery: false,
            groupInfoModalRestoreScrollTop: 0,
            groupInfoModalRestoreActiveTab: "media",
            groupInfoSubmitting: false,
            groupInfoError: "",
            groupInfoForm: {
                name: "",
                nickname: "",
                photo: null,
            },
            conversationInfoMediaItems: [],
            conversationInfoMediaPage: 1,
            conversationInfoMediaHasMore: true,
            conversationInfoMediaLoading: false,
            conversationInfoMediaLoaded: false,
            showGroupMembersModal: false,
            groupMembersModalTitleOverride: "",
            groupMembersModalMembersOverride: [],
            showInviteMembersModal: false,
            groupInviteSubmitting: false,
            groupInviteError: "",
            showSeenByModal: false,
            seenByModalUsers: [],
            showReactionsModal: false,
            reactionsModalData: [],
            showBetaInfoModal: false,
            showPrivacyInfoModal: false,
            showGroupChatRequestsModal: false,
            selectedHistoryMembersRequest: null,
            activeGroupRequestTooltipId: null,
            showLeaveGroupModal: false,
            leaveGroupSubmitting: false,
            leaveGroupError: "",
            showApprovalModal: false,
            selectedApprovalMembersRequest: null,
            contactActionMenuKey: null,
            contactActionTarget: null,
            conversationDeleteModalVisible: false,
            conversationDeleteSubmitting: false,
            conversationDeleteError: "",
            now: new Date(),
            nowTimer: null,
            notifications: [],
            notificationIdCounter: 0,
            showInitialCacheLoader: false,
            didHydrateFromCache: false,
            cachePersistTimer: null,
            cacheVersion: "v1",
        };
    },
    computed: {
        pendingGroupChatRequestCount() {
            return this.groupChatRequestHistory.filter(
                (request) => request.approval_status === "pending",
            ).length;
        },
        greetingLabel() {
            const hour = this.now.getHours();

            if (hour < 12) {
                return "Good morning,";
            }

            if (hour < 18) {
                return "Good afternoon,";
            }

            return "Good evening,";
        },
        currentDateBadge() {
            return new Intl.DateTimeFormat("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
            }).format(this.now);
        },
        currentTimeBadge() {
            return new Intl.DateTimeFormat("en-US", {
                hour: "numeric",
                minute: "2-digit",
            }).format(this.now);
        },
        totalUnreadCount() {
            return this.users.reduce(
                (total, user) => total + Number(user.unread_count || 0),
                0,
            );
        },
        showInitialPageSkeleton() {
            return this.loadingConversation && !this.didHydrateFromCache;
        },
        typingIndicatorLabel() {
            if (!this.activeConversationIsGroup) {
                return "typing...";
            }

            const senderName = String(
                this.typingIndicatorSenderName || "",
            ).trim();
            return senderName
                ? `${senderName} is typing...`
                : "Someone is typing...";
        },
        activeUser() {
            return (
                this.users.find(
                    (user) =>
                        user.conversation_key === this.selectedConversationKey,
                ) || null
            );
        },
        activeUserName() {
            return this.activeUser?.name || "Select a conversation";
        },
        activeUserAvatar() {
            return (
                this.activeUser?.profile ||
                `https://ui-avatars.com/api/?name=${encodeURIComponent(this.activeUserName)}&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true`
            );
        },
        activeUserStatus() {
            if (!this.activeUser) {
                return "Choose a user on the left";
            }

            return this.getConversationStatusLabel(this.activeUser);
        },
        latestSeenReceiptMessageId() {
            for (let index = this.messages.length - 1; index >= 0; index -= 1) {
                const message = this.messages[index];

                if (message?.is_mine && message?.read_at) {
                    return Number(message.id);
                }
            }

            return null;
        },
        latestGroupSeenReceiptMessageId() {
            if (!this.activeConversationIsGroup) {
                return null;
            }

            for (let index = this.messages.length - 1; index >= 0; index -= 1) {
                const message = this.messages[index];

                if (!message?.is_mine || message?.is_system) {
                    continue;
                }

                if (this.getGroupSeenUsers(message).length > 0) {
                    return Number(message.id);
                }
            }

            return null;
        },
        activeGroupMembers() {
            if (!this.activeConversationIsGroup) {
                return [];
            }

            const members = Array.isArray(this.activeUser?.members)
                ? this.activeUser.members
                : [];
            return members.map((member) => ({
                ...member,
                id: Number(member.id),
                last_read_at: member.last_read_at || null,
            }));
        },
        groupMembersModalMembers() {
            if (this.groupMembersModalMembersOverride.length > 0) {
                return this.groupMembersModalMembersOverride;
            }

            return this.activeGroupMembers;
        },
        groupMembersModalTitle() {
            return this.groupMembersModalTitleOverride || this.activeUserName;
        },
        activeGroupSelfMember() {
            const authUserId = Number(this.authUser?.id || 0);
            return (
                this.activeGroupMembers.find(
                    (member) => Number(member.id) === authUserId,
                ) || null
            );
        },
        replyTargetLabel() {
            if (!this.replyTargetMessage) {
                return "you";
            }

            if (this.replyTargetMessage.is_mine) {
                return "you";
            }

            if (this.activeConversationIsGroup) {
                return this.replyTargetMessage.sender_name || "User";
            }

            return this.activeUserName;
        },
        activeConversationIsGroup() {
            return this.activeUser?.conversation_type === "group";
        },
        isAdmin() {
            return Boolean(this.authUser?.is_admin);
        },
        visibleUsers() {
            const search = this.searchQuery.trim().toLowerCase();
            const filtered = [...this.users]
                .filter((user) => {
                    const haystack =
                        `${user.name || ""} ${user.email || ""} ${user.preview || ""}`.toLowerCase();
                    const matchesSearch = !search || haystack.includes(search);
                    return matchesSearch;
                })
                .sort((a, b) => {
                    const aTime = a.latest_at
                        ? new Date(a.latest_at).getTime()
                        : 0;
                    const bTime = b.latest_at
                        ? new Date(b.latest_at).getTime()
                        : 0;
                    return bTime - aTime;
                });

            return filtered;
        },
        latestPinnedPreview() {
            return this.pinnedMessages?.[0]?.preview || "";
        },
        messageActionModalMessagePreview() {
            return (
                this.messageActionModalMessage?.body ||
                this.messageActionModalMessage?.attachment?.name ||
                "This message will be removed."
            );
        },
        messageActionModalSubmitDisabled() {
            if (this.messageActionModalMode !== "edit") {
                return false;
            }

            const body = this.messageActionModalBody.trim();
            const original = this.messageActionModalMessage?.body || "";

            return !body || body === original.trim();
        },
        reactionTargetPreview() {
            const preview = this.getMessageSnippet(this.selectedMessage);

            if (!preview) {
                return "Pick an emoji to react.";
            }

            return preview.length > 96 ? `${preview.slice(0, 93)}...` : preview;
        },
        selectedAttachmentName() {
            return this.selectedAttachment?.name || "";
        },
        messageCharacterCount() {
            return this.draftMessage.length;
        },
        messageCharactersRemaining() {
            return Math.max(
                0,
                this.messageCharacterLimit - this.messageCharacterCount,
            );
        },
        canSubmitGroupInfo() {
            if (!this.activeUser) {
                return false;
            }

            if (this.activeConversationIsGroup) {
                return this.groupInfoForm.name.trim() !== "";
            }

            return true;
        },
        conversationInfoImageItems() {
            return this.conversationInfoMediaItems.filter(
                (item) => item?.attachment?.type === "image",
            );
        },
        conversationInfoFileItems() {
            return this.conversationInfoMediaItems.filter(
                (item) => item?.attachment?.type !== "image",
            );
        },
        filteredInvitableUsers() {
            const memberIds = new Set(
                (Array.isArray(this.activeUser?.member_ids)
                    ? this.activeUser.member_ids
                    : []
                ).map((id) => Number(id)),
            );

            return this.availableUsers.filter((user) => {
                if (memberIds.has(Number(user.id))) {
                    return false;
                }

                return true;
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
        this.receiveSound = new Audio("/sounds/receive.mp3");
        this.receiveSound.preload = "auto";
        this.sendSound = new Audio("/sounds/sent.mp3");
        this.sendSound.preload = "auto";
        this.hydrateOnlineUsersFromSharedState();
        this.bindSharedOnlineUsersListener();
        this.initializeOnlineUsers();
        this.bindPresenceLifecycleEvents();
        this.announcePresence("online");
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
        window.addEventListener("popstate", this.handleBrowserNavigation);
        window.addEventListener("click", this.handleGlobalClick);
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

        this.announcePresence("offline");
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

        window.removeEventListener("popstate", this.handleBrowserNavigation);
        window.removeEventListener("click", this.handleGlobalClick);
        this.destroyImageGallery();
    },
    methods: {
        normalizedMessagesBaseUrl() {
            const fallbackPath =
                `${window.location.origin}${window.location.pathname}`.replace(
                    /\/+$/,
                    "",
                );
            return String(this.messagesBaseUrl || fallbackPath).replace(
                /\/+$/,
                "",
            );
        },
        buildConversationUrl(conversation = null) {
            const baseUrl = this.normalizedMessagesBaseUrl();
            const token = String(conversation?.conversation_token || "").trim();

            return token ? `${baseUrl}/${encodeURIComponent(token)}` : baseUrl;
        },
        currentConversationTokenFromLocation() {
            const basePath = new URL(
                this.normalizedMessagesBaseUrl(),
                window.location.origin,
            ).pathname.replace(/\/+$/, "");
            const currentPath = window.location.pathname.replace(/\/+$/, "");

            if (
                currentPath === basePath ||
                !currentPath.startsWith(`${basePath}/`)
            ) {
                return null;
            }

            return (
                decodeURIComponent(currentPath.slice(basePath.length + 1)) ||
                null
            );
        },
        updateConversationUrl(conversation = null, historyMode = "push") {
            const nextUrl = this.buildConversationUrl(conversation);
            const nextPathname = new URL(nextUrl, window.location.origin)
                .pathname;
            const currentPathname = window.location.pathname.replace(
                /\/+$/,
                "",
            );

            if (currentPathname === nextPathname.replace(/\/+$/, "")) {
                return;
            }

            const state = {
                conversation_key: conversation?.conversation_key || null,
            };

            if (historyMode === "replace") {
                window.history.replaceState(state, "", nextUrl);
                return;
            }

            window.history.pushState(state, "", nextUrl);
        },
        resolveConversationFromLocation() {
            const token = this.currentConversationTokenFromLocation();

            if (!token) {
                return this.users[0] || null;
            }

            return (
                this.users.find((user) => user.conversation_token === token) ||
                this.users[0] ||
                null
            );
        },
        handleBrowserNavigation() {
            const targetConversation = this.resolveConversationFromLocation();

            if (
                !targetConversation ||
                targetConversation.conversation_key ===
                    this.selectedConversationKey
            ) {
                return;
            }

            this.selectUser(targetConversation, { updateHistory: false });
        },
        handleGlobalClick(event) {
            this.contactActionMenuKey = null;

            if (!this.showComposerEmojiPicker) {
                return;
            }

            const overlay = this.getComposerEmojiOverlayElement();
            const button = this.getComposerEmojiButtonElement();
            const target = event?.target || null;

            if (overlay?.contains?.(target) || button?.contains?.(target)) {
                return;
            }

            this.closeComposerEmojiPicker();
        },
        ensureAuthHeaders() {
            const token = localStorage.getItem("auth_token");

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
        getConversationBodyElement() {
            return this.$refs.messageStream?.getBodyElement?.() || null;
        },
        getComposerInputElement() {
            return this.$refs.composerArea?.getInputElement?.() || null;
        },
        getAttachmentInputElement() {
            return this.$refs.composerArea?.getAttachmentInputElement?.() || null;
        },
        getComposerEmojiButtonElement() {
            return this.$refs.composerArea?.getEmojiButtonElement?.() || null;
        },
        getComposerEmojiOverlayElement() {
            return this.$refs.composerArea?.getEmojiOverlayElement?.() || null;
        },
        getMessagesCacheKey() {
            const authUserId = Number(this.authUser?.id || 0);
            return authUserId
                ? `employee-messages-cache:${this.cacheVersion}:${authUserId}`
                : "";
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
                const cachedUsers = Array.isArray(parsed?.users)
                    ? parsed.users
                    : [];
                const cachedSelectedConversationKey =
                    parsed?.selectedConversationKey || null;
                const cachedConversation = parsed?.activeConversation || null;

                if (cachedUsers.length) {
                    const userMap = new Map(
                        cachedUsers.map((user) => [
                            user.conversation_key,
                            user,
                        ]),
                    );
                    this.users = this.users.map((user) => {
                        const cachedUser = userMap.get(user.conversation_key);
                        return cachedUser ? { ...user, ...cachedUser } : user;
                    });
                }

                const locationConversationToken =
                    this.currentConversationTokenFromLocation();

                if (
                    cachedSelectedConversationKey &&
                    !locationConversationToken
                ) {
                    const exists = this.users.some(
                        (user) =>
                            user.conversation_key ===
                            cachedSelectedConversationKey,
                    );
                    if (exists) {
                        this.selectedConversationKey =
                            cachedSelectedConversationKey;
                    }
                }

                if (
                    cachedConversation &&
                    cachedConversation.conversation_key ===
                        this.selectedConversationKey
                ) {
                    this.messages = Array.isArray(cachedConversation.messages)
                        ? cachedConversation.messages.map((message) =>
                              this.normalizeMessage(message),
                          )
                        : [];
                    this.pinnedMessages = Array.isArray(
                        cachedConversation.pinnedMessages,
                    )
                        ? cachedConversation.pinnedMessages
                        : [];
                    this.conversationPage = Number(
                        cachedConversation.conversationPage || 1,
                    );
                    this.conversationLastPage = Number(
                        cachedConversation.conversationLastPage || 1,
                    );
                    this.conversationHasMore = Boolean(
                        cachedConversation.conversationHasMore,
                    );
                }

                return Boolean(
                    cachedUsers.length ||
                    (cachedConversation && this.messages.length),
                );
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

                localStorage.setItem(
                    cacheKey,
                    JSON.stringify({
                        savedAt: Date.now(),
                        selectedConversationKey: this.selectedConversationKey,
                        users: this.users.slice(0, 40).map((user) => ({
                            conversation_key: user.conversation_key,
                            preview: user.preview || "",
                            latest_at: user.latest_at || null,
                            unread_count: Number(user.unread_count || 0),
                            is_unread: Boolean(user.is_unread),
                            active_label: user.active_label || "",
                            is_active: Boolean(user.is_active),
                            last_seen_at: user.last_seen_at || null,
                        })),
                        activeConversation,
                    }),
                );
            } catch (error) {
                // Ignore cache write failures.
            }
        },
        initializeDirectMessageListener() {
            if (this.messageChannel || !window.Echo || !this.authUser?.id) {
                return;
            }

            this.messageChannel = window.Echo.private(
                `direct-messages.${this.authUser.id}`,
            )
                .listen(".direct-message.sent", (event) => {
                    const message = event?.message;
                    if (!message) return;

                    if (
                        Number(message.sender_id || 0) !==
                        Number(this.authUser?.id || 0)
                    ) {
                        this.playReceiveSound();
                    }

                    this.handleIncomingDirectMessage(message);
                })
                .listen(".direct-message.updated", (event) => {
                    const payload = event?.payload || {};
                    const message = payload.message || null;
                    if (!message) return;

                    this.handleIncomingDirectMessage(
                        message,
                        payload.pinned_messages || null,
                        payload.conversation_preview || null,
                    );
                })
                .listen(".direct-message.typing", (event) => {
                    const payload = event?.payload || {};
                    const activeUserId = Number(this.activeUser?.id || 0);
                    const senderId = Number(payload.sender_id || 0);

                    if (
                        !payload.is_typing ||
                        !activeUserId ||
                        senderId !== activeUserId
                    ) {
                        this.typingIndicator = false;
                        this.typingIndicatorSenderName = "";

                        if (this.typingIndicatorTimer) {
                            window.clearTimeout(this.typingIndicatorTimer);
                            this.typingIndicatorTimer = null;
                        }

                        return;
                    }

                    this.typingIndicator = true;
                    this.typingIndicatorSenderName = "";

                    if (this.typingIndicatorTimer) {
                        window.clearTimeout(this.typingIndicatorTimer);
                    }

                    this.typingIndicatorTimer = window.setTimeout(() => {
                        this.typingIndicator = false;
                        this.typingIndicatorTimer = null;
                    }, 2500);

                    this.$nextTick(() => {
                        const bodyEl =
                            this.getConversationBodyElement() ||
                            this.$el.querySelector(".conversation-panel__body");
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
                .listen(".group-chat.typing", (event) => {
                    const payload = event?.payload || {};
                    const activeGroupId = this.activeConversationIsGroup
                        ? Number(this.activeUser?.id || 0)
                        : 0;
                    const senderId = Number(payload.sender_id || 0);
                    const groupChatId = Number(payload.group_chat_id || 0);

                    if (
                        !payload.is_typing ||
                        !activeGroupId ||
                        groupChatId !== activeGroupId ||
                        senderId === Number(this.authUser?.id || 0)
                    ) {
                        this.typingIndicator = false;
                        this.typingIndicatorSenderName = "";

                        if (this.typingIndicatorTimer) {
                            window.clearTimeout(this.typingIndicatorTimer);
                            this.typingIndicatorTimer = null;
                        }

                        return;
                    }

                    this.typingIndicator = true;
                    this.typingIndicatorSenderName = String(
                        payload.sender_name || "",
                    ).trim();

                    if (this.typingIndicatorTimer) {
                        window.clearTimeout(this.typingIndicatorTimer);
                    }

                    this.typingIndicatorTimer = window.setTimeout(() => {
                        this.typingIndicator = false;
                        this.typingIndicatorSenderName = "";
                        this.typingIndicatorTimer = null;
                    }, 2500);

                    this.$nextTick(() => {
                        const bodyEl =
                            this.getConversationBodyElement() ||
                            this.$el.querySelector(".conversation-panel__body");
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
                .listen(".direct-message.seen", (event) => {
                    const payload = event?.payload || {};
                    const threadUserId = Number(
                        payload.reader_id || payload.partner_id || 0,
                    );
                    const selectedUserId = this.activeConversationIsGroup
                        ? 0
                        : Number(this.activeUser?.id || 0);

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

                    this.applySeenReceipt(threadUserId, readAt, [
                        ...messageIds,
                    ]);
                })
                .listen(".group-chat.seen", (event) => {
                    const payload = event?.payload || {};
                    const groupChatId = Number(payload.group_chat_id || 0);
                    const readerId = Number(
                        payload.reader_id || payload.reader?.id || 0,
                    );
                    const readAt =
                        payload.read_at || payload.reader?.last_read_at || null;

                    if (!groupChatId || !readerId || !readAt) {
                        return;
                    }

                    this.applyGroupSeenReceipt(
                        groupChatId,
                        payload.reader || null,
                        readAt,
                    );
                })
                .listen(".group-chat.created", (event) => {
                    if (!event?.conversation) {
                        return;
                    }

                    this.upsertConversation({
                        ...event.conversation,
                        conversation_type: "group",
                    });
                })
                .listen(".group-chat.message-sent", (event) => {
                    if (!event?.message || !event?.conversation) {
                        return;
                    }

                    if (
                        Number(event.message.sender_id || 0) !==
                        Number(this.authUser?.id || 0)
                    ) {
                        this.playReceiveSound();
                    }

                    this.handleIncomingGroupMessage(
                        event.message,
                        event.conversation,
                    );
                })
                .listen(".group-chat.message-updated", (event) => {
                    if (!event?.message || !event?.conversation) {
                        return;
                    }

                    this.handleIncomingGroupMessage(
                        event.message,
                        event.conversation,
                        event.pinned_messages || null,
                    );
                })
                .listen(".group-chat.updated", (event) => {
                    this.handleGroupConversationUpdated(event);
                })
                .listen(".group-chat.request-updated", (event) => {
                    if (!event?.request) {
                        return;
                    }

                    if (!this.isAdmin) {
                        if (
                            event.action === "created" &&
                            Number(event.request?.creator?.id || 0) ===
                                Number(this.authUser?.id || 0)
                        ) {
                            this.upsertGroupChatRequestHistory(event.request);
                        }

                        if (
                            event.action === "cancelled" &&
                            Number(event.request?.creator?.id || 0) ===
                                Number(this.authUser?.id || 0)
                        ) {
                            this.groupChatRequestHistory =
                                this.groupChatRequestHistory.filter(
                                    (item) =>
                                        Number(item.id) !==
                                        Number(event.request?.id || 0),
                                );
                        }

                        if (
                            ["approved", "rejected"].includes(event.action) &&
                            Number(event.request?.creator?.id || 0) ===
                                Number(this.authUser?.id || 0)
                        ) {
                            this.upsertGroupChatRequestHistory(event.request);
                        }

                        if (
                            event.action === "approved" &&
                            Number(event.request?.creator?.id || 0) ===
                                Number(this.authUser?.id || 0)
                        ) {
                            this.notify({
                                type: "success",
                                title: "Group chat approved",
                                message: `"${event.request?.name || "Your group chat"}" is now available in your messages.`,
                                duration: 4200,
                            });
                        }

                        if (
                            event.action === "rejected" &&
                            Number(event.request?.creator?.id || 0) ===
                                Number(this.authUser?.id || 0)
                        ) {
                            this.notify({
                                type: "error",
                                title: "Group chat request declined",
                                message: `"${event.request?.name || "Your group chat"}" was declined by an admin.`,
                                duration: 4200,
                            });
                        }

                        return;
                    }

                    this.handlePendingGroupChatRequestUpdate(
                        event.action,
                        event.request,
                    );
                });
        },
        bindPresenceLifecycleEvents() {
            if (this.presenceLifecycleBound) {
                return;
            }

            this.handlePresencePageHide = () => {
                this.announcePresence("offline", true);
            };

            this.handlePresenceBeforeUnload = () => {
                this.announcePresence("offline", true);
            };

            window.addEventListener("pagehide", this.handlePresencePageHide);
            window.addEventListener(
                "beforeunload",
                this.handlePresenceBeforeUnload,
            );
            this.presenceLifecycleBound = true;
        },
        unbindPresenceLifecycleEvents() {
            if (!this.presenceLifecycleBound) {
                return;
            }

            if (this.handlePresencePageHide) {
                window.removeEventListener(
                    "pagehide",
                    this.handlePresencePageHide,
                );
            }

            if (this.handlePresenceBeforeUnload) {
                window.removeEventListener(
                    "beforeunload",
                    this.handlePresenceBeforeUnload,
                );
            }

            this.handlePresencePageHide = null;
            this.handlePresenceBeforeUnload = null;
            this.presenceLifecycleBound = false;
        },
        async announcePresence(status = "online", useKeepAlive = false) {
            try {
                const token = localStorage.getItem("auth_token");
                const headers = {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                };

                if (token) {
                    headers.Authorization = `Bearer ${token}`;
                }

                await fetch("/api/presence", {
                    method: "POST",
                    headers,
                    credentials: "same-origin",
                    keepalive: useKeepAlive,
                    body: JSON.stringify({ status }),
                });
            } catch (error) {
                console.error("Failed to announce presence:", error);
            }
        },
        playReceiveSound() {
            if (!this.receiveSound) return;

            this.receiveSound.currentTime = 0;
            const played = this.receiveSound.play();

            if (played && typeof played.catch === "function") {
                played.catch(() => {});
            }
        },
        playSendSound() {
            if (!this.sendSound) return;

            this.sendSound.currentTime = 0;
            const played = this.sendSound.play();

            if (played && typeof played.catch === "function") {
                played.catch(() => {});
            }
        },
        handleIncomingDirectMessage(
            message,
            pinnedMessages = null,
            conversationPreview = null,
        ) {
            if (!message?.id) {
                return;
            }

            const authUserId = Number(this.authUser?.id || 0);
            const senderId = Number(message.sender_id || 0);
            const recipientId = Number(message.recipient_id || 0);
            const partnerId = senderId === authUserId ? recipientId : senderId;
            const activeConversationKey =
                this.activeUser?.conversation_key || null;
            const targetConversationKey = `direct:${partnerId}`;
            const isActiveConversation =
                activeConversationKey === targetConversationKey;
            const targetUser = this.users.find(
                (user) => user.conversation_key === targetConversationKey,
            );
            const snippet = this.getMessageSnippet(message);
            const hasExistingMessage = this.messages.some(
                (item) => Number(item.id) === Number(message.id),
            );

            if (targetUser) {
                targetUser.latest_at =
                    message.created_at || new Date().toISOString();
                targetUser.preview = snippet;
                targetUser.unread_count =
                    senderId === authUserId
                        ? 0
                        : isActiveConversation
                          ? 0
                          : Number(targetUser.unread_count || 0) + 1;
                targetUser.is_unread = Number(targetUser.unread_count || 0) > 0;
            }

            if (isActiveConversation) {
                this.upsertLocalMessage({
                    ...message,
                    is_mine: senderId === authUserId,
                    read_at:
                        senderId === authUserId
                            ? null
                            : (message.read_at ?? null),
                });

                if (message.is_unsent) {
                    this.clearSelectedMessage();
                }

                if (Array.isArray(pinnedMessages)) {
                    this.pinnedMessages = pinnedMessages;
                }

                if (conversationPreview?.preview && targetUser) {
                    this.applyConversationPreview(
                        targetUser,
                        conversationPreview,
                    );
                }

                if (!hasExistingMessage) {
                    if (message?.attachment?.type === "image") {
                        this.pendingImageAutoScrollCount += 1;
                    }
                    this.scrollConversationToBottom();
                }

                if (
                    senderId !== authUserId &&
                    this.isConversationPanelVisible()
                ) {
                    this.markConversationSeen(partnerId);
                }
                return;
            }

            if (targetUser) {
                if (conversationPreview?.preview) {
                    this.applyConversationPreview(
                        targetUser,
                        conversationPreview,
                    );
                }
                this.syncUsersOnlineState();
            }
        },
        handleIncomingGroupMessage(
            message,
            conversation,
            pinnedMessages = null,
        ) {
            const conversationKey =
                conversation?.conversation_key ||
                `group:${conversation?.id || message?.group_chat_id}`;
            const isActiveConversation =
                this.selectedConversationKey === conversationKey;
            const hasExistingMessage = this.messages.some(
                (item) => Number(item.id) === Number(message.id),
            );
            const authUserId = Number(this.authUser?.id || 0);
            const senderId = Number(message?.sender_id || 0);

            this.upsertConversation({
                ...conversation,
                conversation_type: "group",
            });

            const targetConversation = this.users.find(
                (user) => user.conversation_key === conversationKey,
            );
            if (targetConversation) {
                targetConversation.latest_at =
                    message?.created_at || new Date().toISOString();
                targetConversation.preview = this.getMessageSnippet(message);
                targetConversation.unread_count =
                    senderId === authUserId
                        ? 0
                        : isActiveConversation
                          ? 0
                          : Number(targetConversation.unread_count || 0) + 1;
                targetConversation.is_unread =
                    Number(targetConversation.unread_count || 0) > 0;
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
                if (message?.attachment?.type === "image") {
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
                const conversationKey =
                    conversation?.conversation_key ||
                    `group:${conversation?.id || this.activeUser?.id || 0}`;
                this.removeConversation(conversationKey);
                return;
            }

            if (conversation?.conversation_key) {
                this.upsertConversation({
                    ...conversation,
                    conversation_type: "group",
                });
            }
        },
        handlePendingGroupChatRequestUpdate(action, request) {
            const normalizedRequest = {
                ...request,
                id: Number(request.id),
            };
            const existingIndex = this.pendingGroupChatApprovals.findIndex(
                (item) => Number(item.id) === Number(normalizedRequest.id),
            );

            if (action === "created") {
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
            const existingIndex = this.groupChatRequestHistory.findIndex(
                (item) => Number(item.id) === Number(normalizedRequest.id),
            );

            if (existingIndex >= 0) {
                this.groupChatRequestHistory.splice(existingIndex, 1, {
                    ...this.groupChatRequestHistory[existingIndex],
                    ...normalizedRequest,
                });
            } else {
                this.groupChatRequestHistory.unshift(normalizedRequest);
            }

            this.groupChatRequestHistory = [
                ...this.groupChatRequestHistory,
            ].sort((left, right) => {
                const leftAt = new Date(
                    left.processed_at || left.created_at || 0,
                ).getTime();
                const rightAt = new Date(
                    right.processed_at || right.created_at || 0,
                ).getTime();

                return rightAt - leftAt;
            });
        },
        formatRequestStatus(status) {
            const normalizedStatus = String(status || "").toLowerCase();

            if (normalizedStatus === "approved") {
                return "Approved";
            }

            if (normalizedStatus === "rejected") {
                return "Rejected";
            }

            return "Pending";
        },
        openApprovedRequestConversation(request) {
            if (!request?.id) {
                return;
            }

            const targetConversationKey = `group:${Number(request.id)}`;
            const targetConversation = this.users.find(
                (user) => user.conversation_key === targetConversationKey,
            );

            if (!targetConversation) {
                this.notify({
                    type: "error",
                    title: "Conversation unavailable",
                    message:
                        "This approved group chat is not ready in your current conversation list yet.",
                    duration: 3600,
                });
                return;
            }

            this.closeGroupChatRequestsModal();
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

                this.onlineUserIds = this.onlineUserIds.filter(
                    (id) => id !== userId,
                );
                this.markUserSeen(userId);
                this.syncUsersOnlineState();
            };

            this.onlineUsersUpdatedListener = (event) => {
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
            };

            this.onlineUsersChannel = window.Echo.join("online-users")
                .here(this.onlineUsersHereListener)
                .joining(this.onlineUsersJoiningListener)
                .leaving(this.onlineUsersLeavingListener);

            this.onlineUsersChannel.listen(
                ".online-users.updated",
                this.onlineUsersUpdatedListener,
            );
        },
        hydrateOnlineUsersFromSharedState() {
            const sharedIds = window.__onlineUsersPresence?.onlineUserIds || [];

            if (!Array.isArray(sharedIds) || sharedIds.length === 0) {
                return;
            }

            this.onlineUserIds = [
                ...new Set(sharedIds.map((id) => Number(id))),
            ];
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

                this.onlineUserIds = [
                    ...new Set(sharedIds.map((id) => Number(id))),
                ];
                this.onlineUsersResolved = true;
                this.syncUsersOnlineState();
            };

            window.addEventListener(
                "online-users:updated",
                this.sharedOnlineUsersListener,
            );
        },
        unbindSharedOnlineUsersListener() {
            if (!this.sharedOnlineUsersListener) {
                return;
            }

            window.removeEventListener(
                "online-users:updated",
                this.sharedOnlineUsersListener,
            );
            this.sharedOnlineUsersListener = null;
        },
        teardownOnlineUsersListeners() {
            if (!this.onlineUsersChannel) {
                return;
            }

            if (this.onlineUsersHereListener) {
                this.onlineUsersChannel.stopListening(
                    "pusher:subscription_succeeded",
                    this.onlineUsersHereListener,
                );
                this.onlineUsersHereListener = null;
            }

            if (this.onlineUsersJoiningListener) {
                this.onlineUsersChannel.stopListening(
                    "pusher:member_added",
                    this.onlineUsersJoiningListener,
                );
                this.onlineUsersJoiningListener = null;
            }

            if (this.onlineUsersLeavingListener) {
                this.onlineUsersChannel.stopListening(
                    "pusher:member_removed",
                    this.onlineUsersLeavingListener,
                );
                this.onlineUsersLeavingListener = null;
            }

            if (this.onlineUsersUpdatedListener) {
                this.onlineUsersChannel.stopListening(
                    ".online-users.updated",
                    this.onlineUsersUpdatedListener,
                );
                this.onlineUsersUpdatedListener = null;
            }

            this.onlineUsersChannel = null;
        },
        getLastSeenStore() {
            try {
                return JSON.parse(
                    localStorage.getItem("online-users-last-seen") || "{}",
                );
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

            localStorage.setItem(
                "online-users-last-seen",
                JSON.stringify(store),
            );
        },
        markUserSeen(userId) {
            const store = this.getLastSeenStore();
            store[Number(userId)] = Date.now();
            localStorage.setItem(
                "online-users-last-seen",
                JSON.stringify(store),
            );
        },
        getLastSeen(userId) {
            const store = this.getLastSeenStore();
            return store[Number(userId)] ?? null;
        },
        syncUsersOnlineState() {
            this.users = this.users.map((user) => {
                if (user.conversation_type === "group") {
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

                const activityState = this.getActivityState(
                    user.id,
                    user.latest_at,
                );

                return {
                    ...user,
                    is_active: activityState.isActive,
                    active_label: activityState.label,
                    last_seen_at: activityState.lastSeenAt,
                };
            });
        },
        getGroupActivityState(user) {
            const memberIds = Array.isArray(user?.member_ids)
                ? user.member_ids.map((id) => Number(id))
                : [];
            const authUserId = Number(this.authUser?.id || 0);
            const otherMemberIds = memberIds.filter(
                (id) => id && id !== authUserId,
            );
            const onlineUserIds = Array.isArray(this.onlineUserIds)
                ? this.onlineUserIds
                : [];
            const onlineOthers = otherMemberIds.filter((id) =>
                onlineUserIds.includes(id),
            );
            const totalMembers = Number(
                user?.member_count || memberIds.length || 0,
            );

            if (onlineOthers.length > 0) {
                return {
                    label:
                        onlineOthers.length === 1
                            ? "1 member online"
                            : `${onlineOthers.length} members online`,
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

            if (user.conversation_type === "group") {
                return this.getGroupActivityState(user).isActive;
            }

            const onlineUserIds = Array.isArray(this.onlineUserIds)
                ? this.onlineUserIds
                : [];
            return (
                onlineUserIds.includes(Number(user.id)) ||
                Boolean(user.is_active)
            );
        },
        getConversationStatusLabel(user) {
            if (!user) {
                return "Offline";
            }

            if (user.conversation_type === "group") {
                return this.getGroupActivityState(user).label;
            }

            if (this.isConversationOnline(user)) {
                return "Online";
            }

            return this.getActivityState(user.id).label;
        },
        getMemberProfile(member) {
            if (member?.profile) {
                return member.profile;
            }

            const name = member?.name || "User";
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true`;
        },
        getRequestMemberPreview(members = []) {
            return members.slice(0, 10);
        },
        getRequestMemberOverflowCount(members = []) {
            return Math.max(members.length - 10, 0);
        },
        toggleGroupRequestTooltip(requestId) {
            this.activeGroupRequestTooltipId =
                this.activeGroupRequestTooltipId === requestId
                    ? null
                    : requestId;
        },
        selectUser(user, { updateHistory = true, historyMode = "push" } = {}) {
            this.contactActionMenuKey = null;
            if (
                !user ||
                user.conversation_key === this.selectedConversationKey
            ) {
                this.closeMobileUsersPanel();
                return;
            }

            this.closeMobileUsersPanel();
            this.clearTypingTimers();
            this.typingIndicator = false;
            this.selectedConversationKey = user.conversation_key;
            this.conversationError = "";
            this.resetConversationState();
            this.showPinnedMessagesPanel = false;
            this.showComposerEmojiPicker = false;
            this.clearSelectedMessage();
            this.clearReplyTarget();
            this.clearSelectedAttachment();
            if (updateHistory) {
                this.updateConversationUrl(user, historyMode);
            }
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
            this.closeSeenByModal();
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

            this.contactActionMenuKey =
                this.contactActionMenuKey === conversationKey
                    ? null
                    : conversationKey;
        },
        openConversationDeleteModal(user) {
            if (!user?.conversation_key) {
                return;
            }

            this.contactActionTarget = user;
            this.conversationDeleteError = "";
            this.conversationDeleteModalVisible = true;
            this.contactActionMenuKey = null;
        },
        closeConversationDeleteModal() {
            if (this.conversationDeleteSubmitting) {
                return;
            }

            this.conversationDeleteModalVisible = false;
            this.conversationDeleteError = "";
            this.contactActionTarget = null;
        },
        applyConversationClear(targetConversation, conversationPreview = null) {
            if (!targetConversation?.conversation_key) {
                return;
            }

            const preview =
                conversationPreview?.preview ||
                (targetConversation.conversation_type === "group"
                    ? "Group chat is ready"
                    : "Start a conversation");
            const latestAt = Object.prototype.hasOwnProperty.call(
                conversationPreview || {},
                "latest_at",
            )
                ? conversationPreview.latest_at
                : null;

            const targetUser = this.users.find(
                (item) =>
                    item.conversation_key ===
                    targetConversation.conversation_key,
            );

            if (targetUser) {
                targetUser.preview = preview;
                targetUser.latest_at = latestAt;
                targetUser.unread_count = 0;
                targetUser.is_unread = false;
            }

            if (
                this.selectedConversationKey ===
                targetConversation.conversation_key
            ) {
                this.messages = [];
                this.pinnedMessages = [];
                this.typingIndicator = false;
                this.typingIndicatorSenderName = "";
                this.clearSelectedMessage();
                this.clearReplyTarget();
                this.showPinnedMessagesPanel = false;
                this.showComposerEmojiPicker = false;
            }

            this.scheduleMessagesCachePersist();
        },
        async confirmDeleteConversationMessages() {
            if (
                !this.contactActionTarget?.id ||
                this.conversationDeleteSubmitting
            ) {
                return;
            }

            this.conversationDeleteSubmitting = true;
            this.conversationDeleteError = "";

            try {
                const target = this.contactActionTarget;
                const endpoint =
                    target.conversation_type === "group"
                        ? `/api/group-chats/${target.id}/messages`
                        : `/api/direct-messages/conversation/${target.id}`;
                const { data } = await axios.delete(endpoint, {
                    headers: this.buildAuthHeaders(),
                });

                this.applyConversationClear(
                    target,
                    data?.conversation_preview || null,
                );
                this.conversationDeleteSubmitting = false;
                this.closeConversationDeleteModal();
            } catch (error) {
                this.conversationDeleteError =
                    error?.response?.data?.message ||
                    "Unable to delete your copy of this conversation.";
                this.conversationDeleteSubmitting = false;
            }
        },
        async loadConversation(
            conversationOrId,
            {
                page = 1,
                preserveScroll = false,
                preserveVisibleState = false,
            } = {},
        ) {
            this.ensureAuthHeaders();

            const user =
                typeof conversationOrId === "object"
                    ? conversationOrId
                    : this.users.find(
                          (item) =>
                              Number(item.id) === Number(conversationOrId),
                      );
            if (!user) return;

            this.clearTypingTimers();
            this.typingIndicator = false;
            const isOlderLoad = page > 1;
            const bodyEl = this.getConversationBodyElement();
            const previousScrollHeight =
                preserveScroll && bodyEl ? bodyEl.scrollHeight : 0;
            const previousScrollTop =
                preserveScroll && bodyEl ? bodyEl.scrollTop : 0;

            if (isOlderLoad) {
                this.loadingOlderConversation = true;
            } else {
                this.loadingConversation = true;
                this.loadingOlderConversation = false;
            }

            this.conversationError = "";
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
                const url =
                    user.conversation_type === "group"
                        ? `/api/group-chats/${user.id}`
                        : `/api/direct-messages/${user.id}`;
                const { data } = await axios.get(url, {
                    params: {
                        page,
                        per_page: 20,
                    },
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (this.selectedConversationKey !== user.conversation_key) {
                    return;
                }

                const messages = Array.isArray(data.messages)
                    ? data.messages.map((message) =>
                          this.normalizeMessage(message),
                      )
                    : [];
                const pagination = data.pagination ?? {};
                this.conversationPage = pagination.current_page ?? page;
                this.conversationLastPage = pagination.last_page ?? page;
                this.conversationHasMore = Boolean(pagination.has_more);

                if (page === 1) {
                    this.messages = messages;
                } else if (messages.length > 0) {
                    const existingIds = new Set(
                        this.messages.map((item) => Number(item.id)),
                    );
                    const olderMessages = messages.filter(
                        (message) => !existingIds.has(Number(message.id)),
                    );
                    this.messages = [...olderMessages, ...this.messages];
                }

                this.pinnedMessages = Array.isArray(data.pinned_messages)
                    ? data.pinned_messages
                    : [];
                user.unread_count = 0;
                user.is_unread = false;
                user.preview =
                    this.messages.length > 0
                        ? this.getMessageSnippet(
                              this.messages[this.messages.length - 1] || null,
                          )
                        : user.conversation_type === "group"
                          ? "Group chat is ready"
                          : "Start a conversation";
                if (user.conversation_type === "group") {
                    user.members = Array.isArray(data?.conversation?.members)
                        ? data.conversation.members
                        : [];
                    const activityState = this.getGroupActivityState({
                        ...user,
                        member_count: Number(
                            user.member_count ||
                                data?.conversation?.member_count ||
                                0,
                        ),
                        member_ids: Array.isArray(
                            data?.conversation?.member_ids,
                        )
                            ? data.conversation.member_ids
                            : user.member_ids,
                    });
                    user.member_count = Number(
                        user.member_count ||
                            data?.conversation?.member_count ||
                            0,
                    );
                    user.member_ids = Array.isArray(
                        data?.conversation?.member_ids,
                    )
                        ? data.conversation.member_ids.map((id) => Number(id))
                        : Array.isArray(user.member_ids)
                          ? user.member_ids
                          : [];
                    if (data?.conversation?.conversation_key) {
                        this.upsertConversation({
                            ...data.conversation,
                            conversation_type: "group",
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
                                Accept: "application/json",
                            },
                        },
                    );
                }

                this.showInitialCacheLoader = false;
                this.scheduleMessagesCachePersist();

                this.$nextTick(() => {
                    if (!bodyEl) return;

                    if (isOlderLoad && preserveScroll) {
                        const nextScrollHeight = bodyEl.scrollHeight;
                        bodyEl.scrollTop =
                            nextScrollHeight -
                            previousScrollHeight +
                            previousScrollTop;
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
                this.conversationError =
                    error?.response?.data?.message || "Please try again later.";
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
            const textarea = this.getComposerInputElement();
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

            this.groupMembersModalTitleOverride = "";
            this.groupMembersModalMembersOverride = [];
            this.showGroupMembersModal = true;
        },
        openRequestMembersModal(request) {
            const members = Array.isArray(request?.members) ? request.members : [];

            if (!members.length) {
                return;
            }

            this.selectedApprovalMembersRequest = {
                ...request,
                members: members.map((member) => ({
                    ...member,
                    id: Number(member.id),
                })),
            };
        },
        closeApprovalMembersView() {
            this.selectedApprovalMembersRequest = null;
        },
        openHistoryMembersView(request) {
            const members = Array.isArray(request?.members) ? request.members : [];

            if (!members.length) {
                return;
            }

            this.selectedHistoryMembersRequest = {
                ...request,
                members: members.map((member) => ({
                    ...member,
                    id: Number(member.id),
                })),
            };
        },
        closeHistoryMembersView() {
            this.selectedHistoryMembersRequest = null;
        },
        closeGroupChatRequestsModal() {
            this.selectedHistoryMembersRequest = null;
            this.showGroupChatRequestsModal = false;
        },
        closeApprovalModal() {
            this.selectedApprovalMembersRequest = null;
            this.showApprovalModal = false;
        },
        openConversationInfoModal() {
            if (!this.activeUser) {
                return;
            }

            this.groupInfoError = "";
            this.groupInfoForm = {
                name: this.activeConversationIsGroup
                    ? this.activeUser.name || ""
                    : this.activeUser.actual_name || this.activeUser.name || "",
                nickname: this.activeConversationIsGroup
                    ? this.activeGroupSelfMember?.nickname || ""
                    : this.activeUser.nickname || "",
                photo: null,
            };
            this.groupInfoModalRestoreScrollTop = 0;
            this.groupInfoModalRestoreActiveTab = "media";
            this.showGroupInfoModal = true;
            this.resetConversationInfoMedia();
            this.loadConversationInfoMedia({ page: 1 });
        },
        closeGroupInfoModal() {
            if (this.groupInfoSubmitting) {
                return;
            }

            this.reopenGroupInfoModalAfterGallery = false;
            this.groupInfoModalRestoreScrollTop = 0;
            this.groupInfoModalRestoreActiveTab = "media";
            this.showGroupInfoModal = false;
            this.groupInfoError = "";
            this.clearGroupInfoPhotoSelection();
            this.resetConversationInfoMedia();
        },
        resetConversationInfoMedia() {
            this.conversationInfoMediaItems = [];
            this.conversationInfoMediaPage = 1;
            this.conversationInfoMediaHasMore = true;
            this.conversationInfoMediaLoading = false;
            this.conversationInfoMediaLoaded = false;
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
                const existingIds = new Set(
                    this.conversationInfoMediaItems.map((item) =>
                        Number(item.message_id),
                    ),
                );
                const normalizedItems = items
                    .map((item) => ({
                        ...item,
                        message_id: Number(item.message_id),
                    }))
                    .filter(
                        (item) =>
                            !append ||
                            !existingIds.has(Number(item.message_id)),
                    );

                this.conversationInfoMediaItems = append
                    ? [...this.conversationInfoMediaItems, ...normalizedItems]
                    : normalizedItems;
                this.conversationInfoMediaPage = Number(
                    data?.pagination?.current_page || page,
                );
                this.conversationInfoMediaHasMore = Boolean(
                    data?.pagination?.has_more,
                );
            } catch (error) {
                console.error("Failed to load shared media:", error);
                this.conversationInfoMediaHasMore = false;
            } finally {
                this.conversationInfoMediaLoading = false;
                this.conversationInfoMediaLoaded = true;
            }
        },
        closeGroupMembersModal() {
            this.showGroupMembersModal = false;
            this.groupMembersModalTitleOverride = "";
            this.groupMembersModalMembersOverride = [];
        },
        handleGroupInfoPhotoChange(file) {
            if (!file) {
                this.clearGroupInfoPhotoSelection();
                return;
            }

            if (!file.type?.startsWith("image/")) {
                this.groupInfoError =
                    "Please choose an image file for the group photo.";
                this.clearGroupInfoPhotoSelection();
                return;
            }

            this.groupInfoError = "";
            this.clearGroupInfoPhotoSelection();
            this.groupInfoForm.photo = file;
        },
        clearGroupInfoPhotoSelection() {
            this.groupInfoForm.photo = null;
        },
        async submitConversationInfo() {
            if (
                !this.activeUser?.id ||
                !this.canSubmitGroupInfo ||
                this.groupInfoSubmitting
            ) {
                return;
            }

            this.groupInfoSubmitting = true;
            this.groupInfoError = "";

            try {
                let data = null;

                if (this.activeConversationIsGroup) {
                    const formData = new FormData();
                    formData.append("name", this.groupInfoForm.name.trim());

                    if (this.groupInfoForm.nickname.trim()) {
                        formData.append(
                            "nickname",
                            this.groupInfoForm.nickname.trim(),
                        );
                    }

                    if (this.groupInfoForm.photo) {
                        formData.append("photo", this.groupInfoForm.photo);
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
                            nickname:
                                this.groupInfoForm.nickname.trim() || null,
                        },
                        {
                            headers: this.buildAuthHeaders(),
                        },
                    ));
                }

                const selectedConversation = this.activeUser;

                if (data?.conversation) {
                    this.upsertConversation(
                        this.activeConversationIsGroup
                            ? {
                                  ...data.conversation,
                                  members: Array.isArray(data?.members)
                                      ? data.members
                                      : data.conversation.members,
                              }
                            : data.conversation,
                    );
                }

                this.groupInfoSubmitting = false;
                this.closeGroupInfoModal();

                if (selectedConversation && this.activeConversationIsGroup) {
                    await this.loadConversation(
                        {
                            ...selectedConversation,
                            ...(data?.conversation || {}),
                            members: Array.isArray(data?.members)
                                ? data.members
                                : selectedConversation.members,
                        },
                        { page: 1 },
                    );
                }
            } catch (error) {
                this.groupInfoError =
                    error?.response?.data?.message ||
                    "Unable to update conversation info.";
            } finally {
                this.groupInfoSubmitting = false;
            }
        },
        openInviteMembersModal() {
            if (!this.activeConversationIsGroup) {
                return;
            }

            this.groupInviteError = "";
            this.showInviteMembersModal = true;
        },
        closeInviteMembersModal() {
            if (this.groupInviteSubmitting) {
                return;
            }

            this.showInviteMembersModal = false;
            this.groupInviteError = "";
        },
        openLeaveGroupModal() {
            if (!this.activeConversationIsGroup) {
                return;
            }

            this.leaveGroupError = "";
            this.showLeaveGroupModal = true;
        },
        closeLeaveGroupModal() {
            if (this.leaveGroupSubmitting) {
                return;
            }

            this.showLeaveGroupModal = false;
            this.leaveGroupError = "";
        },
        async submitInviteMembers(selectedUserIds = []) {
            const memberIds = selectedUserIds.map((id) => Number(id));

            if (
                !this.activeConversationIsGroup ||
                !this.activeUser?.id ||
                memberIds.length === 0 ||
                this.groupInviteSubmitting
            ) {
                return;
            }

            this.groupInviteSubmitting = true;
            this.groupInviteError = "";

            try {
                const { data } = await axios.post(
                    `/api/group-chats/${this.activeUser.id}/members`,
                    {
                        member_ids: memberIds,
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
                this.groupInviteError =
                    error?.response?.data?.message ||
                    "Unable to invite members.";
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
            this.leaveGroupError = "";

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
                this.removeConversation(
                    data?.conversation_key || this.activeUser.conversation_key,
                );
            } catch (error) {
                this.leaveGroupError =
                    error?.response?.data?.message ||
                    "Unable to leave group chat.";
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
            this.typingIndicatorSenderName = "";
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

                axios
                    .post(
                        endpoint,
                        { is_typing: true },
                        {
                            headers: this.buildAuthHeaders(),
                        },
                    )
                    .catch((error) => {
                        console.error("Failed to send typing state:", error);
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

            axios
                .post(
                    endpoint,
                    { is_typing: false },
                    {
                        headers: this.buildAuthHeaders(),
                    },
                )
                .catch((error) => {
                    console.error("Failed to clear typing state:", error);
                });
        },
        async sendMessage() {
            this.ensureAuthHeaders();

            const body = this.draftMessage.trim();
            if (
                (!body && !this.selectedAttachment) ||
                !this.activeUser ||
                this.sendingMessage
            ) {
                return;
            }

            this.sendTypingState(false);
            this.sendingMessage = true;

            try {
                let data = null;

                if (this.activeConversationIsGroup) {
                    const formData = new FormData();

                    if (body) {
                        formData.append("body", body);
                    }

                    if (this.replyTargetMessage?.id) {
                        formData.append(
                            "reply_to_id",
                            this.replyTargetMessage.id,
                        );
                    }

                    if (this.selectedAttachment) {
                        formData.append("attachment", this.selectedAttachment);
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
                    formData.append("recipient_id", this.activeUser.id);

                    if (body) {
                        formData.append("body", body);
                    }

                    if (this.replyTargetMessage?.id) {
                        formData.append(
                            "reply_to_id",
                            this.replyTargetMessage.id,
                        );
                    }

                    if (this.selectedAttachment) {
                        formData.append("attachment", this.selectedAttachment);
                    }

                    ({ data } = await axios.post(
                        "/api/direct-messages",
                        formData,
                        {
                            headers: this.buildAuthHeaders(),
                        },
                    ));
                }

                const message = data?.message;
                this.draftMessage = "";
                this.composerSelectionStart = 0;
                this.composerSelectionEnd = 0;

                if (message) {
                    this.upsertLocalMessage({
                        ...message,
                        is_mine: true,
                        read_at: null,
                    });
                    if (message?.attachment?.type === "image") {
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
                        const textarea = this.getComposerInputElement();
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
                        const textarea = this.getComposerInputElement();
                        if (textarea) {
                            textarea.focus();
                            textarea.value = this.draftMessage;
                            textarea.setSelectionRange(0, 0);
                        }
                        this.resizeComposer();
                    });
                }
            } catch (error) {
                const message =
                    error?.response?.data?.message || "Unable to send message.";
                this.notify({
                    type: "error",
                    title: "Message not sent",
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
            if (
                !message?.id ||
                !message.is_mine ||
                message.is_unsent ||
                message.is_system
            ) {
                return;
            }

            const currentBody = message.body || "";
            if (!currentBody.trim()) {
                return;
            }

            this.openMessageActionModal(message, "edit");
        },
        async unsendMessage(message) {
            if (
                !message?.id ||
                !message.is_mine ||
                message.is_unsent ||
                message.is_system
            ) {
                return;
            }

            this.openMessageActionModal(message, "delete");
        },
        openMessageActionModal(message, mode = "edit") {
            this.activeMessageActionsId = null;
            this.clearReactionPicker();
            this.messageActionModalMode = mode === "delete" ? "delete" : "edit";
            this.messageActionModalMessage = message || null;
            this.messageActionModalBody = message?.body || "";
            this.messageActionModalError = "";
            this.messageActionModalSaving = false;
            this.messageActionModalVisible = true;

            this.$nextTick(() => {
                const input = this.$refs.messageActionModalInput;

                if (this.messageActionModalMode === "edit" && input?.focus) {
                    input.focus();

                    if (
                        this.messageActionModalMode === "edit" &&
                        typeof input.setSelectionRange === "function"
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
            this.messageActionModalMode = "edit";
            this.messageActionModalMessage = null;
            this.messageActionModalBody = "";
            this.messageActionModalError = "";
            this.messageActionModalSaving = false;
        },
        openAlertModal({
            type = "error",
            title = "Notice",
            message = "",
        } = {}) {
            this.alertModalType = type === "success" ? "success" : "error";
            this.alertModalTitle = title || "Notice";
            this.alertModalMessage = message || "";
            this.alertModalVisible = true;
        },
        closeAlertModal() {
            this.alertModalVisible = false;
            this.alertModalType = "error";
            this.alertModalTitle = "";
            this.alertModalMessage = "";
        },
        notify({
            type = "info",
            title = "Notice",
            message = "",
            duration = 3600,
        } = {}) {
            const id = `toast-${Date.now()}-${(this.notificationIdCounter += 1)}`;
            const timeout = window.setTimeout(() => {
                this.dismissNotification(id);
            }, duration);

            this.notifications.push({
                id,
                type: ["success", "error", "info"].includes(type)
                    ? type
                    : "info",
                title,
                message,
                timeout,
            });
        },
        dismissNotification(notificationId) {
            const index = this.notifications.findIndex(
                (item) => item.id === notificationId,
            );

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
            if (type === "success") {
                return "fa-solid fa-check";
            }

            if (type === "error") {
                return "fa-solid fa-triangle-exclamation";
            }

            return "fa-solid fa-circle-info";
        },
        async submitMessageActionModal() {
            if (
                !this.messageActionModalMessage?.id ||
                this.messageActionModalSaving
            ) {
                return;
            }

            if (this.messageActionModalMode === "edit") {
                const trimmedBody = this.messageActionModalBody.trim();
                const originalBody = (
                    this.messageActionModalMessage.body || ""
                ).trim();

                if (!trimmedBody || trimmedBody === originalBody) {
                    return;
                }

                this.messageActionModalSaving = true;
                this.messageActionModalError = "";

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

                    if (
                        data?.conversation_preview?.preview &&
                        this.activeUser
                    ) {
                        this.applyConversationPreview(
                            this.activeUser,
                            data.conversation_preview,
                        );
                    }

                    this.closeMessageActionModal(true);
                } catch (error) {
                    this.messageActionModalError =
                        error?.response?.data?.message ||
                        "Unable to edit message.";
                } finally {
                    this.messageActionModalSaving = false;
                }

                return;
            }

            this.messageActionModalSaving = true;
            this.messageActionModalError = "";

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
                    this.applyConversationPreview(
                        this.activeUser,
                        data.conversation_preview,
                    );
                } else {
                    await this.loadConversation(this.activeUser, { page: 1 });
                }

                if (Array.isArray(data?.pinned_messages)) {
                    this.pinnedMessages = data.pinned_messages;
                }

                this.closeMessageActionModal(true);
            } catch (error) {
                this.messageActionModalError =
                    error?.response?.data?.message ||
                    "Unable to unsend message.";
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

            const textarea = this.getComposerInputElement();
            const current = textarea?.value ?? this.draftMessage ?? "";

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
            textarea.dispatchEvent(new Event("input", { bubbles: true }));

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
                const textarea = this.getComposerInputElement();
                if (!textarea) {
                    return;
                }

                textarea.focus();
                const caret = this.draftMessage.length;
                if (typeof textarea.setSelectionRange === "function") {
                    textarea.setSelectionRange(caret, caret);
                }
                this.resizeComposer();
            });
        },
        clearReplyTarget() {
            this.replyTargetMessage = null;
        },
        triggerAttachmentPicker(mode = "file") {
            this.attachmentMode = mode;
            this.attachmentAccept =
                mode === "image"
                    ? "image/*"
                    : ".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt";

            this.$nextTick(() => {
                const input = this.getAttachmentInputElement();
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

            const isImage = file.type?.startsWith("image/");
            if (this.attachmentMode === "image" && !isImage) {
                this.notify({
                    type: "error",
                    title: "Image required",
                    message: "Please choose an image file for this action.",
                    duration: 3200,
                });
                event.target.value = "";
                return;
            }

            this.clearSelectedAttachment(false);
            this.selectedAttachment = file;
            this.selectedAttachmentPreviewType = isImage ? "image" : "file";
            this.selectedAttachmentPreviewUrl = isImage
                ? URL.createObjectURL(file)
                : null;
        },
        clearSelectedAttachment(resetInput = true) {
            if (this.selectedAttachmentPreviewUrl) {
                URL.revokeObjectURL(this.selectedAttachmentPreviewUrl);
                this.selectedAttachmentPreviewUrl = null;
            }

            this.selectedAttachment = null;
            this.selectedAttachmentPreviewType = "file";
            this.attachmentMode = "file";
            this.attachmentAccept =
                ".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xlsx,.txt";

            if (resetInput) {
                const input = this.getAttachmentInputElement();
                if (input) {
                    input.value = "";
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
            this.activeMessageActionsId =
                this.activeMessageActionsId === message.id ? null : message.id;
        },
        toggleReactionPicker(message) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.reactionTargetId = message.id;
            this.activeMessageActionsId = null;
            this.showReactionPicker =
                this.showReactionPicker && this.reactionTargetId === message.id
                    ? !this.showReactionPicker
                    : true;
        },
        async togglePinMessage(message) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.selectedMessage = message;
            this.selectedMessageId = message.id;
            this.clearReactionPicker();
            this.activeMessageActionsId = null;

            const isPinningMessage = !message.pinned_at;
            const maxPinnedMessagesPerConversation = 10;

            if (
                isPinningMessage &&
                this.pinnedMessages.length >= maxPinnedMessagesPerConversation
            ) {
                this.notify({
                    type: "error",
                    title: "Pin limit reached",
                    message:
                        "Maximum pinned messages is 10 for each direct message or group chat.",
                    duration: 4000,
                });
                return;
            }

            try {
                const endpoint = this.activeConversationIsGroup
                    ? `/api/group-messages/${message.id}/pin`
                    : `/api/direct-messages/${message.id}/pin`;
                const { data } = await axios.patch(
                    endpoint,
                    {
                        is_pinned: isPinningMessage,
                    },
                    {
                        headers: this.buildAuthHeaders(),
                    },
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
                console.error("Failed to toggle pin:", error);
            }
        },
        async unpinPinnedMessage(pin) {
            if (!pin?.message_id) return;

            const message = this.messages.find(
                (item) => Number(item.id) === Number(pin.message_id),
            ) || {
                id: pin.message_id,
                pinned_at: pin.pinned_at || new Date().toISOString(),
            };

            await this.togglePinMessage(message);
        },
        buildAuthHeaders() {
            const token = localStorage.getItem("auth_token");
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
                recipient_id:
                    message.recipient_id != null
                        ? Number(message.recipient_id)
                        : null,
                group_chat_id:
                    message.group_chat_id != null
                        ? Number(message.group_chat_id)
                        : null,
                is_mine: senderId === authUserId,
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
        openGroupChatModal() {
            if (
                !this.isAdmin &&
                this.pendingGroupChatRequestCount >= this.groupChatRequestLimit
            ) {
                this.openAlertModal({
                    type: "error",
                    title: "Unable to create more group requests",
                    message: `You already reached the limit of ${this.groupChatRequestLimit} pending group chat requests. Please cancel one request before creating another.`,
                });
                return;
            }

            this.groupChatError = "";
            this.groupChatForm = {
                name: "",
                member_ids: [],
            };
            this.showGroupChatModal = true;
        },
        closeGroupChatModal() {
            if (this.groupChatSubmitting) {
                return;
            }

            this.showGroupChatModal = false;
            this.groupChatError = "";
        },
        async submitGroupChat(form = { name: "", member_ids: [] }) {
            const normalizedForm = {
                name: String(form?.name || "").trim(),
                member_ids: Array.isArray(form?.member_ids)
                    ? form.member_ids.map((id) => Number(id))
                    : [],
            };

            if (
                !normalizedForm.name ||
                normalizedForm.member_ids.length === 0 ||
                this.groupChatSubmitting
            ) {
                return;
            }

            if (
                !this.isAdmin &&
                this.pendingGroupChatRequestCount >= this.groupChatRequestLimit
            ) {
                this.openAlertModal({
                    type: "error",
                    title: "Unable to create more group requests",
                    message: `You already reached the limit of ${this.groupChatRequestLimit} pending group chat requests. Please cancel one request before creating another.`,
                });
                return;
            }

            this.groupChatSubmitting = true;
            this.groupChatError = "";

            try {
                const { data } = await axios.post(
                    "/api/group-chats",
                    {
                        name: normalizedForm.name,
                        member_ids: normalizedForm.member_ids,
                    },
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                if (data?.conversation) {
                    this.upsertConversation(data.conversation);
                    this.selectedConversationKey =
                        data.conversation.conversation_key;
                    this.updateConversationUrl(data.conversation, "push");
                    this.resetConversationState();
                    await this.loadConversation(data.conversation, { page: 1 });
                }

                if (data?.group_chat?.approval_status === "pending") {
                    const selectedMembers = normalizedForm.member_ids
                        .map((memberId) =>
                            this.availableUsers.find(
                                (user) =>
                                    Number(user.id) === Number(memberId),
                            ),
                        )
                        .filter(Boolean)
                        .map((member) => ({
                            id: Number(member.id),
                            name: member.name,
                            profile: member.profile || null,
                        }));

                    this.upsertGroupChatRequestHistory({
                        id: Number(data.group_chat.id),
                        name: data.group_chat.name,
                        approval_status: data.group_chat.approval_status,
                        approval_level: data.group_chat.approval_level,
                        created_at: new Date().toISOString(),
                        processed_at: new Date().toISOString(),
                        rejection_reason: null,
                        creator: {
                            id: Number(this.authUser?.id || 0),
                            name: this.authUser?.name || "User",
                        },
                        processed_by: {
                            id: null,
                            name: null,
                        },
                        members: [
                            ...selectedMembers,
                            {
                                id: Number(this.authUser?.id || 0),
                                name: this.authUser?.name || "You",
                                profile: this.authUser?.profile || null,
                            },
                        ],
                    });
                }

                this.showGroupChatModal = false;
                this.groupChatError = "";
                this.notify({
                    type: "success",
                    title: "Group chat",
                    message: data?.message || "Saved successfully.",
                    duration: 3000,
                });
            } catch (error) {
                this.groupChatError =
                    error?.response?.data?.errors?.name?.[0] ||
                    error?.response?.data?.message ||
                    "Unable to create group chat.";
            } finally {
                this.groupChatSubmitting = false;
            }
        },
        promptCancelGroupChatRequest(request) {
            if (!request?.id || this.cancelGroupChatRequestSubmitting) {
                return;
            }

            this.selectedCancelGroupChatRequest = request;
            this.cancelGroupChatRequestModalVisible = true;
        },
        closeCancelGroupChatRequestModal() {
            if (this.cancelGroupChatRequestSubmitting) {
                return;
            }

            this.cancelGroupChatRequestModalVisible = false;
            this.selectedCancelGroupChatRequest = null;
        },
        async confirmCancelGroupChatRequest() {
            const request = this.selectedCancelGroupChatRequest;

            if (!request?.id) {
                return;
            }

            this.cancelGroupChatRequestSubmitting = true;

            try {
                const { data } = await axios.delete(
                    `/api/group-chats/${request.id}`,
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                this.groupChatRequestHistory =
                    this.groupChatRequestHistory.filter(
                        (item) => Number(item.id) !== Number(request.id),
                    );
                this.cancelGroupChatRequestModalVisible = false;
                this.selectedCancelGroupChatRequest = null;

                this.notify({
                    type: "success",
                    title: "Request cancelled",
                    message:
                        data?.message ||
                        "Your group chat request was cancelled.",
                    duration: 3000,
                });
            } catch (error) {
                this.notify({
                    type: "error",
                    title: "Unable to cancel request",
                    message:
                        error?.response?.data?.message ||
                        "Unable to cancel this group chat request.",
                    duration: 3600,
                });
            } finally {
                this.cancelGroupChatRequestSubmitting = false;
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

                this.pendingGroupChatApprovals =
                    this.pendingGroupChatApprovals.filter(
                        (item) => item.id !== request.id,
                    );

                if (data?.conversation) {
                    this.upsertConversation(data.conversation);
                }
            } catch (error) {
                console.error("Failed to approve group chat request:", error);
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

                this.pendingGroupChatApprovals =
                    this.pendingGroupChatApprovals.filter(
                        (item) => item.id !== request.id,
                    );
            } catch (error) {
                console.error("Failed to reject group chat request:", error);
            }
        },
        upsertConversation(conversation) {
            if (!conversation?.conversation_key) {
                return;
            }

            const existingConversation =
                this.users.find(
                    (item) =>
                        item.conversation_key === conversation.conversation_key,
                ) || null;
            const incomingUnreadCount = Number(conversation.unread_count || 0);
            const existingUnreadCount = Number(
                existingConversation?.unread_count || 0,
            );
            const shouldPreserveUnreadCount = Boolean(
                existingConversation &&
                existingUnreadCount > 0 &&
                incomingUnreadCount === 0 &&
                this.selectedConversationKey !== conversation.conversation_key,
            );

            const nextConversation = {
                ...conversation,
                id: Number(conversation.id),
                conversation_key: conversation.conversation_key,
                conversation_token:
                    conversation.conversation_token ||
                    existingConversation?.conversation_token ||
                    null,
                conversation_type: conversation.conversation_type || "direct",
                member_ids: Array.isArray(conversation.member_ids)
                    ? conversation.member_ids.map((id) => Number(id))
                    : [],
                members: Array.isArray(conversation.members)
                    ? conversation.members.map((member) => ({
                          ...member,
                          id: Number(member.id),
                          last_read_at: member.last_read_at || null,
                          display_name:
                              member.display_name ||
                              member.nickname ||
                              member.name ||
                              "User",
                      }))
                    : [],
                actual_name:
                    conversation.actual_name ||
                    existingConversation?.actual_name ||
                    conversation.name,
                nickname: Object.prototype.hasOwnProperty.call(
                    conversation,
                    "nickname",
                )
                    ? conversation.nickname
                    : existingConversation?.nickname || null,
                unread_count: shouldPreserveUnreadCount
                    ? existingUnreadCount
                    : incomingUnreadCount,
                is_unread: shouldPreserveUnreadCount
                    ? true
                    : Boolean(
                          incomingUnreadCount > 0 || conversation.is_unread,
                      ),
                active_label:
                    conversation.conversation_type === "group"
                        ? conversation.active_label ||
                          `${Number(conversation.member_count || 0)} members`
                        : conversation.active_label,
            };

            if (nextConversation.conversation_type === "direct") {
                const nickname = String(nextConversation.nickname || "").trim();
                const actualName = String(
                    nextConversation.actual_name ||
                        nextConversation.name ||
                        "User",
                ).trim();
                nextConversation.name = nickname || actualName || "User";
            }

            const existingIndex = this.users.findIndex(
                (item) =>
                    item.conversation_key === nextConversation.conversation_key,
            );

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

            const existingIndex = this.messages.findIndex(
                (item) => Number(item.id) === Number(normalizedMessage.id),
            );
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
            const index = this.users.findIndex(
                (item) => item.conversation_key === conversationKey,
            );

            if (index >= 0) {
                this.users.splice(index, 1);
            }

            if (this.selectedConversationKey === conversationKey) {
                const nextConversation = this.users[0] || null;
                this.selectedConversationKey =
                    nextConversation?.conversation_key || null;
                this.resetConversationState();

                if (nextConversation) {
                    this.updateConversationUrl(nextConversation, "replace");
                    this.loadConversation(nextConversation, { page: 1 });
                } else {
                    this.updateConversationUrl(null, "replace");
                }
            }

            this.scheduleMessagesCachePersist();
        },
        removeLocalMessage(messageId) {
            const normalizedId = Number(messageId);
            const existingIndex = this.messages.findIndex(
                (item) => Number(item.id) === normalizedId,
            );

            if (existingIndex >= 0) {
                this.messages.splice(existingIndex, 1);
            }

            if (
                this.selectedMessageId &&
                Number(this.selectedMessageId) === normalizedId
            ) {
                this.clearSelectedMessage();
            }

            this.scheduleMessagesCachePersist();
        },
        applyConversationPreview(user, conversationPreview) {
            if (!user || !conversationPreview) {
                return;
            }

            if (
                Object.prototype.hasOwnProperty.call(
                    conversationPreview,
                    "latest_at",
                )
            ) {
                user.latest_at = conversationPreview.latest_at;
            }

            if (
                Object.prototype.hasOwnProperty.call(
                    conversationPreview,
                    "preview",
                )
            ) {
                user.preview = conversationPreview.preview;
            }

            if (
                conversationPreview.latest_message?.id &&
                this.selectedConversationKey === user.conversation_key
            ) {
                this.upsertLocalMessage({
                    ...conversationPreview.latest_message,
                    is_mine:
                        Number(conversationPreview.latest_message.sender_id) ===
                        Number(this.authUser?.id),
                });
            }

            this.syncUsersOnlineState();
            this.scheduleMessagesCachePersist();
        },
        isConversationPanelVisible() {
            if (typeof window === "undefined") {
                return false;
            }

            const panel = this.$el?.querySelector(".conversation-panel");
            if (!panel) {
                return false;
            }

            const styles = window.getComputedStyle(panel);
            return (
                styles.display !== "none" &&
                styles.visibility !== "hidden" &&
                panel.getClientRects().length > 0
            );
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

            if (
                !this.messages.some(
                    (message) => !message.is_mine && !message.read_at,
                )
            ) {
                return;
            }

            try {
                const { data } = await axios.post(
                    `/api/direct-messages/${selectedUserId}/seen`,
                    {},
                    {
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                const readAt = data?.read_at ?? null;
                const messageIds = new Set(
                    (data?.message_ids ?? []).map((id) => Number(id)),
                );

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
                console.error("Failed to mark conversation as seen:", error);
            }
        },
        async markGroupConversationSeen() {
            const activeUser = this.activeUser;
            if (!activeUser || !this.activeConversationIsGroup) return;
            if (!this.isConversationPanelVisible()) return;

            if (
                !this.messages.some(
                    (message) => !message.is_mine && !message.is_system,
                )
            ) {
                return;
            }

            try {
                const { data } = await axios.post(
                    `/api/group-chats/${activeUser.id}/seen`,
                    {},
                    {
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                const targetConversation = this.users.find(
                    (user) =>
                        user.conversation_key === activeUser.conversation_key,
                );
                if (targetConversation) {
                    targetConversation.unread_count = 0;
                    targetConversation.is_unread = false;
                }

                this.applyGroupSeenReceipt(
                    activeUser.id,
                    {
                        id: Number(this.authUser?.id || 0),
                        name: this.authUser?.name || "User",
                        nickname: this.activeGroupSelfMember?.nickname || null,
                        display_name:
                            this.activeGroupSelfMember?.display_name ||
                            this.authUser?.name ||
                            "User",
                    },
                    data?.read_at || new Date().toISOString(),
                );
            } catch (error) {
                console.error(
                    "Failed to mark group conversation as seen:",
                    error,
                );
            }
        },
        async scrollToPinnedMessage(pin) {
            if (!pin?.message_id) return;

            this.showPinnedMessagesPanel = false;
            this.selectedMessage =
                this.messages.find(
                    (message) => Number(message.id) === Number(pin.message_id),
                ) || null;
            this.selectedMessageId = this.selectedMessage?.id || null;

            await this.scrollToMessageById(pin.message_id, {
                flashClass: "message-row--highlighted",
            });
        },
        async scrollToReplyMessage(message) {
            const replyTargetId = message?.reply_to?.id || message?.reply_to_id;
            if (!replyTargetId) return;

            this.selectedMessage =
                this.messages.find(
                    (item) => Number(item.id) === Number(replyTargetId),
                ) || null;
            this.selectedMessageId = this.selectedMessage?.id || null;

            await this.scrollToMessageById(replyTargetId, {
                flashClass: "message-row--highlighted",
                shakeClass: "message-row--reply-target",
            });
        },
        async scrollToMessageById(
            messageId,
            { flashClass = null, shakeClass = null } = {},
        ) {
            if (!messageId) return;

            await this.$nextTick();

            const body = this.$el.querySelector(".conversation-panel__body");
            const target = this.$el.querySelector(
                `[data-message-id="${messageId}"]`,
            );

            if (!body || !target) {
                return;
            }

            const bodyRect = body.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const nextTop =
                body.scrollTop +
                targetRect.top -
                bodyRect.top -
                body.clientHeight / 2 +
                target.clientHeight / 2;
            body.scrollTo({
                top: nextTop,
                behavior: "smooth",
            });

            const timers =
                target.__messageTimers || (target.__messageTimers = {});

            if (timers.flash) {
                window.clearTimeout(timers.flash);
            }

            if (timers.shake) {
                window.clearTimeout(timers.shake);
            }

            if (flashClass) {
                target.classList.add(flashClass);
                timers.flash = window.setTimeout(
                    () => target.classList.remove(flashClass),
                    900,
                );
            }

            if (shakeClass) {
                timers.shake = window.setTimeout(() => {
                    target.classList.add(shakeClass);
                    window.setTimeout(
                        () => target.classList.remove(shakeClass),
                        650,
                    );
                }, 420);
            } else if (flashClass) {
                timers.flash = window.setTimeout(
                    () => target.classList.remove(flashClass),
                    900,
                );
            }
        },
        async setReaction(message, reactionKey) {
            if (!message?.id || message.is_unsent || message.is_system) return;

            this.clearReactionPicker();
            const currentUserReaction = Array.isArray(message.reactions)
                ? message.reactions.find(
                      (reaction) =>
                          Number(reaction.user_id) ===
                          Number(this.authUser?.id || 0),
                  )?.reaction || null
                : message.reaction || null;
            const nextReaction =
                currentUserReaction === reactionKey ? null : reactionKey;

            try {
                const endpoint = this.activeConversationIsGroup
                    ? `/api/group-messages/${message.id}/reaction`
                    : `/api/direct-messages/${message.id}/reaction`;
                const { data } = await axios.patch(
                    endpoint,
                    { reaction: nextReaction },
                    {
                        headers: this.buildAuthHeaders(),
                    },
                );

                if (data?.message) {
                    this.upsertLocalMessage(data.message);
                }
            } catch (error) {
                console.error("Failed to set reaction:", error);
            }
        },
        openImageGallery(payload) {
            const attachment = payload?.attachment || payload;
            if (!attachment?.url) {
                return;
            }

            const shouldRestoreGroupInfoModal = this.showGroupInfoModal;
            if (shouldRestoreGroupInfoModal) {
                this.groupInfoModalRestoreScrollTop = Math.max(
                    0,
                    Number(payload?.scrollTop || 0),
                );
                this.groupInfoModalRestoreActiveTab =
                    payload?.activeTab || "media";
                this.reopenGroupInfoModalAfterGallery = true;
                this.showGroupInfoModal = false;
            } else {
                this.reopenGroupInfoModalAfterGallery = false;
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

            this.$nextTick(() => {
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
                    container: document.body,
                    addClass: "messages-lightgallery",
                });

                this.imageGalleryInstance?.LGel?.on("lgAfterClose", () => {
                    if (this.reopenGroupInfoModalAfterGallery) {
                        window.setTimeout(() => {
                            this.showGroupInfoModal = true;
                            this.reopenGroupInfoModalAfterGallery = false;
                        }, 90);
                    }
                });

                this.imageGalleryInstance.openGallery();
            });
        },
        destroyImageGallery() {
            if (this.imageGalleryInstance) {
                this.imageGalleryInstance.destroy();
                this.imageGalleryInstance = null;
            }
        },
        getMessageSnippet(message) {
            if (!message) return "";

            if (message.is_unsent) return "Unsent Message";

            if (message.body) return message.body;

            if (message.attachment?.name) return message.attachment.name;

            return "Attachment";
        },
        getReactionEmoji(reactionKey) {
            return (
                this.reactionOptions.find(
                    (reaction) => reaction.key === reactionKey,
                )?.emoji || ""
            );
        },
        getUniqueReactionEmojis(reactions) {
            if (!Array.isArray(reactions) || reactions.length === 0) {
                return [];
            }
            const uniqueReactions = new Set();
            reactions.forEach((reaction) => {
                if (reaction && reaction.reaction) {
                    uniqueReactions.add(reaction.reaction);
                }
            });
            return Array.from(uniqueReactions).map((reactionKey) =>
                this.getReactionEmoji(reactionKey)
            );
        },
        formatReactionsTooltip(reactions) {
            if (!Array.isArray(reactions) || reactions.length === 0) {
                return "";
            }
            const grouped = {};
            reactions.forEach((r) => {
                if (r && r.reaction && r.user_name) {
                    if (!grouped[r.reaction]) {
                        grouped[r.reaction] = [];
                    }
                    grouped[r.reaction].push(r.user_name);
                }
            });
            return Object.entries(grouped)
                .map(([reactionKey, names]) => {
                    const emoji = this.getReactionEmoji(reactionKey);
                    return `${emoji}: ${names.join(", ")}`;
                })
                .join("\n");
        },
        downloadAttachment(attachment) {
            if (!attachment?.url) return;

            const link = document.createElement("a");
            link.href = attachment.url;
            link.download = attachment.name || "attachment";
            link.target = "_blank";
            link.rel = "noopener";
            document.body.appendChild(link);
            link.click();
            link.remove();
        },
        formatPinnedAt(timestamp) {
            if (!timestamp) return "just now";

            try {
                return new Intl.DateTimeFormat([], {
                    month: "short",
                    day: "numeric",
                    hour: "numeric",
                    minute: "2-digit",
                }).format(new Date(timestamp));
            } catch (error) {
                return "just now";
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
        updateContactPreview(message, clearUnread = false) {
            const user = this.users.find(
                (item) =>
                    item.conversation_key === this.activeUser?.conversation_key,
            );
            if (!user) return;

            user.latest_at = message.created_at || new Date().toISOString();
            user.preview = this.getMessageSnippet(message);
            user.unread_count = clearUnread
                ? 0
                : Number(user.unread_count || 0);
            user.is_unread = Number(user.unread_count || 0) > 0;
            this.scheduleMessagesCachePersist();
        },
        applySeenReceipt(partnerId, readAt, messageIds = []) {
            const normalizedPartnerId = Number(partnerId || 0);
            const normalizedReadAt = readAt
                ? new Date(readAt).toISOString()
                : null;

            if (!normalizedPartnerId || !normalizedReadAt) {
                return;
            }

            const normalizedMessageIds = new Set(
                Array.isArray(messageIds)
                    ? messageIds.map((id) => Number(id))
                    : [],
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
        getSeenMemberName(member) {
            const nickname = String(member?.nickname || "").trim();
            const realName = String(member?.name || "").trim();

            return nickname || realName || "User";
        },
        getGroupSeenUsers(message) {
            if (
                !this.activeConversationIsGroup ||
                !message?.is_mine ||
                !message?.created_at
            ) {
                return [];
            }

            const messageCreatedAt = Date.parse(message.created_at);
            const senderId = Number(
                message.sender_id || this.authUser?.id || 0,
            );

            if (Number.isNaN(messageCreatedAt)) {
                return [];
            }

            return this.activeGroupMembers
                .filter((member) => Number(member.id) !== senderId)
                .filter((member) => {
                    const lastReadAt = Date.parse(member.last_read_at || "");
                    return (
                        !Number.isNaN(lastReadAt) &&
                        lastReadAt >= messageCreatedAt
                    );
                })
                .sort(
                    (left, right) =>
                        Date.parse(right.last_read_at || "") -
                        Date.parse(left.last_read_at || ""),
                );
        },
        getSeenReceiptPreviewUsers(message) {
            return this.getGroupSeenUsers(message).slice(0, 10);
        },
        getSeenReceiptOverflowCount(message) {
            return Math.max(this.getGroupSeenUsers(message).length - 10, 0);
        },
        formatGroupSeenReceiptTooltip(message) {
            const seenUsers = this.getGroupSeenUsers(message);

            if (!seenUsers.length) {
                return "Seen";
            }

            return `Seen ${this.formatSeenDateTime(seenUsers[0]?.last_read_at)}`;
        },
        openSeenByModal(message) {
            const seenUsers = this.getGroupSeenUsers(message);

            if (!this.activeConversationIsGroup || !seenUsers.length) {
                return;
            }

            this.seenByModalUsers = seenUsers;
            this.showSeenByModal = true;
        },
        closeSeenByModal() {
            this.showSeenByModal = false;
            this.seenByModalUsers = [];
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
        applyGroupSeenReceipt(groupChatId, reader, readAt) {
            const normalizedGroupChatId = Number(groupChatId || 0);
            const normalizedReaderId = Number(
                reader?.id || reader?.user_id || 0,
            );
            const normalizedReadAt = readAt
                ? new Date(readAt).toISOString()
                : null;

            if (
                !normalizedGroupChatId ||
                !normalizedReaderId ||
                !normalizedReadAt
            ) {
                return;
            }

            const conversationKey = `group:${normalizedGroupChatId}`;
            const conversation = this.users.find(
                (user) => user.conversation_key === conversationKey,
            );

            if (!conversation) {
                return;
            }

            const existingMembers = Array.isArray(conversation.members)
                ? conversation.members
                : [];
            const existingIndex = existingMembers.findIndex(
                (member) => Number(member.id) === normalizedReaderId,
            );
            const nextMember = {
                ...(existingIndex >= 0 ? existingMembers[existingIndex] : {}),
                ...(reader || {}),
                id: normalizedReaderId,
                name:
                    reader?.name ||
                    existingMembers[existingIndex]?.name ||
                    "User",
                nickname: Object.prototype.hasOwnProperty.call(
                    reader || {},
                    "nickname",
                )
                    ? reader.nickname
                    : existingMembers[existingIndex]?.nickname || null,
                display_name:
                    reader?.display_name ||
                    this.getSeenMemberName(
                        reader || existingMembers[existingIndex] || {},
                    ),
                last_read_at: normalizedReadAt,
            };

            if (existingIndex >= 0) {
                existingMembers.splice(existingIndex, 1, nextMember);
            } else {
                existingMembers.push(nextMember);
            }

            conversation.members = [...existingMembers];

            if (this.showSeenByModal) {
                this.seenByModalUsers = this.seenByModalUsers
                    .map((member) =>
                        Number(member.id) === normalizedReaderId
                            ? { ...member, ...nextMember }
                            : member,
                    )
                    .sort(
                        (left, right) =>
                            Date.parse(right.last_read_at || "") -
                            Date.parse(left.last_read_at || ""),
                    );
            }

            this.scheduleMessagesCachePersist();
        },
        refreshUserActivityLabels() {
            this.users.forEach((user) => {
                const activityState =
                    user.conversation_type === "group"
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
                    label: "Online",
                    isActive: true,
                    lastSeenAt: lastSeenAt || Date.now(),
                };
            }

            if (!fallbackAt) {
                return {
                    label: "Offline",
                    isActive: false,
                    lastSeenAt: null,
                };
            }

            const latestDate = new Date(fallbackAt);
            const diffMs = Date.now() - latestDate.getTime();

            if (Number.isNaN(diffMs) || diffMs < 0) {
                return {
                    label: "Offline",
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
                const body =
                    this.getConversationBodyElement() ||
                    this.$el.querySelector(".conversation-panel__body");
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

            const distanceFromBottom =
                bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight;
            this.showScrollToBottomButton = distanceFromBottom > 220;
        },
        isConversationNearBottom(bodyEl, threshold = 220) {
            if (!bodyEl) return true;

            const distanceFromBottom =
                bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight;
            return distanceFromBottom <= threshold;
        },
        resizeComposer() {
            const textarea = this.getComposerInputElement();
            if (!textarea) return;

            textarea.style.height = "auto";
            textarea.style.height = `${Math.min(textarea.scrollHeight, 140)}px`;
        },
        formatTime(value) {
            if (!value) return "";

            try {
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return "";
                }

                const diffMs = Date.now() - date.getTime();
                const oneDayMs = 24 * 60 * 60 * 1000;

                if (diffMs >= oneDayMs) {
                    const datePart = new Intl.DateTimeFormat([], {
                        month: "short",
                        day: "numeric",
                        year: "numeric",
                    }).format(date);

                    const timePart = new Intl.DateTimeFormat([], {
                        hour: "numeric",
                        minute: "2-digit",
                    }).format(date);

                    return `${datePart} ${timePart}`;
                }

                return new Intl.DateTimeFormat([], {
                    hour: "numeric",
                    minute: "2-digit",
                }).format(date);
            } catch (error) {
                return "";
            }
        },
        formatSeenReceiptTooltip(value) {
            if (!value) {
                return "";
            }

            try {
                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return "";
                }

                return `Seen ${this.formatSeenDateTime(date)}`;
            } catch (error) {
                return "";
            }
        },
        formatSeenDateTime(value) {
            if (!value) {
                return "";
            }

            try {
                const date = value instanceof Date ? value : new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return "";
                }

                const datePart = new Intl.DateTimeFormat("en-US", {
                    month: "long",
                    day: "2-digit",
                    year: "numeric",
                }).format(date);
                const timePart = new Intl.DateTimeFormat("en-US", {
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true,
                }).format(date);

                return `${datePart} at ${timePart}`;
            } catch (error) {
                return "";
            }
        },
        getSeenReceiptAvatar() {
            return this.activeUserAvatar;
        },
        shouldShowSeenReceipt(message) {
            if (!message?.is_mine) {
                return false;
            }

            if (this.activeConversationIsGroup) {
                return (
                    Number(message.id) === this.latestGroupSeenReceiptMessageId
                );
            }

            if (!message?.read_at) {
                return false;
            }

            return Number(message.id) === this.latestSeenReceiptMessageId;
        },
        formatConversationTimestamp(value) {
            if (!value) {
                return "";
            }

            try {
                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return "";
                }

                const now = this.now;
                const isSameDay = date.toDateString() === now.toDateString();

                if (isSameDay) {
                    return new Intl.DateTimeFormat("en-US", {
                        hour: "numeric",
                        minute: "2-digit",
                    }).format(date);
                }

                const yesterday = new Date(now);
                yesterday.setDate(now.getDate() - 1);

                if (date.toDateString() === yesterday.toDateString()) {
                    return "Yesterday";
                }

                return new Intl.DateTimeFormat("en-US", {
                    month: "short",
                    day: "numeric",
                }).format(date);
            } catch (error) {
                return "";
            }
        },
        formatUnreadCount(value) {
            const count = Number(value || 0);
            if (count <= 0) {
                return "";
            }

            return count >= 10 ? "10+" : String(count);
        },
        formatGroupMemberDate(value) {
            if (!value) {
                return "";
            }

            try {
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return "";
                }

                return this.formatSeenDateTime(date);
            } catch (error) {
                return "";
            }
        },
    },
};
</script>

<style lang="scss">
.messages-page {
    width: 100%;
    min-height: 100dvh;
    padding: 26px 24px;
    overflow: hidden;
    background:
        radial-gradient(
            circle at top left,
            rgba(31, 91, 255, 0.15),
            transparent 24%
        ),
        radial-gradient(
            circle at top right,
            rgba(99, 102, 241, 0.08),
            transparent 22%
        ),
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
    flex: 0 0 clamp(380px, 32vw, 460px);
    width: clamp(380px, 32vw, 460px);
    display: flex;
    flex-direction: column;
    transition:
        transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        visibility 0s linear 0.3s;
    background: linear-gradient(
        180deg,
        rgba(52, 58, 66, 0.96),
        rgba(43, 49, 56, 0.98)
    );
}

.conversation-panel {
    flex: 1 1 0;
    min-width: 0;
    width: 100%;
    position: relative;
    z-index: 1;
    background:
        radial-gradient(
            circle at top right,
            rgba(33, 91, 246, 0.14),
            transparent 24%
        ),
        linear-gradient(180deg, rgba(49, 55, 63, 0.96), rgba(40, 45, 52, 0.98));
}

.contacts-panel__header-block {
    padding: 18px 18px 10px;
}

.contacts-panel__header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 12px;
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
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    margin-top: 14px;
    width: 100%;
    padding-right: 10px;
}

.contacts-panel__utility-link,
.contacts-panel__utility-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex: 0 1 auto;
    min-width: 0;
    min-height: 44px;
    padding: 0 13px;
    border-radius: 16px;
    font-size: 0.82rem;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    transition:
        transform 0.2s ease,
        background 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.contacts-panel__utility-link i,
.contacts-panel__utility-badge i {
    font-size: 0.9rem;
    flex: 0 0 auto;
}

.contacts-panel__utility-link span,
.contacts-panel__utility-badge span {
    min-width: 0;
    line-height: 1;
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
    background: linear-gradient(
        135deg,
        rgba(28, 88, 246, 0.24),
        rgba(67, 122, 255, 0.18)
    );
    color: #dfe9ff;
    letter-spacing: 0.02em;
    box-shadow: inset 0 0 0 1px rgba(142, 181, 255, 0.16);
}

.contacts-panel__utility-badge:hover {
    transform: translateY(-1px);
    box-shadow:
        inset 0 0 0 1px rgba(142, 181, 255, 0.24),
        0 10px 24px rgba(28, 88, 246, 0.18);
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
    transition:
        transform 0.18s ease,
        background 0.18s ease;
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
    transition:
        background 0.18s ease,
        transform 0.18s ease;
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
    background: linear-gradient(
        135deg,
        rgba(28, 88, 246, 0.96),
        rgba(19, 67, 201, 0.98)
    );
    box-shadow:
        0 14px 30px rgba(13, 59, 176, 0.28),
        inset 0 0 0 1px rgba(255, 255, 255, 0.08);
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
    transition:
        background 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
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
    transition:
        background 0.18s ease,
        color 0.18s ease;
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
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.03),
        rgba(255, 255, 255, 0.01)
    );
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
    transition:
        transform 0.18s ease,
        filter 0.18s ease;
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
    background: linear-gradient(
        180deg,
        rgba(24, 14, 30, 0.98),
        rgba(14, 9, 18, 0.98)
    );
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
    transition:
        background 0.16s ease,
        transform 0.16s ease;
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
    background: linear-gradient(
        180deg,
        rgba(27, 16, 31, 0.98),
        rgba(14, 9, 18, 0.99)
    );
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
        linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.02),
            rgba(255, 255, 255, 0.01)
        ),
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

.conversation-panel__body {
    position: relative;
    flex: 1;
    overflow-y: auto;
    overflow-x: clip;
    // padding: 18px 22px 18px;
    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(255, 102, 179, 0.08),
            transparent 18%
        ),
        radial-gradient(
            circle at 80% 30%,
            rgba(138, 92, 255, 0.08),
            transparent 20%
        ),
        linear-gradient(180deg, rgba(25, 15, 31, 0.98), rgba(18, 10, 24, 0.98));
}

.message-stream {
    width: 100%;
    min-width: 0;
    overflow: hidden;
    padding: 18px 22px 30px 22px;
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
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.12),
        transparent
    );
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
    box-shadow:
        0 0 0 2px rgba(255, 77, 136, 0.22),
        0 16px 30px rgba(0, 0, 0, 0.18);
    transform: translateY(-1px);
    animation: messageFlash 0.8s ease-in-out;
}

.message-row--reply-target .message-bubble {
    animation: messageShake 0.65s ease-in-out;
}

.message-bubble {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    max-width: min(76%, 640px);
    width: fit-content;
    border-radius: 18px;
    padding: 12px 14px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.12);
}

.message-bubble-wrap {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    max-width: min(76%, 640px);
    width: fit-content;
    flex: 0 1 auto;
    min-width: 0;
}

.message-row--theirs .message-bubble-wrap {
    align-items: flex-start;
    align-self: flex-start;
}

.message-bubble-wrap .message-bubble {
    width: fit-content;
    max-width: 100%;
    align-self: flex-start;
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

.message-row--theirs .message-bubble__time {
    position: static;
    margin-top: 8px;
    white-space: nowrap;
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
    transition:
        opacity 0.16s ease,
        visibility 0.16s ease;
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
    gap: 8px;
    min-width: 180px;
    padding: 10px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(36, 42, 51, 0.98);
    box-shadow: 0 18px 36px rgba(0, 0, 0, 0.34);
    z-index: 120;
    backdrop-filter: blur(10px);
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
    transition:
        transform 0.16s ease,
        background 0.16s ease;
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
    min-height: 52px;
    border-radius: 14px;
    justify-content: flex-start;
    padding: 0.72rem 0.9rem;
    gap: 12px;
    font-size: 1rem;
    white-space: nowrap;
    font-weight: 600;
    border: 0;
    background: rgba(255, 255, 255, 0.04);
    transition:
        transform 0.18s ease,
        background-color 0.18s ease,
        box-shadow 0.18s ease;
}

.bubble-action--menu:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.16);
}

.bubble-action--menu-primary {
    background: linear-gradient(180deg, #3563f0 0%, #2d55da 100%);
    color: #fff;
}

.bubble-action--menu-primary:hover {
    background: linear-gradient(180deg, #3d6cfa 0%, #315ce7 100%);
    color: #fff;
}

.bubble-action__icon {
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

.bubble-action__icon i {
    font-size: 0.78rem;
}

.bubble-action--danger .bubble-action__icon {
    color: #fff;
}

.bubble-action--menu-primary .bubble-action__icon {
    background: rgba(255, 255, 255, 0.14);
    border-color: rgba(255, 255, 255, 0.18);
}

.bubble-action__content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1px;
    min-width: 0;
}

.bubble-action__label {
    line-height: 1.1;
}

.bubble-action__hint {
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.15;
}

.bubble-action--menu-primary .bubble-action__hint {
    color: rgba(255, 255, 255, 0.78);
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
    transition:
        transform 0.16s ease,
        background 0.16s ease,
        border-color 0.16s ease;
}

.reaction-modal__option:hover {
    transform: translateY(-1px);
    background: rgb(68, 75, 86);
}

.reaction-modal__option.is-active {
    background: linear-gradient(
        135deg,
        rgba(255, 77, 136, 0.22),
        rgba(184, 61, 230, 0.22)
    );
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
    background: linear-gradient(
        180deg,
        rgba(15, 23, 42, 0.08),
        rgba(15, 23, 42, 0.18)
    );
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
    display: flex;
    flex-wrap: nowrap;
    justify-content: flex-end;
    align-items: center;
    gap: 4px;
}

.message-bubble__time-edit {
    color: rgba(255, 255, 255, 0.74);
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
}

.message-bubble__status--sent {
    color: rgba(255, 255, 255, 0.52);
}

.message-bubble__status--seen {
    color: rgba(149, 255, 200, 0.92);
}

.message-bubble__seen-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0;
    border: 0;
    padding: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.message-bubble__seen-group:hover .message-bubble__seen-avatar,
.message-bubble__seen-group:hover .message-bubble__seen-overflow {
    transform: translateY(-1px);
}

.message-bubble__seen-avatar {
    width: 1.2rem;
    height: 1.2rem;
    border-radius: 999px;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.22);
    box-shadow: 0 8px 16px rgba(2, 6, 23, 0.18);
    cursor: default;
    transition: transform 0.16s ease;
}

.message-bubble__seen-group
    .message-bubble__seen-avatar
    + .message-bubble__seen-avatar {
    margin-left: -0.3rem;
}

.message-bubble__seen-overflow {
    min-width: 1.5rem;
    height: 1.2rem;
    margin-left: 0.3rem;
    padding: 0 0.38rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.66rem;
    font-weight: 700;
    line-height: 1;
    transition: transform 0.16s ease;
}

.message-bubble__seen-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
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

.message-reaction-badges {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 0.3rem 0.5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
    cursor: pointer;
    transition: all 0.2s ease;
}

.message-reaction-badges:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: scale(1.05);
}

.message-reaction-badges--floating {
    position: absolute;
    bottom: -0.9rem;
    z-index: 4;
}

.message-row--mine .message-reaction-badges--floating {
    left: -0.5rem;
}

.message-row--theirs .message-reaction-badges--floating {
    right: -0.5rem;
}

.message-reaction-badge__glyph {
    display: inline-block;
    line-height: 1;
    font-size: 0.9rem;
}

.message-reaction-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.1rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    margin-left: 2px;
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
    transition:
        transform 0.16s ease,
        background 0.16s ease,
        color 0.16s ease,
        box-shadow 0.16s ease;
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

.group-chat-approval-list {
    display: grid;
    gap: 10px;
    padding-bottom: 4px;
}

.group-chat-approval-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
}

.group-chat-approval-card {
    position: relative;
    padding: 16px;
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.05),
            rgba(255, 255, 255, 0.025)
        ),
        rgba(11, 15, 26, 0.88);
    box-shadow: 0 18px 38px rgba(4, 8, 20, 0.24);
    display: flex;
    flex-direction: column;
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

.group-chat-approval-card__meta {
    margin-top: 14px;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.9rem;
}

.group-chat-approval-card__meta-date {
    display: block;
    margin-top: 4px;
    color: rgba(255, 255, 255, 0.56);
    font-size: 0.82rem;
}

.group-chat-approval-card__timestamp {
    margin-top: 6px;
    color: rgba(255, 255, 255, 0.56);
    font-size: 0.82rem;
    line-height: 1.4;
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
    transition:
        transform 0.2s ease,
        background 0.2s ease,
        border-color 0.2s ease;
}

.group-chat-approval-card__open-btn:hover {
    transform: translateY(-1px);
    background: rgba(104, 149, 253, 0.22);
    border-color: rgba(104, 149, 253, 0.34);
}

.group-chat-approval-card__members {
    position: relative;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.9rem;
}

.group-chat-approval-card__member-trigger {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    transition: transform 0.18s ease;
}

.group-chat-approval-card__member-trigger:hover {
    transform: translateY(-1px);
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

.group-chat-approval-card__member-summary {
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.84rem;
    font-weight: 700;
    white-space: nowrap;
}

.group-chat-approval-card__actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 18px;
    padding-top: 2px;
}

.group-chat-approval-card__actions .message-action-modal__btn {
    flex: 1 1 0;
}

.approval-members-view {
    display: grid;
    gap: 14px;
}

.approval-members-view__back {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    color: #f3f6fb;
    font-weight: 700;
    transition:
        transform 0.18s ease,
        background 0.18s ease,
        border-color 0.18s ease;
}

.approval-members-view__back:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.14);
}

.approval-members-view__list {
    display: grid;
    gap: 10px;
}

.approval-members-view__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 255, 255, 0.04);
}

.approval-members-view__item img {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    flex: 0 0 42px;
    background: rgba(255, 255, 255, 0.08);
}

.approval-members-view__content {
    min-width: 0;
}

.approval-members-view__name {
    color: #f3f6fb;
    font-size: 0.95rem;
    font-weight: 700;
    word-break: break-word;
}

.approval-members-view__meta {
    margin-top: 2px;
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.82rem;
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
    margin: 10px 16px 10px;
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
    transition:
        transform 0.15s ease,
        background 0.15s ease;
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
        box-shadow:
            0 0 0 0 rgba(255, 77, 136, 0.45),
            0 16px 30px rgba(0, 0, 0, 0.18);
    }
    100% {
        box-shadow:
            0 0 0 12px rgba(255, 77, 136, 0),
            0 16px 30px rgba(0, 0, 0, 0.18);
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
        radial-gradient(
            circle at top right,
            rgba(28, 88, 246, 0.12),
            transparent 26%
        ),
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
.group-chat-approval-card {
    background: rgb(56, 62, 72);
    border-color: rgba(255, 255, 255, 0.06);
    color: #f3f6fb;
}

.pinned-modal__item-body:hover,
.reaction-modal__option:hover {
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
.message-action-modal__context {
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
        radial-gradient(
            circle at top right,
            rgba(28, 88, 246, 0.1),
            transparent 32%
        ),
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
    // padding: 22px 24px 18px;
    background:
        radial-gradient(
            circle at 15% 15%,
            rgba(28, 88, 246, 0.08),
            transparent 18%
        ),
        radial-gradient(
            circle at 85% 25%,
            rgba(99, 102, 241, 0.06),
            transparent 20%
        ),
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
    box-shadow:
        0 0 0 2px rgba(28, 88, 246, 0.24),
        0 16px 30px rgba(0, 0, 0, 0.18);
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

.bubble-action--menu {
    background: rgba(255, 255, 255, 0.04);
}

.bubble-action--menu:hover {
    background: rgba(255, 255, 255, 0.08);
}

.bubble-action--menu-primary,
.bubble-action--menu-primary:hover {
    background: linear-gradient(180deg, #3563f0 0%, #2d55da 100%);
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
        box-shadow:
            0 0 0 0 rgba(28, 88, 246, 0.45),
            0 16px 30px rgba(0, 0, 0, 0.18);
    }
    100% {
        box-shadow:
            0 0 0 12px rgba(28, 88, 246, 0),
            0 16px 30px rgba(0, 0, 0, 0.18);
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
        flex-basis: clamp(360px, 33vw, 420px);
        width: clamp(360px, 33vw, 420px);
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
        flex-basis: clamp(340px, 42vw, 400px);
        width: clamp(340px, 42vw, 400px);
    }

    .contacts-panel__header {
        padding: 0;
    }

    .contacts-panel__header-block {
        padding: 16px 16px 10px;
    }

    .contacts-panel__search {
        padding: 4px 16px 12px;
    }

    .contacts-panel__utility-row {
        gap: 8px;
    }

    .contacts-panel__utility-link,
    .contacts-panel__utility-badge {
        min-height: 42px;
        padding: 0 12px;
        font-size: 0.76rem;
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
        gap: 6px;
        padding-right: 4px;
    }

    .contacts-panel__utility-link,
    .contacts-panel__utility-badge {
        min-height: 38px;
        padding: 0 10px;
        gap: 5px;
        font-size: 0.7rem;
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

    .contacts-panel__header-block {
        padding: 14px 14px 8px;
    }

    .contacts-panel__header {
        padding: 0;
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

    .message-bubble-wrap {
        max-width: 92%;
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
        margin-top: 8px;
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
