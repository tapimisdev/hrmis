<template>
    <!-- PAGINATION -->
    <div v-if="totalPages > 1" class="own-pagination">
        <div class="small text-muted">
            Showing {{ startItem }}–{{ endItem }} of {{ rows.length }}
        </div>

        <div class="fb-pagination">
            <button
                class="fb-page-btn"
                :disabled="currentPage === 1"
                @click="goToPage(currentPage - 1)"
            >
                ‹ Prev
            </button>

            <button
                v-for="p in visiblePages"
                :key="p"
                class="fb-page-btn"
                :class="{ active: p === currentPage }"
                @click="goToPage(p)"
            >
                {{ p }}
            </button>

            <button
                class="fb-page-btn"
                :disabled="currentPage === totalPages"
                @click="goToPage(currentPage + 1)"
            >
                Next ›
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "OwnPagination",

    props: {
        totalPages: { type: Number, required: true },
        startItem: { type: Number, required: true },
        endItem: { type: Number, required: true },
        rows: { type: Array, required: true },
        currentPage: { type: Number, required: true },
        visiblePages: { type: Array, required: true },
        goToPage: { type: Function, required: true },
    },
}
</script>

<style lang="scss" scoped>


/* =========================================
   PAGINATION
========================================= */
.own-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border-top: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;

    .btn-group {
        .btn {
            // optional hover refinement
        }
    }
}

/* =========================================
   RESPONSIVE
========================================= */
@media (max-width: 576px) {
    .own-pagination {
        flex-direction: column;
        align-items: stretch;

        .btn-group {
            width: 100%;

            .btn {
                flex: 1;
            }
        }
    }
}

/* =========================================
   OLD FACEBOOK STYLE PAGINATION
========================================= */
.fb-pagination {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 12px;

    .fb-page-btn {
        background: var(--bs-secondary-bg);
        border: 1px solid var(--bs-border-color);
        padding: 3px 8px;
        min-width: 28px;
        text-align: center;
        cursor: pointer;
        font-weight: 500;
        line-height: 1.2;
        transition:
            background 0.15s ease,
            border-color 0.15s ease,
            color 0.15s ease;

        &:hover:not(:disabled) {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-secondary-border-subtle);
        }

        &.active {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: var(--bs-white);
            font-weight: 600;
        }

        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            color: var(--bs-secondary-color);
        }
    }
}
</style>