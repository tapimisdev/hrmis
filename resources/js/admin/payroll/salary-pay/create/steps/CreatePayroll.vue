<template>
    <div class="">
        <h5 class="mb-3 text-primary text-uppercase">Step 1: Create Payroll</h5>
        <p class="text-muted mb-4">
            Fill in all payroll details and review before sending it for
            approval.
        </p>
        <div class="payroll-create-grid">
            <!-- Label Field -->
            <div class="payroll-field payroll-field-full">
                <label class="form-label text-body fw-bold">Label</label>
                <input
                    type="text"
                    class="form-control"
                    v-model="localForm.label"
                    :class="{ 'is-invalid': errors.label }"
                />
                <small v-if="errors.label" class="text-danger">{{
                    errors.label[0]
                }}</small>
            </div>

            <!-- Employment Type Select (Static) -->
            <div class="payroll-field">
                <label class="form-label text-body fw-bold"
                    >Employment Type</label
                >
                <select
                    class="form-select"
                    v-model="localForm.employment_type_id"
                    @change="fetchGroups"
                    :class="{ 'is-invalid': errors.employment_type_id }"
                >
                    <option value="">-- CHOOSE EMPLOYMENT TYPE --</option>
                    <option
                        v-for="(type, index) in employment_types"
                        :key="index"
                        :value="type.id ?? type"
                    >
                        {{ type.name }}
                    </option>
                </select>
                <small v-if="errors.employment_type_id" class="text-danger">{{
                    errors.employment_type_id[0]
                }}</small>
            </div>

            <!-- Date Field -->
            <div class="payroll-field">
                <label class="form-label text-body fw-bold">{{ isRegular ? "Payroll Month" : "Date" }}</label>
                <input
                    type="date"
                    class="form-control"
                    v-model="localForm.date"
                    :class="{ 'is-invalid': errors.date }"
                />
                <small v-if="errors.date" class="text-danger">{{
                    errors.date[0]
                }}</small>
            </div>

            <!-- Cutoff Select -->
            <div v-if="!isRegular" class="payroll-field">
                <label class="form-label text-body fw-bold">Cutoff</label>
                <select
                    class="form-select"
                    v-model="localForm.cutoff"
                    :class="{ 'is-invalid': errors.cutoff }"
                >
                    <option value="">-- CHOOSE CUTOFF --</option>
                    <option value="first_cutoff">1st Cutoff</option>
                    <option value="second_cutoff">2nd Cutoff</option>
                </select>
                <small v-if="errors.cutoff" class="text-danger">{{
                    errors.cutoff[0]
                }}</small>
            </div>

            <!-- Employment Type Select (Static) -->
            <div class="payroll-field">
                <label class="form-label text-body fw-bold"
                    >Group</label
                >
                <select
                    class="form-select"
                    v-model="localForm.group_id"
                    :class="{ 'is-invalid': errors.group_id }"
                >
                    <option value="">-- CHOOSE GROUP --</option>
                    <option
                        v-for="(type, index) in groups"
                        :key="index"
                        :value="type.id ?? type"
                    >
                        {{ type.name }}
                    </option>
                    <option value="custom"
                      style="
                          background: var(--bs-secondary-bg);
                          color: var(--bs-body-color);
                          font-weight: 600;
                      ">
                      Custom
                  </option>
                </select>
                <small v-if="errors.group_id" class="text-danger">{{
                    errors.group_id[0]
                }}</small>
            </div>
        </div>

        <div v-if="isCos" class="payroll-deduction-group">
            <div class="payroll-deduction-heading">Deduction Options</div>

            <div class="payroll-deduction-grid">
                <div class="payroll-field payroll-field-full">
                    <label class="form-label text-body fw-bold">Apply Deduction</label>
                    <select
                        class="form-select"
                        v-model="localForm.apply_deduction"
                        :class="{ 'is-invalid': errors.apply_deduction }"
                        @change="syncDeferredDeduction"
                    >
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                    <small v-if="errors.apply_deduction" class="text-danger">{{
                        errors.apply_deduction[0]
                    }}</small>
                </div>

                <div class="payroll-field payroll-field-full">
                    <label class="form-label text-body fw-bold">
                        {{ deductionSelectionLabel }}
                    </label>
                    <div
                        v-if="!canChooseDeductionOptions"
                        class="deduction-empty-state"
                    >
                        {{ deferredDeductionPlaceholder }}
                    </div>
                    <div
                        v-else
                        class="deduction-option-list"
                        :class="{
                            'is-invalid':
                                errors.deduction_apply_options ||
                                errors.deduction_defer_options ||
                                errors.deduction_deferred_cutoff ||
                                errors.deduction_deferred_date,
                        }"
                    >
                        <label
                            v-for="option in visibleDeductionOptions"
                            :key="option.value"
                            class="deduction-option"
                        >
                            <input
                                class="form-check-input"
                                type="checkbox"
                                :value="option.value"
                                :checked="isDeductionOptionSelected(option.value)"
                                :disabled="isRequiredDeductionOption(option.value)"
                                @change="toggleDeductionOption(option.value, $event.target.checked)"
                            />
                            <span>
                                <span class="deduction-option-title">{{ option.label }}</span>
                                <span v-if="option.description" class="deduction-option-description">
                                    {{ option.description }}
                                </span>
                            </span>
                        </label>
                    </div>
                    <small v-if="errors.deduction_apply_options" class="text-danger d-block">{{
                        errors.deduction_apply_options[0]
                    }}</small>
                    <small v-if="errors.deduction_defer_options" class="text-danger d-block">{{
                        errors.deduction_defer_options[0]
                    }}</small>
                    <small v-if="errors.deduction_deferred_cutoff" class="text-danger d-block">{{
                        errors.deduction_deferred_cutoff[0]
                    }}</small>
                    <small v-if="errors.deduction_deferred_date" class="text-danger d-block">{{
                        errors.deduction_deferred_date[0]
                    }}</small>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CreatePayroll",
    props: {
        modelValue: Object,
        errors: Object,
    },
    emits: ["update:modelValue"],
    data() {
        const token = localStorage.getItem("auth_token");
        return {
            token,
            loading: false,
            localForm: {
                label: "",
                cutoff: "",
                employment_type_id: "",
                apply_deduction: "yes",
                deduction_apply_options: ["current"],
                deduction_defer_options: [],
                deduction_deferred_cutoff: "",
                deduction_deferred_date: "",
                group_id: "",
                date: new Date().toISOString().split("T")[0],
            },
            employment_types: [],
            groups: [],
            deductionOptions: {
                apply_options: [],
                defer_options: [],
            },
        };
    },
    watch: {
        localForm: {
            deep: true,
            handler(newVal) {
                this.$emit("update:modelValue", newVal);
            },
        },
        "localForm.employment_type_id"() {
            if (this.isRegular) {
                this.localForm.cutoff = "";
            }
            if (!this.isCos) {
                this.localForm.apply_deduction = "yes";
                this.localForm.deduction_apply_options = [];
                this.localForm.deduction_defer_options = [];
                this.clearDeferredDeduction();
            }
            this.syncDeferredDeduction();
            this.fetchDeductionOptions();
        },
        "localForm.cutoff"() {
            this.syncDeferredDeduction();
            this.fetchDeductionOptions();
        },
        "localForm.date"() {
            this.syncDeferredDeduction();
            this.fetchDeductionOptions();
        },
        "localForm.apply_deduction"(value) {
            this.resetDeductionSelections(value);
            this.syncDeferredDeduction();
        },
    },
    created() {
        this.fetchData("employment_types", "/api/get-employment-types", true);
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
                });
        },

        fetchGroups() {
          this.groups = [];
          this.localForm.group_id = '';
          this.fetchData("groups", `/api/payroll/groups/${this.localForm.employment_type_id}`, true);
        },
        clearDeferredDeduction() {
            this.localForm.deduction_deferred_cutoff = "";
            this.localForm.deduction_deferred_date = "";
        },
        resetDeductionSelections(value = this.localForm.apply_deduction) {
            if (!this.isCos) {
                this.localForm.deduction_apply_options = [];
                this.localForm.deduction_defer_options = [];
                return;
            }

            if (value === "yes") {
                this.localForm.deduction_apply_options = ["current"];
                this.localForm.deduction_defer_options = [];
                return;
            }

            this.localForm.deduction_apply_options = [];
            this.localForm.deduction_defer_options = ["tbd"];
        },
        syncDeferredDeduction() {
            if (!this.isCos || this.localForm.apply_deduction !== "no") {
                this.clearDeferredDeduction();
                return;
            }

            if (!this.localForm.cutoff && this.localForm.date) {
                this.localForm.cutoff = this.inferCutoffFromDate(this.localForm.date);
            }

            if (!this.localForm.deduction_defer_options.includes("next_cutoff")) {
                this.clearDeferredDeduction();
                return;
            }

            const nextCutoff = this.deductionOptions.defer_options.find(
                (option) => option.value === "next_cutoff"
            ) || this.deferredDeductionOption;

            if (!nextCutoff?.cutoff || !nextCutoff?.date) {
                this.clearDeferredDeduction();
                return;
            }

            this.localForm.deduction_deferred_cutoff = nextCutoff.cutoff;
            this.localForm.deduction_deferred_date = nextCutoff.date;
        },
        fetchDeductionOptions() {
            if (!this.isCos || !this.localForm.date || !this.localForm.cutoff) {
                this.deductionOptions = { apply_options: [], defer_options: [] };
                return;
            }

            axios
                .get("/api/payroll/salary-pay/deduction-options", {
                    params: {
                        date: this.localForm.date,
                        cutoff: this.localForm.cutoff,
                    },
                    headers: { Authorization: `Bearer ${this.token}` },
                })
                .then((response) => {
                    this.deductionOptions = response.data;
                    this.syncDeferredDeduction();
                });
        },
        isDeductionOptionSelected(value) {
            const selected = this.localForm.apply_deduction === "yes"
                ? this.localForm.deduction_apply_options
                : this.localForm.deduction_defer_options;

            return selected.includes(value);
        },
        isRequiredDeductionOption(value) {
            return (
                this.localForm.apply_deduction === "yes" &&
                this.localForm.deduction_apply_options.length === 1 &&
                this.localForm.deduction_apply_options.includes(value)
            );
        },
        toggleDeductionOption(value, checked) {
            const key = this.localForm.apply_deduction === "yes"
                ? "deduction_apply_options"
                : "deduction_defer_options";

            if (this.localForm.apply_deduction === "no") {
                this.localForm[key] = checked ? [value] : [];
                this.syncDeferredDeduction();
                return;
            }

            const selected = new Set(this.localForm[key]);
            checked ? selected.add(value) : selected.delete(value);
            this.localForm[key] = selected.size ? Array.from(selected) : ["current"];
        },
        formatLocalDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        },
        inferCutoffFromDate(value) {
            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return "";
            }

            return date.getDate() <= 15 ? "first_cutoff" : "second_cutoff";
        },
    },
    computed: {
        isRegular() {
            return String(this.localForm.employment_type_id) === "1";
        },
        isCos() {
            return String(this.localForm.employment_type_id) === "2";
        },
        deferredDeductionValue() {
            if (
                !this.localForm.deduction_deferred_cutoff ||
                !this.localForm.deduction_deferred_date
            ) {
                return "";
            }

            return `${this.localForm.deduction_deferred_cutoff}|${this.localForm.deduction_deferred_date}`;
        },
        deductionSelectionLabel() {
            return this.localForm.apply_deduction === "yes"
                ? "Which deductions should be applied?"
                : "When should this payroll's deduction be applied?";
        },
        canChooseDeductionOptions() {
            return this.isCos && Boolean(this.localForm.date) && Boolean(this.localForm.cutoff);
        },
        visibleDeductionOptions() {
            if (!this.canChooseDeductionOptions) return [];

            if (this.localForm.apply_deduction === "yes") {
                return this.deductionOptions.apply_options.length
                    ? this.deductionOptions.apply_options
                    : [{ value: "current", label: "Current Deductions" }];
            }

            return this.deductionOptions.defer_options.length
                ? this.deductionOptions.defer_options
                : [
                    { value: "tbd", label: "To be determined" },
                    this.deferredDeductionOption
                        ? {
                            value: "next_cutoff",
                            label: this.deferredDeductionOption.label,
                            cutoff: this.deferredDeductionOption.cutoff,
                            date: this.deferredDeductionOption.date,
                        }
                        : null,
                ].filter(Boolean);
        },
        deferredDeductionPlaceholder() {
            if (!this.localForm.cutoff) {
                return "Select a payroll cutoff first to show deduction options.";
            }

            if (!this.localForm.date) {
                return "Select a payroll date first to show deduction options.";
            }

            return "Choose a deduction schedule.";
        },
        deferredDeductionOption() {
            if (!this.localForm.date || !this.localForm.cutoff) return null;

            const date = new Date(this.localForm.date);
            if (Number.isNaN(date.getTime())) return null;

            let cutoff = "second_cutoff";
            let scheduleDate = new Date(date.getFullYear(), date.getMonth(), 16);

            if (this.localForm.cutoff === "second_cutoff") {
                cutoff = "first_cutoff";
                scheduleDate = new Date(date.getFullYear(), date.getMonth() + 1, 1);
            }

            const monthYear = scheduleDate.toLocaleString(undefined, {
                month: "long",
                year: "numeric",
            });
            const cutoffLabel = cutoff === "first_cutoff" ? "1st Cutoff" : "2nd Cutoff";
            const formattedDate = this.formatLocalDate(scheduleDate);

            return {
                cutoff,
                date: formattedDate,
                value: `${cutoff}|${formattedDate}`,
                label: `${cutoffLabel} of ${monthYear}`,
            };
        },
    },
};
</script>

