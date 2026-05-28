<template>
    <div class="wrapper position-relative payslip-page">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="payslip-top-grid">
                    <div class="card shadow rounded-4 payslip-filter">
                        <form class="card-body payslip-filter__body" @submit.prevent="fetchPayslipData">
                            <div class="payslip-filter__field">
                                <label for="month" class="form-label">Month</label>
                                <select id="month" v-model="selectedMonth" class="form-control">
                                    <option v-for="(month, idx) in months" :key="idx" :value="idx + 1">
                                        {{ month }}
                                    </option>
                                </select>
                            </div>
                            <div class="payslip-filter__field">
                                <label for="year" class="form-label">Year</label>
                                <select id="year" v-model="selectedYear" class="form-control">
                                    <option v-for="year in years" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>
                            <div class="payslip-filter__field payslip-filter__field--cutoff">
                                <label for="cutoff" class="form-label">Cutoff</label>
                                <select id="cutoff" v-model="selectedCutoff" class="form-control">
                                    <option value="">All Cutoffs</option>
                                    <option value="first_cutoff">1st Cutoff</option>
                                    <option value="second_cutoff">2nd Cutoff</option>
                                </select>
                            </div>
                            <div class="payslip-filter__search">
                                <button
                                    type="submit"
                                    class="btn btn-primary px-4"
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
                        </form>
                    </div>

                    <div class="card shadow rounded-4 payslip-reminder">
                        <div class="card-body">
                            <div class="payslip-reminder__header">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Payslip Reminders</span>
                            </div>
                            <ul class="payslip-reminder__list">
                                <li>Use the cutoff filter when you need to view only the 1st or 2nd cutoff payslip.</li>
                                <li>Downloaded files follow the same month, year, and cutoff currently selected.</li>
                                <li>For missing payroll records or incorrect amounts, contact HR for validation.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="payslip-results">
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
                    <div v-else class="card shadow rounded-4 payslip-card">
                        <div class="card-header bg-white d-flex justify-content-end gap-2 py-3 payslip-actions" role="group">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-warning px-4 py-2 d-flex align-items-center gap-2 fw-semibold"
                                :disabled="downloading"
                                @click="downloadPayslip"
                            >
                                <i class="fa-solid fa-download"></i>
                                {{ downloading ? 'Downloading...' : 'Download' }}
                            </button>
                        </div>
                        <div class="card-body payslip-card__body">
                            <component
                                :is="templateFor(ps)"
                                v-for="(ps, index) in payslip"
                                :key="index"
                                :payslip="ps"
                            />
                        </div>
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
            selectedCutoff: '',
            payslip: [],
            loading: false,
            downloading: false,
            isLoaded: false,
            error: null
        };
    },
    mounted() {
        if (this.applyFiltersFromUrl()) {
            this.fetchPayslipData(false);
        }
    },
    methods: {
        fetchPayslipData(updateUrl = true) {
            if (updateUrl) {
                this.syncFiltersToUrl();
            }

            this.loading = true;
            this.error = null;
            this.payslip = [];
            axios.get('/employee/payslip/data', {
                params: {
                    month: this.selectedMonth,
                    year: this.selectedYear,
                    cutoff: this.selectedCutoff || undefined
                }
            }).then(response => {
                this.payslip = response.data;   
            }).catch(error => {
                this.error = error.response.data.message || 'An error occurred while fetching payslip data.';
            }).finally(() => {
                this.loading = false;
                this.isLoaded = true;
            });
        },
        applyFiltersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const month = params.get('month');
            const year = Number(params.get('year'));
            const cutoff = params.get('cutoff');
            let hasFilter = false;

            const monthValue = this.monthValueFromQuery(month);
            if (monthValue) {
                this.selectedMonth = monthValue;
                hasFilter = true;
            }

            if (year && this.years.includes(year)) {
                this.selectedYear = year;
                hasFilter = true;
            }

            if (['first_cutoff', 'second_cutoff'].includes(cutoff)) {
                this.selectedCutoff = cutoff;
                hasFilter = true;
            }

            return hasFilter;
        },
        syncFiltersToUrl() {
            const params = new URLSearchParams(window.location.search);
            params.set('month', this.months[this.selectedMonth - 1]);
            params.set('year', this.selectedYear);

            if (this.selectedCutoff) {
                params.set('cutoff', this.selectedCutoff);
            } else {
                params.delete('cutoff');
            }

            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        },
        monthValueFromQuery(month) {
            if (!month) {
                return null;
            }

            const numericMonth = Number(month);
            if (Number.isInteger(numericMonth) && numericMonth >= 1 && numericMonth <= 12) {
                return numericMonth;
            }

            const normalizedMonth = month.toLowerCase();
            const monthIndex = this.months.findIndex((item) => item.toLowerCase() === normalizedMonth);

            return monthIndex >= 0 ? monthIndex + 1 : null;
        },
        async downloadPayslip() {
            this.downloading = true;

            try {
                const response = await axios.get('/employee/payslip/download', {
                    params: {
                        month: this.selectedMonth,
                        year: this.selectedYear,
                        cutoff: this.selectedCutoff || undefined
                    },
                    responseType: 'blob'
                });

                const blob = new Blob([response.data], {
                    type: response.headers['content-type']
                });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = this.filenameFromHeader(response.headers['content-disposition']);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                const message = await this.errorMessage(error);
                if (window.Swal) {
                    window.Swal.fire('Payslip Download', message, 'info');
                } else {
                    alert(message);
                }
            } finally {
                this.downloading = false;
            }
        },
        filenameFromHeader(header) {
            const match = header?.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
            return match ? match[1].replace(/['"]/g, '') : 'payslip.xlsx';
        },
        async errorMessage(error) {
            const fallback = 'Unable to download payslip.';
            const data = error.response?.data;

            if (data instanceof Blob) {
                try {
                    return JSON.parse(await data.text()).message || fallback;
                } catch (_) {
                    return fallback;
                }
            }

            return data?.message || fallback;
        },
        templateFor(payslip) {
            return String(payslip.employment_type_id || '') === '2'
                ? 'cos-template'
                : 'cos-template';
        }
    }
};
</script>

