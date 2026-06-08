<template>
    <div class="card-simple px-3 mb-2 old-social-card">
        <div class="d-flex justify-content-between align-items-end gap-3">
            <slot name="headline-left">
                <div>
                    <div class="small text-muted">Result</div>
                    <div class="fs-4 fw-bold old-money" :class="resultClass">
                        {{ resultValue }}
                    </div>
                    <div class="small text-muted">
                        {{ safeText(normalizedComputation.formula) }}
                    </div>
                </div>
            </slot>

            <div class="text-end">
                <slot name="headline-right">
                    <template v-if="summaryLabel">
                        <div class="small text-muted">{{ summaryLabel }}</div>
                        <div class="fw-semibold fs-5 text-body">{{ summaryValue }}</div>
                    </template>

                    <div v-if="effectiveDate" class="small text-muted">
                        Effective Date: {{ effectiveDate }}
                    </div>
                </slot>
            </div>
        </div>

        <hr class="my-3 old-divider" />

        <div class="fw-semibold small mb-2 text-body-emphasis">
            {{ sectionTitle }}
        </div>

        <div class="accordion accordion-flush old-accordion" :id="accordionId">
            <slot :accordion-id="accordionId" :panel-id="panelId"></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "BreakdownViewTemplate",
    props: {
        computation: { type: Object, default: () => ({}) },
        resultValue: { type: [String, Number], default: "" },
        resultClass: { type: String, default: "text-info" },
        summaryLabel: { type: String, default: "" },
        summaryValue: { type: [String, Number], default: "" },
        effectiveDate: { type: String, default: "" },
        sectionTitle: { type: String, default: "Computation Overview" },
        badgeText: { type: String, default: "" },
        badgeClass: {
            type: String,
            default: "bg-info-subtle text-info border border-info-subtle",
        },
        badgeCaption: { type: String, default: "Computation" },
    },
    data() {
        return {
            _uid: `bd_${Math.random().toString(36).slice(2, 9)}`,
        };
    },
    computed: {
        normalizedComputation() {
            return this.computation || {};
        },
        accordionId() {
            return `acc_${this._uid}`;
        },
        badgeTextValue() {
            return (
                this.badgeText ||
                this.safeText(this.normalizedComputation.meta && this.normalizedComputation.meta.type) ||
                "DETAIL"
            );
        },
    },
    methods: {
        safeText(value) {
            if (value === null || value === undefined) return "";
            return String(value);
        },
        panelId(name) {
            const key = this.safeText(name)
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "_")
                .replace(/^_+|_+$/g, "");

            return `${key || "panel"}_${this._uid}`;
        },
    },
};
</script>

<style scoped>
.old-social-card {
    background: var(--bs-body-bg);
    box-shadow: none;
}

.old-divider {
    border-top: 1px solid var(--bs-border-color);
    opacity: 0.9;
}

:deep(.old-name) {
    font-size: 0.95rem;
    line-height: 1.1;
}

:deep(.old-sub) {
    line-height: 1.15;
}

:deep(.old-pill) {
    font-size: 0.72rem;
    letter-spacing: 0.02em;
    padding: 0.28rem 0.5rem;
    border-radius: 999px;
}

:deep(.old-money) {
    line-height: 1.05;
}

.old-accordion :deep(.accordion-item) {
    border: 0;
    border-top: 1px solid var(--bs-border-color);
    background: transparent;
}

.old-accordion :deep(.accordion-item:first-child) {
    border-top: 0;
}

.old-accordion :deep(.old-acc-btn) {
    background: transparent !important;
    box-shadow: none !important;
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
}

.old-accordion :deep(.accordion-button::after) {
    transform: scale(0.9);
    opacity: 0.75;
}

.old-accordion :deep(.accordion-button:not(.collapsed)) {
    color: var(--bs-body-color);
    background: transparent;
}

.old-accordion :deep(.old-mini) {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.25rem;
    padding: 0.6rem 0.7rem;
    background: var(--bs-secondary-bg);
}

.old-accordion :deep(.old-row) {
    padding: 0.25rem 0;
    border-bottom: 1px dashed var(--bs-border-color);
}

.old-accordion :deep(.old-row:last-child) {
    border-bottom: 0;
}

.old-accordion :deep(.old-row-head) {
    border-bottom-style: solid;
    opacity: 0.9;
}

.old-accordion :deep(.old-nested) {
    padding: 0.15rem 0 0.4rem;
}

.old-accordion :deep(.old-row-nested) {
    padding-left: 0.25rem;
    opacity: 0.95;
}

.old-accordion :deep(.old-total) {
    display: flex;
    justify-content: space-between;
    padding-top: 0.4rem;
    margin-top: 0.35rem;
    border-top: 1px solid var(--bs-border-color);
}

.old-accordion :deep(.old-tip) {
    padding: 0.35rem 0.5rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.25rem;
    background: var(--bs-body-bg);
}
</style>
