<template>
    <div class="uploading pb-5">
        <!-- Upload -->
        <div v-if="upload && !loading">
            <label class="form-label fw-bold">Upload File (CSV or Excel)</label>
            <div
                class="upload-box text-center p-5 border border-2 border-dashed rounded-3"
                @click="triggerFileInput"
            >
                <i
                    class="fa-solid fa-file-arrow-up fa-2x mb-3 text-primary"
                ></i>
                <p class="mb-1 fw-semibold">Click or drag file to upload</p>
                <small class="text-muted">Supported: CSV, XLS, XLSX</small>
                <input
                    type="file"
                    class="d-none"
                    ref="fileInput"
                    accept=".csv, .xls, .xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                    @change="onFileUpload"
                />
            </div>
        </div>

        <!-- Loading Skeleton -->
        <div v-if="loading">
            <p class="fw-bold mb-2 text-uppercase">Parsing CSV...</p>
            <div class="table-container">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th v-for="i in 9" :key="i">
                                <div class="skeleton skeleton-header"></div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in 6" :key="row">
                            <td v-for="col in 9" :key="col">
                                <div class="skeleton skeleton-cell"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Preview -->
        <div v-if="!upload && !loading">
            <h6 class="fw-bold mb-3 text-uppercase">Preview Credits</h6>
            <div class="table-container table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">Actions</th>
                            <th class="text-center">#</th>
                            <th v-for="header in tableHeaders" :key="header">{{ header }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(credit, index) in creditsData.credits"
                            :key="index"
                        >
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger" @click="deleteRow(index)" title="Delete row">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <div>{{ index + 1 }}</div>
                            </td>
                            <td v-for="key in parsedHeaderKeys" :key="key">
                                <template v-if="key === 'employee_no'">
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="credit.employee_no"
                                    />
                                </template>

                                <template v-else-if="key === 'month_year'">
                                    <input
                                        type="month"
                                        class="form-control"
                                        v-model="credit.month_year"
                                    />
                                </template>

                                <template v-else-if="['sick_leave', 'vacation_leave', 'total_credits', 'credits'].includes(key)">
                                    <input
                                        type="number"
                                        step="0.01"
                                        class="form-control"
                                        v-model.number="credit[key]"
                                    />
                                </template>

                                <template v-else-if="key === 'remarks'">
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        v-model="credit.remarks"
                                    ></textarea>
                                </template>

                                <template v-else>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="credit[key]"
                                    />
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Errors -->
            <div v-if="errors.length" class="mt-4">
                <hr />
                <small class="fw-medium text-danger text-uppercase mb-3"
                    >An error occurred during importing</small
                >
                <table class="table table-danger table-bordered mt-2">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Field</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(err, i) in errors" :key="i">
                            <td>{{ err.row }}</td>
                            <td>{{ err.field }}</td>
                            <td>{{ err.message }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <button
                    class="btn btn-secondary px-4 py-2 fw-medium text-uppercase"
                    @click="backToForm"
                >
                    Back
                </button>
                <button
                    class="btn btn-primary px-4 py-2 fw-medium text-uppercase"
                    @click="saveCredits"
                >
                    Upload
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import * as XLSX from "xlsx";
const token = localStorage.getItem("auth_token");

export default {
    props: {
        leaveTypes: { type: Array, required: false },
        saveUrl: { type: String, required: true },
        type: { type: String, required: true },
    },

    data() {
        return {
            upload: true,
            loading: false,
            creditsData: { credits: [] },
            errors: [],
            parsedHeaderKeys: [],
            parsedHeaderLabels: [],
        };
    },

    computed: {
        requiredHeaders() {
            if (this.type === "leave") {
                return ["employee_no", "month_year", "sick_leave", "vacation_leave", "total_credits"];
            }

            if (this.type === "offset") {
                return ["employee_no", "month_year", "credits"];
            }

            return [];
        },

        acceptedHeaders() {
            if (this.type === "leave") {
                return ["employee_no", "month_year", "sick_leave", "vacation_leave", "total_credits", "remarks"];
            }

            if (this.type === "offset") {
                return ["employee_no", "month_year", "credits", "remarks"];
            }

            return [];
        },

        tableHeaders() {
            return this.parsedHeaderLabels.length ? this.parsedHeaderLabels : (this.type === 'leave'
                ? ['Employee No', 'Month & Year', 'Sick Leave', 'Vacation Leave', 'Total Credits', 'Remarks']
                : ['Employee No', 'Month & Year', 'Credits', 'Remarks']
            );
        },
    },

    methods: {
        triggerFileInput() {
            this.$refs.fileInput.click();
        },

        formatHeader(raw) {
            const map = {
                employee_no: 'Employee No',
                month_year: 'Month & Year',
                sick_leave: 'Sick Leave',
                vacation_leave: 'Vacation Leave',
                total_credits: 'Total Credits',
                credits: 'Credits',
                remarks: 'Remarks',
            };
            return map[raw] || raw.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase());
        },

        onFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.loading = true;
            this.errors = [];

            const extension = file.name.split('.').pop().toLowerCase();
            if (!['csv', 'xls', 'xlsx'].includes(extension)) {
                this.errors = [{ row: '-', field: 'file', message: 'Unsupported file type. Upload CSV, XLS or XLSX.' }];
                this.loading = false;
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                if (extension === 'csv') {
                    const rawText = e.target.result;
                    const csvLines = rawText
                        .split(/\r?\n/)
                        .map((line) => line.trim())
                        .filter(Boolean);

                    if (!csvLines.length || csvLines.length < 2) {
                        this.errors = [{ row: '-', field: 'file', message: 'CSV should contain at least one data row.' }];
                        this.loading = false;
                        return;
                    }

                    const delimiter = csvLines[0].includes('\t') ? '\t' : ',';
                    const headers = csvLines[0]
                        .split(delimiter)
                        .map((h) => String(h).trim().toLowerCase().replace(/\s+/g, '_'));

                    const rows = csvLines.slice(1).map((row) =>
                        row.split(delimiter).map((cell) => String(cell).trim()),
                    );

                    this.processParsedRows(headers, rows);
                    return;
                }

                let workbook;
                try {
                    const data = new Uint8Array(e.target.result);
                    workbook = XLSX.read(data, { type: 'array' });
                } catch (err) {
                    this.errors = [{ row: '-', field: 'file', message: `Error reading file: ${err.message}` }];
                    this.loading = false;
                    return;
                }

                if (!workbook || !workbook.SheetNames || workbook.SheetNames.length === 0) {
                    this.errors = [{ row: '-', field: 'file', message: 'File has no sheets or data.' }];
                    this.loading = false;
                    return;
                }

                const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                const sheetData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

                if (!sheetData.length || sheetData.length < 2) {
                    this.errors = [{ row: '-', field: 'file', message: 'File should contain at least one data row.' }];
                    this.loading = false;
                    return;
                }

                const headers = sheetData[0]
                    .map((h) => String(h).trim().toLowerCase().replace(/\s+/g, '_'));

                const rows = sheetData.slice(1).map((r) => r.map((cell) => String(cell).trim()));

                this.processParsedRows(headers, rows);
            };

            if (extension === 'csv') {
                reader.readAsText(file);
            } else {
                reader.readAsArrayBuffer(file);
            }
        },

        processParsedRows(headers, rows) {
            if (!rows.length) {
                this.errors = [{ row: '-', field: 'file', message: 'Uploaded file must include at least one data row.' }];
                this.loading = false;
                return;
            }
            const required = this.type === 'leave'
                ? ["employee_no", "month_year", "sick_leave", "vacation_leave", "total_credits", "remarks"]
                : ["employee_no", "month_year", "credits", "remarks"];

            const missing = required.filter((header) => !headers.includes(header));
            if (missing.length) {
                const message = `Invalid file template. Missing required headers: ${missing.join(', ')}`;
                this.errors = [{ row: '-', field: 'headers', message }];
                this.loading = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: message,
                });
                return;
            }

            const outOfOrder = required.filter((header, index) => headers[index] !== header);
            if (outOfOrder.length) {
                const message = `Invalid file template. Headers must be in exact order: ${required.join(', ')}`;
                this.errors = [{ row: '-', field: 'headers', message }];
                this.loading = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: message,
                });
                return;
            }

            if (this.type === 'offset') {
                const requiredOffset = ["employee_no", "month_year", "credits", "remarks"];
                const missingOffset = requiredOffset.filter((header) => !headers.includes(header));
                if (missingOffset.length) {
                    const message = `Invalid file template. Offset import requires: ${missingOffset.join(', ')}`;
                    this.errors = [{ row: '-', field: 'headers', message }];
                    this.loading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops',
                        text: message,
                    });
                    return;
                }
            }

            this.parsedHeaderKeys = headers;
            this.parsedHeaderLabels = headers.map((h) => this.formatHeader(h));

            let credits = rows.map((rowArray) => {
                const obj = {};
                headers.forEach((header, i) => {
                    obj[header] = rowArray[i] || '';
                });

                if (this.type === 'leave') {
                    obj.employee_no = obj.employee_no || '';
                    obj.month_year = obj.month_year || '';
                    obj.sick_leave = Number(obj.sick_leave || 0);
                    obj.vacation_leave = Number(obj.vacation_leave || 0);
                    obj.total_credits = Number(obj.total_credits || (obj.sick_leave + obj.vacation_leave));
                    obj.remarks = obj.remarks || '';
                    return obj;
                }

                if (this.type === 'offset') {
                    const creditsValue = Number(obj.credits || 0);
                    obj.employee_no = obj.employee_no || '';
                    obj.month_year = obj.month_year || '';
                    obj.credits = creditsValue;
                    obj.remarks = obj.remarks || '';
                    return obj;
                }

                return obj;
            });

            credits = credits.filter(credit => credit.employee_no && credit.employee_no.trim() !== '');

            this.creditsData.credits = credits;
            this.creditsData.credits = credits;
            this.loading = false;
            this.upload = false;
        },

        deleteRow(index) {
            this.creditsData.credits.splice(index, 1);
        },

        backToForm() {
            this.upload = true;
            this.errors = [];
            this.creditsData = { credits: [] };
        },

        saveCredits() {
            this.errors = [];

            Swal.fire({
                title: "Are you sure to continue?",
                html: `
          This upload is intended for a <strong>one-time migration</strong> of all employees’ credits.<br><br>
          It should <strong>not</strong> be used for updating existing credits, individually or in bulk.<br>
          Using it incorrectly may cause <strong>data inconsistencies or loss of previous records</strong>.
        `,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#0d6efd",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, upload it (5)",
                cancelButtonText: "Cancel",
                didOpen: () => {
                    const confirmBtn = Swal.getConfirmButton();
                    confirmBtn.disabled = true;
                    let countdown = 5;
                    const interval = setInterval(() => {
                        countdown--;
                        if (countdown > 0) {
                            confirmBtn.textContent = `Yes, upload it (${countdown})`;
                        } else {
                            clearInterval(interval);
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = `Yes, upload it`;
                        }
                    }, 1000);
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: "Uploading...",
                    text: "Please wait while we save your credits",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                });

                const payload = {
                    type: this.type,
                    credits: this.creditsData.credits.map((item) => {
                        if (this.type === 'offset') {
                            return {
                                employee_no: item.employee_no,
                                as_of: item.month_year || item.as_of,
                                credits: Number(item.credits ?? 0),
                                remarks: item.remarks || '',
                            };
                        }

                        // leave
                        return {
                            employee_no: item.employee_no,
                            month_year: item.month_year,
                            sick_leave: Number(item.sick_leave ?? 0),
                            vacation_leave: Number(item.vacation_leave ?? 0),
                            total_credits: Number(item.total_credits ?? 0),
                            remarks: item.remarks || '',
                        };
                    }),
                };
                axios
                    .post(this.saveUrl, payload, {
                        headers: { Authorization: `Bearer ${token}` },
                    })
                    .then(() => {
                        Swal.fire({
                            icon: "success",
                            title: "Uploaded!",
                            text: "Credits saved successfully.",
                            showCancelButton: true,
                            confirmButtonText: "View Credits",
                            cancelButtonText: "Got it",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/admin/service/credits';
                            }
                            this.backToForm();
                        });
                    })
                    .catch((err) => {
                        Swal.close();
                        if (err.response?.status === 422) {
                            this.errors = [];
                            Object.entries(
                                err.response.data.errors,
                            ).forEach(([key, messages]) => {
                                const match =
                                    key.match(/credits\.(\d+)\.(.+)/);
                                if (match) {
                                    this.errors.push({
                                        row: Number(match[1]) + 1,
                                        field: match[2],
                                        message: messages[0],
                                    });
                                }
                            });
                        } else {
                            Swal.fire(
                                "Error",
                                "Unexpected error occurred",
                                "error",
                            );
                        }
                    });
            });
        },
    },
};
</script>

<style scoped>
select {
    width: 100%;
}
input {
    width: 100%;
}
input[type="month"] {
    width: 100%;
}
textarea {
    min-width: 100%;
    max-width: 300px;
    width: 100%;
}
.uploading {
    margin: auto;
}
.upload-box {
    cursor: pointer;
}
table {
    width: 100%;
    border-collapse: collapse;
}
button {
    padding: 6px 12px;
    cursor: pointer;
}
.skeleton {
    width: 100%;
    background: linear-gradient(90deg, #e0e0e0 25%, #f5f5f5 37%, #e0e0e0 63%);
    background-size: 400% 100%;
    animation: shimmer 1.4s ease infinite;
    border-radius: 4px;
}
.skeleton-header {
    height: 18px;
}
.skeleton-cell {
    height: 14px;
}
@keyframes shimmer {
    0% {
        background-position: 100% 0;
    }
    100% {
        background-position: 0 0;
    }
}
</style>
