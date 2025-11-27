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
                                :href="
                                    employeeUrl.replace('__YEAR__', row.year)
                                "
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
        <ModalVue
            ref="taxModal"
            :title="modalTitle"
            type="default"
            size="modal-md"
        >
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
    name: "PayrollEmployeeComponentIndex",

    props: {
        slug: String,
        employeeUrl: String,
        fetchUrl: String,
        showUrl: String,
        updateUrl: String,
        storeUrl: String,
    },

    data() {
        return {
            tableData: [],
            modalTitle: "Add Year",

            form: {
                id: null,
                slug: "",
                year: "",
                originalYear: null,
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
        /** Load table data **/
        loadTable() {
            axios
                .get(this.fetchUrl.replace("__SLUG__", this.slug))
                .then((res) => (this.tableData = res.data.data || []))
                .catch((err) => console.error(err));
        },

        /** Open modal -> always reset to ADD mode **/
        openModal() {
            this.resetForm();
            this.modalTitle = "Add Year";
            this.$refs.taxModal.open();
        },

        /** Close modal **/
        closeModal() {
            this.$refs.taxModal.close();
        },

        /** Reset form completely **/
        resetForm() {
            this.form = {
                id: null,
                slug: this.slug,
                year: "",
                originalYear: null,
            };
            this.clearErrors();
        },

        /** Open modal for editing **/
        editRecord(id) {
            this.resetForm();
            this.form.id = id;
            this.modalTitle = "Edit Year";

            axios
                .get(
                    this.showUrl
                        .replace("__SLUG__", this.slug)
                        .replace("__YEAR__", id)
                )
                .then((res) => {
                    this.form.year = res.data.data.year;
                    this.form.originalYear = res.data.data.year;
                    this.$refs.taxModal.open();
                })
                .catch((err) => console.error(err));
        },

        /** Clear errors **/
        clearErrors() {
            this.errors = {};
            if (this.$refs.yearError) this.$refs.yearError.innerText = "";
        },

        /** Save form **/
        async submitForm() {
            this.clearErrors();

            const method = this.form.id ? "PUT" : "POST";
            const url = this.form.id
                ? this.updateUrl
                      .replace("__YEAR__", this.form.originalYear)
                      .replace("__SLUG__", this.slug)
                : this.storeUrl.replace("__SLUG__", this.slug);

            try {
                await axios({ method, url, data: this.form });

                this.loadTable();
                this.closeModal();

                alert(
                    "success",
                    this.form.id
                        ? "Year updated successfully."
                        : "Year added successfully."
                );
            } catch (err) {
                if (err.response?.status === 422) {
                    this.errors = err.response.data.errors || {};

                    if (this.errors.year && this.$refs.yearError) {
                        this.$refs.yearError.innerText = this.errors.year[0];
                    }
                } else {
                    alert("error", err.message || "Something went wrong.");
                }
            }
        },
    },
};
</script>
