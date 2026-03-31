<template>
    <section
        class="conversation-panel"
        :class="{ 'is-mobile-hidden': showMobileUsersPanel }"
    >
        <slot name="header" />
        <slot name="pinned-banner" />
        <slot name="pinned-panel" />
        <slot name="body" />
        <slot name="reply" />
        <slot name="attachment" />
        <slot name="composer" />
        <slot name="floating-scroll" />
    </section>
</template>

<script>
export default {
    name: 'ConversationWorkspace',
    props: {
        showMobileUsersPanel: {
            type: Boolean,
            default: false,
        },
    },
};
</script>
