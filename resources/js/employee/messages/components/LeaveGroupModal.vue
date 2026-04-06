<template>
    <transition name="fade">
        <div
            v-if="isOpen"
            class="leave-group-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="leave-group-modal"
                role="dialog"
                aria-modal="true"
            >
                <div class="leave-group-modal__header">
                    <div class="leave-group-modal__headline">
                        <div
                            class="leave-group-modal__badge leave-group-modal__badge--danger"
                        >
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </div>
                        <div class="leave-group-modal__eyebrow">
                            Leave group
                        </div>
                        <h3 class="leave-group-modal__title">
                            Leave {{ groupName }}?
                        </h3>
                        <p class="leave-group-modal__subtitle">
                            You will stop receiving messages from this group
                            unless someone invites you again.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="leave-group-modal__close"
                        :disabled="isSubmitting"
                        @click="handleClose"
                        aria-label="Close dialog"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="leave-group-modal__body">
                    <div class="leave-group-modal__context">
                        <div class="leave-group-modal__context-label">
                            Group
                        </div>
                        <div class="leave-group-modal__preview">
                            {{ groupName }}
                        </div>
                    </div>
                    <p v-if="error" class="leave-group-modal__error">
                        {{ error }}
                    </p>
                </div>

                <div class="leave-group-modal__footer">
                    <button
                        type="button"
                        class="leave-group-modal__btn leave-group-modal__btn--ghost"
                        :disabled="isSubmitting"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="leave-group-modal__btn leave-group-modal__btn--danger"
                        :disabled="isSubmitting"
                        @click="handleConfirm"
                    >
                        <span
                            v-if="isSubmitting"
                            class="spinner-border spinner-border-sm"
                            aria-hidden="true"
                        ></span>
                        <span v-else>Leave group</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "LeaveGroupModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        groupName: {
            type: String,
            required: true,
        },
        isSubmitting: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String,
            default: "",
        },
    },
    emits: ["close", "confirm"],
    methods: {
        handleClose() {
            this.$emit("close");
        },
        handleConfirm() {
            this.$emit("confirm");
        },
    },
};
</script>

<style scoped lang="scss">
.leave-group-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(12, 16, 23, 0.56);
    backdrop-filter: blur(14px);
}

.leave-group-modal {
    width: min(92vw, 620px);
    max-height: min(88vh, 760px);
    display: flex;
    flex-direction: column;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(
            circle at top right,
            rgba(220, 53, 69, 0.1),
            transparent 26%
        ),
        linear-gradient(180deg, rgba(49, 55, 63, 0.98), rgba(37, 42, 49, 0.99));
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.32);
    overflow: hidden;
}

.leave-group-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.leave-group-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.leave-group-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.leave-group-modal__badge--danger {
    background: rgba(220, 53, 69, 0.16);
    color: #ffb3be;
}

.leave-group-modal__eyebrow,
.leave-group-modal__context-label {
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-size: 0.72rem;
    color: rgba(214, 222, 235, 0.62);
}

.leave-group-modal__title {
    margin: 0;
    color: #f3f6fb;
    font-size: 1.1rem;
    font-weight: 800;
}

.leave-group-modal__subtitle {
    margin: 0;
    color: rgba(214, 222, 235, 0.62);
    line-height: 1.55;
}

.leave-group-modal__close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    color: #f3f6fb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
}

.leave-group-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px 22px 18px;
    overflow-y: auto;
}

.leave-group-modal__context {
    margin-bottom: 16px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 16px;
    background: rgb(56, 62, 72);
}

.leave-group-modal__context-label {
    margin-bottom: 8px;
}

.leave-group-modal__preview {
    color: #f3f6fb;
    line-height: 1.55;
    font-weight: 700;
}

.leave-group-modal__error {
    margin: 14px 0 0;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.18);
    color: #ffd7dd;
    font-size: 0.92rem;
}

.leave-group-modal__footer {
    display: flex;
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
}

.leave-group-modal__btn {
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

.leave-group-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.04);
    color: #f3f6fb;
}

.leave-group-modal__btn--danger {
    background: linear-gradient(135deg, #d64c61, #ba364d);
    color: #fff;
}

.leave-group-modal__close:disabled,
.leave-group-modal__btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
