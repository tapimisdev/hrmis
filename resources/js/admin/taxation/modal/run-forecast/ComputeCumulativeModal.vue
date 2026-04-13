<template>
    <ModalVue
        ref="modal"
        headerIcon="fa-solid fa-calculator"
        title="Compute Cumulative Tax"
        id="compute-cumulative-modal"
        size="modal-lg"
        subtitle="Review Tab B assumptions before running the cumulative computation."
        type="default"
    >
        <div class="modal-body">
            <form @submit.prevent="submit">
                <TabBAssumptions v-model="form" :errors="errors" />
            </form>

            <div
                class="modal-footer d-flex justify-content-between align-items-center"
            >
                <div class="small text-muted">
                    Using the parent taxation record for tax settings and allocation.
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
                        Compute Cumulative
                    </button>
                </div>
            </div>
        </div>
    </ModalVue>
</template>

<script>
import ModalVue from "../../../../components/ModalVue.vue";
import TabBAssumptions from "./tabs/TabBAssumptions.vue";

const defaultForm = () => ({
    assumptions: {
        basicPay: true,
        midYear: false,
        yearEnd: false,
        longevity: false,
        hazardPay: false,
        lessBirRR32015: false,
    },
    deductions: {
        gsis: true,
        philhealth: true,
        pagibig: true,
    },
    othersEarnings: [],
    othersDeductions: [],
});

export default {
    name: "ComputeCumulativeModal",
    components: {
        ModalVue,
        TabBAssumptions,
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
            errors: {},
            form: defaultForm(),
        };
    },
    methods: {
        buildForm() {
            const taxation = this.taxation || {};
            const settings = taxation.settings || {};

            return {
                assumptions: {
                    ...defaultForm().assumptions,
                    basicPay: true,
                    midYear: Boolean(taxation.mid_year),
                    yearEnd: Boolean(taxation.year_end),
                    longevity: Boolean(taxation.longevity),
                    hazardPay: Boolean(taxation.hazard_pay),
                    lessBirRR32015: Boolean(
                        taxation.less_bir_rr3_2015 ?? taxation.α2,
                    ),
                },
                deductions: {
                    gsis: Boolean(taxation.allowable_gsis ?? true),
                    philhealth: Boolean(taxation.allowable_philhealth ?? true),
                    pagibig: Boolean(taxation.allowable_pagibig ?? true),
                },
                othersEarnings: Array.isArray(settings.other_earnings)
                    ? settings.other_earnings.map((item) => ({
                          name: item.name ?? "",
                          tax_type: item.tax_type ?? "taxable",
                          amount: Number(item.amount ?? 0),
                      }))
                    : [],
                othersDeductions: Array.isArray(settings.other_allowables)
                    ? settings.other_allowables.map((item) => ({
                          name: item.name ?? "",
                          amount: Number(item.amount ?? 0),
                      }))
                    : [],
            };
        },
        open() {
            this.errors = {};
            this.form = this.buildForm();
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
                },
                othersEarnings: this.form.othersEarnings,
                othersDeductions: this.form.othersDeductions,
            });
        },
    },
};
</script>
