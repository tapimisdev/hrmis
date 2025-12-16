<template>
    <div class="table-responsive px-1">
        <table
            class="table table-sm table-striped align-middle position-relative"
        >
            <LoaderVue
                :visible="loading"
                status="uploading"
                message="Uploading, please wait..."
                style="top: 200px"
            />
            <thead>
                <tr>
                    <th>Payroll Details</th>
                    <th>Period</th>
                    <th>Emp Count</th>
                    <th>Financial Summary</th>
                    <th>Status</th>
                    <th>Processed Info</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="payroll in payrolls" :key="payroll.id">
                    <td>
                        <div>
                            <strong>{{ payroll.label }}</strong>
                        </div>
                        <div class="text-muted small">
                            {{ payroll.employment_code }} -
                            {{ payroll.employment_name }}
                        </div>
                        <div class="text-muted small">
                            Ref: {{ payroll.payroll_no }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <span
                                :class="{
                                    'badge bg-success':
                                        payroll.cutoff === 'first_cutoff',
                                    'badge bg-danger':
                                        payroll.cutoff === 'second_cutoff',
                                }"
                            >
                                {{ payroll.cutoff.replace("_", " ") }}
                            </span>
                        </div>
                        <div class="small mt-1">
                            {{ payroll.period_covered }}
                        </div>
                    </td>
                    <td>{{ payroll.no_employee }} Employees</td>
                    <td>
                        <div>
                            <strong>Net:</strong>
                            {{ formatCurrency(payroll.netpay_amount) }}
                        </div>
                        <div class="small text-muted">
                            Gross: {{ formatCurrency(payroll.gross_amount) }}
                        </div>
                        <div class="small text-muted">
                            Deduct:
                            {{ formatCurrency(payroll.deduction_amount) }}
                        </div>
                    </td>
                    <td>
                        <span
                            :class="{
                                'badge bg-warning bg-opacity-10 text-warning border border-warning':
                                    payroll.status === 'draft',
                                'badge bg-secondary bg-opacity-10 text-secondary border border-secondary':
                                    payroll.status === 'pending_approval',
                                'badge bg-success bg-opacity-10 text-success border border-success':
                                    payroll.status === 'approved',
                                'badge bg-danger bg-opacity-10 text-danger border border-danger':
                                    payroll.status === 'cancelled',
                                'badge bg-info bg-opacity-10 text-info border border-info':
                                    payroll.status === 'completed',
                                'badge bg-dark bg-opacity-10 text-dark border border-dark':
                                    payroll.status === 'failed',
                            }"
                        >
                            {{ payroll.status.replace("_", " ") }}
                        </span>
                    </td>
                    <td>
                        <div class="small fw-medium">
                            {{ payroll.processed_by }}
                        </div>
                        <div class="text-muted small opacity-75">
                            {{
                                new Date(
                                    payroll.payroll_date
                                ).toLocaleDateString("en-PH", {
                                    year: "numeric",
                                    month: "short",
                                    day: "numeric",
                                })
                            }}
                        </div>
                    </td>
                    <td>
                        <a
                            :href="`/admin/payroll/salary-pay/${payroll.payroll_no}?batch_id=${payroll.batch_id}`"
                            class="btn btn-sm btn-primary me-1"
                            title="Manage"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-title="Manage Payroll"
                        >
                            <i class="fa fa-cogs"></i>
                        </a>
                        <button
                            @click="remove(payroll.id)"
                            class="btn btn-sm btn-danger"
                            title="Cancel"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-title="Cancel Payroll"
                        >
                            <i class="fa fa-ban"></i>
                        </button>
                    </td>
                </tr>
                <tr v-if="!loading && payrolls.length === 0">
                    <td colspan="7" class="text-center text-muted py-3">
                        No payroll records found.
                    </td>
                </tr>
                <tr v-if="loading">
                    <td colspan="7" class="text-center text-muted">
                        Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import LoaderVue from "../../../components/LoaderVue.vue";
export default {
    components: { LoaderVue },
    name: "TablePayrollVue",
    props: {
        payrolls: {
            type: Array,
            default: () => [],
        },
        loading: Boolean,
    },
    data() {
        return {
            error: null,
            token: localStorage.getItem("auth_token"),
        };
    },
    methods: {
        formatCurrency(value) {
            if (value == null) return "-";
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2,
            }).format(value);
        },
        remove(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    axios
                        .delete(`/api/payroll/salary-pay/${id}/delete`, {
                            headers: {
                                Authorization: `Bearer ${this.token}`,
                            },
                        })
                        .then((result) => {
                            const res = result.data;
                            if (res.status == 'success') {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Payroll has been successfully deleted!.",
                                    icon: "success",
                                }).then((ok) => {
                                    if (ok.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: "Oops!",
                                    text: 'Error: An error occurred while deleting the payroll.',
                                    icon: "error",
                                });
                            }
                        })
                        .catch((error) => {
                            Swal.fire({
                                title: "Oops!",
                                text: error.response.data.message,
                                icon: "error",
                            });
                        });
                }
            }); // swal end
        },
    },
};
</script>

<style lang="scss" scoped>
.table {
    font-size: 0.75rem;
    th {
        white-space: nowrap;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
        padding: 0.75rem 0.5rem;
    }

    td {
        vertical-align: middle;
        word-wrap: break-word;
        white-space: normal;
        padding: 0.875rem 0.5rem;
    }

    tbody tr {
        transition: background-color 0.15s ease;
        &:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
    }

    .badge {
        padding: 0.35rem 0.65rem;
        font-weight: 500;
        font-size: 0.7rem;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }

    .fw-semibold {
        font-weight: 600;
    }

    .fw-medium {
        font-weight: 500;
    }
}

// .card {
//   border: 1px solid #e9ecef;
//   border-radius: 0.5rem;
//   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
// }
</style>
