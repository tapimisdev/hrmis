<template>
    <Transition name="slide-rtl">
        <div v-if="is_open" class="card-c overflow-hidden">
            <div class="bg-primary px-3 py-2 text-light">
                <slot name="header"></slot>
            </div>

            <div class="px-3 py-3 bg-body-secondary mh-100">
                <slot></slot>
                lorem200R
            </div>
        </div>
    </Transition>
</template>

<script>
export default {
    props: {
        is_open: {
            type: Boolean,
            required: true
        }
    }
}
</script>

<style scoped>
.card-c {
    border-radius: 2px !important;
    position: sticky;
    top: 80px;
}
/* ENTER */
.slide-rtl-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.slide-rtl-enter-active {
    transition: all 0.3s ease;
}
.slide-rtl-enter-to {
    transform: translateX(0);
    opacity: 1;
}

/* LEAVE */
.slide-rtl-leave-from {
    transform: translateX(0);
    opacity: 1;
}
.slide-rtl-leave-active {
    transition: all 0.25s ease;
}
.slide-rtl-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>
