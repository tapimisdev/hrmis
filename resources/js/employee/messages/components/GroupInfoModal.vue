<template>
    <transition name="fade" @after-enter="restoreViewState">
        <div
            v-if="isOpen"
            class="group-info-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="group-info-modal"
                role="dialog"
                aria-modal="true"
            >
                <div class="group-info-modal__header">
                    <div class="group-info-modal__headline">
                        <div
                            class="group-info-modal__badge group-info-modal__badge--edit"
                        >
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div class="group-info-modal__eyebrow">
                            {{ isGroup ? "Group info" : "Conversation info" }}
                        </div>
                        <h3 class="group-info-modal__title">
                            Edit {{ title }}
                        </h3>
                        <p class="group-info-modal__subtitle">
                            {{
                                isGroup
                                    ? "Update the group name, photo, your nickname, and browse shared media."
                                    : "Set a nickname for this conversation and browse shared media."
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="group-info-modal__close"
                        :disabled="isSubmitting"
                        @click="handleClose"
                        aria-label="Close dialog"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div
                    class="group-info-modal__body"
                    ref="bodyScroll"
                    @scroll.passive="handleScroll"
                >
                    <div class="group-info-modal__editor">
                        <div class="group-info-modal__avatar">
                            <img :src="photoPreview || avatar" :alt="title" />
                            <button
                                v-if="isGroup"
                                type="button"
                                class="group-info-modal__btn group-info-modal__btn--ghost"
                                :disabled="isSubmitting"
                                @click="$refs.photoInput?.click()"
                            >
                                Change photo
                            </button>
                            <input
                                v-if="isGroup"
                                ref="photoInput"
                                type="file"
                                class="d-none"
                                accept="image/*"
                                @change="handlePhotoChange"
                            />
                        </div>

                        <div v-if="isGroup" class="mb-3">
                            <label class="form-label text-white-50 small"
                                >Group name</label
                            >
                            <input
                                v-model="form.name"
                                type="text"
                                class="group-info-modal__input"
                                maxlength="120"
                                placeholder="Enter group name"
                            />
                        </div>

                        <div v-else class="mb-3">
                            <label class="form-label text-white-50 small"
                                >Name</label
                            >
                            <div class="group-info-modal__preview">
                                {{ displayName }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-white-50 small">
                                {{ isGroup ? "Your nickname" : "Nickname" }}
                            </label>
                            <input
                                v-model="form.nickname"
                                type="text"
                                class="group-info-modal__input"
                                maxlength="120"
                                :placeholder="
                                    isGroup
                                        ? 'Set your nickname in this group'
                                        : 'Set a nickname for this conversation'
                                "
                            />
                        </div>
                    </div>

                    <div class="group-info-modal__media">
                        <div class="group-info-modal__media-header">
                            <div>
                                <div class="group-info-modal__context-label">
                                    Shared media
                                </div>
                                <div class="text-white-50 small">
                                    Images and files from this conversation.
                                </div>
                            </div>
                            <div
                                v-if="mediaLoading && mediaItems.length"
                                class="text-white-50 small"
                            >
                                Loading...
                            </div>
                        </div>

                        <div class="group-info-modal__tabs">
                            <button
                                type="button"
                                class="group-info-modal__tab"
                                :class="{
                                    'is-active': activeTab === 'media',
                                }"
                                @click="activeTab = 'media'"
                            >
                                Media
                            </button>
                            <button
                                type="button"
                                class="group-info-modal__tab"
                                :class="{
                                    'is-active': activeTab === 'files',
                                }"
                                @click="activeTab = 'files'"
                            >
                                Files
                            </button>
                        </div>

                        <div
                            v-if="
                                activeTab === 'media' && imageItems.length
                            "
                            class="group-info-modal__media-section"
                        >
                            <div class="group-info-modal__media-grid">
                                <button
                                    v-for="item in imageItems"
                                    :key="`image-${item.message_id}`"
                                    type="button"
                                    class="group-info-modal__media-image"
                                    @click="handleOpenGallery(item.attachment)"
                                >
                                    <img
                                        :src="item.attachment.url"
                                        :alt="item.attachment.name || 'Image'"
                                    />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="activeTab === 'files' && fileItems.length"
                            class="group-info-modal__media-section"
                        >
                            <button
                                v-for="item in fileItems"
                                :key="`file-${item.message_id}`"
                                type="button"
                                class="group-info-modal__media-file"
                                @click="$emit('download', item.attachment)"
                            >
                                <span class="group-info-modal__media-file-icon">
                                    <i class="fa-regular fa-file-lines"></i>
                                </span>
                                <span class="group-info-modal__media-file-body">
                                    <span class="group-info-modal__media-file-name"
                                        >{{ item.attachment.name }}</span
                                    >
                                    <small class="text-white-50">
                                        {{ formatFileSize(item.attachment.size) }}
                                        · {{ formatTime(item.created_at) }}
                                    </small>
                                </span>
                            </button>
                        </div>

                        <div
                            v-if="
                                mediaLoaded &&
                                !filteredItems.length &&
                                !mediaLoading
                            "
                            class="text-white-50 small"
                        >
                            {{
                                activeTab === "files"
                                    ? "No shared files yet."
                                    : "No shared media yet."
                            }}
                        </div>

                        <div
                            v-if="
                                mediaLoading && !mediaItems.length
                            "
                            class="chat-loading chat-loading--inline"
                        >
                            <span class="loader-dot"></span>
                            <div class="fw-semibold">
                                Loading shared media...
                            </div>
                        </div>

                        <div
                            v-if="
                                mediaLoaded &&
                                hasMore &&
                                !mediaLoading
                            "
                            class="group-info-modal__media-hint"
                        >
                            Scroll to load more
                        </div>
                    </div>

                    <p v-if="error" class="group-info-modal__error">
                        {{ error }}
                    </p>
                </div>

                <div class="group-info-modal__footer">
                    <button
                        type="button"
                        class="group-info-modal__btn group-info-modal__btn--ghost"
                        :disabled="isSubmitting"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="group-info-modal__btn group-info-modal__btn--primary"
                        :disabled="isSubmitting || !canSubmit"
                        @click="handleSubmit"
                    >
                        <span
                            v-if="isSubmitting"
                            class="spinner-border spinner-border-sm"
                            aria-hidden="true"
                        ></span>
                        <span v-else>Save changes</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "GroupInfoModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        title: {
            type: String,
            required: true,
        },
        avatar: {
            type: String,
            required: true,
        },
        displayName: {
            type: String,
            default: "",
        },
        initialForm: {
            type: Object,
            default: () => ({ name: "", nickname: "" }),
        },
        isGroup: {
            type: Boolean,
            default: true,
        },
        mediaItems: {
            type: Array,
            default: () => [],
        },
        mediaLoading: {
            type: Boolean,
            default: false,
        },
        mediaLoaded: {
            type: Boolean,
            default: false,
        },
        hasMore: {
            type: Boolean,
            default: false,
        },
        isSubmitting: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String,
            default: "",
        },
        initialActiveTab: {
            type: String,
            default: "media",
        },
        restoreScrollTop: {
            type: Number,
            default: 0,
        },
    },
    emits: ["close", "submit", "scroll", "photo-change", "open-gallery", "download"],
    data() {
        return {
            form: {
                name: "",
                nickname: "",
            },
            photoPreview: "",
            activeTab: this.initialActiveTab || "media",
        };
    },
    computed: {
        imageItems() {
            return this.mediaItems.filter((item) =>
                item.attachment?.url?.match(/\.(jpg|jpeg|png|gif|webp)$/i)
            );
        },
        fileItems() {
            return this.mediaItems.filter(
                (item) =>
                    !item.attachment?.url?.match(/\.(jpg|jpeg|png|gif|webp)$/i)
            );
        },
        filteredItems() {
            return this.activeTab === "media" ? this.imageItems : this.fileItems;
        },
        canSubmit() {
            return (
                this.form.name?.trim() || 
                this.form.nickname?.trim() ||
                this.photoPreview
            );
        },
    },
    watch: {
        initialForm(newVal) {
            if (newVal) {
                this.form = { ...newVal };
            }
        },
        isOpen(newVal) {
            if (newVal) {
                this.activeTab = this.initialActiveTab || "media";
                return;
            }

            if (!newVal) {
                this.photoPreview = "";
                if (this.$refs.photoInput) {
                    this.$refs.photoInput.value = "";
                }
            }
        },
        initialActiveTab(newVal) {
            if (this.isOpen && newVal) {
                this.activeTab = newVal;
            }
        },
    },
    methods: {
        handleClose() {
            this.$emit("close");
        },
        handleOpenGallery(attachment) {
            this.$emit("open-gallery", {
                attachment,
                scrollTop: this.$refs.bodyScroll?.scrollTop || 0,
                activeTab: this.activeTab,
            });
        },
        handlePhotoChange(event) {
            const file = event.target.files?.[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
                this.$emit("photo-change", file);
            }
        },
        handleScroll() {
            if (this.$refs.bodyScroll) {
                const { scrollTop, scrollHeight, clientHeight } =
                    this.$refs.bodyScroll;
                if (
                    scrollHeight - scrollTop - clientHeight < 100 &&
                    this.hasMore &&
                    !this.mediaLoading
                ) {
                    this.$emit("scroll");
                }
            }
        },
        handleSubmit() {
            this.$emit("submit", this.form);
        },
        restoreViewState() {
            this.activeTab = this.initialActiveTab || "media";

            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    const body = this.$refs.bodyScroll;
                    if (body) {
                        body.scrollTop = Math.max(
                            0,
                            Number(this.restoreScrollTop || 0),
                        );
                    }
                });
            });
        },
        formatFileSize(bytes) {
            if (!bytes) return "0 B";
            const k = 1024;
            const sizes = ["B", "KB", "MB", "GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
        },
        formatTime(date) {
            if (!date) return "";
            const d = new Date(date);
            return d.toLocaleDateString();
        },
    },
};
</script>

