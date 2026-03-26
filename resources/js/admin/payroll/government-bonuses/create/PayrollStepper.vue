<template>
    <div class="stepper-container">
        <div class="stepper-wrapper">
            <div class="stepper-sidebar border">
                <div class="sidebar-header">
                    <h5>Payroll Setup</h5>
                    <span>Step {{ currentStep + 1 }}/{{ steps.length }}</span>
                </div>

                <div class="steps-list">
                    <div
                        v-for="(step, index) in steps"
                        :key="index"
                        class="step"
                        :class="{
                            active: currentStep === index,
                            completed: index < currentStep,
                        }"
                        @click="goToStep(index)"
                    >
                        <div class="step-circle">
                            <i v-if="index < currentStep" class="fas fa-check"></i>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="step-info">
                            <h6>{{ step.label }}</h6>
                            <p>{{ step.desc }}</p>
                        </div>
                    </div>
                </div>

                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        :style="{ width: ((currentStep + 1) / steps.length) * 100 + '%' }"
                    ></div>
                </div>
            </div>

            <div class="stepper-content border">
                <LoaderVue
                    :hasBackground="true"
                    :visible="loading"
                    status="loading"
                    message="Loading..."
                />

                <div class="content-body">
                    <keep-alive :include="['CreatePayroll', 'ReviewPayroll', 'ApprovalPayroll']">
                        <component
                            :is="steps[currentStep].component"
                            v-model="form"
                            :errors="errors"
                        />
                    </keep-alive>
                </div>

                <div class="content-footer">
                    <button class="btn btn-back" :disabled="currentStep === 0" @click="prevStep">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </button>

                    <span class="step-mobile">{{ currentStep + 1 }}/{{ steps.length }}</span>

                    <button
                        v-if="currentStep < steps.length - 1"
                        class="btn btn-next"
                        :disabled="loading"
                        @click="nextStep"
                    >
                        <span v-if="loading">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                        <template v-else>
                            <span>Next</span>
                            <i class="fas fa-arrow-right"></i>
                        </template>
                    </button>

                    <button v-else class="btn btn-submit" @click="submitForm">
                        <i class="fas fa-rocket"></i>
                        <span>Generate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { markRaw } from "vue";
import CreatePayroll from "./steps/CreatePayroll.vue";
import ReviewPayroll from "./steps/ReviewPayroll.vue";
import ApprovalPayroll from "./steps/ApprovalPayroll.vue";
import LoaderVue from "../../../../components/LoaderVue.vue";

