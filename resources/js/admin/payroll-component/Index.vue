<template>
    <div>
        <!-- Cards Grid -->
        <div class="row g-3 g-md-4">
            <!-- Empty State -->
            <div v-if="tableData.length === 0" class="col-12">
                <div class="card modern-empty shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="empty-icon mb-3">
                            <i class="fa-regular fa-folder-open"></i>
                        </div>
                        <div class="fw-semibold fs-5">No data yet</div>
                        <div class="text-body-secondary mt-1">
                            Create a year record to get started.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year Cards -->
            <div
                v-else
                v-for="(row, index) in tableData"
                :key="row.id"
                class="col-12 col-sm-6 col-lg-4 col-xl-3"
            >
                <div class="card modern-card h-100">
                    <div class="card-body d-flex flex-column">
                        <!-- Header -->
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge index-badge">
                                    #{{ index + 1 }}
                                </span>
                                <span class="text-body-secondary small">
                                    Payroll Year
                                </span>
                            </div>

                            <button
                                type="button"
                                class="btn btn-icon"
                                title="Edit Year"
                                @click="editRecord(row.id)"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>

                        <!-- Year -->
                        <div class="year-display mb-3">
                            <div class="fw-semibold">Year</div>
                            <div class="year-number">
                                {{ row.year }}
                            </div>
                        </div>

                        <!-- Spacer -->
                        <div class="mt-auto pt-3 border-top border-opacity-10"></div>

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <a
                                :href="employeeUrl.replace('__YEAR__', row.year)"
                                class="btn btn-primary btn-sm w-100"
                                title="View Employees"
                            >
                                <i class="fa-solid fa-table-list me-1"></i>
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal (same as yours) -->
        <ModalVue ref="taxModal" :title="modalTitle" type="default" size="modal-md">
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
                            <div class="error-field text-danger mt-1" ref="yearError"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" @click="closeModal">
                            <i class="fa-solid fa-xmark me-1"></i> Close
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i
                                class="fa-solid me-1"
                                :class="form.id ? 'fa-pen-to-square' : 'fa-plus'"
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
                        .replace("__YEAR__", id),
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
                        : "Year added successfully.",
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

<style lang="scss" scoped>
.modern-card {
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.2);
    background: var(--bs-body-bg);
    border-radius: 18px;
    overflow: hidden;
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease,
        border-color 0.15s ease;

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.10);
    }
}

/* Badge */
.index-badge {
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.20);
    font-weight: 600;
}

/* Year block */
.year-display {
    .fw-semibold {
        letter-spacing: 0.2px;
        color: rgba(var(--bs-body-color-rgb), 0.75);
        font-size: 0.9rem;
    }
}

.year-number {
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1.05;
    margin-top: 4px;
}

/* Small icon button */
.btn-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.10);
    background: rgba(var(--bs-body-color-rgb), 0.03);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 0.15s ease;

    &:hover {
        background: rgba(var(--bs-primary-rgb), 0.10);
        border-color: rgba(var(--bs-primary-rgb), 0.25);
        color: var(--bs-primary);
    }
}

/* Empty state */
.modern-empty {
    border-radius: 18px;
    background: linear-gradient(
        180deg,
        rgba(var(--bs-body-color-rgb), 0.02),
        rgba(var(--bs-body-color-rgb), 0.00)
    );
}

.empty-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    margin: 0 auto;
    display: grid;
    place-items: center;
    font-size: 1.4rem;

    background: rgba(var(--bs-primary-rgb), 0.10);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.20);
    color: var(--bs-primary);
}

</style>
