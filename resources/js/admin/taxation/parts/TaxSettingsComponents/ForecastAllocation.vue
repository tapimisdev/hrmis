<template>
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fa-solid fa-chart-pie text-muted"></i>
            <span class="fw-semibold">Allocation</span>
        </div>

        <div class="card-body">
            <!-- EMPTY STATE -->
            <div
                v-if="!hasAllocation"
                class="d-flex flex-column align-items-center justify-content-center text-center py-4 text-muted"
            >
                <i class="fa-solid fa-sliders fs-3 mb-2 opacity-50"></i>
                <div class="fw-semibold small">No Allocation Configured</div>
                <div class="small">
                    Set allocation percentages to view the breakdown.
                </div>
            </div>

            <!-- HAS DATA -->
            <template v-else>
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="allocBasicPay"
                        checked
                        disabled
                    />
                    <label class="form-check-label small" for="allocBasicPay">
                        Basic Pay
                        <span class="text-muted"
                            >— {{ local.basicPayPct ?? 0 }}%</span
                        >
                    </label>
                </div>

                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="allocHazardPay"
                        checked
                        disabled
                    />
                    <label class="form-check-label small" for="allocHazardPay">
                        Hazard Pay
                        <span class="text-muted"
                            >— {{ local.hazardPayPct ?? 0 }}%</span
                        >
                    </label>
                </div>

                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="allocLongevityPay"
                        checked
                        disabled
                    />
                    <label
                        class="form-check-label small"
                        for="allocLongevityPay"
                    >
                        Longevity Pay
                        <span class="text-muted"
                            >— {{ local.longevityPct ?? 0 }}%</span
                        >
                    </label>
                </div>

                <div class="small text-muted mt-3">
                    Total:
                    <strong
                        :class="total === 100 ? 'text-success' : 'text-danger'"
                    >
                        {{ total }}%
                    </strong>
                    <span v-if="total !== 100" class="text-danger ms-1">
                        (should be 100%)
                    </span>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: "ForecastAllocation",
    props: {
        allocation: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            local: this.allocation ? { ...this.allocation } : {},
        };
    },

    computed: {
        hasAllocation() {
            const a = this.allocation || {};
            const total =
                Number(a.basicPayPct || 0) +
                Number(a.hazardPayPct || 0) +
                Number(a.longevityPct || 0);

            return total > 0;
        },

        total() {
            return (
                Number(this.local.basicPayPct || 0) +
                Number(this.local.hazardPayPct || 0) +
                Number(this.local.longevityPct || 0)
            );
        },
    },

    watch: {
        allocation: {
            deep: true,
            handler(val) {
                this.local = val ? { ...val } : {};
            },
        },
    },
};
</script>
