<template>
    <div class="card common-info-card p-4 bg-white mb-2">
        <!-- Header -->
        <div class="mb-3">
            <h5 class="text-primary fw-bold mb-0">
                Common Information
            </h5>
        </div>

        <!-- Info Grid -->
        <div class="row g-3">
            <div
                v-for="(value, key) in details"
                :key="key"
                class="col-12 col-sm-6 col-lg-4"
            >
                <div class="info-box p-3 rounded-3 d-flex flex-column justify-content-center shadow-sm bg-gradient-hover">
                    <span class="label text-muted small mb-1 text-uppercase">
                        {{ formatLabel(key) }}
                    </span>
                    <span class="value fw-semibold text-dark fs-6">
                        {{ value }}
                    </span>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "CommonInfo",
    props: {
        details: {
            type: Object,
            required: true
        }
    },
    methods: {
        formatLabel(key) {
            const labels = {
                employment_type: "Employment",
                division: "Division",
                unit: "Unit",
                shift: "Shift",
                schedule: "Schedule"
            };
            return labels[key] || key.replace(/_/g, " ");
        }
    }
};
</script>

<style scoped>
.common-info-card {
    transition: all 0.3s ease-in-out;
}

.info-box {
    background-color: #f8f9fa;
    transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
    cursor: default;
}

.info-box:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
    background: linear-gradient(135deg, #ffffff, #e9f0ff);
}

.label {
    letter-spacing: 0.5px;
}

.value {
    word-break: break-word;
}
</style>