<style scoped lang="scss">
.group-info-modal-backdrop {
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

.group-info-modal {
    width: min(92vw, 620px);
    max-height: min(88vh, 760px);
    display: flex;
    flex-direction: column;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(
            circle at top right,
            rgba(28, 88, 246, 0.12),
            transparent 26%
        ),
        linear-gradient(180deg, rgba(49, 55, 63, 0.98), rgba(37, 42, 49, 0.99));
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.32);
    overflow: hidden;
}

.group-info-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.group-info-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-info-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.group-info-modal__badge--edit {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.group-info-modal__eyebrow,
.group-info-modal__context-label {
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-size: 0.72rem;
    color: rgba(214, 222, 235, 0.62);
}

.group-info-modal__title {
    margin: 0;
    color: #f3f6fb;
    font-size: 1.1rem;
    font-weight: 800;
}

.group-info-modal__subtitle {
    margin: 0;
    color: rgba(214, 222, 235, 0.62);
    line-height: 1.55;
}

.group-info-modal__close {
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

.group-info-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px 22px 18px;
    overflow-y: auto;
}

.group-info-modal__editor {
    display: grid;
    gap: 16px;
}

.group-info-modal__avatar {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.group-info-modal__avatar img {
    width: 96px;
    height: 96px;
    border-radius: 24px;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22);
}

.group-info-modal__input,
.group-info-modal__preview,
.group-info-modal__media-file {
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgb(56, 62, 72);
    color: #f3f6fb;
}

.group-info-modal__input {
    width: 100%;
    height: 54px;
    border-radius: 18px;
    padding: 14px 16px;
    outline: none;
}

.group-info-modal__input:focus {
    border-color: rgba(28, 88, 246, 0.55);
    box-shadow: 0 0 0 4px rgba(28, 88, 246, 0.14);
}

.group-info-modal__preview {
    padding: 14px 16px;
    border-radius: 16px;
    line-height: 1.55;
}

.group-info-modal__media {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: grid;
    gap: 16px;
}

.group-info-modal__media-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.group-info-modal__tabs {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.group-info-modal__tab {
    min-width: 84px;
    height: 38px;
    padding: 0 16px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.7);
    font-weight: 700;
}

.group-info-modal__tab.is-active {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
    color: #fff;
}

.group-info-modal__media-section {
    display: grid;
    gap: 12px;
}

.group-info-modal__media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
}

.group-info-modal__media-image {
    border: 0;
    padding: 0;
    border-radius: 16px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.06);
    aspect-ratio: 1 / 1;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
}

.group-info-modal__media-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.group-info-modal__media-file {
    width: 100%;
    border-radius: 16px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
}

.group-info-modal__media-file-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(95, 139, 255, 0.16);
    color: #aecdff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
}

.group-info-modal__media-file-body {
    min-width: 0;
    display: grid;
    gap: 2px;
}

.group-info-modal__media-file-name {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 700;
}

.group-info-modal__media-hint {
    text-align: center;
    color: rgba(214, 222, 235, 0.62);
    font-size: 0.82rem;
}

.group-info-modal__error {
    margin: 14px 0 0;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.18);
    color: #ffd7dd;
    font-size: 0.92rem;
}

.group-info-modal__footer {
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

.group-info-modal__btn {
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

.group-info-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.04);
    color: #f3f6fb;
}

.group-info-modal__btn--primary {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
    color: #fff;
}

.group-info-modal__close:disabled,
.group-info-modal__btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
