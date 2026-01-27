<template>
    <div
        class="modal fade"
        id="changePassModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-md">
            <div class="modal-content shadow-lg border-0 rounded-4">
                
                <button
                    type="button"
                    class="btn-close position-absolute"
                    style="right: 16px; top: 24px; z-index: 99999;"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

                <div class="modal-body p-0">
                    <change-password title="Change Password"></change-password>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ChangePassword from "./ChangePassword.vue";

export default {
    components: { ChangePassword },
    methods: {
        open() {
            $("#changePassModal").modal("show");
        },
    },
};
</script>

<style scoped>
.badge {
    font-size: 0.85rem;
}
.table {
    font-size: 0.9rem;
}
</style>
