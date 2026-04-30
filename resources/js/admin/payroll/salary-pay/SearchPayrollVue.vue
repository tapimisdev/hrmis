<template>
    <div class="position-relative">
        <div class="row mb-3">
            <div
                v-for="(field, index) in visibleSelects"
                :key="index"
                class="col-12 col-md-2 mb-3"
            >
                <div class="d-flex gap-2">
                    <label class="form-label fw-bold">{{ field.label }}</label>
                    <span v-if="errors[field.model]" class="text-danger small">
                        ({{ errors[field.model][0] }})
                    </span>
                </div>
                <select
                    class="form-select"
                    v-model="form[field.model]"
                    :class="{ 'is-invalid': errors[field.model] }"
                    @change="search"
                >
                    <option v-if="field.placeholder" value="">{{ field.placeholder }}</option>
                    <option
                        v-for="(option, i) in field.options"
                        :key="i"
                        :value="option.value ?? option"
                    >
                        {{ option.text ?? option }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import LoaderVue from "./../../../components/LoaderVue.vue";
export default {
    components: { LoaderVue },
    data() {
        const token = localStorage.getItem("auth_token");

        const years = Array.from(
            { length: 5 },
            (_, i) => new Date().getFullYear() - i
        );
        const months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December",
        ];

        return {
            token,
            loading: false,
            errors: {},
            dataStorage: [],
            form: {
                employment_type: 1,
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1,
                cutoff: "all",
                status: "draft",
            },
            selects: [
                {
                    label: "Employment Type",
                    model: "employment_type",
                    options: [
                        { text: 'regular', value: 1 },
                        { text: 'contractual', value: 2 },
                    ],
                },
                {
                    label: "Year",
                    model: "year",
                    options: years,
                },
                {
                    label: "Month",
                    model: "month",
                    options: months.map((m, i) => ({ text: m, value: i + 1 })),
                },
                {
                    label: "Cutoff",
                    model: "cutoff",
                    options: [
                        { text: "All Cutoffs", value: "all" },
                        { text: "1st Cutoff", value: "first_cutoff" },
                        { text: "2nd Cutoff", value: "second_cutoff" },
                    ],
                },
                {
                    label: "Status",
                    model: "status",
                    placeholder: "All statuses",
                    options: [
                        { text: "Draft", value: "draft" },
                        { text: "Pending Review", value: "pending" },
                        { text: "Approved", value: "approved" },
                        { text: "For Releasing", value: "for_releasing" },
                        { text: "Completed", value: "completed" },
                        { text: "Cancelled", value: "cancelled" },
                    ],
                },
            ],
        };
    },

    methods: {
        fetchData(stateKey, url, useDataWrapper = false) {
            axios
                .get(url, {
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then((response) => {
                    this[stateKey] = useDataWrapper
                        ? response.data.data
                        : response.data;
                })
                .catch(() => {});
        },
        search() {
            this.errors = {};
            this.loading = true;
            this.$emit("payroll-list", this.dataStorage, this.loading);

            const payload = this.normalizedForm();

            axios
                .post("/api/payroll/salary-pay/processed", payload, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${this.token}`,
                    },
                })
                .then((response) => {
                    this.dataStorage = response.data.data;
                    this.$emit("payroll-list", response.data.data, false);
                    this.loading = false;
                })
                .catch((error) => {
                    if (error.response?.status === 422) {
                        this.errors = error.response.data.errors;
                    } else {
                        Swal.fire(
                            "Error",
                            error.response?.data?.message ||
                                "Something went wrong.",
                            "error"
                        );
                    }
                    this.loading = false;
                    this.$emit("payroll-list", [], false);
                });
        },
        normalizedForm() {
            return {
                ...this.form,
                employment_type: this.toNumberOrValue(this.form.employment_type),
                year: this.toNumberOrValue(this.form.year),
                month: this.toNumberOrValue(this.form.month),
                cutoff: this.apiCutoffValue(this.form.cutoff),
            };
        },
        toNumberOrValue(value) {
            if (value === "" || value === null || value === undefined) {
                return value;
            }

            const number = Number(value);
            return Number.isNaN(number) ? value : number;
        },
        normalizedCutoff(value) {
            if (value === "" || value === null || value === undefined) {
                return "";
            }

            const normalized = String(value);
            const aliases = {
                0: "all",
                all: "all",
                1: "second_cutoff",
                first: "first_cutoff",
                second: "second_cutoff",
            };

            return aliases[normalized] ?? normalized;
        },
        apiCutoffValue(value) {
            const cutoff = this.normalizedCutoff(value);
            return cutoff === "all" ? "" : cutoff;
        },
        applyQueryFilters() {
            const params = new URLSearchParams(window.location.search);

            for (const [key, value] of params.entries()) {
                if (!Object.prototype.hasOwnProperty.call(this.form, key)) {
                    continue;
                }

                if (key === "cutoff") {
                    this.form[key] = this.normalizedCutoff(value);
                    continue;
                }

                this.form[key] = ["employment_type", "year", "month"].includes(key)
                    ? this.toNumberOrValue(value)
                    : value;
            }

            if (this.isRegular) {
                this.form.cutoff = "";
            } else if (!this.form.cutoff) {
                this.form.cutoff = "all";
            }
        },
        syncQueryString() {
            const params = new URLSearchParams();
            const form = {
                ...this.normalizedForm(),
                cutoff: this.normalizedCutoff(this.form.cutoff),
            };

            Object.entries(form).forEach(([key, value]) => {
                if (value === "" || value === null || value === undefined) {
                    return;
                }

                params.set(key, value);
            });

            const query = params.toString();
            const newUrl = query
                ? `${window.location.pathname}?${query}`
                : window.location.pathname;

            window.history.replaceState({}, "", newUrl);
        },
    },
    computed: {
        isRegular() {
            return String(this.form.employment_type) === "1";
        },
        visibleSelects() {
            return this.selects.filter((field) => {
                return field.model !== "cutoff" || !this.isRegular;
            });
        },
    },

    watch: {
        "form.employment_type"() {
            if (this.isRegular) {
                this.form.cutoff = "";
            } else if (!this.form.cutoff) {
                this.form.cutoff = "all";
            }
        },
        form: {
            deep: true,
            handler() {
                this.syncQueryString();
            },
        },
    },

    mounted() {
        this.applyQueryFilters();
        this.syncQueryString();
        this.search();
    },
};
</script>
