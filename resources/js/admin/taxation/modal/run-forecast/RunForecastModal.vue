<template>
    <ModalVue
        ref="addModal"
        headerIcon="fa-solid fa-calculator"
        title="Run Forecast"
        id="add-modal"
        size="modal-lg"
        subtitle="Generate and calculate employee tax forecasts based on the selected year, period, and assumptions."
        type="default"
    >
        <div class="modal-body">
            <form @submit.prevent>
                <!-- TABS -->
                <div class="fb-tabs border-bottom mb-3">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="fb-tab"
                        :class="{
                            active: activeTab === t.key,
                            'has-error': tabHasErrors(t.key),
                        }"
                        @click="goTab(t.key, true)"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <!-- TAB A -->
                <TabALists
                    v-if="activeTab === 'A'"
                    :loading="loading"
                    :options="options"
                    v-model="form"
                    :errors="errors"
                />

                <!-- TAB B -->
                <TabBAssumptions
                    v-if="activeTab === 'B'"
                    v-model="form"
                    :errors="errors"
                />

                <!-- TAB C -->
                <div v-if="activeTab === 'C'">
                    <TabCAllocation v-model="form.allocation" :errors="errors" />
                    <ForecastPreview class="mt-3" :form="form" :options="options" />
                </div>
            </form>

            <!-- FOOTER -->
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button
                    type="button"
                    class="fb-btn fb-secondary"
                    @click="prevTab"
                    :disabled="activeTab === 'A' || loading.next"
                >
                    <i class="fa-solid fa-chevron-left me-1"></i> Back
                </button>

                <div class="d-flex gap-2">
                    <button
                        type="button"
                        class="fb-btn bg-danger"
                        @click="close"
                        :disabled="loading.next || loading.submit"
                    >
                        Cancel
                    </button>

                    <button
                        v-if="activeTab !== 'C'"
                        type="button"
                        class="fb-btn fb-primary"
                        @click="nextTab"
                        :disabled="loading.next"
                    >
                        <span
                            v-if="loading.next"
                            class="spinner-border spinner-border-sm me-2"
                        ></span>
                        Next <i class="fa-solid fa-chevron-right ms-1"></i>
                    </button>

                    <button
                        v-else
                        type="button"
                        class="fb-btn fb-primary"
                        @click="submitForecast"
                        :disabled="loading.submit"
                    >
                        <span
                            v-if="loading.submit"
                            class="spinner-border spinner-border-sm me-2"
                        ></span>
                        Run Forecast
                    </button>
                </div>
            </div>
        </div>
    </ModalVue>
</template>

<script>
import axios from "axios";
import ModalVue from "../../../../components/ModalVue.vue";

import TabALists from "./tabs/TabALists.vue";
import TabBAssumptions from "./tabs/TabBAssumptions.vue";
import TabCAllocation from "./tabs/TabCAllocation.vue";
import ForecastPreview from "./tabs/ForecastPreview.vue";

const token = localStorage.getItem("auth_token");

