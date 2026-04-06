<template>
    <transition name="fade">
        <div
            v-if="isOpen"
            class="invite-members-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="invite-members-modal"
                role="dialog"
                aria-modal="true"
            >
                <div class="invite-members-modal__header">
                    <div class="invite-members-modal__headline">
                        <div
                            class="invite-members-modal__badge invite-members-modal__badge--edit"
                        >
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <div class="invite-members-modal__eyebrow">
                            Invite members
                        </div>
                        <h3 class="invite-members-modal__title">
                            Add users to {{ groupName }}
                        </h3>
                        <p class="invite-members-modal__subtitle">
                            Invite more people into this group conversation.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="invite-members-modal__close"
                        :disabled="isSubmitting"
                        @click="handleClose"
                        aria-label="Close dialog"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="invite-members-modal__body">
                    <div class="mb-3">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="invite-members-modal__input"
                            placeholder="Search user to invite"
                        />
                    </div>
                    <div class="invite-members-modal__member-list">
                        <label
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="invite-members-modal__member-item"
                        >
                            <input
                                :checked="selectedUserIds.includes(user.id)"
                                type="checkbox"
                                @change="toggleUser(user.id)"
                            />
                            <img :src="user.profile" :alt="user.name" />
                            <span>{{ user.name }}</span>
                        </label>
                    </div>
                    <div
                        v-if="filteredUsers.length === 0"
                        class="text-white-50 small mt-2"
                    >
                        No more users available to invite.
                    </div>
                    <p v-if="error" class="invite-members-modal__error">
                        {{ error }}
                    </p>
                </div>

                <div class="invite-members-modal__footer">
                    <button
                        type="button"
                        class="invite-members-modal__btn invite-members-modal__btn--ghost"
                        :disabled="isSubmitting"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="invite-members-modal__btn invite-members-modal__btn--primary"
                        :disabled="isSubmitting || selectedUserIds.length === 0"
                        @click="handleSubmit"
                    >
                        <span
                            v-if="isSubmitting"
                            class="spinner-border spinner-border-sm"
                            aria-hidden="true"
                        ></span>
                        <span v-else>Invite</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "InviteMembersModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        groupName: {
            type: String,
            required: true,
        },
        availableUsers: {
            type: Array,
            default: () => [],
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
    emits: ["close", "submit"],
    data() {
        return {
            searchQuery: "",
            selectedUserIds: [],
        };
    },
    computed: {
        filteredUsers() {
            if (!this.searchQuery.trim()) {
                return this.availableUsers;
            }
            const query = this.searchQuery.toLowerCase();
            return this.availableUsers.filter((user) => {
                const haystack =
                    `${user.name || ""} ${user.email || ""}`.toLowerCase();
                return haystack.includes(query);
            });
        },
    },
    watch: {
        isOpen(newVal) {
            if (!newVal) {
                this.searchQuery = "";
                this.selectedUserIds = [];
            }
        },
    },
    methods: {
        handleClose() {
            this.$emit("close");
        },
        toggleUser(userId) {
            const index = this.selectedUserIds.indexOf(userId);
            if (index > -1) {
                this.selectedUserIds.splice(index, 1);
            } else {
                this.selectedUserIds.push(userId);
            }
        },
        handleSubmit() {
            this.$emit("submit", this.selectedUserIds);
        },
    },
};
</script>

<style scoped lang="scss">
.invite-members-modal-backdrop {
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

.invite-members-modal {
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

.invite-members-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.invite-members-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.invite-members-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.invite-members-modal__badge--edit {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.invite-members-modal__eyebrow {
    margin-bottom: 6px;
    color: rgba(214, 222, 235, 0.62);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.68rem;
}

.invite-members-modal__title {
    margin: 0;
    color: #f3f6fb;
    font-size: 1.1rem;
    font-weight: 800;
}

.invite-members-modal__subtitle {
    margin: 0;
    color: rgba(214, 222, 235, 0.62);
    line-height: 1.55;
}

.invite-members-modal__close {
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

.invite-members-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px 22px 18px;
    overflow-y: auto;
}

.invite-members-modal__input {
    width: 100%;
    height: 54px;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgb(56, 62, 72);
    color: #f3f6fb;
    padding: 14px 16px;
    outline: none;
}

.invite-members-modal__input:focus {
    border-color: rgba(28, 88, 246, 0.55);
    box-shadow: 0 0 0 4px rgba(28, 88, 246, 0.14);
}

.invite-members-modal__member-list {
    display: grid;
    gap: 10px;
    padding-bottom: 4px;
}

.invite-members-modal__member-item {
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

.invite-members-modal__member-item:hover {
    background: rgb(67, 74, 85);
}

.invite-members-modal__member-item img {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
}

.invite-members-modal__error {
    margin: 14px 0 0;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.18);
    color: #ffd7dd;
    font-size: 0.92rem;
}

.invite-members-modal__footer {
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

.invite-members-modal__btn {
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

.invite-members-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.04);
    color: #f3f6fb;
}

.invite-members-modal__btn--primary {
    background: linear-gradient(135deg, #1c58f6, #1748c5);
    color: #fff;
}

.invite-members-modal__close:disabled,
.invite-members-modal__btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
