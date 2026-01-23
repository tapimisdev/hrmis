<template>
    <div class="stepper-container">
        <div class="stepper-wrapper">
            <!-- Sidebar Stepper -->
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
                            <i
                                v-if="index < currentStep"
                                class="fas fa-check"
                            ></i>
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
                        :style="{
                            width:
                                ((currentStep + 1) / steps.length) * 100 + '%',
                        }"
                    ></div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="stepper-content border">
                <LoaderVue
                    :hasBackground="true"
                    :visible="loading"
                    status="loading"
                    message="Loading..."
                />

                <div class="content-body">
                    <keep-alive
                        :include="[
                            'CreatePayroll',
                            'ReviewPayroll',
                            'ApprovalPayroll',
                        ]"
                    >
                        <component
                            :is="steps[currentStep].component"
                            v-model="form"
                            :errors="errors"
                        />
                    </keep-alive>
                </div>

                <div class="content-footer">
                    <button
                        class="btn btn-back"
                        :disabled="currentStep === 0"
                        @click="prevStep"
                    >
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </button>

                    <span class="step-mobile"
                        >{{ currentStep + 1 }}/{{ steps.length }}</span
                    >

                    <button
                        v-if="currentStep < steps.length - 1"
                        class="btn btn-next"
                        :disabled="
                            loading || (nextDisabled && currentStep == 1)
                        "
                        @click="nextStep"
                    >
                        <span v-if="loading">
                            <span
                                class="spinner-border spinner-border-sm"
                            ></span>
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
import AdjustmentPayroll from "./steps/AdjustmentPayroll.vue";
import ReviewPayroll from "./steps/ReviewPayroll.vue";
import ApprovalPayroll from "./steps/ApprovalPayroll.vue";
import LoaderVue from "../../../../components/LoaderVue.vue";

