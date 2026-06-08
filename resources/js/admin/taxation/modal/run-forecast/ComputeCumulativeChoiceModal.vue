<template>
    <ModalVue
        ref="modal"
        headerIcon="fa-solid fa-circle-question"
        title="Compute Cumulative Tax"
        id="compute-cumulative-choice-modal"
        size="modal-md"
        subtitle="Choose how you want to proceed."
    >
        <div class="modal-body">
            <div class="notes-box">
                <div class="notes-title">Notes</div>
                <div class="notes-item">
                    <span class="fw-semibold">Compute Cumulative and Override</span>
                    updates the assumptions and inputs before computing.
                </div>
                <div class="notes-item">
                    <span class="fw-semibold">Compute Cumulative with Same Configuration</span>
                    uses the currently saved setup as-is.
                </div>
            </div>

            <div class="actions-stack">
                <button
                    type="button"
                    class="fb-btn fb-primary"
                    :disabled="isSubmitting"
                    @click="selectMode('override')"
                >
                    Compute Cumulative and Override
                </button>

                <button
                    type="button"
                    class="fb-btn fb-secondary"
                    :disabled="isSubmitting"
                    @click="selectMode('same_configuration')"
                >
                    <span
                        v-if="isSubmitting && activeAction === 'same_configuration'"
                        class="spinner-border spinner-border-sm me-2"
                    ></span>
                    {{
                        isSubmitting && activeAction === "same_configuration"
                            ? "Computing Cumulative..."
                            : "Compute Cumulative with Same Configuration"
                    }}
                </button>
            </div>
        </div>
    </ModalVue>
</template>

<script>
import ModalVue from "../../../../components/ModalVue.vue";

export default {
    name: "ComputeCumulativeChoiceModal",
    components: { ModalVue },
    props: {
        isSubmitting: {
            type: Boolean,
            default: false,
        },
        activeAction: {
            type: String,
            default: "",
        },
    },
    emits: ["select"],
    methods: {
        open() {
            this.$refs.modal?.open?.();
        },
        close() {
            this.$refs.modal?.close?.();
        },
        selectMode(mode) {
            if (this.isSubmitting) return;

            this.$emit("select", mode);
        },
    },
};
</script>

<style scoped lang="scss">
.notes-box {
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.035);
    padding: 16px 18px;
}

.notes-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--bs-body-color);
    margin-bottom: 10px;
}

.notes-item {
    font-size: 14px;
    line-height: 1.55;
    color: var(--bs-secondary-color);

    & + .notes-item {
        margin-top: 10px;
    }
}

.actions-stack {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--bs-border-color);
}
</style>
