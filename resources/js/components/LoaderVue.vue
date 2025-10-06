<template>
    <div 
        v-show="visible" 
        class="position-absolute top-0 end-0 p-3"
        style="z-index: 1050;"
    >
        <div class="d-flex align-items-center gap-2 bg-light shadow p-2 rounded">
            <div class="spinner-border text-primary spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="fw-semibold">{{ computedMessage }}</span>
        </div>
    </div>
</template>

<script>
export default {
    name: "LoadingOverlay",
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        status: {
            type: String,
            default: "" // e.g., 'uploading', 'saving', 'loading'
        },
        message: {
            type: String,
            default: ""
        }
    },
    computed: {
        computedMessage() {
            if (this.message) return this.message;

            switch (this.status) {
                case "uploading": return "Uploading, please wait...";
                case "saving": return "Saving, please wait...";
                case "loading": return "Loading, please wait...";
                default: return "Please wait...";
            }
        }
    }
}
</script>
