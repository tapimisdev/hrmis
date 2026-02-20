<template>
    <div>
        <h5 class="mb-1 text-primary text-uppercase">
            Step 2: Employee Eligibility Review
        </h5>

        <p class="text-muted m-0 mt-2">
            <strong>Payroll Label:</strong> {{ modelValue.label }}
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
                        Eligible Employees ({{
                            modelValue.employees.eligible?.length ?? 0
                        }})
                    </button>
                </h2>

                <div
                    id="collapseEligible"
                    class="accordion-collapse collapse show"
                    aria-labelledby="headingEligible"
                    data-bs-parent="#eligibilityAccordion"
                >
                    <div class="accordion-body">
                        <div
                            v-if="modelValue.employees.eligible?.length"
                            class="mb-3 d-flex gap-2"
                        >
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success"
                                @click="setEligibleSelected(true)"
                            >
                                Select all
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                @click="setEligibleSelected(false)"
                            >
                                Clear
                            </button>

                            <span class="ms-auto small text-muted">
                                Selected:
                                <strong>{{ selectedEligibleCount }}</strong>
                            </span>
                        </div>

                        <div
                            v-if="modelValue.employees.eligible?.length"
                            class="list-group list-group-flush"
                        >
                            <div
                                v-for="employee in modelValue.employees
                                    .eligible"
                                :key="employee.id"
                                class="list-group-item d-flex justify-content-between align-items-center"
                            >
                                <div class="d-flex align-items-start gap-3">
                                    <div class="form-check mt-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :id="`emp-${employee.employee_no ?? employee.id}`"
                                            v-model="employee.selected"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="mb-0"
                                            :for="`emp-${employee.employee_no ?? employee.id}`"
                                            style="cursor: pointer"
                                        >
                                            <strong class="text-uppercase">
                                                {{ employee.lastname }},
                                                {{ employee.firstname }}
                                                {{ employee.middlename }}
                                                {{ employee.suffix }}
                                            </strong>
                                        </label>

                                        <div class="text-muted text-uppercase">
                                            {{ employee.position }} —
                                            {{ employee.division }}
                                        </div>

                                        <div
                                            v-for="remark in employee.remarks"
                                            :key="remark.text"
                                        >
                                            <a
                                                v-if="remark.value != 0"
                                                class="text-underline"
                                                href="#"
                                                @click.prevent="
                                                    openRemark(remark.url)
                                                "
                                            >
                                                <span
                                                    class="text-warning small"
                                                >
                                                    {{ remark.text }}
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <span class="badge bg-success">Eligible</span>
                            </div>
                        </div>

                        <p v-else class="text-muted mb-0">
                            No eligible employees found.
                        </p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 shadow-sm border-0 rounded">
                <h2 class="accordion-header" id="headingIneligible">
                    <button
                        class="accordion-button collapsed bg-danger bg-opacity-10 text-danger fw-semibold"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseIneligible"
                        aria-expanded="false"
                        aria-controls="collapseIneligible"
                    >
                        <i class="fas fa-circle-xmark me-2"></i>
                        Ineligible Employees ({{
                            modelValue.employees.not_eligible?.length ?? 0
                        }})
                    </button>
                </h2>

                <div
                    id="collapseIneligible"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingIneligible"
                    data-bs-parent="#eligibilityAccordion"
                >
                    <div class="accordion-body">
                        <div
                            v-if="modelValue.employees.not_eligible?.length"
                            class="list-group list-group-flush"
                        >
                            <div
                                v-for="employee in modelValue.employees
                                    .not_eligible"
                                :key="employee.id"
                                class="list-group-item"
                            >
                                <div class="fw-semibold text-uppercase">
                                    {{ employee.lastname }},
                                    {{ employee.firstname }}
                                    {{ employee.middlename }}
                                    {{ employee.suffix }}
                                </div>

                                <div class="text-muted text-uppercase">
                                    {{ employee.position }} —
                                    {{ employee.division }}
                                </div>

                                <div
                                    class="mt-1"
                                    v-if="employee.remarks?.length"
                                >
                                    <a
                                        class="text-danger small"
                                        href="#"
                                        @click.prevent="
                                            openRemark(employee.remarks[0].url)
                                        "
                                    >
                                        <strong>Reason:</strong>
                                        {{ employee.remarks[0].text }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-muted mb-0">
                            No ineligible employees found.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ReviewPayroll",
    props: {
        modelValue: [String, Number, Object, Array],
    },
    methods: {
        openRemark(url) {
            if (!url) return;
            window.open(
                url,
                "_blank",
                "toolbar=yes,scrollbars=yes,resizable=yes,width=800,height=600",
            );
        },
        setEligibleSelected(value) {
            const list = this.modelValue?.employees?.eligible ?? [];
            list.forEach((emp) => {
                emp.selected = value;
            });
        },
    },
    computed: {
        payrollPeriod() {
            if (!this.modelValue?.cutoff || !this.modelValue?.date) return "";

            const date = new Date(this.modelValue.date);
            const lastDay = new Date(
                date.getFullYear(),
                date.getMonth() + 1,
                0,
            ).getDate();

            const monthYear = date.toLocaleString(undefined, {
                month: "long",
                year: "numeric",
            });

            const period =
                this.modelValue.cutoff === "first_cutoff"
                    ? `1 - 15`
                    : `16 - ${lastDay}`;

            return `${period} ${monthYear}`;
        },
        selectedEligibleCount() {
            const list = this.modelValue?.employees?.eligible ?? [];
            return list.filter((e) => Boolean(e.selected)).length;
        },
    },
};
</script>
