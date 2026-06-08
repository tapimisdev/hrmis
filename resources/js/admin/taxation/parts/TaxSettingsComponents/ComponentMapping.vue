<template>
    <div class="card shadow-sm h-100">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fa-solid fa-diagram-project text-muted"></i>
            <span class="fw-semibold">Component Mapping</span>
        </div>
        <div class="card-body">
            <!-- HAS DATA -->
            <div v-if="items && items.length" class="row g-3">
                <div v-for="(item, idx) in items" :key="idx" class="col-md-4">
                    <div class="d-flex align-items-start gap-2 h-100">
                        <i
                            class="fa-solid fa-check mt-1"
                            :class="iconClass(item, item.type)"
                        ></i>

                        <div class="small">
                            <div class="fw-semibold">{{ item.label }}</div>
                            <div class="text-muted">{{ item.note }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div
                v-else
                class="d-flex flex-column align-items-center justify-content-center text-center py-4 text-muted"
            >
                <i class="fa-solid fa-box-open fs-3 mb-2 opacity-50"></i>
                <div class="fw-semibold small">No Component Mapping Yet</div>
                <div class="small">
                    Once mappings are configured, they will appear here.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ComponentMapping",
    props: {
        items: { type: Array, default: () => [] },
    },
    methods: {
        iconClass(item, type) {
            if (!item.ok) return "text-muted";

            // If allowable group → warning
            if (type === "allowables") {
                return "text-warning";
            }

            // Default success (earnings, others, etc.)
            return "text-success";
        },
    },
};
</script>
