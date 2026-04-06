<template>
    <aside
        class="contacts-panel"
        :class="{
            'is-mobile-open': showMobileUsersPanel,
            'is-mobile-users-open': showMobileUsersPanel,
            'is-mobile-closing': mobileUsersPanelClosing,
        }"
    >
        <slot name="header" />
        <slot name="search" />
        <slot name="list" />
    </aside>
</template>

<script>
export default {
    name: 'MessagesSidebar',
    props: {
        showMobileUsersPanel: {
            type: Boolean,
            default: false,
        },
        mobileUsersPanelClosing: {
            type: Boolean,
            default: false,
        },
    },
};
</script>
