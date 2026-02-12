<template>
    <div class="border rounded p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">Tab C — Monthly Tax Portion Allocation</div>

            <div class="d-flex gap-2">
                <button
                    type="button"
                    class="fb-btn fb-secondary"
                    @click="resetDefault"
                >
                    Reset (65/20/15)
                </button>
                <button
                    type="button"
                    class="fb-btn fb-primary"
                    @click="autoBalance"
                >
                    Auto-balance to 100%
                </button>
            </div>
        </div>

        <div class="text-muted small mb-3">
            Assign how the computed <b>monthly tax</b> is portioned across
            earnings types.
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Hazard Pay (%)</label>
                <div class="input-group input-group-sm">
                    <input
                        type="number"
                        class="form-control"
                        min="0"
                        max="100"
                        step="1"
                        :value="local.hazardPayPct"
                        @input="update('hazardPayPct', $event.target.value)"
                    />
                    <span class="input-group-text">%</span>
                </div>
                <small v-if="errors?.hazardPayPct" class="text-danger">{{
                    errors.hazardPayPct[0]
                }}</small>
            </div>

            <div class="col-md-4">
                <label class="form-label">Basic Pay (%)</label>
                <div class="input-group input-group-sm">
                    <input
                        type="number"
                        class="form-control"
                        min="0"
                        max="100"
                        step="1"
                        :value="local.basicPayPct"
                        @input="update('basicPayPct', $event.target.value)"
                    />
                    <span class="input-group-text">%</span>
                </div>
                <small v-if="errors?.basicPayPct" class="text-danger">{{
                    errors.basicPayPct[0]
                }}</small>
            </div>

            <div class="col-md-4">
                <label class="form-label">Longevity (%)</label>
                <div class="input-group input-group-sm">
                    <input
                        type="number"
                        class="form-control"
                        min="0"
                        max="100"
                        step="1"
                        :value="local.longevityPct"
                        @input="update('longevityPct', $event.target.value)"
                    />
                    <span class="input-group-text">%</span>
                </div>
                <small v-if="errors?.longevityPct" class="text-danger">{{
                    errors.longevityPct[0]
                }}</small>
            </div>
        </div>

        <hr class="my-3" />

        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="small text-muted">Total</span>
                <div class="fw-bold" :class="totalClass">
                    {{ total }}%
                    <span v-if="total !== 100" class="small fw-normal ms-2">
                        (must be 100%)
                    </span>
                </div>
            </div>

            <div class="text-end">
                <small v-if="errors?.allocation" class="text-danger d-block">{{
                    errors.allocation[0]
                }}</small>
                <small v-if="total !== 100" class="text-muted d-block">
                    Tip: click <b>Auto-balance</b> to fix total.
                </small>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabCAllocation",
    props: {
        modelValue: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
    },
    emits: ["update:modelValue"],

    computed: {
        local() {
            return {
                hazardPayPct: Number(this.modelValue?.hazardPayPct ?? 0),
                basicPayPct: Number(this.modelValue?.basicPayPct ?? 0),
                longevityPct: Number(this.modelValue?.longevityPct ?? 0),
            };
        },
        total() {
            return (
                Number(this.local.hazardPayPct || 0) +
                Number(this.local.basicPayPct || 0) +
                Number(this.local.longevityPct || 0)
            );
        },
        totalClass() {
            if (this.total === 100) return "text-success";
            if (this.total > 100) return "text-danger";
            return "text-warning";
        },
    },

    methods: {
        update(key, val) {
            const n = Math.max(0, Math.min(100, Number(val || 0)));
            this.$emit("update:modelValue", { ...this.modelValue, [key]: n });
        },

        resetDefault() {
            this.$emit("update:modelValue", {
                ...this.modelValue,
                hazardPayPct: 65,
                basicPayPct: 20,
                longevityPct: 15,
            });
        },

        autoBalance() {
            // Keep Hazard + Basic as-is, adjust Longevity to reach 100
            const hazard = Number(this.local.hazardPayPct || 0);
            const basic = Number(this.local.basicPayPct || 0);
            let longevity = 100 - (hazard + basic);
            longevity = Math.max(0, Math.min(100, longevity));

            this.$emit("update:modelValue", {
                ...this.modelValue,
                longevityPct: longevity,
            });
        },
    },
};
</script>