export default {
    name: "PeraRataStepper",
    components: {
        CreatePayroll,
        AdjustmentPayroll,
        ReviewPayroll,
        ApprovalPayroll,
        LoaderVue,
    },
    data() {
        return {
            token: localStorage.getItem("auth_token"),
            currentStep: 0,
            loading: false,
            nextDisabled: false,
            errors: {},
            employees: [],
            form: {
                label: "",
                month: "",
                employees: [],
                employment_type_id: 1,
                approved_by: {},
            },
            steps: [
                {
                    label: "Create PERA & RATA Payroll",
                    desc: "Set basic details",
                    component: markRaw(CreatePayroll),
                },
                {
                    label: "Employee Review",
                    desc: "View eligibility",
                    component: markRaw(ReviewPayroll),
                },
                {
                    label: "Approval",
                    desc: "Finalize & submit",
                    component: markRaw(ApprovalPayroll),
                },
            ],
        };
    },
    methods: {
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
                const res = await axios.post(
                    "/api/payroll/pera-rata/check-employees",
                    this.form,
                    {
                        headers: {
                            Accept: "application/json",
                            Authorization: `Bearer ${this.token}`,
                        },
                    }
                );
                this.form.employees = res.data.data;
                if (this.form.employees.eligible.length == 0) {
                    this.nextDisabled = true;
                } else {
                    this.nextDisabled = false;
                }
                return true;
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    Swal.fire(
                        error.response?.data?.message ===
                            "No employees found for this employment type."
                            ? "No Employees Found"
                            : "Error",
                        error.response?.data?.message ||
                            "Something went wrong.",
                        error.response?.data?.message ===
                            "No employees found for this employment type."
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
                const res = await axios.post(
                    "/api/payroll/pera-rata/generate",
                    this.form,
                    {
                        headers: {
                            Accept: "application/json",
                            Authorization: `Bearer ${this.token}`,
                        },
                    }
                );
                this.form.employees = res.data.data;
                const batch_id = res.data.batch_id;
                const payroll_no = res.data.payroll_no;
                window.location.href = `/admin/payroll/pera-rata/${payroll_no}?batch_id=${batch_id}`;
                return true;
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    Swal.fire(
                        error.response?.data?.message ===
                            "No employees found for this employment type."
                            ? "No Employees Found"
                            : "Error",
                        error.response?.data?.message ||
                            "Something went wrong.",
                        error.response?.data?.message ===
                            "No employees found for this employment type."
                            ? "info"
                            : "error"
                    );
                }
                return false;
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        window.Echo.channel("refresh")
            .listen(".RefreshData", (e) => {
                this.validateAndGetReview();
            })
            .error((error) => {
                console.error("Error:", error);
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

/* Sidebar */
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
        background: linear-gradient(
            135deg,
            var(--bs-primary),
            var(--bs-secondary)
        );
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
    &:not(:last-child)::after {
        content: "";
        position: absolute;
        left: 18px;
        top: 40px;
        width: 2px;
        height: 32px;
        background: var(--bs-body-bg);
        transition: all 0.3s ease;
    }

    &:hover {
        transform: translateX(3px);
    }

    &.active {
        .step-circle {
            background: linear-gradient(
                135deg,
                var(--bs-primary),
                var(--bs-secondary)
            );
            color: $white;
            box-shadow: 0 4px 12px rgba(var(--bs-primary), 0.3);
        }
        h6 {
            color: var(--bs-primary);
            font-weight: 700;
        }
        p {
            color: var(--bs-secondary);
        }
    }

    &.completed {
        .step-circle {
            background: $success;
            color: $white;
        }
        &::after {
            background: $success;
        }
        h6,
        p {
            color: lighten($dark, 20%);
        }
    }
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-body-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.step-info {
    flex: 1;
    padding-top: 2px;

    h6 {
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 2px;
        color: lighten($dark, 20%);
        transition: all 0.3s ease;
    }

    p {
        font-size: 12px;
        color: lighten($dark, 35%);
        margin: 0;
        line-height: 1.4;
        transition: all 0.3s ease;
    }
}

.progress-bar {
    height: 6px;
    background: var(--bs-body-bg);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 20px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bs-primary), var(--bs-secondary));
    transition: width 0.5s ease;
    box-shadow: 0 2px 6px rgba(var(--bs-primary), 0.3);
}

/* Content */
.stepper-content {
    background: var(--bs-secondary-bg);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba($black, 0.08);
    display: flex;
    flex-direction: column;
    position: relative;
}

.content-body {
    flex: 1;
    padding: 32px;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.content-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 32px;
    background: var(--bs-secondary-bg);
    border-top: 2px solid var(--bs-body-bg);
    gap: 12px;
}

.btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba($black, 0.15);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
}

.btn-back {
    background: $white;
    color: lighten($dark, 15%);
    border: 2px solid var(--bs-body-bg);

    &:hover:not(:disabled) {
        background: var(--bs-body-bg);
        color: $dark;
    }
}

.btn-next {
    background: linear-gradient(135deg, var(--bs-primary), var(--bs-secondary));
    color: $white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary), 0.3);
}

.btn-submit {
    background: linear-gradient(135deg, $success, darken($success, 10%));
    color: $white;
    box-shadow: 0 4px 12px rgba($success, 0.3);
}

.step-mobile {
    display: none;
    font-weight: 700;
    color: var(--bs-primary);
}

/* Responsive */
@media (max-width: 991px) {
    .stepper-wrapper {
        grid-template-columns: 1fr;
    }

    .stepper-sidebar {
        position: static;
    }

    .steps-list {
        display: flex;
        overflow-x: auto;
        gap: 16px;
        padding-bottom: 12px;
    }

    .step {
        flex-direction: column;
        align-items: center;
        text-align: center;
        min-width: 120px;
        margin: 0;

        &::after {
            display: none;
        }
    }

    .step-info h6 {
        font-size: 13px;
    }
    .step-info p {
        font-size: 11px;
    }
}

@media (max-width: 768px) {
    .stepper-container {
        padding: 16px;
    }
    .stepper-sidebar,
    .stepper-content {
        border-radius: 16px;
        padding: 20px;
    }
    .content-body {
        padding: 24px 20px;
    }
    .content-footer {
        padding: 16px 20px;
    }

    .btn span {
        display: none;
    }
    .btn {
        min-width: 44px;
        justify-content: center;
    }
    .btn-submit {
        flex: 1;
        span {
            display: inline;
        }
    }

    .step-mobile {
        display: block;
    }
}
</style>
