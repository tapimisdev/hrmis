<template>
    <ModalVue
        ref="modal"
        headerIcon="fa-solid fa-circle-check"
        title="Apply Tax Computation"
        id="apply-tax-computation-modal"
        size="modal-xl"
        subtitle="This will apply the current taxation setup to Payroll."
    >
        <div class="modal-body">
            <div class="notes-box">
                <div class="notes-title">Notes</div>
                <div class="notes-item">
                    This applies the selected taxation setup for <span class="fw-semibold">{{ monthRangeLabel }}</span>.
                </div>
                <div class="notes-item">
                    Existing Payroll records for covered months will <span class="fw-semibold text-danger">not be overridden</span>.
                </div>
                <div class="notes-item">
                    If an employee already has an existing payroll record for a month, that month will be <span class="fw-semibold">skipped</span>.
                </div>
            </div>

            <div class="preview-box mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="preview-title">Month Preview</div>
                    <div v-if="previewMeta.employee_count" class="small text-muted">
                        {{ previewMeta.employee_count }} employee(s)
                    </div>
                </div>

                <div v-if="isLoadingPreview" class="small text-muted py-3">
                    Loading payroll month preview...
                </div>

                <div v-else-if="previewError" class="text-danger small py-2">
                    {{ previewError }}
                </div>

                <div v-else-if="previewRows.length" class="table-responsive">
                    <table class="table table-sm align-middle preview-table mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Salary</th>
                                <th>Hazard Pay</th>
                                <th>Longevity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in previewRows" :key="row.month">
                                <td class="fw-semibold month-cell">{{ row.label }}</td>
                                <td
                                    v-for="component in row.components"
                                    :key="`${row.month}-${component.label}`"
                                    class="component-cell"
                                >
                                    <div
                                        class="preview-status"
                                        :class="statusClass(component.status_type)"
                                    >
                                        {{ component.status }}
                                    </div>
                                    <div class="preview-metric">
                                        <span class="metric-label">Existing</span>
                                        <span class="metric-value">{{ component.existing_count }} / {{ component.employee_count }}</span>
                                    </div>
                                    <div class="preview-metric">
                                        <span class="metric-label">To apply</span>
                                        <span class="metric-value">{{ component.apply_count }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="small text-muted py-3">
                    No month preview available.
                </div>
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
import axios from "axios";
import ModalVue from "../../../components/ModalVue.vue";

export default {
    name: "ApplyTaxComputationModal",
    components: { ModalVue },
    props: {
        isSubmitting: {
            type: Boolean,
            default: false,
        },
        taxationId: {
            type: [Number, String],
            default: null,
        },
        type: {
            type: String,
            default: "forecast",
        },
    },
    emits: ["confirm"],
    data() {
        return {
            isLoadingPreview: false,
            previewError: "",
            previewRows: [],
            previewMeta: {},
        };
    },
    computed: {
        monthRangeLabel() {
            return {
                forecast: "January to December",
                q2: "April to December",
                q3: "July to December",
                q4: "October to December",
                nov: "December only",
                final: "January to December",
            }[this.type] || "January to December";
        },
    },
    methods: {
        async open() {
            this.previewError = "";
            this.previewRows = [];
            this.previewMeta = {};
            this.$refs.modal?.open?.();
            await this.fetchPreview();
        },
        close() {
            this.$refs.modal?.close?.();
        },
        async fetchPreview() {
            if (!this.taxationId || !this.type) return;

            this.isLoadingPreview = true;
            this.previewError = "";

            try {
                const response = await axios.post(
                    "/admin/taxation/apply-to-payroll-preview",
                    {
                        taxation_id: this.taxationId,
                        type: this.type,
                    },
                );

                this.previewRows = response?.data?.data?.months || [];
                this.previewMeta = response?.data?.data || {};
            } catch (error) {
                this.previewError =
                    error?.response?.data?.message ||
                    "Failed to load payroll month preview.";
            } finally {
                this.isLoadingPreview = false;
            }
        },
        submit() {
            if (this.isSubmitting) return;

            this.$emit("confirm");
        },
        statusClass(type) {
            return {
                "preview-status-apply": type === "apply",
                "preview-status-partial": type === "partial",
                "preview-status-skip": type === "skip",
            };
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

.preview-box {
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.02);
    padding: 16px 18px;
}

.preview-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--bs-body-color);
}

.preview-table th {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--bs-secondary-color);
    border-bottom-color: var(--bs-border-color);
    background: rgba(255, 255, 255, 0.03);
    padding: 12px 10px;
}

.preview-table td {
    border-bottom-color: var(--bs-border-color);
    vertical-align: top;
    padding: 12px 10px;
}

.month-cell {
    min-width: 120px;
    color: var(--bs-body-color);
}

.component-cell {
    min-width: 185px;
}

.preview-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
}

.preview-status-apply {
    background: rgba(25, 135, 84, 0.12);
    color: #7dd3a8;
}

.preview-status-partial {
    background: rgba(255, 193, 7, 0.12);
    color: #f5cf66;
}

.preview-status-skip {
    background: rgba(220, 53, 69, 0.12);
    color: #ff9aa5;
}

.preview-metric {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
    line-height: 1.5;
}

.preview-metric + .preview-metric {
    margin-top: 2px;
}

.metric-label {
    color: var(--bs-secondary-color);
}

.metric-value {
    color: var(--bs-body-color);
    font-weight: 600;
}
</style>
