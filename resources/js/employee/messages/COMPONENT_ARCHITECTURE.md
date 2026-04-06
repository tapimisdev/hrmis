# Messages Component Architecture

## Overview
The `MessagesPage.vue` component has been refactored into smaller, focused components for better maintainability and readability.

## Component Structure

```
MessagesPage.vue (Main container & logic orchestrator)
├── MessagesSidebar.vue (Already extracted)
│   └── ContactCard.vue (NEW - Individual conversation items)
│
├── ConversationWorkspace.vue (Already extracted)
│   ├── ConversationHeaderBar.vue (NEW - Conversation header with actions)
│   ├── MessageStream.vue (NEW - Message list container)
│   │   └── MessageBubble.vue (NEW - Individual message bubbles)
│   ├── ComposerArea.vue (NEW - Input area with attachments)
│   └── PinnedMessagesPanel.vue (NEW - Pinned messages overlay)
│
└── NotificationStack.vue (NEW - Toast notifications)

// Modals (to be extracted in future refactoring):
├── GroupChatModal
├── ConversationInfoModal
├── GroupMembersModal
├── LeaveGroupModal
└── ... other modals
```

## New Components

### 1. **ContactCard.vue**
**Location:** `components/ContactCard.vue`

**Purpose:** Displays a single conversation item in the contacts list

**Props:**
- `user` (Object): The conversation/user object
- `isActive` (Boolean): Whether this contact is currently selected
- `isOnline` (Boolean): Whether the user/group is online

**Emits:**
- `@select`: When user clicks on the contact
- `@delete`: When user deletes messages from this contact
- `@menu-toggle`: When the context menu is toggled

**Replaces:** The v-for loop rendering contact cards (lines ~200-300 in original)

---

### 2. **ConversationHeaderBar.vue**
**Location:** `components/ConversationHeaderBar.vue`

**Purpose:** Displays conversation header with user info and action buttons

**Props:**
- `activeUser` (Object): The active conversation
- `activeUserName` (String): Display name
- `activeUserAvatar` (String): Avatar URL
- `activeUserStatus` (String): Status label
- `isOnline` (Boolean): Online status
- `isGroup` (Boolean): Is group chat

**Emits:**
- `@invite-members`: Invite dialog
- `@show-members`: Members list
- `@leave-group`: Leave group chat
- `@show-info`: Conversation info
- `@open-users-panel`: Mobile users panel

**Replaces:** The `conversation-panel__top` section (lines ~400-550 in original)

---

### 3. **MessageBubble.vue**
**Location:** `components/MessageBubble.vue`

**Purpose:** Renders a single message with all its properties

**Props:**
- `message` (Object): Message data
- `isGroup` (Boolean): Is group chat
- `isMine` (Boolean): Is user's message
- `isSystem` (Boolean): Is system message
- `shouldShowSeenReceipt` (Boolean): Show seen indicator
- `reactionOptions` (Array): Available reactions

**Emits:**
- `@scroll-to-reply`: Scroll to the replied message
- `@open-image`: Open image in gallery
- `@select`: Select message for actions
- `@delete`: Delete message
- `@edit`: Edit message
- `@pin`: Pin/unpin message
- `@set-reaction`: Add reaction

**Replaces:** The v-for loop rendering individual messages (lines ~700-1000 in original)

---

### 4. **MessageStream.vue**
**Location:** `components/MessageStream.vue`

**Purpose:** Container for the message list with scroll handling

**Props:**
- `messages` (Array): Array of messages
- `loading` (Boolean): Loading state
- `loadingOlder` (Boolean): Loading older messages
- `didHydrateFromCache` (Boolean): Cache hydration state
- `isGroup` (Boolean): Is group chat
- `typingIndicator` (Boolean): Show typing indicator
- `typingIndicatorLabel` (String): Typing label
- `showScrollToBottomButton` (Boolean): Show scroll button
- `latestSeenReceiptMessageId` (Number): Latest seen for DMs
- `latestGroupSeenReceiptMessageId` (Number): Latest seen for groups
- `reactionOptions` (Array): Available reactions

**Emits:**
- Message action events (scroll, delete, edit, pin, reactions, etc.)

**Replaces:** The `conversation-panel__body` and message rendering section (lines ~600-1200 in original)

---

### 5. **ComposerArea.vue**
**Location:** `components/ComposerArea.vue`

**Purpose:** Message input area with attachments, emoji picker, and reply preview

**Props:**
- `draftMessage` (String): Current message text
- `replyTargetMessage` (Object): Message being replied to
- `replyTargetLabel` (String): Reply label text
- `selectedAttachment` (File): Selected attachment
- `selectedAttachmentPreviewUrl` (String): Attachment preview
- `selectedAttachmentPreviewType` (String): 'image' or 'file'
- `attachmentAccept` (String): File accept patterns
- `messageCharacterLimit` (Number): Max characters
- `showPinnedMessagesPanel` (Boolean): Pinned panel visibility
- `showComposerEmojiPicker` (Boolean): Emoji picker visibility
- `activeUser` (Object): Active conversation
- `sendingMessage` (Boolean): Sending state
- `placeholder` (String): Input placeholder
- `composerEmojiOptions` (Array): Emojis to show

**Emits:**
- `@update:draftMessage`: Message text update
- `@send-message`: Send the message
- `@clear-reply`: Clear reply target
- `@clear-attachment`: Clear attachment
- `@toggle-pinned`: Toggle pinned panel
- `@toggle-emoji-picker`: Toggle emoji picker
- `@emoji-selected`: Emoji selected
- `@attachment-selected`: Attachment selected
- `@blur`: Input blur
- `@focus`: Input focus

