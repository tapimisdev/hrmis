<template>
    <ModalVue
        ref="modal"
        headerIcon="fa-solid fa-circle-check"
        title="Apply Tax Computation"
        id="apply-tax-computation-modal"
        size="modal-md"
        subtitle="This will apply the current taxation setup to Payroll."
    >
        <div class="modal-body">
            <div class="notes-box">
                <div class="notes-title">Notes</div>
                <div class="notes-item">
                    This applies the selected taxation setup from <span class="fw-semibold">January to December</span>.
                </div>
                <div class="notes-item">
                    Existing Payroll tax data for covered months may be <span class="fw-semibold text-danger">overridden</span>.
                </div>
            </div>

            <div class="small text-muted mt-3">
                To continue, type <span class="fw-bold text-success">APPLY</span> below.
            </div>

            <label for="apply-tax-confirmation" class="form-label fw-semibold mt-3">
                Confirmation
            </label>
            <input
                id="apply-tax-confirmation"
                v-model="confirmationText"
                type="text"
                class="form-control"
                placeholder="Type APPLY to confirm"
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
                    class="fb-btn fb-primary"
                    :disabled="isSubmitting"
                    @click="submit"
                >
                    <span
                        v-if="isSubmitting"
                        class="spinner-border spinner-border-sm me-2"
                    ></span>
                    Apply to Payroll
                </button>
            </div>
        </div>
    </ModalVue>
</template>

<script>
import ModalVue from "../../../components/ModalVue.vue";

export default {
    name: "ApplyTaxComputationModal",
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

            if (String(this.confirmationText || "").trim().toUpperCase() !== "APPLY") {
                this.error = 'Confirmation text must be "APPLY".';
                return;
            }

            this.$emit("confirm");
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
</style>
