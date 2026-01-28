<template>
    <!-- Show Devices Modal -->
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
                    <h5 class="text-uppercase fw-bold text-center mb-0">
                        Active Devices
                    </h5>
                    <button
                        type="button"
                        class="btn-close position-absolute"
                        style="right: 16px; top: 24px; z-index: 99999"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body p-3">
                    <!-- Loading Spinner -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading devices...</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table id="myTable" class="table table-striped w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">IP Address</th>
                                    <th class="text-nowrap">User Agent</th>
                                    <th class="text-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="device in devices"
                                    :key="device.id"
                                    :data-id="device.id"
                                >
                                    <td>{{ device.ip }}</td>
                                    <td>{{ device.user_agent }}</td>
                                    <td>
                                        <button
                                            class="btn btn-outline-danger d-flex align-items-center justify-content-center gap-2"
                                            @click="confirmDelete(device)"
                                        >
                                            <i
                                                class="fa-solid fa-arrow-right-from-bracket fa-rotate-180"
                                            ></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="devices.length === 0"
                                    class="text-center text-muted"
                                >
                                    <td colspan="3">
                                        No active devices found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal (Custom, like Notes) -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
        <div class="modal-overlay-content" @click.stop>
            <h5 class="fw-bold text-danger">Confirm Delete</h5>
            <p>
                Are you sure you want to delete this device? This will log it
                out.
            </p>
            <div class="d-flex flex-column gap-2 mt-3">
                <button
                    class="btn btn-danger w-100"
                    @click="proceedDelete"
                    :disabled="isDeleting"
                >
                    <span
                        v-if="isDeleting"
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                    ></span>
                    {{ isDeleting ? "Deleting..." : "Delete" }}
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
    props: {
        sessionId: { type: String, required: true },
    },
    data() {
        return {
            devices: [],
            loading: false,
            showDeleteModal: false,
            deviceToDelete: null,
            isDeleting: false,
            dataTable: null,
        };
    },
    methods: {
        async open() {
            this.loading = true;
            await this.fetchDevices();

            const modal = document.getElementById("showDevices");
            $(modal).modal("show");

            $(modal).on("shown.bs.modal", () => {
                this.$nextTick(() => {
                    this.initDataTable();
                    this.loading = false;
                });
            });
        },

        async fetchDevices() {
            const token = localStorage.getItem("auth_token");
            try {
                const response = await axios.get("/api/employee/devices", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                    params: {
                        session_id: this.sessionId,
                    },
                });
                this.devices = response.data;
            } catch (error) {
                console.error("Error fetching devices:", error);
                this.loading = false;
            }
        },

        initDataTable() {
            if (this.dataTable) this.dataTable.destroy();

            this.dataTable = $("#myTable").DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                responsive: true,
                autoWidth: false,
                lengthMenu: [5, 10, 25, 50],
                pageLength: 10,
                language: {
                    search: "Search devices:",
                    lengthMenu: "Show _MENU_ devices per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ devices",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous",
                    },
                },
            });
        },

        confirmDelete(device) {
            this.deviceToDelete = device;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            this.showDeleteModal = false;
            this.deviceToDelete = null;
        },

        async proceedDelete() {
            if (!this.deviceToDelete) return;

            this.isDeleting = true;
            const token = localStorage.getItem("auth_token");

            try {
                await axios.delete(
                    `/api/employee/devices/${this.deviceToDelete.id}`,
                    {
                        headers: { Authorization: `Bearer ${token}` },
                    },
                );

                this.devices = this.devices.filter(
                    (d) => d.id !== this.deviceToDelete.id,
                );

                if (this.dataTable) {
                    const row = $(
                        `#myTable tbody tr[data-id="${this.deviceToDelete.id}"]`,
                    );
                    this.dataTable.row(row).remove().draw();
                }

                this.showDeleteModal = false;
                this.deviceToDelete = null;
            } catch (error) {
                console.error("Error deleting device:", error);
            } finally {
                this.isDeleting = false;
            }
        },
    },
    beforeUnmount() {
        if (this.dataTable) {
            this.dataTable.destroy();
            this.dataTable = null;
        }
    },
};
</script>

<style scoped>
.table th.text-nowrap {
    white-space: nowrap;
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
    z-index: 1070;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-overlay-content {
    background: #f1f1f1;
    padding: 30px;
    border-radius: 8px;
    max-width: 380px;
    width: 100%;
    color: #000;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
    border: 2px solid #dc3545;

    [data-bs-theme="dark"] & {
        background-color: #25282b;
        color: #fff;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.6);
    }
}

.modal-overlay {
    animation: fadeIn 0.2s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
