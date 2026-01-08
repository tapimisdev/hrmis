<template>
    <div>
        <table class="table table-striped w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="tableData.length === 0">
                    <td colspan="4" class="text-center">No data available</td>
                </tr>
                <tr v-for="(row, index) in tableData" :key="row.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ row.name }}</td>
                    <td>{{ row.type }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-primary"
                                @click="openEditModal(row)"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button
                                class="btn btn-danger"
                                @click="confirmDelete(row.id)"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <ModalVue ref="editModal" type="default" title="Edit Payroll Component">
            <template #default>
                <div class="modal-body">
                    <form @submit.prevent="updateRecord">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="selectedRecord.name"
                                required
                            />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select
                                class="form-select"
                                v-model="selectedRecord.type"
                                required
                            >
                                <option value="earnings">Earnings</option>
                                <option value="taxes">Taxes</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="closeEditModal"
                            >
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </ModalVue>
    </div>
</template>

<script>
import axios from "axios";
import ModalVue from "../../components/ModalVue.vue";

export default {
    name: "PayrollComponentIndex",
    components: { ModalVue },
    props: {
        fetchUrl: { type: String, required: true },
        updateUrl: { type: String, required: true },
        deleteUrl: { type: String, required: true },
    },
    data() {
        return {
            tableData: [],
            selectedRecord: {},
        };
    },
    mounted() {
        this.loadTable();
    },
    methods: {
        loadTable() {
            axios
                .get(this.fetchUrl)
                .then((res) => {
                    this.tableData = res.data.data || res.data || [];
                })
                .catch((err) => console.error(err));
        },
        openEditModal(row) {
            this.selectedRecord = { ...row };
            if (this.$refs.editModal && this.$refs.editModal.open) {
                this.$refs.editModal.open();
            }
        },
        closeEditModal() {
            this.selectedRecord = {};
            if (this.$refs.editModal && this.$refs.editModal.$el) {
                $(this.$refs.editModal.$el).modal("hide");
            }
        },
        updateRecord() {
            const url = this.updateUrl.replace(
                "__ID__",
                this.selectedRecord.id
            );
            axios
                .put(url, this.selectedRecord)
                .then(() => {
                    this.loadTable();
                    this.closeEditModal();
                    Swal.fire(
                        "Updated!",
                        "Record has been updated.",
                        "success"
                    );
                })
                .catch((err) => console.error(err));
        },
        confirmDelete(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.deleteRecord(id);
                }
            });
        },

        deleteRecord(id) {
            const url = this.deleteUrl.replace("__ID__", id);
            axios
                .delete(url)
                .then(() => {
                    this.loadTable();
                    Swal.fire(
                        "Deleted!",
                        "Record has been deleted.",
                        "success"
                    );
                })
                .catch((err) => console.error(err));
        },
    },
};
</script>
