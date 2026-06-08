<template>
    <ModalVue
        ref="modal"
        headerIcon="fa-solid fa-calculator"
        title="COMPUTE ADJUSTMENT TAX"
        id="compute-adjustment-modal"
        size="modal-lg"
        subtitle="Review the adjustment assumptions before running the NOV adjustment computation."
        type="default"
    >
        <div class="modal-body">
            <form @submit.prevent="submit">
                <ComputeAdjustmentFields
                    v-model="form"
                    :government-bonuses="governmentBonuses"
                    :is-loading-bonuses="isLoadingBonuses"
                    :taxation-year="taxationYear"
                />
            </form>

            <div
                class="modal-footer d-flex justify-content-between align-items-center"
            >
                <div class="small text-muted">
                    Using the parent taxation record for tax settings and allowable deductions.
                </div>

                <div class="d-flex gap-2">
                    <button
                        type="button"
                        class="fb-btn bg-danger"
                        @click="close"
                        :disabled="isSubmitting"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="fb-btn fb-primary"
                        @click="submit"
                        :disabled="isSubmitting"
                    >
                        <span
                            v-if="isSubmitting"
                            class="spinner-border spinner-border-sm me-2"
                        ></span>
                        Compute Adjustment
                    </button>
                </div>
            </div>
        </div>
    </ModalVue>
</template>

<script>
import axios from "axios";
import ModalVue from "../../../../components/ModalVue.vue";
import ComputeAdjustmentFields from "./tabs/ComputeAdjustmentFields.vue";

const defaultForm = () => ({
    assumptions: {
        basicPay: true,
        midYear: false,
        yearEnd: false,
        longevity: true,
        hazardPay: true,
        lessBirRR32015: true,
    },
    deductions: {
        gsis: true,
        philhealth: true,
        pagibig: true,
    },
    governmentBonuses: [],
});

export default {
    name: "ComputeAdjustmentModal",
    components: {
        ModalVue,
        ComputeAdjustmentFields,
    },
    props: {
        taxation: {
            type: Object,
            default: () => ({}),
        },
        isSubmitting: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["submit"],
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            errors: {},
            form: defaultForm(),
            governmentBonuses: [],
            isLoadingBonuses: false,
        };
    },
    computed: {
        taxationYear() {
            return this.taxation?.year ?? "";
        },
    },
    methods: {
        buildForm() {
            const taxation = this.taxation || {};

            return {
                assumptions: {
                    ...defaultForm().assumptions,
                    basicPay: true,
                    midYear: false,
                    yearEnd: false,
                    longevity: true,
                    hazardPay: true,
                    lessBirRR32015: true,
                },
                deductions: {
                    gsis: Boolean(taxation.allowable_gsis ?? true),
                    philhealth: Boolean(taxation.allowable_philhealth ?? true),
                    pagibig: Boolean(taxation.allowable_pagibig ?? true),
                },
                governmentBonuses: [],
            };
        },
        async fetchGovernmentBonuses() {
            if (!this.taxationYear) {
                this.governmentBonuses = [];
                return;
            }

            this.isLoadingBonuses = true;

            try {
                const response = await axios.post(
                    "/api/payroll/government-bonuses/processed",
                    {
                        year: String(this.taxationYear),
                        status: "completed",
                    },
                    {
                        headers: {
                            Accept: "application/json",
                            Authorization: `Bearer ${this.token}`,
                        },
                    },
                );

                const rows = Array.isArray(response?.data?.data)
                    ? response.data.data
                    : [];

                this.governmentBonuses = rows.map((item) => ({
                    id: item.id,
                    name: item.bonus_type_name || "Government Bonus",
                    monthLabel: this.formatMonth(item.month),
                }));
            } catch (error) {
                this.governmentBonuses = [];
            } finally {
                this.isLoadingBonuses = false;
            }
        },
        formatMonth(value) {
            if (!value) return "";

            const date = new Date(`${value}-01T00:00:00`);

            if (Number.isNaN(date.getTime())) {
                return String(value);
            }

            return date.toLocaleDateString("en-PH", {
                year: "numeric",
                month: "long",
            });
        },
        async open() {
            this.errors = {};
            this.form = this.buildForm();
            await this.fetchGovernmentBonuses();
            this.$refs.modal?.open?.();
        },
        close() {
            this.$refs.modal?.close?.();
        },
        setErrors(errors = {}) {
            this.errors = errors || {};
        },
        submit() {
            if (this.isSubmitting) return;

            this.$emit("submit", {
                assumptions: {
                    ...this.form.assumptions,
                    basicPay: true,
                    longevity: true,
                    hazardPay: true,
                    lessBirRR32015: true,
                },
                governmentBonuses: this.form.governmentBonuses,
            });
        },
    },
};
</script>
