<template>
    <div class="uploading pb-5">
        <!-- Upload -->
        <div v-if="upload && !loading">
            <label class="form-label fw-bold">Upload File (CSV)</label>
            <div
                class="upload-box text-center p-5 border border-2 border-dashed rounded-3"
                @click="triggerFileInput"
            >
                <i
                    class="fa-solid fa-file-arrow-up fa-2x mb-3 text-primary"
                ></i>
                <p class="mb-1 fw-semibold">Click or drag file to upload</p>
                <small class="text-muted">Supported: CSV</small>
                <input
                    type="file"
                    class="d-none"
                    ref="fileInput"
                    accept=".csv"
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
            <div class="table-container table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th v-if="type === 'leave'">Leave</th>
                            <th>Employee No</th>
                            <th>Month & Year</th>
                            <th>Sick Leave</th>
                            <th>Vacation Leave</th>
                            <th>Total Credits</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(credit, index) in creditsData.credits"
                            :key="index"
                        >
                            <td>
                                <div class="px-3">{{ index + 1 }}</div>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="credit.employee_no"
                                />
                            </td>
                            <td>
                                <input
                                    type="month"
                                    class="form-control"
                                    v-model.number="credit.month_year"
                                />
                            </td>
                            <td>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model.number="credit.sick_leave"
                                />
                            </td>
                            <td>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model.number="credit.vacation_leave"
                                />
                            </td>
                            <td>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model.number="credit.total_credits"
                                />
                            </td>
                            <td>
                                <textarea
                                    class="form-control"
                                    rows="3"
                                    v-model="credit.remarks"
                                ></textarea>
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
        };
    },

    methods: {
        triggerFileInput() {
            this.$refs.fileInput.click();
        },

        onFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.loading = true;
            this.errors = [];

            const reader = new FileReader();
            reader.onload = (e) => {
                const rows = e.target.result
                    .split("\n")
                    .map((r) => r.trim())
                    .filter(Boolean);
                const headers = rows[0]
                    .split(",")
                    .map((h) => h.trim().toLowerCase().replace(/\s+/g, "_"));
                const credits = rows.slice(1).map((row) => {
                    const values = row.split(",");
                    const obj = {};
                    headers.forEach((h, i) => (obj[h] = values[i] || ""));
                    return {
                        month_year: obj.month_year || "",
                        employee_no: obj.employee_no || "",
                        sick_leave: Number(obj.sick_leave) || 0,
                        vacation_leave: Number(obj.vacation_leave || 0),
                        total_credits: Number(obj.total_credits || 0),
                        remarks: obj.remarks || "",
                    };
                });

                console.log(headers, credits);

                this.creditsData.credits = credits;
                this.loading = false;
                this.upload = false;
            };

            reader.readAsText(file);
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

                axios
                    .post(this.saveUrl, this.creditsData, {
                        headers: { Authorization: `Bearer ${token}` },
                    })
                    .then(() => {
                        Swal.fire({
                            icon: "success",
                            title: "Uploaded!",
                            text: "Credits saved successfully.",
                            timer: 3500,
                            showConfirmButton: true,
                            confirmButtonText: "Got it",
                        });
                        this.backToForm();
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
    width: 180px;
}
input {
    width: 150px;
}
input[type="month"] {
    width: 200px;
}
textarea {
    width: 250px;
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