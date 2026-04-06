<template>
    <transition name="fade">
        <div
            v-if="isOpen"
            class="message-action-modal-backdrop"
            @click.self="handleClose"
        >
            <div
                class="message-action-modal"
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
                            {{ users.length }} members
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

                <div class="message-action-modal__body">
                    <div v-if="users.length" class="group-chat-member-list">
                        <div
                            v-for="member in users"
                            :key="`seen-modal-${member.id}`"
                            class="group-chat-member-item group-chat-member-item--static"
                        >
                            <img
                                :src="getMemberProfile(member)"
                                :alt="getMemberName(member)"
                            />
                            <div class="group-chat-member-item__content">
                                <div
                                    class="group-chat-member-item__headline"
                                >
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
                                    class="text-white-50"
                                >
                                    {{ member.name }}
                                </small>
                            </div>
                            <div
                                v-if="member.last_read_at"
                                class="group-chat-member-item__date"
                            >
                                Seen
                                {{ formatDate(member.last_read_at) }}
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
        getMemberName(member) {
            return member.nickname || member.display_name || member.name || "User";
        },
        formatDate(date) {
            if (!date) return "";
            const now = new Date();
            const messageDate = new Date(date);
            const diffInSeconds =
                Math.floor((now - messageDate) / 1000);

            if (diffInSeconds < 60) return "just now";
            if (diffInSeconds < 3600)
                return `${Math.floor(diffInSeconds / 60)}m ago`;
            if (diffInSeconds < 86400)
                return `${Math.floor(diffInSeconds / 3600)}h ago`;
            if (diffInSeconds < 604800)
                return `${Math.floor(diffInSeconds / 86400)}d ago`;

            return messageDate.toLocaleDateString();
        },
    },
};
</script>

<style lang="scss">
// Uses styles from parent MessagesPage
</style>
