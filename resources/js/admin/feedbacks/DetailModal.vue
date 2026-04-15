<template>
    <div
        class="modal fade"
        id="feedbackDetailModal"
        tabindex="-1"
        aria-labelledby="feedbackDetailModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content feedback-modal">
                <div class="modal-header feedback-modal__header border-0">
                    <div class="feedback-modal__header-copy">
                        <div class="feedback-modal__eyebrow">Employee Feedback</div>
                        <h5 id="feedbackDetailModalLabel" class="modal-title fw-bold mb-1">
                            {{ feedback.subject || "Feedback Details" }}
                        </h5>
                        <small class="feedback-modal__timestamp">
                            {{ feedback.date_submitted || "" }}
                        </small>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body pt-3">
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>

                    <div v-else class="feedback-modal__body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="feedback-panel h-100">
                                    <div class="feedback-panel__label">Submitted By</div>
                                    <div class="feedback-panel__identity">
                                        <div class="feedback-panel__value feedback-panel__value--uppercase">
                                            {{ displaySenderName }}
                                        </div>
                                        <span
                                            v-if="feedback.is_anonymous"
                                            class="feedback-chip feedback-chip--anonymous"
                                        >
                                            Anonymous
                                        </span>
                                    </div>
                                    <div
                                        v-if="!feedback.is_anonymous && feedback.user_email"
                                        class="feedback-panel__subvalue"
                                    >
                                        {{ feedback.user_email }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="feedback-panel h-100">
                                    <div class="feedback-panel__label">Category</div>
                                    <div class="feedback-panel__value">
                                        {{ feedback.category || "-" }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="feedback-panel">
                                    <div class="feedback-panel__label">Message</div>
                                    <div class="feedback-message">
                                        {{ feedback.message || "No message provided." }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="feedback-panel">
                                    <div class="feedback-panel__label">Attachment</div>

                                    <div v-if="feedback.attachment_url" class="attachment-block">
                                        <div class="attachment-block__summary">
                                            <div class="attachment-block__icon">
                                                <i :class="attachmentIconClass"></i>
                                            </div>
                                            <div class="attachment-block__meta">
                                                <div class="attachment-block__name">
                                                    {{ feedback.attachment_name || "Attachment" }}
                                                </div>
                                                <div class="attachment-block__submeta">
                                                    {{ feedback.attachment_mime || "Uploaded file" }}
                                                    <span v-if="formattedAttachmentSize">
                                                        · {{ formattedAttachmentSize }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="attachment-block__actions">
                                                <a
                                                    :href="feedback.attachment_url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="btn btn-primary btn-sm"
                                                >
                                                    Open
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="attachment-empty">
                                        <i class="fa-regular fa-folder-open me-2"></i>
                                        No attachment uploaded
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as bootstrap from "bootstrap";

export default {
    name: "FeedbackDetailModal",
    data() {
        return {
            modalInstance: null,
            loading: false,
            feedback: {},
        };
    },
    computed: {
        formattedAttachmentSize() {
            const bytes = this.feedback.attachment_size;

            if (!bytes) {
                return "";
            }

            const sizes = ["B", "KB", "MB", "GB"];
            const order = Math.min(
                Math.floor(Math.log(bytes) / Math.log(1024)),
                sizes.length - 1,
            );

            return `${(bytes / 1024 ** order).toFixed(order === 0 ? 0 : 1)} ${sizes[order]}`;
        },
        isImageAttachment() {
            return /^image\//i.test(this.feedback.attachment_mime || "");
        },
        isPdfAttachment() {
            return /application\/pdf|pdf/i.test(this.feedback.attachment_mime || "");
        },
        isPreviewableAttachment() {
            return this.isImageAttachment || this.isPdfAttachment;
        },
        attachmentIconClass() {
            if (this.isImageAttachment) {
                return "fa-regular fa-image";
            }

            if (this.isPdfAttachment) {
                return "fa-regular fa-file-pdf";
            }

            return "fa-regular fa-file-lines";
        },
        displaySenderName() {
            const name = this.feedback.is_anonymous
                ? this.feedback.anonymous_nickname || "Anonymous"
                : this.feedback.user_name || "Unknown";

            return String(name).toUpperCase();
        },
    },
    mounted() {
        const element = document.getElementById("feedbackDetailModal");

        if (element) {
            this.modalInstance = new bootstrap.Modal(element);
        }

        window.feedbackDetailModal = this;
    },
    beforeUnmount() {
        if (window.feedbackDetailModal === this) {
            delete window.feedbackDetailModal;
        }
    },
    methods: {
        open(feedback) {
            this.feedback = feedback || {};
            this.loading = false;
            this.modalInstance?.show();
        },
    },
};
</script>

<style scoped>
.feedback-modal {
    border: 0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
    background: var(--bs-body-bg);
}

.feedback-modal__header {
    background: transparent;
    color: var(--bs-emphasis-color);
    padding: 1.2rem 1.35rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.feedback-modal__header :deep(.btn-close) {
    filter: none;
}

.feedback-modal__eyebrow {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    opacity: 0.8;
    margin-bottom: 0.2rem;
}

.feedback-modal__timestamp {
    color: var(--bs-secondary-color);
}

.feedback-modal__body {
    padding-bottom: 0.25rem;
}

.modal-body,
.modal-footer {
    background: linear-gradient(180deg, var(--bs-body-bg) 0%, var(--bs-tertiary-bg) 100%);
}

.feedback-modal__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}

.feedback-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.42rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.feedback-chip--category {
    background: rgba(3, 41, 133, 0.12);
    color: #032985;
}

.feedback-chip--anonymous {
    background: rgba(224, 123, 27, 0.16);
    color: #c26a0a;
}

.feedback-chip--named {
    background: rgba(20, 184, 166, 0.14);
    color: #0f766e;
}

.feedback-panel {
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 1rem;
    background: var(--bs-body-bg);
    padding: 1rem 1.05rem;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.feedback-panel__label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--bs-secondary-color);
    margin-bottom: 0.45rem;
    font-weight: 700;
}

.feedback-panel__value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-emphasis-color);
}

.feedback-panel__value--uppercase {
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.feedback-panel__identity {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.feedback-panel__subvalue {
    margin-top: 0.35rem;
    color: var(--bs-secondary-color);
    word-break: break-word;
}

.feedback-message {
    white-space: pre-wrap;
    line-height: 1.7;
    color: var(--bs-emphasis-color);
    min-height: 5rem;
}

.attachment-block {
    display: flex;
    flex-direction: column;
}

.attachment-block__summary {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    flex-wrap: wrap;
}

.attachment-block__icon {
    width: 2.9rem;
    height: 2.9rem;
    border-radius: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(3, 41, 133, 0.1);
    color: #032985;
    font-size: 1.1rem;
}

.attachment-block__meta {
    flex: 1 1 260px;
    min-width: 0;
}

.attachment-block__name {
    font-weight: 700;
    color: var(--bs-emphasis-color);
    word-break: break-word;
}

.attachment-block__submeta {
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    word-break: break-word;
}

.attachment-block__actions {
    margin-left: auto;
}

.attachment-block__note,
.attachment-empty {
    border: 1px dashed rgba(148, 163, 184, 0.4);
    border-radius: 0.85rem;
    padding: 0.9rem 1rem;
    color: var(--bs-secondary-color);
    background: var(--bs-body-bg);
}

.attachment-preview img {
    max-height: 240px;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    display: block;
    margin: 0 auto;
    background: #fff;
}

.attachment-frame {
    width: 100%;
    min-height: 420px;
    background: #fff;
}

[data-bs-theme="dark"] .feedback-modal {
    background: #1f242b;
}

[data-bs-theme="dark"] .feedback-modal__header {
    background: transparent;
}

[data-bs-theme="dark"] .modal-body,
[data-bs-theme="dark"] .modal-footer {
    background: linear-gradient(180deg, #232930 0%, #1f242b 100%);
}

[data-bs-theme="dark"] .feedback-panel {
    background: #262d35;
    border-color: rgba(148, 163, 184, 0.18);
    box-shadow: none;
}

[data-bs-theme="dark"] .attachment-block__note,
[data-bs-theme="dark"] .attachment-empty {
    background: #262d35;
}

@media (max-width: 767px) {
    .attachment-block__actions {
        width: 100%;
        margin-left: 0;
    }

    .attachment-block__actions .btn {
        width: 100%;
    }
}
</style>
