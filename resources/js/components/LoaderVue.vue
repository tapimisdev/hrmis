<template>
    <div 
        v-show="visible" 
        :class="hasBackground ? 'has-bg' : 'position-absolute top-0 end-0 p-3'"
        style="z-index: 800;"
    >
        <div class="loader d-flex align-items-center gap-2 bg-light shadow p-2 rounded">
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
        hasBackground: {
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

<style lang="scss" scoped>
@import '../../sass/variables';
.has-bg {
  position: absolute;
  border-radius: 16px;
  background-color: rgba($dark, 0.2);
  height: 100%;
  width: 100%;
  display: flex;
  .loader {
    position: absolute;
    top: 12px;
    right: 12px;
  }
}
</style>
