<template>
    <ModalVue
        ref="modal"
        headerIcon="fa-solid fa-triangle-exclamation"
        title="Delete Taxation"
        id="delete-taxation-modal"
        size="modal-md"
        subtitle="This action cannot be undone."
    >
        <div class="modal-body">
            <div class="small text-muted mb-3">
                To confirm deletion, type <span class="fw-bold text-danger">DELETE</span> below.
            </div>

            <label for="delete-taxation-confirmation" class="form-label fw-semibold">
                Confirmation
            </label>
            <input
                id="delete-taxation-confirmation"
                v-model="confirmationText"
                type="text"
                class="form-control"
                placeholder="Type DELETE to confirm"
                :disabled="isSubmitting"
                @input="error = ''"
                @keydown.enter.prevent="submit"
            />

            <div v-if="error" class="text-danger small mt-2">
                {{ error }}
            </div>

            <div class="modal-footer d-flex justify-content-end gap-2 mt-3">
                <button
                    type="button"
                    class="fb-btn bg-secondary"
                    :disabled="isSubmitting"
                    @click="close"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="fb-btn bg-danger"
                    :disabled="isSubmitting"
                    @click="submit"
                >
                    <span
                        v-if="isSubmitting"
                        class="spinner-border spinner-border-sm me-2"
                    ></span>
                    Delete
                </button>
            </div>
        </div>
    </ModalVue>
</template>

<script>
import ModalVue from "../../../components/ModalVue.vue";

export default {
    name: "DeleteTaxationModal",
    components: { ModalVue },
    props: {
        isSubmitting: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["confirm"],
    data() {
        return {
            confirmationText: "",
            error: "",
        };
    },
    methods: {
        open() {
            this.confirmationText = "";
            this.error = "";
            this.$refs.modal?.open?.();
        },
        close() {
            this.$refs.modal?.close?.();
        },
        submit() {
            if (this.isSubmitting) return;

            if (String(this.confirmationText || "").trim().toUpperCase() !== "DELETE") {
                this.error = 'Confirmation text must be "DELETE".';
                return;
            }

            this.$emit("confirm");
        },
    },
};
</script>
