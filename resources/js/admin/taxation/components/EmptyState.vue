<template>
    <!-- EMPTY STATE -->
    <div class="card text-center py-5 mt-3 mb-3 empty-state">
        <div class="mb-3">
            <i class="fa-solid fa-folder-open fa-2x text-muted"></i>
        </div>

        <h6 class="fw-semibold mb-2">
            <!-- {{
                hasActiveFilters
                    ? "No matching records found"
                    : "No forecast data available"
            }} -->
        </h6>

        <p class="text-muted small mb-3">
            <!-- {{
                hasActiveFilters
                    ? "Try adjusting your search or filters."
                    : "Pull data from payroll to generate forecast records."
            }} -->
        </p>
    </div>
</template>
