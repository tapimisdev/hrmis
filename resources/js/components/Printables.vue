<template>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <button
            type="button"
            class="btn btn-sm btn-outline-primary px-3 py-2 fw-500 d-flex align-items-center gap-2"
            title="Download CSV"
        >
            <i class="fa-solid fa-file-csv"></i>
            <span class="small">CSV</span>
        </button>
        <button
            type="button"
            class="btn btn-sm btn-outline-success px-3 py-2 fw-500 d-flex align-items-center gap-2"
            title="Download Excel"
        >
            <i class="fa-solid fa-file-excel"></i>
            <span class="small">Excel</span>
        </button>
        <button
            type="button"
            class="btn btn-sm btn-outline-danger px-3 py-2 fw-500 d-flex align-items-center gap-2"
            title="Download PDF"
        >
            <i class="fa-solid fa-file-pdf"></i>
            <span class="small">PDF</span>
        </button>
    </div>
</template>

<script>
export default {
    data() {
        return {};
    },
};
</script>

<style lang="scss" scoped></style>
