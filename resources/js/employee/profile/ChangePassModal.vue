<template>
    <div
        class="modal fade"
        id="chanePAssModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content modern-modal">
                <!-- Header -->
                <div class="modal-header modern-header border-bottom">
                    <div class="header-content mb-3 d-flex align-items-center">
                        <div class="icon-wrapper me-2">
                            <i class="text-light fas fa-clock"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">Change Password</h5>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <ChangePassword :title:="'change Password'" />
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
            $("#chanePAssModal").modal("show");
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
