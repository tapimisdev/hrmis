<!-- components/SkeletonTable.vue -->
<template>
    <tbody>
        <tr v-for="row in rows" :key="row">
            <td v-for="col in columns" :key="col">
                <div class="skeleton"></div>
            </td>
        </tr>
    </tbody>
</template>

<script>
export default {
    name: "SkeletonTable",
    props: {
        rows: {
            type: Number,
            default: 5 // default to 5 placeholder rows
        },
        columns: {
            type: Number,
            required: true // you tell the skeleton how many columns to render
        }
    }
};
</script>

<style scoped>
.skeleton {
    height: 48px;
    width: 100%;
    border-radius: 4px;
    background: linear-gradient(
        90deg,
        #e0e0e0 25%,
        #f0f0f0 50%,
        #e0e0e0 75%
    );
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
