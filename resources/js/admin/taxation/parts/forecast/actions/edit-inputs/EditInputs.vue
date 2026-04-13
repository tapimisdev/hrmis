<template>
    <TaxTemplate :is_open="true">
        <template #header>Edit Inputs</template>
        <EditSkeleton v-if="is_loading" class="mt-4" />
        <template v-else>
            <BasicInformation :row="row" />

            <div class="container-fluid pb-4">
                <p>
                    Edit the taxation inputs for the selected employee. The
                    values configured here will be used by the system to compute
                    the employee's taxable income and withholding tax.
                </p>

                <div class="row justify-content-center g-3">
                    <!-- Earnings Included / Exemptions -->
                    <div class="col-12">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-bold mb-2">Earnings Included</div>

                            <div v-for="item in earningChecks" :key="item.key" class="form-check mb-2">
                                <input :id="item.key" v-model="form.assumptions[item.key]" class="form-check-input"
                                    type="checkbox" :disabled="item.disabled" />
                                <label class="form-check-label" :for="item.key">
                                    {{ item.label }}
                                </label>
                            </div>

                            <hr class="my-3" />

                            <div class="fw-bold mb-2">Less / Exemptions</div>

                            <div class="form-check">
                                <input id="lessBirRR32015" v-model="form.assumptions.lessBirRR32015"
                                    class="form-check-input" type="checkbox" />
                                <label class="form-check-label d-flex justify-content-between align-items-center w-100"
                                    for="lessBirRR32015">
                                    <span>Less BIR RR 3-2015</span>

                                    <AppTooltip position="left"
                                        text="Applies the ₱90,000 tax exemption for bonuses and benefits under BIR RR 3-2015. This reduces the taxable portion of bonuses." />
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Other Earnings -->
                    <div class="col-12">
                        <DynamicRows v-model="form.othersEarnings" title="Others (Earnings)" :errors="errors"
                            error-key="othersEarnings" :enableTaxType="true" class="w-100">
                            <template #title>
                                <div class="d-flex align-items-center">
                                    <span>Others (Earnings)</span>
                                    <AppTooltip class="ms-2"
                                        text="Includes additional taxable or non-taxable earnings such as bonuses, allowances, or other compensation not listed above." />
                                </div>
                            </template>
                        </DynamicRows>
                    </div>

                    <!-- Allowable Deductions -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="fw-bold mb-2 d-flex align-items-center">
                                <span>Allowable Deductions</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input id="gsis" v-model="form.deductions.gsis" class="form-check-input"
                                            type="checkbox" disabled />
                                        <label class="form-check-label" for="gsis">
                                            GSIS
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input id="philhealth" v-model="form.deductions.philhealth"
                                            class="form-check-input" type="checkbox" disabled />
                                        <label class="form-check-label" for="philhealth">
                                            PhilHealth
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input id="pagibig" v-model="form.deductions.pagibig" class="form-check-input"
                                            type="checkbox" disabled />
                                        <label class="form-check-label" for="pagibig">
                                            Pag-IBIG (max ₱200)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3" />

                            <DynamicRows v-model="form.othersDeductions" title="Others (Allowable Deductions)"
                                :errors="errors" error-key="othersDeductions">
                                <template #title>
                                    <div class="d-flex align-items-center">
                                        <span>Others (Allowable Deductions)</span>
                                        <AppTooltip class="ms-2"
                                            text="Includes other allowable deductions not listed above, such as approved employee deductions or BIR-recognized adjustments that reduce taxable income." />
                                    </div>
                                </template>
                            </DynamicRows>

                            <small v-if="errors?.othersDeductions" class="text-danger">
                                {{ errors.othersDeductions }}
                            </small>
                        </div>
                    </div>
                    <div class="col-12">
                        <TabCAllocation v-model="form.allocation" :errors="errors" />
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button
                            type="button"
                            class="fb-btn fb-primary"
                            :disabled="is_saving"
                            @click="save"
                        >
                            <i
                                class="fa-solid me-2"
                                :class="is_saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"
                            ></i>
                            {{ is_saving ? "Saving..." : "Save & Recompute" }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </TaxTemplate>
</template>

<script>
import TaxTemplate from "../../../../components/TaxTemplate.vue";
import BasicInformation from "../components/BasicInformation.vue";
import DynamicRows from "../../../../modal/run-forecast/tabs/partials/DynamicRows.vue";
import AppTooltip from "../../../../../../components/AppTooltip.vue";
import EditSkeleton from "./EditSkeleton.vue";
import TabCAllocation from "../../../../modal/run-forecast/tabs/TabCAllocation.vue";

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

    allocation: {
        basicPayPct: 20,
        hazardPayPct: 60,
        longevityPct: 20,
    },
});