<style scoped>
.payroll-create-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem 1.5rem;
    max-width: 860px;
}

.payroll-field {
    min-width: 0;
}

.payroll-field-full {
    grid-column: 1 / -1;
}

.payroll-field :deep(.form-control),
.payroll-field :deep(.form-select) {
    width: 100%;
}

.payroll-deduction-group {
    border-top: 1px solid var(--bs-border-color);
    margin-top: 1.5rem;
    padding-top: 1.25rem;
}

.payroll-deduction-heading {
    color: var(--bs-primary);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-bottom: 1rem;
    text-transform: uppercase;
}

.payroll-deduction-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem 1.5rem;
}

.deduction-option-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.deduction-empty-state {
    border-left: 3px solid var(--bs-primary);
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    line-height: 1.4;
    padding: 0.35rem 0 0.35rem 0.85rem;
}

.deduction-option {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.75rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 6px;
    background: var(--bs-body-bg);
    cursor: pointer;
}

.deduction-option-title {
    display: block;
    font-weight: 700;
    color: var(--bs-body-color);
}

.deduction-option-description {
    display: block;
    margin-top: 0.2rem;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

@media (max-width: 768px) {
    .payroll-create-grid {
        grid-template-columns: 1fr;
        max-width: none;
    }

    .payroll-deduction-group {
        max-width: none;
    }

    .payroll-deduction-grid {
        grid-template-columns: 1fr;
    }

    .deduction-option-list {
        grid-template-columns: 1fr;
    }
}
</style>
