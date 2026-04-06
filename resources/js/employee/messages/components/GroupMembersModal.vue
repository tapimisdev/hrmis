<template>
    <transition name="fade">
        <div
            v-if="isOpen"
            class="group-members-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="group-members-modal"
                role="dialog"
                aria-modal="true"
            >
                <div class="group-members-modal__header">
                    <div class="group-members-modal__headline">
                        <div
                            class="group-members-modal__badge group-members-modal__badge--edit"
                        >
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="group-members-modal__eyebrow">
                            Group members
                        </div>
                        <h3 class="group-members-modal__title">
                            {{ groupName }}
                        </h3>
                        <p class="group-members-modal__subtitle">
                            {{ members.length }} members in this chat.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="group-members-modal__close"
                        @click="handleClose"
                        aria-label="Close dialog"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="group-members-modal__body">
                    <div v-if="members.length" class="group-members-modal__list">
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="group-members-modal__item"
                        >
                            <img
                                :src="getMemberProfile(member)"
                                :alt="member.display_name || member.name"
                                @error="handleImageError($event, member)"
                            />
                            <div class="group-members-modal__content">
                                <div class="group-members-modal__item-headline">
                                    <span>{{
                                        member.display_name || member.name
                                    }}</span>
                                    <small
                                        v-if="
                                            Number(member.id) ===
                                            Number(currentUserId)
                                        "
                                        class="text-white-50"
                                        >(You)</small
                                    >
                                </div>
                                <small
                                    v-if="
                                        member.nickname &&
                                        member.nickname !== member.name
                                    "
                                    class="text-white-50"
                                >
                                    {{ member.name }}
                                </small>
                                <small
                                    v-if="Number(member.id) === Number(ownerId)"
                                    class="text-white-50"
                                >
                                    Owner
                                </small>
                                <small
                                    v-else-if="member.added_by_name"
                                    class="text-white-50"
                                    >Added by {{ member.added_by_name }}</small
                                >
                            </div>
                            <div
                                v-if="member.joined_at"
                                class="group-members-modal__date"
                            >
                                Added {{ formatDate(member.joined_at) }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-white-50">
                        No members found for this group.
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "GroupMembersModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        members: {
            type: Array,
            default: () => [],
        },
        groupName: {
            type: String,
            required: true,
        },
        currentUserId: {
            type: [String, Number],
            required: true,
        },
        ownerId: {
            type: [String, Number],
            default: null,
        },
    },
    emits: ["close"],
    methods: {
        handleClose() {
            this.$emit("close");
        },
        getMemberProfile(member) {
            return member.profile || member.avatar || "";
        },
        handleImageError(event, member) {
            const fallback = member.avatar || "";

            if (!fallback || event.target.src === fallback) {
                return;
            }

            event.target.onerror = null;
            event.target.src = fallback;
        },
        formatDate(date) {
            if (!date) return "";
            const now = new Date();
            const memberDate = new Date(date);
            const diffInSeconds = Math.floor((now - memberDate) / 1000);

            if (diffInSeconds < 60) return "just now";
            if (diffInSeconds < 3600)
                return `${Math.floor(diffInSeconds / 60)}m ago`;
            if (diffInSeconds < 86400)
                return `${Math.floor(diffInSeconds / 3600)}h ago`;
            if (diffInSeconds < 604800)
                return `${Math.floor(diffInSeconds / 86400)}d ago`;

            return memberDate.toLocaleDateString();
        },
    },
};
</script>

<style scoped lang="scss">
.group-members-modal-backdrop {
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

.group-members-modal {
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

.group-members-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.group-members-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-members-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.group-members-modal__badge--edit {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.group-members-modal__eyebrow {
    margin-bottom: 6px;
    color: rgba(214, 222, 235, 0.62);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.68rem;
}

.group-members-modal__title {
    margin: 0;
    color: #f3f6fb;
    font-size: 1.1rem;
    font-weight: 800;
}

.group-members-modal__subtitle {
    margin: 0;
    color: rgba(214, 222, 235, 0.62);
    line-height: 1.55;
}

.group-members-modal__close {
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

.group-members-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px 22px 18px;
    overflow-y: auto;
}

.group-members-modal__list {
    display: grid;
    gap: 10px;
    padding-bottom: 4px;
}

.group-members-modal__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    position: relative;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgb(56, 62, 72);
    color: #f3f6fb;
}

.group-members-modal__item img {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
}

.group-members-modal__content {
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding-right: 110px;
}

.group-members-modal__item-headline {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.group-members-modal__date {
    position: absolute;
    right: 12px;
    bottom: 10px;
    font-size: 0.72rem;
    color: rgba(214, 222, 235, 0.62);
    text-align: right;
}
</style>
