<template>
    <div>
        <div v-if="replyTargetMessage" class="composer-reply">
            <div class="composer-reply__meta">
                <strong>Replying to {{ replyTargetLabel }}</strong>
                <button
                    type="button"
                    class="composer-reply__close"
                    @click="$emit('clear-reply-target')"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="composer-reply__preview">
                {{ getMessageSnippetText(replyTargetMessage) }}
            </div>
        </div>

        <div v-if="selectedAttachment" class="attachment-preview">
            <div class="attachment-preview__meta">
                <span
                    v-if="
                        selectedAttachmentPreviewType === 'image' &&
                        selectedAttachmentPreviewUrl
                    "
                    class="attachment-preview__thumb"
                >
                    <img
                        :src="selectedAttachmentPreviewUrl"
                        :alt="selectedAttachment.name"
                    />
                </span>
                <span v-else class="attachment-preview__icon">
                    <i class="fa-regular fa-file-lines"></i>
                </span>
                <div class="attachment-preview__body">
                    <div class="attachment-preview__name">
                        {{ selectedAttachment.name }}
                    </div>
                    <small class="text-white-50">
                        {{ formatFileSizeText(selectedAttachment.size) }}
                    </small>
                </div>
            </div>
            <button
                type="button"
                class="attachment-preview__remove"
                @click="$emit('clear-selected-attachment')"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form class="composer" @submit.prevent="$emit('send-message')">
            <input
                ref="attachmentInput"
                type="file"
                class="d-none"
                :accept="attachmentAccept"
                @change="$emit('attachment-change', $event)"
            />
            <button
                type="button"
                class="composer__button"
                :class="{ 'is-active': showPinnedMessagesPanel }"
                aria-label="Pinned messages"
                title="View pinned messages"
                @click.stop="$emit('toggle-pinned-messages-panel')"
            >
                <i class="fa-solid fa-thumbtack"></i>
            </button>
            <button
                ref="composerEmojiButton"
                type="button"
                class="composer__button"
                aria-label="Insert emoji"
                @click="$emit('toggle-composer-emoji-picker')"
            >
                <i class="fa-regular fa-face-smile"></i>
            </button>
            <button
                type="button"
                class="composer__button"
                aria-label="Attach file"
                @click="$emit('trigger-attachment-picker', 'file')"
            >
                <i class="fa-regular fa-file-lines"></i>
            </button>
            <div class="composer__field">
                <div class="composer__input-shell">
                    <textarea
                        ref="composerInput"
                        :value="draftMessage"
                        class="composer__input"
                        rows="1"
                        :placeholder="
                            activeConversationIsGroup
                                ? 'Message the group'
                                : 'Aa'
                        "
                        :maxlength="messageCharacterLimit"
                        :disabled="!activeUser || sendingMessage"
                        @input="handleInput"
                        @blur="$emit('composer-blur')"
                        @focus="$emit('capture-selection')"
                        @click="$emit('capture-selection')"
                        @keyup="$emit('capture-selection')"
                        @select="$emit('capture-selection')"
                        @keydown.enter.exact.prevent="$emit('send-message')"
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
                @click="$emit('scroll-to-bottom')"
                title="Scroll to bottom"
                aria-label="Scroll to bottom"
            >
                <i class="fa-solid fa-arrow-down"></i>
            </button>
            <button
                type="submit"
                class="composer__send"
                :disabled="
                    !activeUser ||
                    sendingMessage ||
                    (!draftMessage.trim() && !selectedAttachment)
                "
            >
                <i v-if="!sendingMessage" class="fa-regular fa-paper-plane"></i>
                <span
                    v-else
                    class="spinner-border spinner-border-sm"
                    aria-hidden="true"
                ></span>
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
                            @pointerdown.prevent.stop="
                                $emit('insert-composer-emoji', emoji)
                            "
                        >
                            {{ emoji }}
                        </button>
                    </div>
                </div>
            </transition>
        </form>
    </div>
</template>

<script>
export default {
    name: "ComposerArea",
    props: {
        replyTargetMessage: {
            type: Object,
            default: null,
        },
        replyTargetLabel: {
            type: String,
            default: "you",
        },
        getMessageSnippet: {
            type: Function,
            default: null,
        },
        selectedAttachment: {
            type: Object,
            default: null,
        },
        selectedAttachmentPreviewUrl: {
            type: String,
            default: null,
        },
        selectedAttachmentPreviewType: {
            type: String,
            default: "file",
        },
        formatFileSize: {
            type: Function,
            default: null,
        },
        attachmentAccept: {
            type: String,
            default: "",
        },
        showPinnedMessagesPanel: {
            type: Boolean,
            default: false,
        },
        showComposerEmojiPicker: {
            type: Boolean,
            default: false,
        },
        composerEmojiOptions: {
            type: Array,
            default: () => [],
        },
        activeUser: {
            type: Object,
            default: null,
        },
        sendingMessage: {
            type: Boolean,
            default: false,
        },
        draftMessage: {
            type: String,
            default: "",
        },
        activeConversationIsGroup: {
            type: Boolean,
            default: false,
        },
        messageCharacterLimit: {
            type: Number,
            default: 2000,
        },
        messageCharacterCount: {
            type: Number,
            default: 0,
        },
        messageCharactersRemaining: {
            type: Number,
            default: 0,
        },
        showScrollToBottomButton: {
            type: Boolean,
            default: false,
        },
    },
    emits: [
        "send-message",
        "clear-reply-target",
        "clear-selected-attachment",
        "toggle-pinned-messages-panel",
        "toggle-composer-emoji-picker",
        "trigger-attachment-picker",
        "update:draft-message",
        "composer-input",
        "composer-blur",
        "capture-selection",
        "scroll-to-bottom",
        "insert-composer-emoji",
        "attachment-change",
    ],
    methods: {
        getInputElement() {
            return this.$refs.composerInput || null;
        },
        getAttachmentInputElement() {
            return this.$refs.attachmentInput || null;
        },
        getEmojiButtonElement() {
            return this.$refs.composerEmojiButton || null;
        },
        getEmojiOverlayElement() {
            return this.$refs.composerEmojiOverlay || null;
        },
        openAttachmentPicker() {
            this.$refs.attachmentInput?.click?.();
        },
        getMessageSnippetText(message) {
            return this.getMessageSnippet ? this.getMessageSnippet(message) : "";
        },
        formatFileSizeText(size) {
            return this.formatFileSize ? this.formatFileSize(size) : "";
        },
        handleInput(event) {
            this.$emit("update:draft-message", event.target.value);
            this.$emit("composer-input");
        },
    },
};
</script>
