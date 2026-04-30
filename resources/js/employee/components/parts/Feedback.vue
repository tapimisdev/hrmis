<template>
    <div>
        <button
            type="button"
            class="feedback-trigger text-decoration-none d-inline-flex align-items-center justify-content-center"
            title="Feedback"
            @click="openModal"
        >
            <i class="fa-regular fa-comment-dots text-light"></i>
        </button>

        <div
            class="modal fade"
            id="employeeFeedbackModal"
            tabindex="-1"
            aria-labelledby="employeeFeedbackModalLabel"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div>
                            <h5
                                id="employeeFeedbackModalLabel"
                                class="modal-title fw-semibold"
                            >
                                Feedback Form
                            </h5>
                            <p class="text-muted small mb-0">
                                Share bugs, ideas, or anything we can improve.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Close"
                            :disabled="submitting"
                            @click="closeModal"
                        ></button>
                    </div>

                    <form
                        id="employeeFeedbackForm"
                        class="modal-body px-4 pb-4 pt-3"
                        @submit.prevent="submitFeedback"
                    >
                        <div class="row g-3">
                        <div class="col-12 mb-1">
                            <label for="feedbackCategory" class="form-label fw-semibold">
                                Category
                            </label>
                            <select
                                id="feedbackCategory"
                                v-model="form.category"
                                class="form-select"
                                :class="{ 'is-invalid': errors.category }"
                                :disabled="submitting"
                            >
                                <option value="">Select feedback type</option>
                                <option value="Bug Report">Bug Report</option>
                                <option value="Feature Request">Feature Request</option>
                                <option value="General Feedback">General Feedback</option>
                            </select>
                            <div v-if="errors.category" class="invalid-feedback">
                                {{ errors.category[0] }}
                            </div>
                        </div>

                        <div class="col-12 mb-1">
                            <label for="feedbackSubject" class="form-label fw-semibold">
                                Subject
                            </label>
                            <input
                                id="feedbackSubject"
                                v-model.trim="form.subject"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': errors.subject }"
                                placeholder="Short summary of your feedback"
                                :disabled="submitting"
                            />
                            <div v-if="errors.subject" class="invalid-feedback">
                                {{ errors.subject[0] }}
                            </div>
                        </div>

                        <div class="col-12 mb-1">
                            <label for="feedbackMessage" class="form-label fw-semibold">
                                Message
                            </label>
                            <textarea
                                id="feedbackMessage"
                                v-model.trim="form.message"
                                rows="5"
                                class="form-control"
                                :class="{ 'is-invalid': errors.message }"
                                placeholder="Tell us what happened, what you expected, or what would help."
                                :disabled="submitting"
                            ></textarea>
                            <div v-if="errors.message" class="invalid-feedback">
                                {{ errors.message[0] }}
                            </div>
                        </div>

                        <div class="col-12 mb-1">
                            <div
                                class="alert alert-info text-muted fw-bold text-uppercase mt-2 mb-3"
                                style="font-size: 10px"
                            >
                                Note:
                                <br />
                                <br />
                                Accepts only the following files (jpg, jpeg, png, doc, docx, xls, xlsx, pdf)
                                <br />
                                Maximum size of 10 MB
                            </div>
                            <label for="feedbackAttachment" class="form-label fw-semibold">
                                Attachment
                            </label>
                            <input
                                id="feedbackAttachment"
                                ref="attachmentInput"
                                type="file"
                                class="form-control"
                                :class="{ 'is-invalid': errors.attachment }"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                :disabled="submitting"
                                @change="handleAttachmentChange"
                            />
                            <div v-if="selectedAttachmentName" class="small text-muted mt-1">
                                Selected: {{ selectedAttachmentName }}
                            </div>
                            <div v-if="errors.attachment" class="invalid-feedback">
                                {{ errors.attachment[0] }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input
                                    id="feedbackAnonymous"
                                    v-model="form.is_anonymous"
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    :disabled="submitting"
                                />
                                <label class="form-check-label fw-semibold" for="feedbackAnonymous">
                                    Submit as anonymous
                                </label>
                            </div>
                            <div class="form-text ms-0 mt-2">
                                Your feedback will be stored, but this flag can be used to hide your identity in review flows.
                            </div>
                        </div>

                        </div>
                    </form>

                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button
                            type="button"
                            class="btn btn-outline-secondary px-4 py-2"
                            :disabled="submitting"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            form="employeeFeedbackForm"
                            class="btn btn-primary px-4 py-2"
                            :disabled="submitting"
                        >
                            <span
                                v-if="submitting"
                                class="spinner-border spinner-border-sm me-2"
                                role="status"
                                aria-hidden="true"
                            ></span>
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import * as bootstrap from "bootstrap";

export default {
    name: "FeedbackComponent",
    props: {
        userId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            modalInstance: null,
            submitting: false,
            errors: {},
            form: {
                category: "",
                subject: "",
                message: "",
                attachment: null,
                is_anonymous: false,
            },
        };
    },
    mounted() {
        const modalElement = document.getElementById("employeeFeedbackModal");

        if (modalElement) {
            this.modalInstance = new bootstrap.Modal(modalElement);
            modalElement.addEventListener("hidden.bs.modal", this.resetForm);
        }
    },
    beforeUnmount() {
        const modalElement = document.getElementById("employeeFeedbackModal");

        if (modalElement) {
            modalElement.removeEventListener("hidden.bs.modal", this.resetForm);
        }
    },
    methods: {
        openModal() {
            this.errors = {};
            this.modalInstance?.show();
        },
        closeModal() {
            this.modalInstance?.hide();
        },
        resetForm() {
            if (this.submitting) {
                return;
            }

            this.errors = {};
            this.form = {
                category: "",
                subject: "",
                message: "",
                attachment: null,
                is_anonymous: false,
            };
            if (this.$refs.attachmentInput) {
                this.$refs.attachmentInput.value = "";
            }
        },
        handleAttachmentChange(event) {
            this.form.attachment = event.target.files?.[0] || null;
        },
        async submitFeedback() {
            const confirmation = await window.Swal?.fire({
                title: "Submit feedback?",
                text: "Are you sure you want to send this feedback?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, submit",
                cancelButtonText: "Cancel",
                reverseButtons: true,
            });

            if (confirmation && !confirmation.isConfirmed) {
                return;
            }

            this.submitting = true;
            this.errors = {};

            try {
                const token = localStorage.getItem("auth_token");
                const payload = new FormData();

                payload.append("category", this.form.category);
                payload.append("subject", this.form.subject);
                payload.append("message", this.form.message);
                payload.append(
                    "is_anonymous",
                    this.form.is_anonymous ? "1" : "0",
                );

                if (this.form.attachment) {
                    payload.append("attachment", this.form.attachment);
                }

                await axios.post("/api/employee/feedback", payload, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "multipart/form-data",
                    },
                });

                this.submitting = false;
                this.resetForm();
                this.closeModal();

                if (window.Swal) {
                    window.Swal.fire({
                        icon: "success",
                        title: "Feedback submitted",
                        text: "Thank you for helping us improve the employee portal.",
                        confirmButtonText: "OK",
                    });
                }
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                    return;
                }

                if (window.Swal) {
                    window.Swal.fire({
                        icon: "error",
                        title: "Submission failed",
                        text: "Please try again in a moment.",
                    });
                }
            } finally {
                this.submitting = false;
            }
        },
    },
    computed: {
        selectedAttachmentName() {
            return this.form.attachment?.name || "";
        },
    },
};
</script>

<style scoped>
.feedback-trigger {
    width: 1.75rem;
    height: 1.75rem;
    background: transparent;
    border: 0;
    padding: 0;
    cursor: pointer;
}

.feedback-trigger i {
    font-size: 1.45rem;
}
</style>
