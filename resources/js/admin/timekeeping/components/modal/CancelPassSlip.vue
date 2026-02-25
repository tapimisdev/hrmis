<template>
    <div class="modal-body">
        <div class="modal-confirm">
            <i class="fa-solid fa-triangle-exclamation icon"></i>
            <p>
                Are you sure you want to cancel this employee's <strong><span class="text-uppercase">Approved</span></strong> Pass Slip?
            </p>

            <div class="modal-confirm-footer">
                <button
                    @click="cancel()"
                    class="btn py-3 px-4 btn-primary"
                >
                    <i class="fa-solid fa-check me-2"></i>
                    Confirm
                </button>
                <button @click="close" class="btn py-3 px-4 btn-danger">
                    <i class="fa-solid fa-times me-2"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script>
const token = localStorage.getItem("auth_token");
import axios from "axios";

export default {
    props: {
        employee_no: { type: String, required: true },
        month: Number,
        year: Number,
        index: Number,
    },
    data() {
        return {
            loading: true,
            errors: {},
        };
    },
    methods: {
        close() {
            $("#myModal").modal("hide");
        },
        async cancel() {
            this.loading = true;
            this.errors = {};

            // Format the date properly for backend (YYYY-MM-DD)
            const year = this.year ?? new Date().getFullYear();
            const month = this.month ?? new Date().getMonth() + 1;
            const day = this.index ?? new Date().getDate();
            const formattedDate = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

            const form = {
                employee_no: this.employee_no,
                date: formattedDate,
            };

            try {
                const res = await axios.post("/api/cancel-pass-slip", form, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                });

                Swal.fire({
                    title: "Pass Slip Cancelled",
                    text: "The employee's pass slip has been successfully cancelled.",
                    icon: "success",
                }).then(() => {
                    this.$emit("success", res.data);
                    this.close();
                    this.resetForm();
                });
            } catch (error) {
                Swal.fire(
                    "Error",
                    error.response?.data?.message || "Something went wrong.",
                    "error",
                );
            } finally {
                this.loading = false;
            }
        },
    },
    computed: {
        formattedDate(dateOnly = false) {
            const year = this.year ?? new Date().getFullYear();
            const month = this.month ?? new Date().getMonth() + 1;
            const day = this.index ?? new Date().getDate();
            const date = new Date(year, month - 1, day);

            if (dateOnly) {
                return date;
            }

            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        },
    },
};
</script>

<style lang="scss" scoped>
.modal-confirm {
    text-align: center;
    padding: 2rem;

    .icon {
        font-size: 3.5rem;
        color: var(--bs-secondary);
        margin-bottom: 1rem;
    }

    p {
        font-size: 1.0625rem;
        color: var(--bs-body-color);
        margin: 0 0 2rem 0;
        line-height: 1.5;
    }

    .modal-confirm-footer {
        display: flex;
        justify-content: center;
        gap: 0.5rem;

        .btn {
            font-size: 1rem;

            i {
                font-size: 1rem;
            }
        }
    }
}
</style>
