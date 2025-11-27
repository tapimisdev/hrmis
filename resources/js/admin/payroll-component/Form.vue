<template>
    <!-- Header -->
    <div
        class="card-header d-flex justify-content-between align-items-end pb-3"
    >
        <Printables />

        <div class="fw-bold display-6">
            {{ parent_table.year }}
        </div>

        <div
            class="search-pill d-flex align-items-center px-2 py-1 bg-body-bg rounded-pill"
        >
            <i class="fa-solid fa-magnifying-glass me-2"></i>
            <input
                type="text"
                class="form-control border px-3 py-1 small-text"
                v-model="search"
                @input="filteredItems"
                placeholder="Search"
                style="max-width: 220px"
            />
        </div>
    </div>

    <div
        class="shadow-sm border rounded-3 overflow-hidden modern-card position-relative"
    >
        <!-- Table -->
        <div class="table-wrapper custom-scrollbar">
            <LoaderVue
                :visible="loading"
                :hasBackground="true"
                status="loading"
                message="loading, please wait..."
            />
            <table v-if="!loading" class="table table-hover mb-0 compact-table">
                <thead>
                    <tr>
                        <th class="sticky-col ps-1">Employee</th>
                        <th class="sticky-header text-end gradient-text">
                            TOTAL
                        </th>
                        <th
                            class="sticky-header text-end text-muted-light"
                            v-for="month in months"
                            :key="month"
                        >
                            {{ month }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="items.length == 0">
                        <td colspan="14" class="text-center">
                            <div class="alert alert-secondary mx-2 mt-2 py-4">
                                No employee(s) found.
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="item in filtered"
                        :key="item.employee_no"
                        :data-employee_no="item.employee_no"
                    >
                        <td
                            class="sticky-col border-end ps-3"
                            :class="{
                                'bg-primary fw-bold':
                                    selected_employee === item.employee_no,
                            }"
                        >
                            <div class="d-flex align-items-center">
                                <div class="avatar">
                                    {{ item.firstname?.charAt(0) || "N"
                                    }}{{ item.lastname?.charAt(0) || "A" }}
                                </div>
                                <div class="ms-2">
                                    <span
                                        class="fw-bold text-body text-nowrap me-2"
                                    >
                                        {{ item.lastname ?? "-------" }},
                                        {{ item.firstname ?? "-------" }}
                                    </span>
                                    <br />
                                    <span class="badge text-body">{{
                                        item.employee_no
                                    }}</span>
                                    <span class="badge tiny-badge me-2">{{
                                        item.division_code
                                    }}</span>
                                </div>
                            </div>
                        </td>

                        <td
                            class="text-end total-score"
                            :class="{
                                'bg-primary fw-bold':
                                    selected_employee === item.employee_no,
                            }"
                        >
                            {{ line_total(item) }}
                        </td>

                        <td
                            v-for="monthKey in monthKeys"
                            :key="monthKey"
                            :class="{
                                'bg-primary fw-bold':
                                    selected_employee === item.employee_no,
                            }"
                        >
                            <input
                                type="number"
                                v-model="item[monthKey]"
                                @change="
                                    create_update(
                                        item[monthKey + '_id'],
                                        item[monthKey],
                                        monthKey,
                                        item.employee_no
                                    )
                                "
                                class="border-less-input"
                            />
                        </td>
                    </tr>
                    <tr class="grand-total">
                        <td class="sticky-col text-end fw-bold bg-body-color">
                            Grand Total
                        </td>
                        <td class="text-end">
                            {{ formatNumber(total_all_line_tota()) }}
                        </td>
                        <td v-for="monthKey in monthKeys" :key="monthKey">
                            <div class="text-end">
                                {{ formatNumber(grand_total(monthKey)) }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import LoaderVue from "../../components/LoaderVue.vue";
import Printables from "../../components/Printables.vue";
import axios from "axios";

export default {
    name: "PayrollEmployeeComponentForm", 
    components: { LoaderVue, Printables },
    props: {
        selected_employee: {
            type: String,
            required: false,
        },
        url: {
            type: String,
            required: true,
        },
        parent_table: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            months: [
                "Jan",
                "Feb",
                "Mar",
                "Apr",
                "May",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec",
            ],
            monthKeys: [
                "january",
                "february",
                "march",
                "april",
                "may",
                "june",
                "july",
                "august",
                "september",
                "october",
                "november",
                "december",
            ],
            items: [],
            filtered: [],
            isFetched: false,
            search: "",
            loading: false,
        };
    },
    created() {
        this.fetchTable();
        this.isFetched = true;
    },
    methods: {
        fetchTable() {
            this.loading = true;
            axios
                .get(this.url)
                .then((res) => {
                    this.items = res.data;
                    this.filtered = res.data;
                    this.filteredItems();
                })
                .catch((error) => {
                    console.log(error);
                    ErrorToast.fire({
                        title:
                            error.response?.data?.error ||
                            error.response?.data?.message ||
                            "An error occurred",
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        create_update(id, amount, month, employee_no) {
            this.loading = true;
            axios
                .post(this.url, {
                    id: id,
                    amount: amount,
                    month: month,
                    employee_no: employee_no,
                })
                .then((res) => {
                    this.fetchTable();
                    SuccesToast.fire({
                        title: res.data.message || "successfully added!",
                    });
                })
                .catch((error) => {
                    console.log(error);
                    ErrorToast.fire({
                        title:
                            error.response?.data?.error ||
                            error.response?.data?.message ||
                            "An error occurred",
                    }).finally(() => {
                        this.loading = false;
                    });
                });
        },
        line_total(employee) {
            let line_total = 0;

            this.monthKeys.forEach((month) => {
                line_total += parseFloat(employee[month]) || 0;
            });

            return line_total;
        },
        total_all_line_tota() {
            let total = 0;

            this.filtered.forEach((item) => {
                total += parseFloat(this.line_total(item)) ?? 0;
            });

            return total;
        },
        grand_total(month) {
            return this.filtered.reduce((sum, item) => {
                return sum + (parseFloat(item[month]) || 0);
            }, 0);
        },
        formatNumber(number) {
            return Number(number).toLocaleString();
        },
        filteredItems() {
            const query = this.search.toLowerCase().trim();

            if (query == null) return this.items;

            this.filtered = this.items.filter(
                (item) =>
                    item.firstname.toLowerCase().includes(query) ||
                    item.lastname.toLowerCase().includes(query) ||
                    item.division_code.toLowerCase().includes(query) ||
                    item.division_name.toLowerCase().includes(query)
            );
        },
    },
};
</script>

<style scoped lang="scss">
.small-title {
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    color: var(--bs-body-color, #212529);
}
.tiny-text {
    font-size: 0.7rem;
    color: var(--bs-secondary-text, #6c757d);
}
.search-pill input {
    font-size: 0.75rem;
    color: var(--bs-body-color, #212529);
}

/* Table */
.table-wrapper {
    max-height: 70vh;
    overflow: auto;
}
.compact-table {
    border-collapse: separate;
    th,
    td {
        padding: 0.4rem 0.625rem;
        font-size: 0.7rem;
    }
    th {
        background: var(--bs-body-bg, #fff);
        font-weight: 600;
        text-transform: uppercase;
        color: var(--bs-body-color, #212529);
    }
}

/* Sticky & Glass */
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--bs-body-bg, #fff);
}
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 20;
    background: var(--bs-body-bg, #fff);
    min-width: 320px;
}

.gradient-text {
    color: var(--bs-body-bg);
}
.total-score {
    background: rgba(var(--bs-primary-rgb, 79, 70, 229), 0.1);
    color: var(--bs-primary);
    padding: 0.125rem 0.5rem;
    font-weight: 700;
    font-size: 0.8rem;
}

/* Avatar */
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 100%;
    background-color: var(--bs-secondary);
    color: var(--bs-body-bg);
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Badge */
.tiny-badge {
    font-size: 0.6rem;
    padding: 0.125rem 0.375rem;
    background: var(--bs-body-color);
    color: var(--bs-body-bg);
}

/* Hover effect */
.row-hover {
    &:hover {
        background: var(--bs-light);
    }
}

/* Mono font for data cells */
.mono-cell {
    color: var(--bs-body-color);
    font-size: 0.72rem;
}

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: var(--bs-body-secondary);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--bs-primary);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--bs-primary);
}

.border-less-input {
    margin: 0;
    padding: 0;
    height: 100%;
    width: 100%;
    // width: 86px;
    background-color: var(--bs-transparent);
    border: none;
    outline: none;
    text-align: end;
    &:focus,
    &:active {
        border: none;
        outline: none;
        box-shadow: none;
    }
    /* Chrome, Edge, Safari */
    &::-webkit-outer-spin-button,
    &::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    -moz-appearance: textfield;
}

.grand-total {
    td {
        background-color: var(--bs-secondary-bg);
        font-weight: bolder;

        &:not(:first-child) {
            background-color: var(--bs-secondary-bg);
            color: var(--bs-body-color);
        }
    }
}
</style>
