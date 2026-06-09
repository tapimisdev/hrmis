<template>
    <div class="container-fluid py-4 bir2316-page">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                            <div>
                                <div class="text-uppercase small fw-semibold text-body-secondary mb-2">Taxation</div>
                                <h1 class="h3 mb-2">BIR 2316</h1>
                                <p class="mb-0 text-body-secondary">
                                    Generate locked snapshot records from existing annual tax data, then review, print, and download the official form.
                                </p>
                            </div>
                            <div class="text-body-secondary fw-semibold">
                                {{ filters.taxable_year || "N/A" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template v-if="!isDetailMode">
                <div class="col-12">
                    <div class="row g-4">
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="small text-body-secondary mb-1">Taxable Income</div>
                                    <div class="fs-5 fw-semibold">{{ peso(summaryState.net_taxable_income) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="small text-body-secondary mb-1">Tax Due</div>
                                    <div class="fs-5 fw-semibold">{{ peso(summaryState.annual_tax_due) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="small text-body-secondary mb-1">Tax Withheld</div>
                                    <div class="fs-5 fw-semibold">{{ peso(summaryState.tax_withheld) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <div class="small text-body-secondary mb-1">Refund / Payable</div>
                                    <div class="fs-5 fw-semibold">{{ peso(summaryState.tax_refund_or_payable) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="h5 mb-0">Filters</h2>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6 col-lg-4">
                                    <label class="form-label small mb-1">Taxable Year</label>
                                    <select v-model.number="filters.taxable_year" class="form-select" :disabled="isLoading" @change="fetchRows">
                                        <option v-for="year in availableYearsState" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <label class="form-label small mb-1">Employee</label>
                                    <select v-model.number="filters.employee_id" class="form-select" :disabled="isLoading" @change="fetchRows">
                                        <option :value="null">All Employees</option>
                                        <option v-for="employee in employeesState" :key="employee.id" :value="employee.id">
                                            {{ employee.employee_no }} - {{ employee.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <label class="form-label small mb-1">Status</label>
                                    <select v-model="filters.status" class="form-select" :disabled="isLoading" @change="fetchRows">
                                        <option value="">All Statuses</option>
                                        <option v-for="status in statusesState" :key="status.value" :value="status.value">{{ status.label }}</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <label class="form-label small mb-1">Division</label>
                                    <select v-model.number="filters.division_id" class="form-select" :disabled="isLoading" @change="fetchRows">
                                        <option :value="null">All Divisions</option>
                                        <option v-for="division in divisionsState" :key="division.id" :value="division.id">{{ division.name }}</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <label class="form-label small mb-1">Employment Type</label>
                                    <select v-model.number="filters.employment_type_id" class="form-select" :disabled="isLoading" @change="fetchRows">
                                        <option :value="null">All Types</option>
                                        <option v-for="type in employmentTypesState" :key="type.id" :value="type.id">{{ type.name }}</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4 d-grid">
                                    <button type="button" class="btn btn-secondary" :disabled="isLoading" @click="resetFilters">
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mt-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary" :disabled="isSubmittingGenerate || isLoading" @click="generateAll">
                                        {{ isSubmittingGenerate ? "Generating..." : "Generate All" }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        :disabled="selectedEmployeeIds.length === 0 || isSubmittingGenerate || isLoading"
                                        @click="generateSelected"
                                    >
                                        Generate Selected
                                    </button>
                                </div>
                                <div class="small text-body-secondary">
                                    {{ selectedEmployeeIds.length }} selected
                                </div>
                            </div>

                            <div v-if="feedback.message" class="alert mt-3 mb-0" :class="feedback.type === 'error' ? 'alert-danger' : 'alert-success'">
                                {{ feedback.message }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                                <div>
                                    <h2 class="h5 mb-1">BIR 2316 Records</h2>
                                    <p class="text-body-secondary mb-0">Generated snapshots remain unchanged even if the underlying tax setup changes later.</p>
                                </div>
                                <div class="small text-body-secondary">
                                    {{ rowsState.length }} record<span v-if="rowsState.length !== 1">s</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="isLoading" class="p-4 text-body-secondary">Loading BIR 2316 records...</div>
                            <div v-else-if="rowsState.length === 0" class="p-4 text-body-secondary">No records found.</div>
                            <div v-else class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">
                                                <input type="checkbox" class="form-check-input" :checked="allRowsSelected" @change="toggleAllRows($event)" />
                                            </th>
                                            <th>Employee No.</th>
                                            <th>Employee Name</th>
                                            <th>TIN</th>
                                            <th>Position</th>
                                            <th>Taxable Year</th>
                                            <th class="text-end">Net Taxable Income</th>
                                            <th class="text-end">Tax Due</th>
                                            <th class="text-end">Tax Withheld</th>
                                            <th class="text-end">Refund / Payable</th>
                                            <th>Status</th>
                                            <th class="pe-3 actions-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in rowsState" :key="row.employee_id">
                                            <td class="ps-3">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    :checked="selectedEmployeeIds.includes(row.employee_id)"
                                                    @change="toggleRow(row.employee_id, $event)"
                                                />
                                            </td>
                                            <td>{{ row.employee_no }}</td>
                                            <td>{{ row.employee_name }}</td>
                                            <td>{{ row.tin || "N/A" }}</td>
                                            <td>{{ row.position }}</td>
                                            <td>{{ row.taxable_year }}</td>
                                            <td class="text-end">{{ peso(row.net_taxable_income) }}</td>
                                            <td class="text-end">{{ peso(row.annual_tax_due) }}</td>
                                            <td class="text-end">{{ peso(row.tax_withheld) }}</td>
                                            <td class="text-end">{{ peso(row.tax_refund_or_payable) }}</td>
                                            <td><span class="status-pill" :class="statusClass(row.status)">{{ row.status_label }}</span></td>
                                            <td class="pe-3 actions-column">
                                                <div v-if="rowActionCount(row) === 1 && row.can_generate" class="d-flex">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary fw-semibold text-nowrap px-3 action-btn"
                                                        :disabled="isSubmittingGenerate"
                                                        @click="generateOne(row.employee_id)"
                                                    >
                                                        Generate
                                                    </button>
                                                </div>
                                                <div v-else class="dropdown">
                                                    <button
                                                        class="btn btn-sm btn-secondary fw-semibold action-menu-btn"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                        aria-label="More actions"
                                                    >
                                                        ...
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li v-if="row.can_generate">
                                                            <button class="dropdown-item" type="button" :disabled="isSubmittingGenerate" @click="generateOne(row.employee_id)">
                                                                Generate
                                                            </button>
                                                        </li>
                                                        <li v-if="row.record_id">
                                                            <a class="dropdown-item" :href="showUrl(row.record_id)">View</a>
                                                        </li>
                                                        <li v-if="row.record_id">
                                                            <a class="dropdown-item" :href="printUrl(row.record_id)" target="_blank" rel="noopener">Print</a>
                                                        </li>
                                                        <li v-if="row.record_id">
                                                            <a class="dropdown-item" :href="pdfUrl(row.record_id)">Download PDF</a>
                                                        </li>
                                                        <li v-if="row.record_id && row.can_lock">
                                                            <button class="dropdown-item" type="button" @click="lockRow(row.record_id)">
                                                                Lock
                                                            </button>
                                                        </li>
                                                        <li v-if="row.record_id && row.can_unlock">
                                                            <button class="dropdown-item" type="button" @click="unlockRow(row.record_id)">
                                                                Unlock
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="col-12">
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card h-100">
                            <div class="card-body p-4">
                        <div v-if="isLoadingRecord" class="text-body-secondary">Loading BIR 2316 record...</div>
                        <div v-else-if="!record" class="text-body-secondary">Record not found.</div>
                        <template v-else>
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
                                <div>
                                    <div class="text-uppercase small fw-semibold text-body-secondary mb-2">BIR 2316 Record</div>
                                    <h2 class="h3 mb-2">{{ record.employee.name }}</h2>
                                    <p class="text-body-secondary mb-0">
                                        {{ record.employee.no }} | {{ record.employee.position || "N/A" }} | {{ record.taxable_year }}
                                    </p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-secondary" :href="baseUrl">Back to List</a>
                                    <a class="btn btn-secondary" :href="printUrl(record.id)" target="_blank" rel="noopener">Print</a>
                                    <a class="btn btn-primary" :href="pdfUrl(record.id)">Download PDF</a>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h3 class="h6 mb-0">Employer Information</h3>
                                        </div>
                                        <div class="card-body p-3">
                                        <dl>
                                            <div><dt>Name</dt><dd>{{ record.employer.name || "N/A" }}</dd></div>
                                            <div><dt>TIN</dt><dd>{{ record.employer.tin || "N/A" }}</dd></div>
                                            <div><dt>Address</dt><dd>{{ record.employer.address || "N/A" }}</dd></div>
                                            <div><dt>RDO Code</dt><dd>{{ record.employer.rdo_code || "N/A" }}</dd></div>
                                        </dl>
                                    </div>
                                </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h3 class="h6 mb-0">Employee Information</h3>
                                        </div>
                                        <div class="card-body p-3">
                                        <dl>
                                            <div><dt>Name</dt><dd>{{ record.employee.name }}</dd></div>
                                            <div><dt>TIN</dt><dd>{{ record.employee.tin || "N/A" }}</dd></div>
                                            <div><dt>Address</dt><dd>{{ record.employee.address || "N/A" }}</dd></div>
                                            <div><dt>Employee No.</dt><dd>{{ record.employee.no }}</dd></div>
                                            <div><dt>Position</dt><dd>{{ record.employee.position || "N/A" }}</dd></div>
                                            <div><dt>Employment Type</dt><dd>{{ record.employee.employment_type || "N/A" }}</dd></div>
                                        </dl>
                                    </div>
                                </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h3 class="h6 mb-0">Compensation Details</h3>
                                        </div>
                                        <div class="card-body p-3">
                                        <div class="detail-grid">
                                            <div><span>Gross Compensation Income</span><strong>{{ peso(record.compensation.gross_compensation_income) }}</strong></div>
                                            <div><span>Non-Taxable / Exempt Compensation</span><strong>{{ peso(nonTaxableCompensation) }}</strong></div>
                                            <div><span>Taxable Compensation Income</span><strong>{{ peso(record.compensation.net_taxable_income) }}</strong></div>
                                            <div><span>Tax Due</span><strong>{{ peso(record.compensation.annual_tax_due) }}</strong></div>
                                            <div><span>Tax Withheld</span><strong>{{ peso(record.compensation.tax_withheld) }}</strong></div>
                                            <div><span>Refund / Payable</span><strong>{{ peso(record.compensation.tax_refund_or_payable) }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h3 class="h6 mb-0">Certification</h3>
                                        </div>
                                        <div class="card-body p-3">
                                        <div class="detail-grid">
                                            <div><span>Authorized Signatory</span><strong>{{ record.certification.authorized_signatory || "N/A" }}</strong></div>
                                            <div><span>Employee Signature</span><strong>{{ record.certification.employee_signature || record.employee.name }}</strong></div>
                                            <div><span>Date Signed</span><strong>{{ record.certification.date_signed || record.generated_at }}</strong></div>
                                            <div><span>Substitute Filing</span><strong>{{ record.certification.substitute_filing ? "Yes" : "No" }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h2 class="h5 mb-0">Record Summary</h2>
                            </div>
                            <div class="card-body p-4">
                                <div v-if="record" class="d-grid gap-3">
                                    <div>
                                        <div class="small text-body-secondary mb-1">Status</div>
                                        <span class="status-pill" :class="statusClass(record.status)">{{ record.status }}</span>
                                    </div>
                                    <div>
                                        <div class="small text-body-secondary mb-1">Generated At</div>
                                        <div class="fw-semibold">{{ record.generated_at || "N/A" }}</div>
                                    </div>
                                    <div>
                                        <div class="small text-body-secondary mb-1">Locked At</div>
                                        <div class="fw-semibold">{{ record.locked_at || "Not locked" }}</div>
                                    </div>
                                    <div>
                                        <div class="small text-body-secondary mb-1">Taxable Year</div>
                                        <div class="fw-semibold">{{ record.taxable_year }}</div>
                                    </div>
                                </div>
                                <div v-else class="text-body-secondary">Nothing selected.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        baseUrl: { type: String, required: true },
        apiUrl: { type: String, required: true },
        generateUrl: { type: String, required: true },
        initialFilters: { type: Object, default: () => ({}) },
        availableYears: { type: Array, default: () => [] },
        employees: { type: Array, default: () => [] },
        divisions: { type: Array, default: () => [] },
        employmentTypes: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        initialRows: { type: Array, default: () => [] },
        initialSummary: { type: Object, default: () => ({}) },
        initialViewId: { type: Number, default: null },
    },
    data() {
        return {
            filters: {
                taxable_year: this.initialFilters.taxable_year ?? null,
                employee_id: this.initialFilters.employee_id ?? null,
                division_id: this.initialFilters.division_id ?? null,
                employment_type_id: this.initialFilters.employment_type_id ?? null,
                status: this.initialFilters.status ?? "",
            },
            availableYearsState: [...this.availableYears],
            employeesState: [...this.employees],
            divisionsState: [...this.divisions],
            employmentTypesState: [...this.employmentTypes],
            statusesState: [...this.statuses],
            rowsState: [...this.initialRows],
            summaryState: {
                net_taxable_income: Number(this.initialSummary.net_taxable_income || 0),
                annual_tax_due: Number(this.initialSummary.annual_tax_due || 0),
                tax_withheld: Number(this.initialSummary.tax_withheld || 0),
                tax_refund_or_payable: Number(this.initialSummary.tax_refund_or_payable || 0),
            },
            selectedEmployeeIds: [],
            isLoading: false,
            isLoadingRecord: false,
            isSubmittingGenerate: false,
            feedback: {
                type: "success",
                message: "",
            },
            record: null,
        };
    },
    computed: {
        isDetailMode() {
            return Boolean(this.initialViewId);
        },
        allRowsSelected() {
            return this.rowsState.length > 0 && this.selectedEmployeeIds.length === this.rowsState.length;
        },
        nonTaxableCompensation() {
            if (!this.record) {
                return 0;
            }

            return Number(this.record.compensation.tax_exempt_bonus || 0) + Number(this.record.compensation.de_minimis || 0);
        },
    },
    mounted() {
        if (this.isDetailMode) {
            this.fetchRecord();
        }
    },
    methods: {
        async fetchRows() {
            this.isLoading = true;
            this.feedback.message = "";

            try {
                const { data } = await axios.get(this.apiUrl, {
                    params: this.normalizedFilters(),
                });

                this.rowsState = data.rows || [];
                this.summaryState = data.summary || this.summaryState;
                this.availableYearsState = data.availableYears || this.availableYearsState;
                this.employeesState = data.employees || this.employeesState;
                this.divisionsState = data.divisions || this.divisionsState;
                this.employmentTypesState = data.employmentTypes || this.employmentTypesState;
                this.statusesState = data.statuses || this.statusesState;
                this.selectedEmployeeIds = this.selectedEmployeeIds.filter((id) =>
                    this.rowsState.some((row) => row.employee_id === id)
                );
            } catch (error) {
                this.setError(error, "Unable to load BIR 2316 records.");
            } finally {
                this.isLoading = false;
            }
        },
        async fetchRecord() {
            this.isLoadingRecord = true;

            try {
                const { data } = await axios.get(`${this.apiUrl}/${this.initialViewId}`);
                this.record = data.data || data;
            } catch (error) {
                this.setError(error, "Unable to load the selected BIR 2316 record.");
            } finally {
                this.isLoadingRecord = false;
            }
        },
        async generateAll() {
            await this.runGenerate({
                taxable_year: this.filters.taxable_year,
                all_employees: true,
            });
        },
        async generateSelected() {
            await this.runGenerate({
                taxable_year: this.filters.taxable_year,
                employee_ids: this.selectedEmployeeIds,
            });
        },
        async generateOne(employeeId) {
            await this.runGenerate({
                taxable_year: this.filters.taxable_year,
                employee_ids: [employeeId],
            });
        },
        async runGenerate(payload) {
            this.isSubmittingGenerate = true;
            this.feedback.message = "";

            try {
                const { data } = await axios.post(this.generateUrl, payload);
                const extraErrors = Array.isArray(data.errors) && data.errors.length ? ` ${data.errors.join(" ")}` : "";

                this.feedback = {
                    type: "success",
                    message: `${data.message || "BIR 2316 generated successfully."}${extraErrors}`,
                };
                await this.fetchRows();
            } catch (error) {
                this.setError(error, "Unable to generate BIR 2316.");
            } finally {
                this.isSubmittingGenerate = false;
            }
        },
        async lockRow(recordId) {
            try {
                await axios.post(`${this.baseUrl}/${recordId}/lock`);
                this.feedback = { type: "success", message: "BIR 2316 record locked." };
                await this.fetchRows();
            } catch (error) {
                this.setError(error, "Unable to lock BIR 2316 record.");
            }
        },
        async unlockRow(recordId) {
            try {
                await axios.post(`${this.baseUrl}/${recordId}/unlock`);
                this.feedback = { type: "success", message: "BIR 2316 record unlocked." };
                await this.fetchRows();
            } catch (error) {
                this.setError(error, "Unable to unlock BIR 2316 record.");
            }
        },
        normalizedFilters() {
            return {
                taxable_year: this.filters.taxable_year,
                employee_id: this.filters.employee_id || null,
                division_id: this.filters.division_id || null,
                employment_type_id: this.filters.employment_type_id || null,
                status: this.filters.status || null,
            };
        },
        toggleAllRows(event) {
            this.selectedEmployeeIds = event.target.checked ? this.rowsState.map((row) => row.employee_id) : [];
        },
        toggleRow(employeeId, event) {
            if (event.target.checked) {
                this.selectedEmployeeIds = [...new Set([...this.selectedEmployeeIds, employeeId])];
                return;
            }

            this.selectedEmployeeIds = this.selectedEmployeeIds.filter((id) => id !== employeeId);
        },
        resetFilters() {
            this.filters = {
                taxable_year: this.availableYearsState[0] || null,
                employee_id: null,
                division_id: null,
                employment_type_id: null,
                status: "",
            };
            this.selectedEmployeeIds = [];
            this.fetchRows();
        },
        showUrl(id) {
            return `${this.baseUrl}/${id}`;
        },
        printUrl(id) {
            return `${this.baseUrl}/${id}/print`;
        },
        pdfUrl(id) {
            return `${this.baseUrl}/${id}/pdf`;
        },
        rowActionCount(row) {
            let count = 0;

            if (row.can_generate) count += 1;
            if (row.record_id) count += 3;
            if (row.record_id && row.can_lock) count += 1;
            if (row.record_id && row.can_unlock) count += 1;

            return count;
        },
        peso(value) {
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(Number(value || 0));
        },
        statusClass(status) {
            return {
                "status-pill--muted": status === "not_generated" || status === "draft",
                "status-pill--success": status === "generated",
                "status-pill--warning": status === "locked",
                "status-pill--danger": status === "cancelled",
            };
        },
        setError(error, fallbackMessage) {
            const responseErrors = error?.response?.data?.errors;
            const flattened = responseErrors
                ? Object.values(responseErrors).flat().join(" ")
                : error?.response?.data?.message || fallbackMessage;

            this.feedback = {
                type: "error",
                message: flattened || fallbackMessage,
            };
        },
    },
};
</script>

<style scoped>
.actions-column {
    width: 1%;
    white-space: nowrap;
}

.action-btn {
    min-width: 6rem;
    white-space: nowrap;
    transform: none !important;
    writing-mode: horizontal-tb;
    text-orientation: mixed;
}

.action-menu-btn {
    min-width: 2.5rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    letter-spacing: 0.12em;
    line-height: 1;
    border: 0;
    white-space: nowrap;
    transform: none !important;
    writing-mode: horizontal-tb;
    text-orientation: mixed;
}

.action-btn:hover,
.action-btn:focus,
.action-btn:active,
.action-menu-btn:hover,
.action-menu-btn:focus,
.action-menu-btn:active {
    transform: none !important;
    writing-mode: horizontal-tb;
    text-orientation: mixed;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.72rem;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 700;
}

.status-pill--muted {
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
}

.status-pill--success {
    background: rgba(var(--bs-success-rgb), 0.15);
    color: var(--bs-success);
}

.status-pill--warning {
    background: rgba(var(--bs-warning-rgb), 0.2);
    color: var(--bs-warning-text-emphasis, var(--bs-warning));
}

.status-pill--danger {
    background: rgba(var(--bs-danger-rgb), 0.15);
    color: var(--bs-danger);
}

.card dl {
    margin: 0;
    display: grid;
    gap: 1rem;
}

.card dl div {
    display: grid;
    gap: 0.2rem;
}

.card dt,
.detail-grid span {
    font-size: 0.74rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--bs-secondary-color);
}

.card dd,
.detail-grid strong {
    margin: 0;
    font-weight: 700;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.detail-grid > div {
    border: 1px solid var(--bir-line);
    border-radius: 0.85rem;
    padding: 1rem;
    background: #fbfdff;
    display: grid;
    gap: 0.25rem;
}
</style>
