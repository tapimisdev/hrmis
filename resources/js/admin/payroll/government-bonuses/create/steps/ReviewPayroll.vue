<template>
    <div>
        <h5 class="mb-1 text-primary text-uppercase">Step 2: Employee Review</h5>

        <p class="text-muted m-0 mt-2">
            <strong>Payroll Label:</strong> {{ modelValue.label }}
        </p>
        <p class="text-muted m-0">
            <strong>Bonus Type:</strong> {{ modelValue.bonus_type?.name || "-" }}
        </p>
        <p class="text-muted m-0 mb-3">
            <strong>Period:</strong> {{ payrollPeriod }}
        </p>

        <div class="accordion" id="eligibilityAccordion">
            <div class="accordion-item mb-3 shadow-sm border-0 rounded">
                <h2 class="accordion-header" id="headingEligible">
                    <button
                        class="accordion-button bg-success bg-opacity-10 text-success fw-semibold"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseEligible"
                        aria-expanded="true"
                        aria-controls="collapseEligible"
                    >
                        <i class="fas fa-circle-check me-2"></i>
                        Rule-Matched Employees ({{ modelValue.employees.eligible?.length ?? 0 }})
                    </button>
                </h2>

                <div
                    id="collapseEligible"
                    class="accordion-collapse collapse show"
                    aria-labelledby="headingEligible"
                    data-bs-parent="#eligibilityAccordion"
                >
                    <div class="accordion-body">
                        <div v-if="modelValue.employees.eligible?.length" class="mb-3 d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-success" @click="setEligibleSelected(true)">
                                Select all
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="setEligibleSelected(false)">
                                Clear
                            </button>
                            <span class="ms-auto small text-muted">
                                Selected: <strong>{{ selectedEligibleCount }}</strong>
                            </span>
                        </div>

                        <div v-if="modelValue.employees.eligible?.length" class="list-group list-group-flush eligibility-list">
                            <div
                                v-for="employee in modelValue.employees.eligible"
                                :key="employee.employee_no"
                                class="list-group-item d-flex justify-content-between align-items-center"
                            >
                                <div class="d-flex align-items-start gap-3 w-100">
                                    <div class="form-check mt-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :id="`eligible-${employee.employee_no}`"
                                            v-model="employee.selected"
                                        />
                                    </div>

                                    <div class="flex-grow-1">
                                        <strong class="text-uppercase">
                                            {{ employee.suffix }} {{ employee.firstname }} {{ employee.middlename }} {{ employee.lastname }}
                                        </strong>
                                        <div class="text-muted text-uppercase">
                                            {{ employee.position }} — {{ employee.division }}
                                        </div>
                                    </div>

                                    <span class="badge bg-success align-self-start">Matched</span>
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-muted mb-0">No employees matched the selected bonus rules.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 shadow-sm border-0 rounded">
                <h2 class="accordion-header" id="headingIneligible">
                    <button
                        class="accordion-button collapsed bg-warning bg-opacity-10 text-warning fw-semibold"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseIneligible"
                        aria-expanded="false"
                        aria-controls="collapseIneligible"
                    >
                        <i class="fas fa-user-shield me-2"></i>
                        Rule-Failed / Override Employees ({{ modelValue.employees.not_eligible?.length ?? 0 }})
                    </button>
                </h2>

                <div
                    id="collapseIneligible"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingIneligible"
                    data-bs-parent="#eligibilityAccordion"
                >
                    <div class="accordion-body">
                        <div v-if="overrideableEmployees.length" class="mb-3 d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-warning" @click="setOverrideSelected(true)">
                                Select overrides
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="setOverrideSelected(false)">
                                Clear overrides
                            </button>
                            <span class="ms-auto small text-muted">
                                Selected overrides: <strong>{{ selectedOverrideCount }}</strong>
                            </span>
                        </div>

                        <div v-if="modelValue.employees.not_eligible?.length" class="list-group list-group-flush eligibility-list">
                            <div
                                v-for="employee in modelValue.employees.not_eligible"
                                :key="employee.employee_no"
                                class="list-group-item d-flex justify-content-between align-items-center"
                            >
                                <div class="d-flex align-items-start gap-3 w-100">
                                    <div class="form-check mt-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :id="`override-${employee.employee_no}`"
                                            v-model="employee.selected"
                                            :disabled="employee.can_override === false"
                                        />
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-uppercase">
                                            {{ employee.suffix }} {{ employee.firstname }} {{ employee.middlename }} {{ employee.lastname }}
                                        </div>
                                        <div class="text-muted text-uppercase">
                                            {{ employee.position }} — {{ employee.division }}
                                        </div>

                                        <div class="mt-1" v-if="employee.remarks?.length">
                                            <div v-for="(remark, index) in employee.remarks" :key="index">
                                                <a
                                                    class="small"
                                                    :class="employee.can_override === false ? 'text-danger' : 'text-warning'"
                                                    href="#"
                                                    @click.prevent="openRemark(remark.url)"
                                                >
                                                    <strong>{{ index === 0 ? "Reason:" : "Also:" }}</strong>
                                                    {{ remark.text }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <span
                                        class="badge align-self-start"
                                        :class="employee.can_override === false ? 'bg-danger' : 'bg-warning text-dark'"
                                    >
                                        {{ employee.can_override === false ? "Locked" : "Override" }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-muted mb-0">No override candidates found.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3 mb-0">
            Total selected for generation: <strong>{{ totalSelectedCount }}</strong>
        </div>
    </div>
</template>

<script>
export default {
    name: "ReviewPayroll",
    props: {
        modelValue: [String, Number, Object, Array],
    },
    computed: {
        payrollPeriod() {
            if (!this.modelValue?.month) return "";

            const date = new Date(`${this.modelValue.month}-01`);
            return date.toLocaleString(undefined, { month: "long", year: "numeric" });
        },
        selectedEligibleCount() {
            const list = this.modelValue?.employees?.eligible ?? [];
            return list.filter((employee) => Boolean(employee.selected)).length;
        },
        overrideableEmployees() {
            const list = this.modelValue?.employees?.not_eligible ?? [];
            return list.filter((employee) => employee.can_override !== false);
        },
        selectedOverrideCount() {
            return this.overrideableEmployees.filter((employee) => Boolean(employee.selected)).length;
        },
        totalSelectedCount() {
            return this.selectedEligibleCount + this.selectedOverrideCount;
        },
    },
    methods: {
        openRemark(url) {
            if (!url) return;
            window.open(url, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,width=800,height=600");
        },
        setEligibleSelected(value) {
            const list = this.modelValue?.employees?.eligible ?? [];
            list.forEach((employee) => {
                employee.selected = value;
            });
        },
        setOverrideSelected(value) {
            this.overrideableEmployees.forEach((employee) => {
                employee.selected = value;
            });
        },
    },
};
</script>

<style scoped>
.eligibility-list {
    max-height: 28rem;
    overflow-y: auto;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.75rem;
}

.eligibility-list .list-group-item {
    background: transparent;
}
</style>