export default {
    name: "GovernmentBonusStepper",
    components: { CreatePayroll, ReviewPayroll, ApprovalPayroll, LoaderVue },
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            currentStep: 0,
            loading: false,
            errors: {},
            form: {
                label: "",
                month: "",
                employees: {
                    eligible: [],
                    not_eligible: [],
                },
                employment_type_id: 1,
                government_bonus_type_id: "",
                approved_by: {},
                bonus_type: null,
                bonus_types: [],
            },
            steps: [
                {
                    label: "Create Government Bonus Payroll",
                    desc: "Set payroll details",
                    component: markRaw(CreatePayroll),
                },
                {
                    label: "Employee Review",
                    desc: "Review default rules and overrides",
                    component: markRaw(ReviewPayroll),
                },
                {
                    label: "Approval",
                    desc: "Finalize and submit",
                    component: markRaw(ApprovalPayroll),
                },
            ],
        };
    },
    methods: {
        async fetchBonusTypes() {
            const response = await axios.get("/api/payroll/government-bonuses/bonus-types", {
                headers: {
                    Accept: "application/json",
                    Authorization: `Bearer ${this.token}`,
                },
            });

            this.form.bonus_types = response.data.data || [];
        },
        async nextStep() {
            if (this.currentStep === 0) {
                const valid = await this.validateAndGetReview();
                if (!valid) return;
            }

            if (this.currentStep < this.steps.length - 1) this.currentStep++;
        },
        prevStep() {
            if (this.currentStep > 0) this.currentStep--;
        },
        goToStep(index) {
            if (index <= this.currentStep) this.currentStep = index;
        },
        async validateAndGetReview() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await axios.post(
                    "/api/payroll/government-bonuses/check-employees",
                    this.form,
                    {
                        headers: {
                            Accept: "application/json",
                            Authorization: `Bearer ${this.token}`,
                        },
                    }
                );

                this.form.employees = response.data.data;
                this.form.bonus_type = response.data.data.bonus_type || null;
                return true;
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    Swal.fire(
                        error.response?.data?.message === "No employees found for this employment type."
                            ? "No Employees Found"
                            : "Error",
                        error.response?.data?.message || "Something went wrong.",
                        error.response?.data?.message === "No employees found for this employment type."
                            ? "info"
                            : "error"
                    );
                }

                return false;
            } finally {
                this.loading = false;
            }
        },
        async submitForm() {
            this.loading = true;
            this.errors = {};

            try {
                const selectedEmployees = [
                    ...(this.form.employees?.eligible || []),
                    ...(this.form.employees?.not_eligible || []),
                ].filter((employee) => Boolean(employee.selected));

                if (!selectedEmployees.length) {
                    this.errors = {
                        employees: ["Select at least one employee to generate the payroll."],
                    };
                    Swal.fire("No Employees Selected", this.errors.employees[0], "warning");
                    return false;
                }

                const response = await axios.post("/api/payroll/government-bonuses/generate", this.form, {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${this.token}`,
                    },
                });

                const batchId = response.data.batch_id;
                const payrollNo = response.data.payroll_no;

                if (!batchId) {
                    Swal.fire(
                        "Error",
                        "Payroll generation did not start because no batch ID was returned.",
                        "error"
                    );
                    return false;
                }

                window.location.href = `/admin/payroll/government-bonuses/${payrollNo}?batch_id=${batchId}`;
                return true;
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                    if (this.errors.employees?.[0]) {
                        Swal.fire("No Employees Selected", this.errors.employees[0], "warning");
                    }
                } else {
                    Swal.fire(
                        error.response?.data?.message || "Something went wrong.",
                        error.response?.data?.error || "Unable to generate government bonus payroll.",
                        "error"
                    );
                }

                return false;
            } finally {
                this.loading = false;
            }
        },
    },
    async mounted() {
        await this.fetchBonusTypes();

        window.Echo.channel("refresh")
            .listen(".RefreshData", () => {
                this.validateAndGetReview();
            })
            .error((error) => {
            });
    },
    beforeDestroy() {
        window.Echo.leave("refresh");
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../../../sass/variables";

.stepper-container {
    padding: 24px;
}

.stepper-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 24px;
}

.stepper-sidebar {
    background: var(--bs-secondary-bg);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba($black, 0.08);
    height: fit-content;
    position: sticky;
    top: 64px;
}

.sidebar-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--bs-body-bg);

    h5 {
        font-size: 20px;
        font-weight: 800;
        background: linear-gradient(135deg, var(--bs-primary), var(--bs-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0 0 4px;
    }

    span {
        font-size: 13px;
        color: lighten($dark, 30%);
        font-weight: 600;
    }
}

.steps-list {
    margin-bottom: 20px;
}

.step {
    display: flex;
    gap: 14px;
    margin-bottom: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;

    &:last-child {
        margin-bottom: 0;
    }
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-weight: 700;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
}

.step.active .step-circle,
.step.completed .step-circle {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.step-info h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
}

.step-info p {
    margin: 4px 0 0;
    font-size: 12px;
    color: var(--bs-secondary-color);
}

.progress-bar {
    height: 8px;
    background: var(--bs-body-bg);
    border-radius: 999px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bs-primary), var(--bs-secondary));
}

.stepper-content {
    position: relative;
    overflow: hidden;
    background: var(--bs-secondary-bg);
    border-radius: 20px;
    display: flex;
    flex-direction: column;
}

.content-body {
    padding: 24px;
    flex: 1;
}

.content-footer {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--bs-border-color);
}

.btn-back,
.btn-next,
.btn-submit {
    border: none;
    border-radius: 12px;
    padding: 12px 18px;
    font-weight: 700;
}

.btn-back {
    background: var(--bs-body-bg);
}

.btn-next,
.btn-submit {
    background: var(--bs-primary);
    color: white;
}

@media (max-width: 991px) {
    .stepper-wrapper {
        grid-template-columns: 1fr;
    }

    .stepper-sidebar {
        position: static;
    }
}
</style>
