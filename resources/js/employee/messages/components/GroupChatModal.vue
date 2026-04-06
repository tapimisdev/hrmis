<template>
    <transition name="fade">
        <div
            v-if="isOpen"
            class="group-chat-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="group-chat-modal"
                role="dialog"
                aria-modal="true"
            >
                <div class="group-chat-modal__header">
                    <div class="group-chat-modal__headline">
                        <div
                            class="group-chat-modal__badge group-chat-modal__badge--edit"
                        >
                            <i class="fa-solid fa-people-group"></i>
                        </div>
                        <div class="group-chat-modal__eyebrow">
                            Create group chat
                        </div>
                        <h3 class="group-chat-modal__title">
                            Start a shared conversation
                        </h3>
                        <p class="group-chat-modal__subtitle">
                            {{
                                isAdmin
                                    ? "Admins can create a group chat immediately."
                                    : "Your request will be sent to admins for approval first."
                            }}
                        </p>
                        <p
                            v-if="!isAdmin"
                            class="group-chat-modal__limit-note"
                        >
                            {{
                                hasReachedPendingRequestLimit
                                    ? `You already have ${pendingRequestCount} pending requests. Cancel one to submit another.`
                                    : `${pendingRequestCount}/${requestLimit} pending requests used.`
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="group-chat-modal__close"
                        :disabled="isSubmitting"
                        @click="handleClose"
                        aria-label="Close dialog"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="group-chat-modal__body">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small"
                            >Group name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            class="group-chat-modal__input"
                            style="min-height: 52px"
                            maxlength="120"
                            placeholder="Enter group name"
                        />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small"
                            >Members</label
                        >
                        <div class="mb-3">
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="group-chat-modal__input"
                                placeholder="Search user to add"
                            />
                        </div>
                        <div class="group-chat-modal__member-list">
                            <label
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="group-chat-modal__member-item"
                            >
                                <input
                                    :checked="form.member_ids.includes(user.id)"
                                    type="checkbox"
                                    @change="toggleMember(user.id)"
                                />
                                <img :src="user.profile" :alt="user.name" />
                                <span>{{ user.name }}</span>
                            </label>
                        </div>
                        <div
                            v-if="filteredUsers.length === 0"
                            class="text-white-50 small mt-2"
                        >
                            No users match your search.
                        </div>
                    </div>

                    <p v-if="error" class="group-chat-modal__error">
                        {{ error }}
                    </p>
                </div>

                <div class="group-chat-modal__footer">
                    <button
                        type="button"
                        class="group-chat-modal__btn group-chat-modal__btn--ghost"
                        :disabled="isSubmitting"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="group-chat-modal__btn group-chat-modal__btn--primary"
                        :disabled="isSubmitting || !canSubmit"
                        @click="handleSubmit"
                    >
                        <span
                            v-if="isSubmitting"
                            class="spinner-border spinner-border-sm"
                            aria-hidden="true"
                        ></span>
                        <span v-else>Create</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "GroupChatModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        availableUsers: {
            type: Array,
            default: () => [],
        },
        isAdmin: {
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
        pendingRequestCount: {
            type: Number,
            default: 0,
        },
        requestLimit: {
            type: Number,
            default: 5,
        },
    },
    emits: ["close", "submit"],
    data() {
        return {
            form: {
                name: "",
                member_ids: [],
            },
            searchQuery: "",
        };
    },
    computed: {
        filteredUsers() {
            if (!this.searchQuery.trim()) {
                return this.availableUsers;
            }
            const query = this.searchQuery.toLowerCase();
            return this.availableUsers.filter((user) =>
                user.name.toLowerCase().includes(query)
            );
        },
        canSubmit() {
            return (
                this.form.name.trim() &&
                this.form.member_ids.length > 0 &&
                !this.hasReachedPendingRequestLimit
            );
        },
        hasReachedPendingRequestLimit() {
            return !this.isAdmin && this.pendingRequestCount >= this.requestLimit;
        },
    },
    watch: {
        isOpen(newVal) {
            if (!newVal) {
                this.resetForm();
            }
        },
    },
    methods: {
        handleClose() {
            this.$emit("close");
        },
        toggleMember(userId) {
            const index = this.form.member_ids.indexOf(userId);
            if (index > -1) {
                this.form.member_ids.splice(index, 1);
            } else {
                this.form.member_ids.push(userId);
            }
        },
        handleSubmit() {
            this.$emit("submit", this.form);
        },
        resetForm() {
            this.form = {
                name: "",
                member_ids: [],
            };
            this.searchQuery = "";
        },
    },
};
</script>

<style scoped lang="scss">
.group-chat-modal-backdrop {
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

.group-chat-modal {
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

.group-chat-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.group-chat-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-chat-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.group-chat-modal__badge--edit {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.group-chat-modal__eyebrow {
    margin-bottom: 6px;
    color: rgba(214, 222, 235, 0.62);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.68rem;
}

.group-chat-modal__title {
    margin: 0;
    color: #f3f6fb;
    font-size: 1.1rem;
    font-weight: 800;
}

.group-chat-modal__subtitle {
    margin: 0;
    color: rgba(214, 222, 235, 0.62);
    line-height: 1.55;
}

.group-chat-modal__limit-note {
    margin: 0;
    color: #8eb5ff;
    font-size: 0.82rem;
    line-height: 1.45;
}

.group-chat-modal__close {
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

.group-chat-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px 22px 18px;
    overflow-y: auto;
}

.group-chat-modal__input {
    width: 100%;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgb(56, 62, 72);
    color: #f3f6fb;
    padding: 14px 16px;
    outline: none;
    line-height: 1.6;
}

.group-chat-modal__input:focus {
    border-color: rgba(28, 88, 246, 0.55);
    box-shadow: 0 0 0 4px rgba(28, 88, 246, 0.14);
}

.group-chat-modal__member-list {
    display: grid;
    gap: 10px;
    padding-bottom: 4px;
}

.group-chat-modal__member-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgb(56, 62, 72);
    color: #f3f6fb;
    cursor: pointer;
}

.group-chat-modal__member-item:hover {
    background: rgb(67, 74, 85);
}

.group-chat-modal__member-item img {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
}

.group-chat-modal__error {
    margin: 14px 0 0;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.18);
    color: #ffd7dd;
    font-size: 0.92rem;
}

.group-chat-modal__footer {
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

.group-chat-modal__btn {
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

.group-chat-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.04);
    color: #f3f6fb;
}

.group-chat-modal__btn--primary {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
    color: #fff;
}

.group-chat-modal__close:disabled,
.group-chat-modal__btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
