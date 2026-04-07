<template>
    <teleport to="body">
        <transition name="fade">
            <div
                v-if="isOpen"
                class="message-action-modal-backdrop"
                @click.self="handleClose"
            >
                <div
                    class="message-action-modal seen-by-modal"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="message-action-modal__header">
                        <div class="message-action-modal__headline">
                            <div
                                class="message-action-modal__badge message-action-modal__badge--edit"
                            >
                                <i class="fa-regular fa-eye"></i>
                            </div>
                            <div class="message-action-modal__eyebrow">
                                Seen by
                            </div>
                            <h3 class="message-action-modal__title">
                                {{ users.length }} {{ users.length === 1 ? "member" : "members" }}
                            </h3>
                            <p class="message-action-modal__subtitle">
                                People who have seen this message.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="message-action-modal__close"
                            @click="handleClose"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="message-action-modal__body seen-by-modal__body">
                        <div v-if="users.length" class="seen-by-modal__list">
                            <div
                                v-for="member in users"
                                :key="`seen-modal-${member.id}`"
                                class="seen-by-modal__item"
                            >
                                <img
                                    class="seen-by-modal__avatar"
                                    :src="getMemberProfile(member)"
                                    :alt="getMemberName(member)"
                                    @error="handleImageError($event, member)"
                                />
                                <div class="seen-by-modal__content">
                                    <div class="seen-by-modal__headline">
                                        <span>{{ getMemberName(member) }}</span>
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
                                            member.name &&
                                            member.nickname !== member.name
                                        "
                                        class="seen-by-modal__meta"
                                    >
                                        {{ member.name }}
                                    </small>
                                    <div
                                        v-if="member.last_read_at"
                                        class="seen-by-modal__date"
                                    >
                                        Seen {{ formatSeenDateTime(member.last_read_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-white-50">
                            No members have seen this message yet.
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script>
export default {
    name: "SeenByModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        users: {
            type: Array,
            default: () => [],
        },
        currentUserId: {
            type: [String, Number],
            required: true,
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
        getMemberName(member) {
            return member.nickname || member.display_name || member.name || "User";
        },
        formatSeenDateTime(date) {
            if (!date) return "";

            try {
                const seenDate = new Date(date);
                const datePart = new Intl.DateTimeFormat("en-US", {
                    month: "short",
                    day: "numeric",
                    year: "numeric",
                }).format(seenDate);
                const timePart = new Intl.DateTimeFormat("en-US", {
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true,
                }).format(seenDate);

                return `${datePart} at ${timePart}`;
            } catch (error) {
                return "";
            }
        },
    },
};
</script>

<style lang="scss">
.seen-by-modal {
    width: min(92vw, 720px);
}

.seen-by-modal__body {
    padding-top: 18px;
}

.seen-by-modal__list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.seen-by-modal__item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
}

.seen-by-modal__avatar {
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.18);
}

.seen-by-modal__content {
    min-width: 0;
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 3px;
}

.seen-by-modal__headline {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    color: #f5f8ff;
    font-weight: 700;
    line-height: 1.35;
}

.seen-by-modal__headline span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.seen-by-modal__meta {
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.83rem;
}

.seen-by-modal__date {
    color: #d7e4ff;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.45;
}

@media (max-width: 640px) {
    .seen-by-modal__item {
        align-items: flex-start;
        padding: 13px 14px;
    }

    .seen-by-modal__avatar {
        width: 48px;
        height: 48px;
        flex-basis: 48px;
        border-radius: 14px;
    }

    .seen-by-modal__headline {
        flex-wrap: wrap;
    }

    .seen-by-modal__headline span {
        white-space: normal;
    }
}
</style>
