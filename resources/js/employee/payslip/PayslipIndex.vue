<template>
    <div class="wrapper position-relative">
        <div class="row">
            <div class="col-12 col-md-4 mb-3 position-sticky" style="align-self: start; top: 26px;">
                <div class="card shadow rounded-4">
                    <form class="card-body">
                        <div class="mb-3">
                            <label for="month">Month</label>
                            <select id="month" v-model="selectedMonth" class="form-control">
                                <option v-for="(month, idx) in months" :key="idx" :value="idx + 1">
                                    {{ month }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="year">Year</label>
                            <select id="year" v-model="selectedYear" class="form-control">
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </form>
                    <div class="card-footer d-flex justify-content-end py-3">
                        <button 
                            @click="fetchPayslipData" 
                            class="btn btn-primary"
                            :disabled="loading"
                        >
                            <span v-if="loading">
                                <i class="fa fa-spinner fa-spin"></i> Loading...
                            </span>
                            <span v-else>
                                <i class="fa fa-search"></i> Search
                            </span>
                        </button>
                    </div>
                </div>

                <div v-if="payslip.length != 0" class="d-flex gap-2 justify-content-end mt-3" role="group">
                    <button type="button" class="btn btn-sm btn-warning px-4 py-2 d-flex align-items-center gap-2 fw-semibold">
                        <i class="fa-solid fa-print"></i>
                        Print
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-warning px-4 py-2 d-flex align-items-center gap-2 fw-semibold">
                        <i class="fa-solid fa-download"></i>
                        Download
                    </button>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div>
                    <!-- Initial state -->
                    <div 
                        v-if="!isLoaded && !loading" 
                        class="alert alert-info d-flex align-items-center gap-3 rounded-4 shadow-sm py-3 px-4"
                        role="alert"
                    >
                        <i class="fa fa-info-circle fs-4 text-info"></i>
                        <div>
                            <strong>Please select a month and year</strong>
                            <div class="small text-muted">
                                Choose a period on the left, then click <b>Search</b> to load the data.
                            </div>
                        </div>
                    </div>

                    <!-- Loading state -->
                    <div 
                        v-else-if="loading" 
                        class="alert alert-secondary d-flex align-items-center gap-3 rounded-4 shadow-sm py-3 px-4"
                        role="alert"
                    >
                        <i class="fa fa-spinner fa-spin fs-4 text-secondary"></i>
                        <div>
                            <strong>Loading payslip</strong>
                            <div class="small text-muted">
                                Please wait while we fetch your payroll details.
                            </div>
                        </div>
                    </div>

                    <!-- Error state -->
                    <div 
                        v-else-if="error" 
                        class="alert alert-danger d-flex align-items-center gap-3 rounded-4 shadow-sm py-3 px-4"
                        role="alert"
                    >
                        <i class="fa-solid fa-circle-exclamation fs-4 text-danger"></i>
                        <div>
                            <strong>Oops, sorry!</strong>
                            <div class="small text-muted">
                                {{ error }}
                            </div>
                        </div>
                    </div>

                    <!-- Success state -->
                    <div v-else>
                        <cos-template v-for="(ps, index) in payslip" :key="index" :payslip="ps" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import CosTemplate from './templates/CosTemplate.vue';
export default {
    components: {
        CosTemplate
    },
    data() {
        const currentYear = new Date().getFullYear();
        return {
            months: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ],
            years: Array.from({ length: 5 }, (_, i) => currentYear - i),
            selectedMonth: new Date().getMonth() + 1,
            selectedYear: currentYear,
            payslip: [],
            loading: false,
            isLoaded: false,
            error: null
        };
    },
    methods: {
        fetchPayslipData() {
            this.loading = true;
            this.error = null;
            this.payslip = [];
            axios.get('/employee/payslip/data', {
                params: {
                    month: this.selectedMonth,
                    year: this.selectedYear
                }
            }).then(response => {
                this.payslip = response.data;   
            }).catch(error => {
                console.error(error);
                this.error = error.response.data.message || 'An error occurred while fetching payslip data.';
            }).finally(() => {
                this.loading = false;
                this.isLoaded = true;
            });
        }
    }
};
</script>