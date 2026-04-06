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
                            class="message-action-modal__badge"
                            :class="
                                type === 'success'
                                    ? 'message-action-modal__badge--edit'
                                    : 'message-action-modal__badge--danger'
                            "
                        >
                            <i
                                :class="
                                    type === 'success'
                                        ? 'fa-solid fa-check'
                                        : 'fa-solid fa-triangle-exclamation'
                                "
                            ></i>
                        </div>
                        <div class="message-action-modal__eyebrow">Notice</div>
                        <h3 class="message-action-modal__title">{{ title }}</h3>
                        <p class="message-action-modal__subtitle">{{ message }}</p>
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

                <div class="message-action-modal__footer">
                    <button
                        type="button"
                        class="message-action-modal__btn"
                        :class="
                            type === 'success'
                                ? 'message-action-modal__btn--primary'
                                : 'message-action-modal__btn--danger'
                        "
                        @click="$emit('close')"
                    >
                        OK
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "AlertModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        type: {
            type: String,
            default: "error",
        },
        title: {
            type: String,
            default: "",
        },
        message: {
            type: String,
            default: "",
        },
    },
    emits: ["close"],
};
</script>
