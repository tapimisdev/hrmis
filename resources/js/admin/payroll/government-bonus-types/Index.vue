<template>
    <div>
        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-5">
                <div class="card shadow-sm sticky-rule-form">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">{{ form.id ? "Edit Government Bonus Rule" : "Create Government Bonus Rule" }}</h5>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Name</label>
                                <input v-model="form.name" type="text" class="form-control" />
                                <div class="text-danger small">{{ errors.name?.[0] }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Slug</label>
                                <input v-model="form.slug" type="text" class="form-control" />
                                <div class="text-danger small">{{ errors.slug?.[0] }}</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Computation Type</label>
                                <select v-model="form.computation_type" class="form-select">
                                    <option value="manual">Manual</option>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage of Salary</option>
                                    <option value="formula">Formula</option>
                                </select>
                                <div class="text-danger small">{{ errors.computation_type?.[0] }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">
                                    {{ form.computation_type === "percentage" ? "Percentage Value" : "Amount Value" }}
                                </label>
                                <input
                                    v-model="form.computation_value"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    :disabled="form.computation_type === 'manual' || form.computation_type === 'formula'"
                                />
                                <div class="text-danger small">{{ errors.computation_value?.[0] }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Service Date Basis</label>
                                <select v-model="form.service_date_basis" class="form-select">
                                    <option value="organization">Organization</option>
                                    <option value="company">Company</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Formula Expression</label>
                                <input
                                    v-model="form.formula_expression"
                                    type="text"
                                    class="form-control"
                                    placeholder="(salary * 0.5) + 1000"
                                    :disabled="form.computation_type !== 'formula'"
                                />
                                <div class="text-danger small">{{ errors.formula_expression?.[0] }}</div>
                                <div class="text-muted small mt-1">
                                    Allowed variables: <code>salary</code>, <code>basic_salary</code>,
                                    <code>monthly_salary</code>, <code>years_of_service</code>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Computation Notes</label>
                                <input v-model="form.computation_notes" type="text" class="form-control" />
                                <div class="text-danger small">{{ errors.computation_notes?.[0] }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold">Minimum Years of Service</label>
                                <input v-model="form.min_years_of_service" type="number" min="0" class="form-control" />
                                <div class="text-danger small">{{ errors.min_years_of_service?.[0] }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold">Minimum Months of Service</label>
                                <input v-model="form.min_months_of_service" type="number" min="0" class="form-control" />
                                <div class="text-danger small">{{ errors.min_months_of_service?.[0] }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold">Status</label>
                                <select v-model="form.is_active" class="form-select">
                                    <option :value="true">Active</option>
                                    <option :value="false">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2 text-muted text-uppercase small fw-bold">Qualification Rules</h6>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Active Account</label>
                                <select v-model="form.require_active_account" class="form-select">
                                    <option :value="true">Required</option>
                                    <option :value="false">Ignore</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Work Shift</label>
                                <select v-model="form.require_work_shift" class="form-select">
                                    <option :value="true">Required</option>
                                    <option :value="false">Ignore</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Information</label>
                                <select v-model="form.require_information" class="form-select">
                                    <option :value="true">Required</option>
                                    <option :value="false">Ignore</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">Salary</label>
                                <select v-model="form.require_salary" class="form-select">
                                    <option :value="true">Required</option>
                                    <option :value="false">Ignore</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-secondary" @click="resetForm">Clear</button>
                        <button class="btn btn-primary" @click="submitForm">
                            {{ form.id ? "Update Rule" : "Add Rule" }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Government Bonus Rules List</h5>
                            <span class="text-muted small">{{ tableData.length }} rule(s)</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Computation</th>
                                        <th>Service Rule</th>
                                        <th>Status</th>
                                        <th style="width: 140px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="tableData.length === 0">
                                        <td colspan="5" class="text-center">No government bonus rules found.</td>
                                    </tr>
                                    <tr v-for="row in tableData" :key="row.id">
                                        <td>
                                            <div class="fw-bold">{{ row.name }}</div>
                                            <div class="text-muted small">{{ row.slug }}</div>
                                        </td>
                                        <td>
                                            <div>{{ computationLabel(row) }}</div>
                                            <div v-if="row.computation_notes" class="text-muted small">{{ row.computation_notes }}</div>
                                        </td>
                                        <td>
                                            <div>{{ serviceRuleLabel(row) }}</div>
                                            <div class="text-muted small">
                                                Active: {{ yesNo(row.require_active_account) }},
                                                Shift: {{ yesNo(row.require_work_shift) }},
                                                Info: {{ yesNo(row.require_information) }},
                                                Salary: {{ yesNo(row.require_salary) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" :class="row.is_active ? 'bg-success' : 'bg-secondary'">
                                                {{ row.is_active ? "Active" : "Inactive" }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-primary btn-sm" @click="editRow(row)">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" @click="confirmDelete(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "GovernmentBonusTypeIndex",
    props: {
        fetchUrl: { type: String, required: true },
        storeUrl: { type: String, required: true },
        updateUrl: { type: String, required: true },
        deleteUrl: { type: String, required: true },
    },
    data() {
        return {
            tableData: [],
            errors: {},
            form: this.defaultForm(),
        };
    },
    mounted() {
        this.loadTable();
    },
    methods: {
        defaultForm() {
            return {
                id: null,
                name: "",
                slug: "",
                computation_type: "manual",
                computation_value: "",
                formula_expression: "",
                computation_notes: "",
                service_date_basis: "organization",
                min_years_of_service: "",
                min_months_of_service: "",
                require_active_account: true,
                require_work_shift: true,
                require_information: true,
                require_salary: true,
                is_active: true,
            };
        },
        yesNo(value) {
            return Number(value) === 1 || value === true ? "Yes" : "No";
        },
        serviceRuleLabel(row) {
            const years = row.min_years_of_service ?? 0;
            const months = row.min_months_of_service ?? 0;
            const basis = row.service_date_basis === "company" ? "company" : "organization";

            if (years > 0 && months > 0) {
                return `${years} year(s) and ${months} month(s) minimum via ${basis} hire date`;
            }

            if (years > 0) {
                return `${years} year(s) minimum via ${basis} hire date`;
            }

            if (months > 0) {
                return `${months} month(s) minimum via ${basis} hire date`;
            }

            return `No minimum service rule (${basis})`;
        },
        computationLabel(row) {
            if (row.computation_type === "fixed") {
                return `Fixed amount: ${Number(row.computation_value || 0).toFixed(2)}`;
            }
            if (row.computation_type === "percentage") {
                return `Percentage of salary: ${Number(row.computation_value || 0).toFixed(2)}%`;
            }
            if (row.computation_type === "formula") {
                return `Formula: ${row.formula_expression || "-"}`;
            }
            return "Manual amount / formula-based handling";
        },
        normalizePayload(payload) {
            return {
                ...payload,
                computation_value:
                    payload.computation_type === "manual" ||
                    payload.computation_type === "formula" ||
                    payload.computation_value === "" ||
                    payload.computation_value === null
                        ? null
                        : Number(payload.computation_value),
                formula_expression:
                    payload.computation_type === "formula"
                        ? payload.formula_expression
                        : null,
                min_years_of_service:
                    payload.min_years_of_service === "" || payload.min_years_of_service === null
                        ? null
                        : Number(payload.min_years_of_service),
                min_months_of_service:
                    payload.min_months_of_service === "" || payload.min_months_of_service === null
                        ? null
                        : Number(payload.min_months_of_service),
                require_active_account: payload.require_active_account ? 1 : 0,
                require_work_shift: payload.require_work_shift ? 1 : 0,
                require_information: payload.require_information ? 1 : 0,
                require_salary: payload.require_salary ? 1 : 0,
                is_active: payload.is_active ? 1 : 0,
            };
        },
        async loadTable() {
            const response = await axios.get(this.fetchUrl);
            this.tableData = response.data.data || [];
        },
        editRow(row) {
            this.errors = {};
            this.form = {
                ...this.defaultForm(),
                ...row,
                computation_value: row.computation_value ?? "",
                formula_expression: row.formula_expression ?? "",
                require_active_account: Number(row.require_active_account) === 1,
                require_work_shift: Number(row.require_work_shift) === 1,
                require_information: Number(row.require_information) === 1,
                require_salary: Number(row.require_salary) === 1,
                is_active: Number(row.is_active) === 1,
            };
        },
        resetForm() {
            this.errors = {};
            this.form = this.defaultForm();
        },
        async submitForm() {
            this.errors = {};
            const payload = this.normalizePayload(this.form);

            try {
                if (this.form.id) {
                    await axios.put(this.updateUrl.replace("__ID__", this.form.id), payload);
                } else {
                    await axios.post(this.storeUrl, payload);
                }

                await this.loadTable();
                this.resetForm();
                Swal.fire("Saved", "Government bonus rule saved successfully.", "success");
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                    return;
                }

                Swal.fire("Error", error.response?.data?.message || "Something went wrong.", "error");
            }
        },
        confirmDelete(id) {
            Swal.fire({
                title: "Delete this rule?",
                text: "This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Delete",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.deleteRow(id);
                }
            });
        },
        async deleteRow(id) {
            await axios.delete(this.deleteUrl.replace("__ID__", id));
            await this.loadTable();
            this.resetForm();
            Swal.fire("Deleted", "Government bonus rule deleted successfully.", "success");
        },
    },
};
</script>

<style scoped>
.sticky-rule-form {
    position: sticky;
    top: 1rem;
}

@media (max-width: 1199.98px) {
    .sticky-rule-form {
        position: static;
    }
}
</style>