**Replaces:** The `composer` form section and emoji picker (lines ~1300-1600 in original)

---

### 6. **PinnedMessagesPanel.vue**
**Location:** `components/PinnedMessagesPanel.vue`

**Purpose:** Modal overlay for displaying pinned messages

**Props:**
- `show` (Boolean): Show/hide panel
- `pinnedMessages` (Array): List of pinned messages

**Emits:**
- `@close`: Close the panel
- `@scroll-to`: Scroll to a pinned message
- `@unpin`: Unpin a message

**Replaces:** The pinned messages modal (lines ~450-550 in original)

---

### 7. **NotificationStack.vue**
**Location:** `components/NotificationStack.vue`

**Purpose:** Toast notification container

**Props:**
- `notifications` (Array): Array of notifications

**Emits:**
- `@dismiss`: Dismiss a notification

**Replaces:** The notification rendering logic

---

## Migration Guide

### Before (MonolithicComponent):
```vue
<template>
  <div class="contact-card">
    <!-- 100+ lines of contact card template -->
  </div>
</template>

<script>
export default {
  data() {
    return {
      // 90+ data properties
    }
  },
  methods: {
    // 200+ methods
  }
}
</script>
```

### After (Component-Based):
```vue
<template>
  <div class="messages-page">
    <!-- Clean, focused layout -->
    <MessagesSidebar>
      <template #list>
        <ContactCard
          v-for="user in visibleUsers"
          :key="user.id"
          :user="user"
          :is-active="user.conversation_key === selectedConversationKey"
          :is-online="isConversationOnline(user)"
          @select="selectUser"
          @delete="openConversationDeleteModal"
        />
      </template>
    </MessagesSidebar>

    <ConversationWorkspace>
      <template #header>
        <ConversationHeaderBar
          :active-user="activeUser"
          :active-user-name="activeUserName"
          :is-group="activeConversationIsGroup"
          @show-info="openConversationInfoModal"
        />
      </template>

      <template #default>
        <MessageStream
          :messages="messages"
          :is-group="activeConversationIsGroup"
          @scroll="handleConversationScroll"
          @select-message="selectMessage"
        />

        <ComposerArea
          :draft-message="draftMessage"
          @send-message="sendMessage"
          @update:draft-message="$set(this, 'draftMessage', $event)"
        />
      </template>
    </ConversationWorkspace>

    <PinnedMessagesPanel
      :show="showPinnedMessagesPanel"
      :pinned-messages="pinnedMessages"
      @close="showPinnedMessagesPanel = false"
    />

    <NotificationStack
      :notifications="notifications"
      @dismiss="dismissNotification"
    />
  </div>
</template>
```

## State Management

The main `MessagesPage.vue` component maintains:

**Core State:**
- `users` - List of conversations
- `selectedConversationKey` - Currently active conversation
- `messages` - Messages in active conversation
- `draftMessage` - Composer input

**UI State:**
- `showPinnedMessagesPanel` - Pinned panel visibility
- `showComposerEmojiPicker` - Emoji picker visibility
- `showScrollToBottomButton` - Scroll button visibility

**Child Components handle their own:**
- Menu visibility (ContactCard)
- Individual emoji selection (ComposerArea)
- Notification animation state

## Benefits

1. **Readability** - Each component is 100-200 lines instead of 11,000+
2. **Reusability** - Components can be used elsewhere
3. **Testing** - Smaller components are easier to unit test
4. **Maintainability** - Changes isolated to specific component
5. **Performance** - Lazy loading and code splitting opportunities
6. **Collaboration** - Multiple developers can work on different components

## Future Refactoring

The following aspects could be further extracted into components:

1. **Modal Components:**
   - GroupChatModal
   - ConversationInfoModal
   - GroupMembersModal
   - GroupLeaveModal
   - GroupInviteModal
   - ConversationDeleteModal
   - AlertModal

2. **Utility Components:**
   - ImageGallery wrapper
   - ReactionPicker
   - SeenByModal
   - BetaInfoModal
   - PrivacyInfoModal

3. **Business Logic Extraction:**
   - MessageService (handle CRUD operations)
   - TypingService (handle typing state)
   - PresenceService (handle online status)
   - NotificationService (handle notifications)
   - CacheService (handle localStorage)

## Script Structure in MessagesPage.vue

Now much cleaner:

```javascript
export default {
  name: "MessagesPage",
  components: {
    MessagesSidebar,
    ConversationWorkspace,
    ContactCard,
    ConversationHeaderBar,
    MessageStream,
    ComposerArea,
    PinnedMessagesPanel,
    NotificationStack,
    // Modal components...
  },
  props: { /* ... */ },
  data() {
    return {
      // Only core state (~40 properties instead of 90+)
    }
  },
  computed: { /* ... */ },
  methods: {
    // High-level orchestration methods only
    selectUser() { },
    sendMessage() { },
    loadConversation() { },
    // Event handlers that delegate to child components
  }
}
```

---

## Implementation Checklist

- [x] ContactCard.vue - Individual conversation items
- [x] ConversationHeaderBar.vue - Header bar with user info
- [x] MessageBubble.vue - Individual message rendering
- [x] MessageStream.vue - Message list container
- [x] ComposerArea.vue - Message input area
- [x] PinnedMessagesPanel.vue - Pinned messages panel
- [x] NotificationStack.vue - Toast notifications
- [ ] Integrate components into MessagesPage.vue
- [ ] Extract modal components (Phase 2)
- [ ] Extract service layer (Phase 3)
- [ ] Add unit tests for components
- [ ] Update documentation
