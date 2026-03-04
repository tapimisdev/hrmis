<template>
    <!-- Tabs Nav -->
    <ul class="nav nav-pills small" role="tablist">
        <li
            v-for="(tab, i) in tabs"
            :key="tab.id"
            class="nav-item"
            role="presentation"
        >
            <button
                class="nav-link"
                :class="{ active: i === activeIndex }"
                data-bs-toggle="tab"
                :data-bs-target="'#' + tab.id"
                type="button"
                role="tab"
                :aria-controls="tab.id"
                :aria-selected="i === activeIndex ? 'true' : 'false'"
            >
                {{ tab.name }}
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content p-2">
        <div
            v-for="(tab, i) in tabs"
            :key="tab.id + '-pane'"
            class="tab-pane fade"
            :class="{ show: i === activeIndex, active: i === activeIndex }"
            :id="tab.id"
            role="tabpanel"
            :aria-labelledby="tab.id + '-tab'"
        >
            <!-- render component -->
            <component :is="tab.component" v-bind="tab.props || {}" />
        </div>
    </div>
</template>

<script>
export default {
    name: "DynamicTabs",
    props: {
        tabs: {
            type: Array,
            required: true,
        },
        activeIndex: {
            type: Number,
            default: 0,
        },
    },
};
</script>