export default {
    name: "RunForecastModal",
    props: {
        selectedYear: String
    },
    components: {
        ModalVue,
        TabALists,
        TabBAssumptions,
        TabCAllocation,
        ForecastPreview,
    },

    data() {
        return {
            tabs: [
                { key: "A", label: "Tab A" },
                { key: "B", label: "Tab B" },
                { key: "C", label: "Tab C" },
            ],

            activeTab: "A",

            loading: {
                hazard: false,
                salary: false,
                longevity: false,
                train: false,
                next: false,
                submit: false,
            },

            options: {
                hazardTax: [],
                salaryTax: [],
                longevityTax: [],
                trainLaw: [],
            },

            errors: {},

            form: {
                year: this.selectedYear,
                hazardTaxId: "",
                salaryTaxId: "",
                longevityTaxId: "",
                trainLawId: "",

                assumptions: {
                    basicPay: true,
                    midYear: false,
                    yearEnd: false,
                    longevity: false,
                    hazardPay: false,
                    lessBirRR32015: false,
                },

                othersEarnings: [],
                deductions: { gsis: true, philhealth: true, pagibig: true },
                othersDeductions: [],

                allocation: {
                    hazardPayPct: 65,
                    basicPayPct: 20,
                    longevityPct: 15,
                },
            },
        };
    },

    mounted() {
        this.fetchAllDropdowns();
    },

    methods: {
        handleOpenaddModal() {
            this.resetState();
            this.$refs.addModal.open();
            this.fetchAllDropdowns();
        },

        close() {
            this.$refs.addModal?.close?.();
        },

        // =============================
        // TAB ERROR LOGIC (ROBUST)
        // - supports snake_case + camelCase
        // - supports nested keys (allocation.hazard_pay_pct)
        // - supports arrays (others_earnings.0.amount)
        // =============================
        tabFieldMap() {
            return {
                A: [
                    // camelCase (frontend)
                    "hazardTaxId",
                    "salaryTaxId",
                    "longevityTaxId",
                    "trainLawId",

                    // snake_case (backend direct)
                    "hazard_tax_id",
                    "salary_tax_id",
                    "longevity_tax_id",
                    "train_law_id",

                    // nested (if backend groups them)
                    "tax_settings.hazard_tax_id",
                    "tax_settings.salary_tax_id",
                    "tax_settings.longevity_tax_id",
                    "tax_settings.train_law_id",
                ],

                B: [
                    // camel
                    "assumptions.basicPay",
                    "assumptions.midYear",
                    "assumptions.yearEnd",
                    "assumptions.longevity",
                    "assumptions.hazardPay",
                    "assumptions.lessBirRR32015",

                    // snake
                    "assumptions.basic_pay",
                    "assumptions.mid_year",
                    "assumptions.year_end",
                    "assumptions.longevity",
                    "assumptions.hazard_pay",
                    "assumptions.less_bir_rr3_2015",

                    // EARNINGS (arrays)
                    // frontend style
                    "othersEarnings",
                    "othersEarnings.*.name",
                    "othersEarnings.*.amount",

                    // backend style (common)
                    "others_earnings",
                    "others_earnings.*.name",
                    "others_earnings.*.amount",

                    // backend nested style (your earlier rules)
                    "earnings.others",
                    "earnings.others.*.name",
                    "earnings.others.*.amount",

                    // DEDUCTIONS (arrays)
                    "othersDeductions",
                    "othersDeductions.*.name",
                    "othersDeductions.*.amount",

                    "others_deductions",
                    "others_deductions.*.name",
                    "others_deductions.*.amount",

                    "deductions.others",
                    "deductions.others.*.name",
                    "deductions.others.*.amount",
                ],

                C: [
                    
                    "allocation",

                    // camel
                    "allocation.hazardPayPct",
                    "allocation.basicPayPct",
                    "allocation.longevityPct",

                    // snake
                    "allocation.hazard_pay_pct",
                    "allocation.basic_pay_pct",
                    "allocation.longevity_pct",
                ],
            };
        },


        toSnake(str) {
            // hazardPayPct -> hazard_pay_pct
            return str.replace(/[A-Z]/g, (m) => "_" + m.toLowerCase());
        },

        toCamel(str) {
            // hazard_pay_pct -> hazardPayPct
            return str.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
        },

        normalizeKeyVariants(key) {
            const variants = new Set([key]);

            const parts = String(key).split(".");
            variants.add(parts.map((p) => this.toCamel(p)).join("."));
            variants.add(parts.map((p) => this.toSnake(p)).join("."));

            variants.add(this.toCamel(String(key)));
            variants.add(this.toSnake(String(key)));

            return [...variants];
        },

        isKeyInTab(errKey, tabKey) {
            const fields = this.tabFieldMap()[tabKey] || [];
            const errVariants = this.normalizeKeyVariants(errKey);

            // match exact OR prefix (for arrays / nested)
            return errVariants.some((k) =>
                fields.some((f) => k === f || k.startsWith(f + "."))
            );
        },

        tabHasErrors(tabKey) {
            const keys = Object.keys(this.errors || {});
            return keys.some((k) => this.isKeyInTab(k, tabKey));
        },

        firstErrorTab() {
            const order = ["A", "B", "C"];
            return order.find((t) => this.tabHasErrors(t)) || null;
        },

        goToFirstErrorTab() {
            const tab = this.firstErrorTab();
            if (tab) this.activeTab = tab;
        },
        // =============================

        // tab click just navigates (keep errors so red stays visible)
        goTab(key, allowDirect = false) {
            if (!allowDirect) return;
            this.activeTab = key;
        },

        prevTab() {
            if (this.activeTab === "B") this.activeTab = "A";
            else if (this.activeTab === "C") this.activeTab = "B";
        },

        async nextTab() {
            this.loading.next = true;
            try {
                if (this.activeTab === "A") this.activeTab = "B";
                else if (this.activeTab === "B") this.activeTab = "C";
            } finally {
                this.loading.next = false;
            }
        },

        async fetchAllDropdowns() {
            await Promise.all([
                this.fetchHazardTax(),
                this.fetchSalaryTax(),
                this.fetchLongevityTax(),
                this.fetchTrainLaw(),
            ]);
        },

        async fetchHazardTax() {
            this.loading.hazard = true;
            try {
                const res = await axios.get("/api/tax/hazard-tax-lists", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.options.hazardTax = Array.isArray(res.data)
                    ? res.data
                    : res.data?.data || [];
            } finally {
                this.loading.hazard = false;
            }
        },

        async fetchSalaryTax() {
            this.loading.salary = true;
            try {
                const res = await axios.get("/api/tax/salary-tax-lists", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.options.salaryTax = Array.isArray(res.data)
                    ? res.data
                    : res.data?.data || [];
            } finally {
                this.loading.salary = false;
            }
        },

        async fetchLongevityTax() {
            this.loading.longevity = true;
            try {
                const res = await axios.get("/api/tax/longevity-tax-lists", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.options.longevityTax = Array.isArray(res.data)
                    ? res.data
                    : res.data?.data || [];
            } finally {
                this.loading.longevity = false;
            }
        },

        async fetchTrainLaw() {
            this.loading.train = true;
            try {
                const res = await axios.get("/api/tax/train-law-lists", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                this.options.trainLaw = Array.isArray(res.data)
                    ? res.data
                    : res.data?.data || [];
            } finally {
                this.loading.train = false;
            }
        },

        // Submit only; backend returns 422 if invalid
        async submitForecast() {
            this.loading.submit = true;
            this.errors = {};

            try {
                const res = await axios.post("/api/tax/run-forecast", this.form, {
                    headers: { Authorization: `Bearer ${token}` },
                });

                this.$emit("forecast-ran", res.data);
                this.close();
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};

                    // ✅ go to the first tab that has errors
                    this.goToFirstErrorTab();

                    // If still not moving, it means your error keys are totally different.
                    // Quick debug (remove after):
                    // console.log("Validation errors keys:", Object.keys(this.errors));
                } else {
                    Swal.fire(
                        "Error",
                        error.response?.data?.message || String(error),
                        "error"
                    );
                }
                this.$emit("payroll-list", [], false);
            } finally {
                this.loading.submit = false;
            }
        },

        resetState() {
            this.activeTab = "A";
            this.errors = {};

            this.form.hazardTaxId = "";
            this.form.salaryTaxId = "";
            this.form.longevityTaxId = "";
            this.form.trainLawId = "";

            this.form.assumptions = {
                basicPay: true,
                midYear: false,
                yearEnd: false,
                longevity: false,
                hazardPay: false,
                lessBirRR32015: false,
            };

            this.form.othersEarnings = [];
            this.form.othersDeductions = [];
            this.form.deductions = {
                gsis: true,
                philhealth: true,
                pagibig: true,
            };

            this.form.allocation = {
                hazardPayPct: 65,
                basicPayPct: 20,
                longevityPct: 15,
            };
        },
    },

    watch: {
        selectedYear(newVal, oldVal) {
            this.form.year = newVal;
        }
    }
};
</script>

<style scoped>
.fb-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    padding-bottom: 8px;
}

.fb-tab {
    height: 28px;
    padding: 0 10px;
    border: 1px solid var(--bs-border-color);
    border-radius: 2px;
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    font-size: 12px;
    line-height: 26px;
}

.fb-tab.active {
    background: var(--bs-body-bg);
    border-color: var(--bs-primary);
    box-shadow: inset 0 -2px 0 0 var(--bs-primary);
    font-weight: 600;
}

/* ✅ RED TAB WHEN THAT TAB HAS ERRORS */
.fb-tab.has-error {
    border-color: var(--bs-danger) !important;
    color: var(--bs-danger) !important;
    background: rgba(var(--bs-danger-rgb), 0.12);
    box-shadow: inset 0 -2px 0 0 var(--bs-danger);
}

/* If active AND has-error, keep it red */
.fb-tab.active.has-error {
    border-color: var(--bs-danger) !important;
    box-shadow: inset 0 -2px 0 0 var(--bs-danger);
}
</style>
