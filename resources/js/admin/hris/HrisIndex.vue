<template>
    <div>
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12 col-md-4 mb-3">
                <label class="mb-3 text-body">Filter By Divisions</label>
                <select
                    v-model="division"
                    class="form-select text-uppercase"
                    @change="onFilterChange"
                >
                    <option value="">- CHOOSE -</option>
                    <option v-for="d in divisions" :key="d.id" :value="d.id">
                        {{ d.name.toUpperCase() }}
                    </option>
                </select>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label class="mb-3 text-body">Filter By Units</label>
                <select
                    v-model="unit"
                    class="form-select text-uppercase"
                    @change="onFilterChange"
                >
                    <option value="">- CHOOSE -</option>
                    <option v-for="u in units" :key="u.id" :value="u.id">
                        {{ u.name.toUpperCase() }}
                    </option>
                </select>
            </div>

            <div class="col-12 col-md-3 mb-3">
                <label class="mb-3 text-body">Filter By Account Status</label>
                <select
                    v-model="account_status"
                    class="form-select text-uppercase"
                    @change="onFilterChange"
                >
                    <option value="active">ACTIVE</option>
                    <option value="inactive">INACTIVE</option>
                    <option value="archived">ARCHIVED</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <table id="myTable" class="table table-striped w-100">
            <thead>
                <tr>
                    <th></th>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Date Hired</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</template>
hris.employee.index
<script>
import axios from "axios";

export default {
    name: "HRISIndex",
    props: {
        url: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            division: "",
            unit: "",
            account_status: "active",

            divisions: [],
            units: [],

            table: null,
            token: localStorage.getItem("auth_token"),
        };
    },

    mounted() {
        this.initTable();
        this.loadDivisions();
    },

    methods: {
        /** ===============================
         * DataTable Initialization
         * =============================== */
        initTable() {
            this.table = $("#myTable").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: this.url,
                    data: (d) => {
                        d.account_status = this.account_status;
                        d.division = this.division;
                        d.unit = this.unit;
                    },
                },
                columns: [
                    { data: "profile" },
                    { data: "employee_no" },
                    { data: "name" },
                    { data: "date_hired" },
                    { data: "actions", orderable: false, searchable: false },
                ],
            });

            // Download PDS
            $(document).on("click", ".download-pds", this.downloadPDS);
        },

        reloadTable() {
            if (this.table) this.table.ajax.reload();
        },

        onFilterChange() {
            if (this.division) {
                this.loadUnits(this.division);
            } else {
                this.units = [];
                this.unit = "";
            }

            this.reloadTable();
        },

        /** ===============================
         * API Calls
         * =============================== */
        async loadDivisions() {
            const { data } = await axios.get("/api/divisions", {
                headers: { Authorization: `Bearer ${this.token}` },
            });
            this.divisions = data;
        },

        async loadUnits(divisionId) {
            if (!divisionId) return;

            const { data } = await axios.get(`/api/units/${divisionId}`, {
                headers: { Authorization: `Bearer ${this.token}` },
            });

            this.units = data;
        },

        /** ===============================
         * Download Handler
         * =============================== */
        async downloadPDS(e) {
            const url = e.target.dataset.url;

            const response = await axios.get(url, {
                headers: { Authorization: `Bearer ${this.token}` },
                responseType: "blob",
            });

            const blobUrl = window.URL.createObjectURL(
                new Blob([response.data])
            );
            const a = document.createElement("a");

            a.href = blobUrl;
            a.download = "pds.xlsx";

            document.body.appendChild(a);
            a.click();
            a.remove();

            window.URL.revokeObjectURL(blobUrl);
        },
    },
};
</script>
