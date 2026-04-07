<template>
    <transition name="fade">
        <div
            v-if="isOpen && selectedMessage"
            class="reaction-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="reaction-modal"
                role="dialog"
                aria-modal="true"
                tabindex="-1"
                @keydown.esc.prevent="handleClose"
            >
                <div class="reaction-modal__header">
                    <div>
                        <div class="reaction-modal__eyebrow">
                            Emoji reaction
                        </div>
                        <h3 class="reaction-modal__title">
                            React to this message
                        </h3>
                        <p class="reaction-modal__subtitle">
                            {{ targetPreview }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="reaction-modal__close"
                        @click="handleClose"
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
                        :class="{
                            'is-active':
                                selectedMessage?.reactions?.some(
                                    r => Number(r.user_id) === Number(currentUserId) && r.reaction === reaction.key
                                ),
                        }"
                        :title="reaction.label"
                        :aria-label="reaction.label"
                        @click="setReaction(reaction.key)"
                    >
                        <span class="reaction-modal__emoji">{{
                            reaction.emoji
                        }}</span>
                        <span class="reaction-modal__label">{{
                            reaction.label
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "ReactionPickerModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        selectedMessage: {
            type: Object,
            default: null,
        },
        reactionOptions: {
            type: Array,
            required: true,
        },
        currentUserId: {
            type: [String, Number],
            required: true,
        },
    },
    emits: ["close", "set-reaction"],
    computed: {
        targetPreview() {
            if (!this.selectedMessage) return "";
            if (this.selectedMessage.body) {
                return this.selectedMessage.body.substring(0, 50);
            }
            if (this.selectedMessage.attachment?.name) {
                return this.selectedMessage.attachment.name;
            }
            return "Attachment";
        },
    },
    methods: {
        handleClose() {
            this.$emit("close");
        },
        setReaction(reactionKey) {
            this.$emit("set-reaction", this.selectedMessage, reactionKey);
        },
    },
};
</script>

<style scoped lang="scss">
.reaction-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5000;
    backdrop-filter: blur(4px);

    @media (max-width: 768px) {
        align-items: flex-end;
    }
}

.reaction-modal {
    position: relative;
    z-index: 5001;
    background: rgba(34, 39, 46, 0.96);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 24px;
    max-width: 520px;
    width: 90vw;
    max-height: 70vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);

    @media (max-width: 768px) {
        border-radius: 24px 24px 0 0;
        width: 100%;
        max-height: 60vh;
    }
}

.reaction-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    gap: 16px;
}

.reaction-modal__eyebrow {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgba(214, 222, 235, 0.56);
    margin-bottom: 8px;
}

.reaction-modal__title {
    font-size: 1.375rem;
    font-weight: 600;
    color: #f3f6fb;
    margin: 4px 0;
}

.reaction-modal__subtitle {
    font-size: 0.875rem;
    color: rgba(214, 222, 235, 0.72);
    margin-top: 4px;
    word-break: break-word;
}

.reaction-modal__close {
    background: transparent;
    border: none;
    color: rgba(214, 222, 235, 0.72);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
    flex-shrink: 0;

    &:hover {
        color: #f3f6fb;
    }
}

.reaction-modal__body {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    overflow-y: auto;

    @media (max-width: 480px) {
        grid-template-columns: repeat(2, 1fr);
    }
}

.reaction-modal__option {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #f3f6fb;

    &:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.16);
    }

    &.is-active {
        background: rgba(79, 172, 254, 0.2);
        border-color: rgba(79, 172, 254, 0.5);
        box-shadow: 0 0 12px rgba(79, 172, 254, 0.3);
    }
}

.reaction-modal__emoji {
    font-size: 2rem;
    line-height: 1;
}

.reaction-modal__label {
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
}
</style>
