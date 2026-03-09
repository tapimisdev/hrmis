<template>
    <div
        class="modal fade"
        id="correctionModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content modern-modal">
                <div class="modal-header modern-header border-bottom">
                    <div class="header-content mb-0 d-flex align-items-center">
                        <div class="icon-wrapper me-2">
                            <i class="text-light fas fa-clock"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">All Correction Requests</h5>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        @click="closeModal"
                    ></button>
                </div>

                <div class="modal-body">
                    <div v-if="loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div v-else>
                        <div class="table-responsive">
                            <table
                                id="correctionRequestsTable"
                                class="table table-striped table-bordered table-hover w-100"
                            >
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Reference No.</th>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Break Out</th>
                                        <th>Break In</th>
                                        <th>Time Out</th>
                                        <th>Overtime In</th>
                                        <th>Overtime Out</th>
                                        <th>Status</th>
                                        <th>Attachment</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        style="font-size: 12px"
                        class="btn py-2 px-4 btn-danger text-uppercase fw-medium"
                        data-bs-dismiss="modal"
                        @click="closeModal"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: "CorrectionList",

    data() {
        return {
            month: null,
            year: null,
            searchable: null,
            dataTable: null,
        };
    },

    methods: {
        open(month, year, searchable) {
            this.month = month;
            this.year = year;
            this.searchable = searchable;

            $("#correctionModal").modal("show");

            this.$nextTick(() => {
                this.initDataTable();
            });
        },

        initDataTable() {
            const vm = this;

            if ($.fn.DataTable.isDataTable("#correctionRequestsTable")) {
                $("#correctionRequestsTable").DataTable().destroy();
            }

            this.dataTable = $("#correctionRequestsTable").DataTable({
                processing: true,
                serverSide: true,
                order: [[0, "desc"]],
                scrollX: true,
                autoWidth: false,

                ajax: {
                    url: "/api/view-correction",
                    type: "GET",
                    headers: {
                        Authorization:
                            "Bearer " + localStorage.getItem("auth_token"),
                    },
                    data(d) {
                        d.month = vm.month;
                        d.year = vm.year;

                        if (vm.searchable && vm.searchable.trim() !== "") {
                            d.searchable = vm.searchable.trim();
                        }
                    },
                },

                columns: [
                    { data: "id", name: "id", visible: false },
                    { data: "reference_no", name: "reference_no" },
                    { data: "date", name: "date" },
                    { data: "time_in", name: "time_in" },
                    { data: "break_out", name: "break_out" },
                    { data: "break_in", name: "break_in" },
                    { data: "time_out", name: "time_out" },
                    { data: "overtime_in", name: "overtime_in" },
                    { data: "overtime_out", name: "overtime_out" },
                    { data: "status", name: "status" },
                    {
                        data: "attachment",
                        name: "attachment",
                        orderable: false,
                        searchable: false,
                    },
                    { data: "action_remarks", name: "action_remarks" },
                ],

                columnDefs: [
                    {
                        targets: [1, 11],
                        className: "min-table-width",
                    },
                    {
                        targets: [3, 4, 5, 6, 7, 8],
                        width: "200px",
                    },
                ],
            });

            if (vm.searchable && vm.searchable.trim() !== "") {
                this.dataTable.search(vm.searchable.trim()).draw();
            }

            $("#correctionModal").on("shown.bs.modal", () => {
                this.dataTable.columns.adjust();
            });
        },

        closeModal() {
            if (this.dataTable) {
                this.dataTable.destroy();
                this.dataTable = null;
            }

            const url = new URL(window.location.href);

            url.searchParams.delete("view-corrections");
            url.searchParams.delete("reference-no");

            window.history.replaceState({}, document.title, url.toString());
            $("#correctionModal").modal("hide");
            this.$emit("clearSearchable");
        },
    },
};
</script>

<style scoped>
.badge {
    font-size: 10px;
    padding: 8px 20px;
    text-transform: uppercase;
}
.table {
    font-size: 0.9rem;
}

th {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: bold;
}

td {
    font-size: 12px;
    vertical-align: middle;

    a {
        width: 100%;
        text-align: center;
        font-size: 12px;
        text-transform: uppercase;
    }
}
</style>
