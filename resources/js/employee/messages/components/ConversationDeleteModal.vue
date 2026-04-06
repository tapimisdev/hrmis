<template>
    <transition name="fade">
        <div
            v-if="isOpen"
            class="message-action-modal-backdrop"
            @click.self="$emit('close')"
        >
            <div class="message-action-modal" role="dialog" aria-modal="true">
                <div class="message-action-modal__header">
                    <div class="message-action-modal__headline">
                        <div
                            class="message-action-modal__badge message-action-modal__badge--danger"
                        >
                            <i class="fa-regular fa-trash-can"></i>
                        </div>
                        <div class="message-action-modal__eyebrow">Delete for you</div>
                        <h3 class="message-action-modal__title">
                            Delete your copy of this chat?
                        </h3>
                        <p class="message-action-modal__subtitle">
                            This clears the messages only for you. Other participants will
                            still be able to see them.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="message-action-modal__close"
                        :disabled="submitting"
                        aria-label="Close dialog"
                        @click="$emit('close')"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="message-action-modal__body">
                    <div class="message-action-modal__context">
                        <div class="message-action-modal__context-label">Conversation</div>
                        <div class="message-action-modal__preview">
                            {{ conversationName || "This chat" }}
                        </div>
                    </div>

                    <p v-if="error" class="message-action-modal__error">{{ error }}</p>
                </div>

                <div class="message-action-modal__footer">
                    <button
                        type="button"
                        class="message-action-modal__btn message-action-modal__btn--ghost"
                        :disabled="submitting"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="message-action-modal__btn message-action-modal__btn--danger"
                        :disabled="submitting || !conversationName"
                        @click="$emit('confirm')"
                    >
                        <span
                            v-if="submitting"
                            class="spinner-border spinner-border-sm"
                            aria-hidden="true"
                        ></span>
                        <span v-else>Delete for me</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "ConversationDeleteModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        conversationName: {
            type: String,
            default: "",
        },
        submitting: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String,
            default: "",
        },
    },
    emits: ["close", "confirm"],
};
</script>
