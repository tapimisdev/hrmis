<template>
    <transition name="fade">
        <div
            v-if="show"
            class="pinned-modal-backdrop"
            @click.self="$emit('close')"
        >
            <div class="pinned-modal">
                <div class="pinned-modal__header">
                    <div>
                        <div class="pinned-modal__title">Pinned messages</div>
                        <small class="text-white-50">
                            {{ pinnedMessages.length }} pinned
                        </small>
                    </div>
                    <button
                        type="button"
                        class="pinned-modal__close"
                        @click="$emit('close')"
                        aria-label="Close pinned messages"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div v-if="pinnedMessages.length" class="pinned-modal__list">
                    <div
                        v-for="pin in pinnedMessages"
                        :key="pin.message_id"
                        class="pinned-modal__item"
                    >
                        <button
                            type="button"
                            class="pinned-modal__item-body"
                            @click="$emit('scroll-to', pin)"
                        >
                            <div class="pinned-modal__item-preview">
                                {{ pin.preview }}
                            </div>
                            <small class="pinned-modal__item-date">
                                Pinned {{ formatPinnedAt(pin.pinned_at || pin.created_at) }}
                            </small>
                        </button>
                        <button
                            type="button"
                            class="pinned-modal__item-unpin"
                            @click.stop="$emit('unpin', pin)"
                            title="Unpin message"
                            aria-label="Unpin message"
                        >
                            <i class="fa-solid fa-thumbtack-slash"></i>
                        </button>
                    </div>
                </div>
                <div v-else class="pinned-modal__empty">
                    <p>No pinned messages yet</p>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "PinnedMessagesPanel",
    props: {
        show: {
            type: Boolean,
            default: false,
        },
        pinnedMessages: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["close", "scroll-to", "unpin"],
    methods: {
        formatPinnedAt(timestamp) {
            if (!timestamp) return "just now";
            try {
                return new Intl.DateTimeFormat([], {
                    month: "short",
                    day: "numeric",
                    hour: "numeric",
                    minute: "2-digit",
                }).format(new Date(timestamp));
            } catch (error) {
                return "just now";
            }
        },
    },
};
</script>
