<template>
    <TaxTemplate :is_open="true">
        <template #header> No Employee Selected </template>

        <div
            class="empty-state text-center d-flex flex-column align-items-center justify-content-center p-4"
        >
            <div
                class="empty-icon d-flex align-items-center justify-content-center mb-3"
            >
                <i class="fa-regular fa-folder-open"></i>
            </div>

            <div class="empty-title mb-2">No record selected</div>

            <div class="empty-sub text-muted">
                Select an employee from the list to view tax forecast details.
            </div>
        </div>
    </TaxTemplate>
</template>

<script>
import TaxTemplate from './components/TaxTemplate.vue';
export default {
    name: 'EmptyState',
    components: { TaxTemplate }
}
</script>