<template>
    <div>
        <!-- HEADER ACTION BAR -->
        <div class="d-flex justify-content-between align-items-center border-bottom mb-3 pb-3">
            <div class="fw-semibold">Employee Tax Forecast</div>
        </div>

        <!-- FILTER BAR -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <!-- Search -->
            <div class="input-group input-group-sm" style="max-width: 320px">
                <span class="input-group-text">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>

                <input :value="search" @input="$emit('update:search', $event.target.value)" type="text"
                    class="form-control" placeholder="Search employee no..." />

                <button class="btn btn-outline-secondary" type="button" @click="$emit('clear')"
                    :disabled="!hasActiveFilters" title="Clear">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Division Filter -->
            <select class="form-select form-select-sm" style="max-width: 300px;" :value="selectedDivision"
                @change="$emit('update:selectedDivision', $event.target.value)">
                <option value="">All Divisions</option>
                <option v-for="d in divisions" :key="d" :value="d">
                    {{ d }}
                </option>
            </select>

            <!-- Unit Filter -->
            <select class="form-select form-select-sm" style="max-width: 300px;" :value="selectedUnit"
                @change="$emit('update:selectedUnit', $event.target.value)" :disabled="units.length === 0">
                <option value="">
                    {{ units.length ? "All Units" : "No units" }}
                </option>
                <option v-for="u in units" :key="u" :value="u">
                    {{ u }}
                </option>
            </select>

            <div class="d-flex justify-content-end align-items-center gap-2 ms-auto">
                <button
                    class="fb-btn bg-success"
                    @click="confirmApplyToPayroll"
                    :disabled="isApplying"
                >
                    <i class="fa-solid fa-check me-1"></i>
                    {{ isApplying ? "Applying..." : "Apply to Payroll" }}
                </button>
            </div>
            
        </div>
    </div>
</template>

<script>
export default {
    name: "TaxForecastFilters",
    props: {
        search: { type: String, default: "" },
        selectedDivision: { type: String, default: "" },
        selectedUnit: { type: String, default: "" },

        divisions: { type: Array, default: () => [] },
        units: { type: Array, default: () => [] },

        filteredCount: { type: Number, default: 0 },
        totalCount: { type: Number, default: 0 },

        hasActiveFilters: { type: Boolean, default: false },
        isApplying: { type: Boolean, default: false },
    },
    methods: {
        async confirmApplyToPayroll() {
            if (this.isApplying) return;

            const result = await Swal.fire({
                title: "Apply Forecast to Payroll?",
                html: `
                    <div class="text-start">
                        <div>This will update the Payroll tax tables used for employee tax computation.</div>
                        <div class="mt-2">This action will apply the selected taxation setup from <b class="text-primary">January to December</b>.</div>
                        <div class="mt-2">If Payroll tax data already exists for those months, it will be <b class="text-danger">overridden</b> by this action.</div>
                        <div class="mt-2">To continue, type <b>Apply</b> below.</div>
                    </div>
                `,
                icon: "warning",
                input: "text",
                inputPlaceholder: "Type Apply to confirm",
                inputAttributes: { autocapitalize: "off" },
                showCancelButton: true,
                confirmButtonText: "Apply to Payroll",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#198754",
                reverseButtons: true,
                preConfirm: (value) => {
                    if (String(value || "").trim() !== "Apply") {
                        Swal.showValidationMessage(
                            'Confirmation text must be "Apply".',
                        );
                        return false;
                    }

                    return true;
                },
            });

            if (!result.isConfirmed) return;

            this.$emit("apply-to-tax");
        },
    },
};
</script>