export default {
    name: "EditInputs",
    components: {
        TaxTemplate,
        BasicInformation,
        DynamicRows,
        AppTooltip,
        EditSkeleton,
        TabCAllocation
    },
    props: {
        row: { type: Object, required: true },
    },
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            is_loading: false,
            is_saving: false,
            form: defaultForm(),
            errors: {},
            earningChecks: [
                { key: "basicPay", label: "Basic Pay", disabled: true },
                {
                    key: "midYear",
                    label: "Mid Year (same as monthly salary)",
                    disabled: false,
                },
                {
                    key: "yearEnd",
                    label: "Year End (same as monthly salary)",
                    disabled: false,
                },
                { key: "longevity", label: "Longevity", disabled: false },
                { key: "hazardPay", label: "Hazard Pay", disabled: false },
            ],
        };
    },
    methods: {
        normalizeForm(payload = {}) {
            return {
                assumptions: {
                    ...defaultForm().assumptions,
                    ...(payload.assumptions || {}),
                },
                deductions: {
                    ...defaultForm().deductions,
                    ...(payload.deductions || {}),
                },
                othersEarnings: Array.isArray(payload.othersEarnings)
                    ? payload.othersEarnings
                    : [],
                othersDeductions: Array.isArray(payload.othersDeductions)
                    ? payload.othersDeductions
                    : [],
                allocation: {
                    ...defaultForm().allocation,
                    ...(payload.allocation || {}),
                },
            };
        },

        fetchEdits() {
            if (!this.row?.id) {
                this.form = defaultForm();
                return;
            }

            this.is_loading = true;
            this.errors = {};

            axios
                .get(`/admin/taxation/edit-inputs/${this.row.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then((response) => {
                    this.form = this.normalizeForm(response.data || {});
                })
                .catch((err) => {
                    console.error(err);
                    this.form = defaultForm();
                })
                .finally(() => {
                    this.is_loading = false;
                });
        },

        // Submit only; backend returns 422 if invalid
        async save() {
            if (!this.row?.id || this.is_saving) return;

            this.is_saving = true;
            this.errors = {};
            const payload = {
                ...this.form,
                year: this.row.year,
            };
            try {
                const res = await axios.post(
                    `/admin/taxation/save/${this.row.id}`,
                    payload,
                    {
                        headers: { Authorization: `Bearer ${this.token}` },
                    },
                );

                await Swal.fire({
                    title: "Saved",
                    text:
                        res?.data?.message ||
                        "Changes were saved and recomputation has started.",
                    icon: "success",
                });

                this.$emit("refresh-forecast", {
                    source: "save",
                    employee_key:
                        this.row?.employee_no !== undefined &&
                        this.row?.employee_no !== null
                            ? `emp:${String(this.row.employee_no).trim()}`
                            : this.row?.id,
                    row_ui_key:
                        this.row?.employee_no !== undefined &&
                        this.row?.employee_no !== null
                            ? `emp-${String(this.row.employee_no).trim()}`
                            : this.row?.id !== undefined && this.row?.id !== null
                                ? `id-${String(this.row.id).trim()}`
                                : null,
                    action: "breakdown",
                });
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else {
                    await Swal.fire(
                        "Error",
                        error.response?.data?.message || String(error),
                        "error",
                    );
                }
            } finally {
                this.is_saving = false;
            }
        },
    },
    watch: {
        "row.id": {
            immediate: true,
            handler() {
                this.fetchEdits();
            },
        },
    },
};
</script>
