<template>
    <div
        class="modal fade"
        id="showDevices"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header p-3">
                    <h5 class="text-uppercase fw-bold text-center mb-0">Active Devices</h5>
                    <button
                        type="button"
                        class="btn-close position-absolute"
                        style="right: 16px; top: 24px; z-index: 99999;"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body p-3">
                    <div v-if="loading" class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div v-else-if="devices.length === 0" class="text-center text-muted">
                        No active devices found.
                    </div>
                    <div  v-else class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="device in devices" :key="device.id">
                                    <td>{{ device.ip }}</td>
                                    <td>{{ device.user_agent }}</td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            @click="confirmDelete(device)"
                                        >
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
        <div class="modal-content" @click.stop>
            <h5>Confirm Delete</h5>
            <p>Are you sure you want to delete this device? This will log it out.</p>
            <div class="mt-3 d-block w-100">
                <button class="btn btn-danger w-100 mb-2" @click="proceedDelete" :disabled="isDeleting">
                    <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ isDeleting ? 'Deleting...' : 'Delete' }}
                </button>
                <button class="btn btn-secondary w-100" @click="cancelDelete">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            devices: [],
            loading: false,
            showDeleteModal: false,
            deviceToDelete: null,
            isDeleting: false,
        };
    },
    methods: {
        async open() {
            $("#showDevices").modal("show");
            await this.fetchDevices();
        },
        async fetchDevices() {
            this.loading = true;
            const token = localStorage.getItem("auth_token");
            try {
                const response = await axios.get("/api/employee/devices", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.devices = response.data;
            } catch (error) {
                console.error("Error fetching devices:", error);
                // Optionally show an error message, e.g., this.$toast.error("Failed to load devices");
            } finally {
                this.loading = false;
            }
        },
        confirmDelete(device) {
            this.showDeleteModal = true;
            this.deviceToDelete = device;
        },
        async proceedDelete() {
            if (!this.deviceToDelete) return;
            this.isDeleting = true;
            const token = localStorage.getItem("auth_token");
            try {
                await axios.delete(`/api/employee/devices/${this.deviceToDelete.id}/delete`, {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.devices = this.devices.filter(d => d.id !== this.deviceToDelete.id);
                this.showDeleteModal = false;
                this.deviceToDelete = null;
                // Optionally show success message, e.g., this.$toast.success("Device logged out");
            } catch (error) {
                console.error("Error deleting device:", error);
                // Optionally show error message
            } finally {
                this.isDeleting = false;
            }
        },
        cancelDelete() {
            this.showDeleteModal = false;
            this.deviceToDelete = null;
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
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10001;
}
.modal-content {
    padding: 30px;
    border-radius: 8px;
    max-width: 380px;
    width: 100%;
}
</style>