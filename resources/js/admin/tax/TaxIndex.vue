<template>
    <div>
        <!-- Table -->
        <table id="myTable" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Year</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="tableData.length === 0">
                    <td colspan="3" class="text-center">No data available</td>
                </tr>
                <tr v-else v-for="(row, index) in tableData" :key="row.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ row.year }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a
                                :href="employeeUrl.replace('__ID__', row.id)"
                                class="btn btn-primary btn my-1"
                                title="Edit"
                            >
                                <i class="fa-solid fa-table-list"></i>
                            </a>
                            <button
                                class="btn btn-secondary my-1"
                                @click="editRecord(row.id)"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Modal -->
        <ModalVue ref="taxModal" :title="modalTitle" type="default">
            <template #default>
                <form @submit.prevent="submitForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="year" class="form-label">
                                Year <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="year"
                                class="form-control"
                                v-model="form.year"
                                :class="{ 'is-invalid': errors.year }"
                            />
                            <div
                                class="error-field text-danger mt-1"
                                ref="yearError"
                            ></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="closeModal"
                        >
                            <i class="fa-solid fa-xmark me-1"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i
                                class="fa-solid me-1"
                                :class="
                                    form.id ? 'fa-pen-to-square' : 'fa-plus'
                                "
                            ></i>
                            {{ form.id ? "Update" : "Add" }}
                        </button>
                    </div>
                </form>
            </template>
        </ModalVue>
    </div>
</template>

<script>
import axios from "axios";
import ModalVue from "../../components/ModalVue.vue";
import { alert } from "../../helper";

export default {
    components: { ModalVue },
    name: "TaxIndex",
    props: {
        employeeUrl: { type: String, required: true },
        fetchUrl: { type: String, required: true },
        showUrl: { type: String, required: true },
        updateUrl: { type: String, required: true },
        storeUrl: { type: String, required: true },
    },
    data() {
        return {
            tableData: [],
            modalTitle: "Add Year",
            form: {
                id: null,
                year: "",
            },
            errors: {},
        };
    },
    mounted() {
        this.loadTable();

        const btn = document.getElementById("create-btn");
        if (btn) btn.addEventListener("click", this.openModal);
    },
    methods: {
        loadTable() {
            axios
                .get(this.fetchUrl)
                .then((res) => {
                    this.tableData = res.data.data || [];
                })
                .catch((err) => console.error(err));
        },

        openModal() {
            this.modalTitle = this.form.id ? "Edit Year" : "Add Year";
            this.form.id = null;
            this.form.year = "";
            this.clearErrors();
            this.$refs.taxModal.open();
        },

        closeModal() {
            this.$refs.taxModal.close();
        },

        editRecord(id) {
            this.clearErrors();
            this.form.id = id;
            this.modalTitle = "Edit Year";

            axios
                .get(this.showUrl.replace("__ID__", id))
                .then((res) => {
                    this.form.year = res.data.data.year;
                    this.$refs.taxModal.open();
                })
                .catch((err) => console.error(err));
        },

        clearErrors() {
            this.errors = {};
            if (this.$refs.yearError) this.$refs.yearError.innerText = "";
        },

        async submitForm() {
            const url = this.form.id
                ? this.updateUrl.replace("__ID__", this.form.id)
                : this.storeUrl;
            const method = this.form.id ? "PUT" : "POST";

            this.clearErrors();

            try {
                await axios({ url, method, data: this.form });
                this.loadTable();
                this.closeModal();
                alert(
                    "success",
                    this.form.id
                        ? "Year updated successfully."
                        : "Year added successfully."
                );
            } catch (err) {
                if (err.response && err.response.status === 422) {
                    this.errors = err.response.data.errors || {};
                    if (this.errors.year && this.$refs.yearError) {
                        this.$refs.yearError.innerText = this.errors.year[0];
                    }
                } else {
                    alert(
                        "error",
                        err.message ||
                            "An error occurred while submitting the form."
                    );
                }
            }
        },
    },
};
</script>
