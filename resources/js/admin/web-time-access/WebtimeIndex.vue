<template>
    <div>

        <WebtimeModal 
            :selected_ids="selectedEmployees" 
            ref="webtimeModal" 
        />
        <WebtimeShowModal
            ref="historyModal"
            @use-latest="showModal"
        />


        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12 col-md-2 mb-3">
                <label class="mb-3 text-body">Filter By Employment Type</label>
                <select
                    v-model="employment_type"
                    class="form-select text-uppercase"
                    @change="onFilterChange"
                >
                    <option value="">- CHOOSE -</option>
                    <option
                        v-for="e in employment_types"
                        :key="e.id"
                        :value="e.id"
                    >
                        {{ e.name.toUpperCase() }}
                    </option>
                </select>
            </div>

            <div class="col-12 col-md-3 mb-3">
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

            <div class="col-12 col-md-3 mb-3">
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

            <div class="col-12 col-md-2 mb-3">
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
            <div class="col-12 col-md-2 mb-3 d-flex flex-column justify-content-end">
                <label class="mb-3 text-body">Bulk Set Schedule</label>
                <button
                    id="bulkActionBtn"
                    class="btn btn-primary"
                    :disabled="!anySelected"
                    @click="openModal"
                >
                    <i class="fas fa-calendar-alt me-2"></i>
                    Set schedule
                </button>
            </div>
        </div>

        <!-- Table -->
        <table id="myTable" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>
                        <input
                            type="checkbox"
                            id="selectAll"
                            class="me-1"
                            @change="toggleSelectAll($event)"
                        />
                    </th>
                    <th>Profile</th>
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

<script>
import axios from "axios";
import WebtimeModal from "./WebtimeModal.vue";
import WebtimeShowModal from "./WebtimeShowModal.vue";

export default {
    name: "WebtimeIndex",
    components: { WebtimeModal, WebtimeShowModal },
    props: {
        url: { type: String, required: true },
    },
    data() {
        return {
            division: "",
            unit: "",
            account_status: "active",
            employment_type: "",

            divisions: [],
            units: [],
            employment_types: [],

            table: null,
            token: localStorage.getItem("auth_token"),

            selectedEmployees: [], // Reactive selection
        };
    },
    computed: {
        anySelected() {
            return this.selectedEmployees.length > 1;
        },
    },
    mounted() {
        // Expose toggleEmployee globally for DataTables render
        window.vueApp = this;

        this.loadEmploymentTypes();
        this.loadDivisions();
        this.initTable();
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
                        d.employment_type = this.employment_type;
                    },
                },
                columns: [
                    {
                        data: "employee_no",
                        orderable: false,
                        searchable: false,
                        width: "8px",
                        render: (data) => {
                            const checked = this.selectedEmployees.includes(
                                data
                            )
                                ? "checked"
                                : "";
                            return `<input type="checkbox" class="employee-checkbox" value="${data}" ${checked} onchange="window.vueApp.toggleEmployee('${data}', this.checked)">`;
                        },
                    },
                    { data: "profile" },
                    { data: "employee_no" },
                    { data: "name" },
                    { data: "date_hired" },
                    {
                        data: "actions",
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => {
                            return `
                                <button title="history" class="btn btn-info text-light" onclick="window.vueApp.showModal('${row.employee_no}')">
                                    <i class="fas fa-history"></i>
                                </button>
                                <button title="set schedule" class="btn btn-primary" 
                                        title="Schedule"
                                        onclick="window.vueApp.editEmployee('${row.employee_no}')">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            `;
                        },
                    },
                ],
                pageLength: 25, // show all entries
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"],
                ],
                scrollX: true,
                autoWidth: false,
            });

            // Keep checkbox state when table redraws
            this.table.on("draw", () => {
                $(".employee-checkbox").each((_, el) => {
                    el.checked = this.selectedEmployees.includes(el.value);
                });
                const allChecked =
                    $(".employee-checkbox").length ===
                    $(".employee-checkbox:checked").length;
                $("#selectAll").prop("checked", allChecked);
            });
        },

        editEmployee(employee_no) {
            this.selectedEmployees = [];
            this.selectedEmployees.push(employee_no);
            $(".employee-checkbox").prop("checked", false);
            $("#selectAll").prop("checked", false);
            this.openModal();
        },

        openModal(){
            this.$refs.webtimeModal.open();
        },

        showModal(employee_no) {
            this.$refs.historyModal.open(employee_no);
        },

        /** ===============================
         * Selection Handlers
         * =============================== */
        toggleEmployee(id, checked) {
            if (checked) {
                if (!this.selectedEmployees.includes(id))
                    this.selectedEmployees.push(id);
            } else {
                this.selectedEmployees = this.selectedEmployees.filter(
                    (e) => e !== id
                );
            }
        },

        toggleSelectAll(event) {
            const checked = event.target.checked;
            if (checked) {
                this.selectedEmployees = [];
                $(".employee-checkbox").each((_, el) => {
                    this.selectedEmployees.push(el.value);
                    el.checked = true;
                });
            } else {
                $(".employee-checkbox").prop("checked", false);
                this.selectedEmployees = [];
            }
        },

        getSelectedEmployees() {
            return this.selectedEmployees;
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

            this.selectedEmployees = [];
            $(".employee-checkbox").prop("checked", false);
            $("#selectAll").prop("checked", false);

            this.reloadTable();
        },

        /** ===============================
         * API Calls
         * =============================== */
        async loadEmploymentTypes() {
            const response = await axios.get("/api/employment-types", {
                headers: { Authorization: `Bearer ${this.token}` },
            });
            this.employment_types = response.data.data;
        },

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
    },
};
</script>
