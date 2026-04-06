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
                            class="message-action-modal__badge message-action-modal__badge--edit"
                        >
                            <i :class="iconClass"></i>
                        </div>
                        <div class="message-action-modal__eyebrow">
                            {{ eyebrow }}
                        </div>
                        <h3 class="message-action-modal__title">{{ title }}</h3>
                        <p class="message-action-modal__subtitle">{{ subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="message-action-modal__close"
                        aria-label="Close dialog"
                        @click="$emit('close')"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="message-action-modal__body">
                    <div class="message-action-modal__context">
                        <div class="message-action-modal__context-label">
                            {{ contextLabel }}
                        </div>
                        <div
                            class="message-action-modal__preview message-action-modal__preview--stacked"
                        >
                            <p v-for="(paragraph, index) in paragraphs" :key="index">
                                {{ paragraph }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="message-action-modal__footer">
                    <button
                        type="button"
                        class="message-action-modal__btn message-action-modal__btn--primary"
                        @click="$emit('close')"
                    >
                        Understood
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "InfoNoticeModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        iconClass: {
            type: String,
            required: true,
        },
        eyebrow: {
            type: String,
            required: true,
        },
        title: {
            type: String,
            required: true,
        },
        subtitle: {
            type: String,
            required: true,
        },
        contextLabel: {
            type: String,
            required: true,
        },
        paragraphs: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["close"],
};
</script>