<style scoped lang="scss">
.payslip-page {
    width: 100%;
}

.payslip-filter {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 18px !important;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12) !important;
    width: 100%;
}

.payslip-top-grid {
    display: grid;
    gap: 20px;
    grid-template-columns: minmax(0, 1.85fr) minmax(340px, 0.9fr);
    align-items: stretch;
}

.payslip-filter__body {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(160px, 240px) minmax(180px, 260px);
    column-gap: 22px;
    row-gap: 22px;
    align-items: end;
    padding: 24px 22px 20px;
}

.payslip-filter__field {
    min-width: 0;
}

.payslip-filter__field .form-label {
    color: #111827;
    font-size: 0.95rem;
    margin-bottom: 10px;
}

.payslip-filter__field .form-control {
    border-color: #d8dee8;
    border-radius: 7px;
    color: #111827;
    font-size: 1rem;
    min-height: 45px;
    padding-left: 15px;
}

.payslip-filter__field .form-control:focus {
    border-color: #0a367f;
    box-shadow: 0 0 0 0.18rem rgba(10, 54, 127, 0.12);
}

.payslip-filter__search {
    display: flex;
    grid-column: 3 / 4;
    justify-content: flex-end;
}

.payslip-filter__search .btn {
    align-items: center;
    background: #062d73;
    border-color: #062d73;
    border-radius: 9px;
    display: inline-flex;
    font-size: 1rem;
    font-weight: 600;
    justify-content: center;
    min-height: 45px;
    width: 152px;
}

.payslip-filter__search .btn span {
    align-items: center;
    display: inline-flex;
    gap: 8px;
    white-space: nowrap;
}

.payslip-filter__field--cutoff {
    max-width: none;
}

.payslip-reminder {
    border: 1px solid rgba(10, 54, 127, 0.12);
    border-radius: 18px !important;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1) !important;
    height: 100%;
    overflow: hidden;
}

.payslip-reminder .card-body {
    background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
    padding: 24px 22px;
}

.payslip-reminder__header {
    align-items: center;
    color: #062d73;
    display: flex;
    font-size: 1.02rem;
    font-weight: 700;
    gap: 10px;
    margin-bottom: 14px;
}

.payslip-reminder__list {
    color: #5b6475;
    font-size: 0.92rem;
    line-height: 1.45;
    margin: 0;
    padding-left: 18px;
}

.payslip-reminder__list li + li {
    margin-top: 8px;
}

.payslip-results {
    width: 100%;
}

.payslip-card {
    overflow: hidden;
}

.payslip-card__body {
    overflow-x: auto;
    padding: 18px;
}

@media (max-width: 767.98px) {
    .payslip-top-grid {
        grid-template-columns: 1fr;
    }

    .payslip-filter__body {
        grid-template-columns: 1fr;
        padding: 18px;
    }

    .payslip-filter__search {
        grid-column: auto;
        justify-content: stretch;
    }

    .payslip-filter__search .btn {
        width: 100%;
    }

    .payslip-actions {
        flex-wrap: wrap;
    }

    .payslip-actions .btn {
        justify-content: center;
        width: 100%;
    }

    .payslip-card__body {
        padding: 12px;
    }
}

</style>
