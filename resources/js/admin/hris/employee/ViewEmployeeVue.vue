<template>
    <div>
        <!-- Common Info (Ultra Compact) -->
        <CommonInfo :details="formattedDetails" />

        <div class="card position-relative" id="card-container">
            <!-- Header -->
            <div class="card-header border-0 py-4 bg-body-secondary">
                <h5 class="text-primary fw-bold mb-0">
                    <i class="fa-solid fa-users me-2"></i> Uploaded List
                </h5>
            </div>
            
            <div class="card-body">
                <!-- Employee Accordion -->
                <div class="accordion mb-4" id="employeeAccordion">
                    <div class="accordion-item border-0 mb-3"
                        v-for="(employee, index) in employees"
                        :key="index">
                        <!-- Accordion Header (Balanced Layout) -->
                        <h2 class="accordion-header " :id="'heading' + index">
                            <button class="accordion-button collapsed rounded px-3 py-2 d-flex align-items-center bg-body-secondary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    :data-bs-target="'#collapse' + index"
                                    aria-expanded="false"
                                    :aria-controls="'collapse' + index"
                                    :class="{ 'bg-danger bg-opacity-25': hasError(index) }">

                                <!-- Wrapper for index + employee info -->
                                <div class="d-flex flex-grow-1 align-items-center">
                                    <!-- Index -->
                                    <div class="me-3 flex-shrink-0 text-center" style="width: 28px;">
                                        <span class="fw-bold text-primary">{{ index + 1 }}.</span>
                                    </div>

                                    <!-- Employee Info -->
                                    <div class="flex-grow-1 lh-sm">
                                        <div class="fw-semibold text-body">
                                            {{ employee.lastname }}, {{ employee.firstname }} {{ employee.middlename }}
                                        </div>
                                        <div class="small text-body-secondary text-capitalize">
                                            {{ employee.employee_no }} · <i>{{ employee.position }}</i>
                                        </div>
                                    </div>
                                </div>
                            </button>
                            <button class="delete-button" @click="removeFromList(index)">
                              <i class="fa-solid fa-xmark"></i>
                            </button>  
                        </h2>

                        <!-- Accordion Body -->
                        <div :id="'collapse' + index"
                            class="accordion-collapse collapse"
                            :aria-labelledby="'heading' + index"
                            data-bs-parent="#employeeAccordion">
                            <div class="accordion-body">

                                <!-- Section: Basic Info -->
                                <div class="section-title">Basic Information</div>
                                <div class="row mb-3">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Employee No <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.employee_no"
                                            :class="{ 'is-invalid': errors[`employees.${index}.employee_no`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.employee_no`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.employee_no`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Bio ID</label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.bio_id"
                                            :class="{ 'is-invalid': errors[`employees.${index}.bio_id`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.bio_id`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.bio_id`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Date Hired <span class="text-danger"> *</span> <span class="text-info">( last promotion )</span></label>
                                        <input type="date"
                                            class="form-control form-control-sm"
                                            v-model="employee.date_hired_company"
                                            :class="{ 'is-invalid': errors[`employees.${index}.date_hired_company`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.date_hired_company`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.date_hired_company`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Date Hired <span class="text-danger"> *</span> <span class="text-info">( original appointment )</span></label>
                                        <input type="date"
                                            class="form-control form-control-sm"
                                            v-model="employee.date_hired_organization"
                                            :class="{ 'is-invalid': errors[`employees.${index}.date_hired_organization`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.date_hired_organization`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.date_hired_organization`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Suffix</label>

                                        <select class="form-control form-control-sm text-uppercase"
                                                v-model="employee.suffix"
                                                :class="{ 'is-invalid': errors[`employees.${index}.suffix`] }">

                                            <option :value="null">- CHOOSE -</option>
                                            <option v-for="suffix in selectOptions.suffixes"
                                                    :key="suffix.value"
                                                    :value="suffix.value">
                                                {{ suffix.label }}
                                            </option>
                                        </select>

                                        <span class="text-danger" v-if="errors[`employees.${index}.suffix`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.suffix`]" :key="i">
                                                {{ err }}
                                            </span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Firstname <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.firstname"
                                            :class="{ 'is-invalid': errors[`employees.${index}.firstname`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.firstname`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.firstname`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-2 mb-3">
                                        <label class="form-label">Middlename</label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.middlename"
                                            :class="{ 'is-invalid': errors[`employees.${index}.middlename`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.middlename`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.middlename`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Lastname <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.lastname"
                                            :class="{ 'is-invalid': errors[`employees.${index}.lastname`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.lastname`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.lastname`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Email <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.email"
                                            :class="{ 'is-invalid': errors[`employees.${index}.email`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.email`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.email`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Status <span class="text-danger"> *</span></label>
                                        <select class="form-control form-control-sm"
                                                v-model="employee.isActive"
                                                :class="{ 'is-invalid': errors[`employees.${index}.isActive`] }">
                                            <option v-for="status in selectOptions.status"
                                                    :key="status.value"
                                                    :value="status.value">
                                                {{ status.label }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errors[`employees.${index}.isActive`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.isActive`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                </div>

                                <!-- Section: Position & Tranche -->
                                <div class="section-title">Position & Tranche </div>
                                <div class="row mb-3">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Position <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.position"
                                            :class="{ 'is-invalid': errors[`employees.${index}.position`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.position`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.position`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Tranche <span class="text-danger"> *</span></label>
                                        <select class="form-control form-control-sm"
                                                v-model="employee.tranche"
                                                @change="computeSalary(employee.tranche , employee.salary_grade, employee.step, index)"
                                                :class="{ 'is-invalid': errors[`employees.${index}.tranche`] }">
                                            <option value="">-- CHOOSE --</option>
                                            <option v-for="tranche in filteredTranches"
                                                    :key="tranche.id"
                                                    :value="tranche.id">
                                               <span>{{ new Date(tranche.date).toLocaleDateString('en-PH', { 
                                                    year: 'numeric', 
                                                    month: 'long', 
                                                    day: 'numeric' 
                                                }) }}</span>
                                                  &mdash; {{ tranche.description }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errors[`employees.${index}.tranche`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.tranche`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Salary Grade <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.salary_grade"
                                            @change="computeSalary(employee.tranche , employee.salary_grade, employee.step, index)"
                                            :class="{ 'is-invalid': errors[`employees.${index}.salary_grade`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.salary_grade`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.salary_grade`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Step <span class="text-danger"> ( required if permanent )</span></label>
                                        <select class="form-control form-control-sm"
                                                v-model="employee.step"
                                                :disabled="details.employment_type_id == 2"
                                                @change="computeSalary(employee.tranche , employee.salary_grade, employee.step, index)"
                                                :class="{ 'is-invalid': errors[`employees.${index}.step`] }">
                                            <option v-for="n in 8"
                                                    :key="n"
                                                    :value="n">
                                                {{ n }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errors[`employees.${index}.step`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.step`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Section: Payroll -->
                                <div class="section-title">Payroll Details</div>
                                <div class="row mb-3 border-bottom pb-3">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Salary Frequency <span class="text-danger"> *</span></label>
                                        <select class="form-control form-control-sm"
                                                v-model="employee.salary_frequency"
                                                @change="handleSalaryCutoffChange(employee.salary_frequency, index)"
                                                :class="{ 'is-invalid': errors[`employees.${index}.salary_frequency`] }">
                                            <option value="">-- CHOOSE --</option>
                                            <option v-for="freq in selectOptions.salaryFrequency"
                                                    :key="freq.value"
                                                    :value="freq.value">
                                                {{ freq.label }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errors[`employees.${index}.salary_frequency`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.salary_frequency`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Salary Cutoff <span class="text-danger"> *</span></label>
                                        <div class="d-flex gap-md-4 pt-2 flex-wrap"
                                            :class="{ 'is-invalid': errors[`employees.${index}.salary_cutoff`] }">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                    type="radio"
                                                    :name="'salary_cutoff' + index"
                                                    value="first_cutoff"
                                                    v-model="employee.salary_cutoff"
                                                    :disabled="employee.salary_frequency === 'twice'">
                                                <label class="form-check-label">
                                                    1st Cut-off (15th)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                    type="radio"
                                                    :name="'salary_cutoff' + index"
                                                    value="second_cutoff"
                                                    v-model="employee.salary_cutoff"
                                                    :disabled="employee.salary_frequency === 'twice'">
                                                <label class="form-check-label">
                                                    2nd Cut-off (30th)
                                                </label>
                                            </div>
                                        </div>
                                        <span class="text-danger" v-if="errors[`employees.${index}.salary_cutoff`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.salary_cutoff`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-5 mb-3">
                                        <label class="form-label">Total Salary <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            disabled
                                            v-model="employee.total_salary"
                                            :class="{ 'is-invalid': errors[`employees.${index}.total_salary`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.total_salary`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.total_salary`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Daily Rate <span class="text-danger"> *</span></label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.daily_rate"
                                            disabled>
                                        <!-- usually no validation errors here since it's calculated -->
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Salary Method <span class="text-danger"> *</span></label>
                                        <select class="form-control form-control-sm"
                                                v-model="employee.salary_method"
                                                :class="{ 'is-invalid': errors[`employees.${index}.salary_method`] }">
                                            <option value="">-- CHOOSE --</option>
                                            <option v-for="method in selectOptions.salaryMethod"
                                                    :key="method.value"
                                                    :value="method.value">
                                                {{ method.label }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errors[`employees.${index}.salary_method`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.salary_method`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Deduction On <span class="text-danger"> *</span></label>
                                        <select class="form-control form-control-sm"
                                                v-model="employee.deduction_on"
                                                :class="{ 'is-invalid': errors[`employees.${index}.deduction_on`] }">
                                            <option value="">-- CHOOSE --</option>
                                            <option value="first_cutoff">First Cutoff</option>
                                            <option value="second_cutoff">Second Cutoff</option>
                                            <option value="both">Both</option>
                                        </select>
                                        <span class="text-danger" v-if="errors[`employees.${index}.deduction_on`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.deduction_on`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>

                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="form-label">Payroll Account No</label>
                                        <input type="text"
                                            class="form-control form-control-sm"
                                            v-model="employee.payroll_account_no"
                                            :class="{ 'is-invalid': errors[`employees.${index}.payroll_account_no`] }">
                                        <span class="text-danger" v-if="errors[`employees.${index}.payroll_account_no`]">
                                            <span v-for="(err, i) in errors[`employees.${index}.payroll_account_no`]" :key="i">{{ err }}</span>
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="card-footer bg-transparent mt-3 pt-3 d-flex justify-content-end gap-3">
                    <button type="button" @click="backToForm" class="btn btn-danger px-4 py-3">
                        <i class="fa-solid fa-arrow-left me-2"></i> Return
                    </button>
                    <button 
                        type="button" 
                        @click="submitEmployees" 
                        class="btn btn-primary px-4 py-3 position-sticky"
                        style="bottom: 20px;" 
                        :disabled="loading"
                        >
                        <span v-if="loading">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i> Saving...
                        </span>
                        <span v-else>
                            <i class="fa-solid fa-save me-2"></i> Save
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const token = localStorage.getItem('auth_token');
import CommonInfo from '../components/CommonInfo.vue';
export default {
    components: { CommonInfo },
    data() {
        return {
            selectOptions: {
                status: [
                    { value: "active", label: "Active" },
                    { value: "inactive", label: "Inactive" },
                    { value: "archived", label: "Archived" },
                ],
                salaryFrequency: [
                    { value: "once", label: "Once (Monthly)" },
                    { value: "twice", label: "Twice (Semi-Monthly)" },
                ],
                salaryMethod: [
                    { value: "cash", label: "Cash" },
                    { value: "bank transfer", label: "Bank Transfer" },
                    { value: "paycheck", label: "Paycheck" },
                    { value: "e_wallet", label: "E-Wallet" },
                ],
                suffixes: [
                    { value: 'jr', label: 'Jr' },
                    { value: 'sr', label: 'Sr' },
                    { value: 'I', label: 'I' },
                    { value: 'II', label: 'II' },
                    { value: 'III', label: 'III' },
                    { value: 'IV', label: 'IV' },
                    { value: 'V', label: 'V' },
                ],
            },
            errors: {},
            tranches: [],
            filteredTranches: [],
            salary_grade: [],
            loading: false
        }
    },
    props: {
        employees: {
            type: Array,
            default: () => []
        },
        details: {
            type: Object,
            default: () => ({})
        },
        employmentTypes: Array,
        divisions: Array,
        units: Array,
        shifts: Array,
        schedules: Array,
    },
    created() {
        this.fetchData('tranches', '/api/tranches', true);
    },
    computed: {
        formattedDetails() {
            return {
                employment_type: this.getName("employmentTypes", this.details.employment_type_id),
                division: this.getName("divisions", this.details.division_id),
                unit: this.getName("units", this.details.unit_id),
                shift: this.getName("shifts", this.details.shift_id),
                schedule: this.getName("schedules", this.details.work_schedule_id)
            };
        }
    },
     watch: {
      'details.employment_type_id': {
        immediate: true, // runs once on component mount
        handler(newVal) {
          this.filterTranches(newVal);
        }
      }
    },
    methods: {
        filterTranches(employmentTypeId) {
            if (!employmentTypeId) {
                this.filteredTranches = this.tranches;
                return;
            }

            this.filteredTranches = this.tranches.filter(
                tranche => Number(tranche.employment_type_id) === Number(employmentTypeId)
            );
        },
        hasError(index) {
            return Object.keys(this.errors).some(key => key.startsWith(`employees.${index}`));
        },
        fetchData(stateKey, url, useDataWrapper = false) {
            axios.get(url, {
                headers: { Authorization: `Bearer ${token}` }
            }).then(response => {
                this[stateKey] = useDataWrapper ? response.data.data : response.data;
            });
        },
        computeSalary(tranche_id, salary_grade, step, index) {
            axios.get(`/api/compute-salary/${tranche_id}/${salary_grade}/${step}`, {
                headers: { Authorization: `Bearer ${token}` }
            })
            .then(response => {
                this.employees[index].total_salary = response.data.data.total_salary;
                this.employees[index].daily_rate = response.data.data.daily_rate;
            })
            .catch(error => {
                console.error('Error computing salary:', error);
            });
        },
        handleSalaryCutoffChange(salary_frequency, index) {
            if (salary_frequency === 'twice') {
                this.employees[index].salary_cutoff = null
            } else {
                this.employees[index].salary_cutoff = 'second_cutoff'
            }
        },
        backToForm() {
            this.$emit('back-to-form')
        },
        getName(objectName, id) {
            const list = this[objectName] || [];
            const item = list.find(d => Number(d.id) === Number(id));
            return item ? item.name : 'Unknown';
        },
        async submitEmployees() {
            // Bundle both props into one payload
            const payload = {
                details: this.details,
                employees: this.employees
            };

            this.loading = true;
            this.errors = {};
            try {
                const res = await axios.post('/api/employee/import', payload, {
                    headers: { 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                Swal.fire({
                    title: "Success!",
                    text: res.data.message ?? "Employees successfully added.",
                    icon: "success"
                }).then(() => {
                    window.location = "/admin/hris/employee";
                });
            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                    // get all indexes from employee errors
                    const indexes = Object.keys(this.errors)
                        .map(key => {
                            const match = key.match(/employees\.(\d+)\./);
                            return match ? parseInt(match[1], 10) : null;
                        })
                        .filter(i => i !== null);

                    // get the smallest index
                    const minIndex = Math.min(...indexes);

                    if (!isNaN(minIndex)) {
                        // open the collapse
                        $(`#collapse${minIndex}`).collapse('show');

                        // scroll to first accordion error
                        this.scrollTo(`heading${minIndex}`);
                    }
                } else {
                    Swal.fire("Error", error.response?.data?.message || "Something went wrong.", "error");
                }
            } finally {
                this.loading = false;
            }
        },
        scrollTo(element_id) {
            // scroll smoothly to the accordion header
            const header = document.getElementById(element_id);
            if (header) {
                header.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center' // center it on screen
                });
            }
        },
        removeFromList(index) {
          Swal.fire({
            title: "Are you sure?",
            text: "This employee will be removed from the list.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, remove it!"
          }).then((result) => {
            if (result.isConfirmed) {
              this.$emit('remove-employee', index);

            }
          });
        }
    }
}
</script>

<style lang="scss" scoped>
@import '../../../../sass/variables';

.is-invalid-accordion {
    background-color: rgba($danger, 0.1) !important;
}
.section-title {
    font-weight: 600;
    text-align: center;
    padding: 0.5rem;
    margin-bottom: 1rem;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 0.25rem;
    text-transform: uppercase;
    font-size: 0.9rem;
}

.mini-box {
    background: #f8f9fa;
    border-radius: .35rem;
    padding: .35rem .5rem;
    border: 1px solid #e5e7eb;
}
.mini-box .label {
    font-size: .80rem;
    font-weight: 500;
    color: #6c757d;
    text-transform: uppercase;
    display: block;
    margin-bottom: .1rem;
}
.mini-box .value {
    font-size: .90rem;
    font-weight: 600;
    color: #212529;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@mixin text-camelcase {
  text-transform: lowercase;

  &::first-letter {
    text-transform: lowercase;
  }
}
.text-camelcase {
  @include text-camelcase;
}

.accordion-item {
  .accordion-header {
    display: flex;
    border: none;
    align-items: center;
    gap: 6px;
    position: relative;
    .delete-button {
      background-color: transparent;
      border: none;
      font-weight: 200 !important;
      font-size: 8px;
      height: 20px;
      width: 20px;
      border-radius: 8px;
      transition: all ease-in-out 0.2s;
      &:hover {
        background-color: rgba($danger, 0.8);
        color: $light;
      }
    }
  }
}
</style>
