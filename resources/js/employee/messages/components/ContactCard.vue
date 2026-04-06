<template>
    <div
        class="contact-card"
        :class="{
            'is-active': isActive,
        }"
        role="button"
        tabindex="0"
        @click="$emit('select', user)"
        @keydown.enter.prevent="$emit('select', user)"
        @keydown.space.prevent="$emit('select', user)"
    >
        <span class="contact-card__avatar">
            <img :src="user.profile" alt="profile" />
            <span
                class="contact-card__status-dot"
                :class="{
                    'contact-card__status-dot--active': isOnline,
                    'contact-card__status-dot--inactive': !isOnline,
                }"
            ></span>
        </span>

        <span class="contact-card__body">
            <span class="contact-card__name-row">
                <span class="contact-card__name">{{ user.name }}</span>
                <span v-if="user.unread_count > 0" class="unread-pill contact-card__name-unread">
                    {{ formatUnreadCount(user.unread_count) }}
                </span>
            </span>
            <span class="contact-card__preview">
                {{ user.preview || getConversationStatusLabel(user) }}
            </span>
            <span class="contact-card__status">
                <i
                    v-if="user.conversation_type === 'group'"
                    class="fa-solid fa-people-group me-1"
                ></i>
                {{ getConversationStatusLabel(user) }}
            </span>
        </span>

        <span class="contact-card__meta">
            <span class="contact-card__time">
                {{ formatConversationTimestamp(user.latest_at) }}
            </span>
            <span class="contact-card__actions">
                <button
                    type="button"
                    class="contact-card__more"
                    :aria-expanded="showMenu"
                    aria-label="More conversation actions"
                    @click.stop="toggleMenu"
                >
                    <i class="fa-solid fa-ellipsis"></i>
                </button>
                <transition name="fade">
                    <div v-if="showMenu" class="contact-card__menu" @click.stop>
                        <button
                            type="button"
                            class="contact-card__menu-item contact-card__menu-item--danger"
                            @click.stop="$emit('delete', user)"
                        >
                            <i class="fa-regular fa-trash-can"></i>
                            <span>Delete messages</span>
                        </button>
                    </div>
                </transition>
            </span>
        </span>
    </div>
</template>

<script>
export default {
    name: "ContactCard",
    props: {
        user: {
            type: Object,
            required: true,
        },
        isActive: {
            type: Boolean,
            default: false,
        },
        isOnline: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["select", "delete"],
    data() {
        return {
            showMenu: false,
        };
    },
    methods: {
        toggleMenu() {
            this.showMenu = !this.showMenu;
            this.$emit("menu-toggle", { user: this.user, isOpen: this.showMenu });
        },
        formatUnreadCount(value) {
            const count = Number(value || 0);
            if (count <= 0) {
                return "";
            }
            return count >= 10 ? "10+" : String(count);
        },
        getConversationStatusLabel(user) {
            if (!user) {
                return "Offline";
            }
            if (user.conversation_type === "group") {
                return user.active_label || `${user.member_count || 0} members`;
            }
            return user.active_label || "Offline";
        },
        formatConversationTimestamp(value) {
            if (!value) {
                return "";
            }
            try {
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return "";
                }
                const now = new Date();
                const isSameDay = date.toDateString() === now.toDateString();
                if (isSameDay) {
                    return new Intl.DateTimeFormat("en-US", {
                        hour: "numeric",
                        minute: "2-digit",
                    }).format(date);
                }
                const yesterday = new Date(now);
                yesterday.setDate(now.getDate() - 1);
                if (date.toDateString() === yesterday.toDateString()) {
                    return "Yesterday";
                }
                return new Intl.DateTimeFormat("en-US", {
                    month: "short",
                    day: "numeric",
                }).format(date);
            } catch (error) {
                return "";
            }
        },
    },
};
</script>
