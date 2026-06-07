<template>
    <div class="individual-tax-page">
        <div class="individual-tax-sheet">
            <div class="individual-tax-toolbar">
                <div>
                    <h1 class="individual-tax-title">Income Tax Estimated Computation</h1>
                    <p class="individual-tax-subtitle">
                        First active regular employee in the system. For the year {{ selectedYearValue }}.
                    </p>
                </div>

                <div ref="toolbarForm" class="individual-tax-toolbar-form">
                    <div class="individual-tax-toolbar-actions">
                        <button
                            type="button"
                            class="btn individual-tax-toolbar-btn individual-tax-toolbar-btn--primary"
                            :disabled="isLoading"
                            @click="openRunModal"
                        >
                            <i class="fa-solid fa-play me-2"></i>
                            Calculate
                        </button>
                    </div>

                    <select
                        ref="employeeSelect"
                        v-model="selectedEmployeeNo"
                        class="form-select individual-tax-employee-select"
                        :disabled="isLoading"
                    >
                        <option
                            v-for="employeeOption in currentEmployees"
                            :key="employeeOption.employee_no"
                            :value="String(employeeOption.employee_no)"
                        >
                            {{ employeeOption.display_name }} - {{ employeeOption.employee_no }}
                        </option>
                    </select>

                    <div class="individual-tax-toolbar-inline-fields">
                        <div class="individual-tax-toolbar-stack">
                            <label class="individual-tax-toolbar-label" for="individual-tax-year-input">
                                Year
                            </label>
                            <input
                                id="individual-tax-year-input"
                                v-model="selectedYearInput"
                                type="text"
                                inputmode="numeric"
                                maxlength="4"
                                class="form-control individual-tax-select"
                                :disabled="isLoading"
                                placeholder="Year"
                                aria-label="Tax year"
                                @input="onYearInput"
                                @blur="applyYearInput"
                                @keyup.enter="applyYearInput"
                            />
                        </div>

                        <div class="individual-tax-toolbar-stack">
                            <label class="individual-tax-toolbar-label" for="individual-tax-train-law-input">
                                Train Law
                            </label>
                            <select
                                id="individual-tax-train-law-input"
                                class="form-select individual-tax-select"
                                :value="currentSelectedTrainLawId != null ? String(currentSelectedTrainLawId) : ''"
                                disabled
                                aria-label="Selected train law"
                            >
                                <option value="">Train law</option>
                                <option
                                    v-for="option in currentTrainLawOptions"
                                    :key="`toolbar-train-law-${option.id}`"
                                    :value="String(option.id)"
                                >
                                    {{ option.year }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isLoading" class="individual-tax-loading-bar"></div>

            <div class="individual-tax-meta">
                <div class="individual-tax-meta-card">
                    <span class="individual-tax-meta-label">Employee Name</span>
                    <div class="individual-tax-meta-value">{{ employeeName }}</div>
                </div>
                <div class="individual-tax-meta-card">
                    <span class="individual-tax-meta-label">Employee No</span>
                    <div class="individual-tax-meta-value">{{ currentEmployee.employee_no }}</div>
                </div>
                <div class="individual-tax-meta-card">
                    <span class="individual-tax-meta-label">Position</span>
                    <div class="individual-tax-meta-value">{{ currentEmployee.position || "N/A" }}</div>
                </div>
            </div>

            <div v-if="currentHasTaxationData" class="individual-tax-grid">
                <section class="individual-tax-panel">
                    <h2 class="individual-tax-heading">Annual Taxable Compensation Income Computation</h2>

                    <table class="individual-tax-table">
                        <tbody>
                            <tr>
                                <td>Annual Basic Salary</td>
                                <td class="amount individual-tax-highlight-blue">
                                    {{ peso(currentSummary.annual_basic_salary) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Hazard Pay</td>
                                <td class="amount">{{ peso(currentSummary.hazard_pay) }}</td>
                            </tr>
                            <tr>
                                <td>Longevity Pay</td>
                                <td class="amount">{{ peso(currentSummary.longevity_pay) }}</td>
                            </tr>
                            <tr>
                                <td>Government Bonuses</td>
                                <td class="amount">{{ peso(currentSummary.government_bonuses) }}</td>
                            </tr>
                            <tr>
                                <td>De minimis</td>
                                <td class="amount">{{ peso(currentSummary.de_minimis) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gross Compensation Income</strong></td>
                                <td class="amount individual-tax-highlight-pink">
                                    {{ peso(currentSummary.gross_compensation_income) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Tax-Exempt Bonus</td>
                                <td class="amount">{{ peso(currentSummary.tax_exempt_bonus) }}</td>
                            </tr>
                            <tr>
                                <td>Net Taxable Benefit</td>
                                <td class="amount">{{ peso(currentSummary.net_taxable_benefit) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gross Taxable Income</strong></td>
                                <td class="amount individual-tax-highlight-orange">
                                    {{ peso(currentSummary.gross_taxable_income) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Allowable Deductions</td>
                                <td class="amount">{{ peso(currentSummary.allowable_deductions) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Net Taxable Income</strong></td>
                                <td class="amount individual-tax-highlight-yellow">
                                    {{ peso(currentSummary.net_taxable_income) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="individual-tax-subsection">
                        <h2 class="individual-tax-heading">Annual Tax Due Computation</h2>

                        <table class="individual-tax-table">
                            <tbody>
                                <tr>
                                    <td>Selected Tax Table</td>
                                    <td class="amount">{{ currentAnnualTaxDueComputation.selected_tax_table_label || "No TRAIN Law table selected" }}</td>
                                </tr>
                                <tr>
                                    <td>Net Taxable Income</td>
                                    <td class="amount individual-tax-highlight-yellow">
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.net_taxable_income) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tax Bracket</td>
                                    <td class="amount">{{ currentAnnualTaxDueComputation.tax_bracket }}</td>
                                </tr>
                                <tr>
                                    <td>Excess Over</td>
                                    <td class="amount">{{ pesoWithSymbol(currentAnnualTaxDueComputation.excess_over) }}</td>
                                </tr>
                                <tr>
                                    <td>Taxable Excess</td>
                                    <td class="amount">
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.net_taxable_income) }}
                                        -
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.excess_over) }}
                                        =
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.taxable_excess) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Rate</td>
                                    <td class="amount">{{ formatPercent(currentAnnualTaxDueComputation.tax_rate) }}</td>
                                </tr>
                                <tr>
                                    <td>Variable Tax</td>
                                    <td class="amount">
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.taxable_excess) }}
                                        ×
                                        {{ formatPercent(currentAnnualTaxDueComputation.tax_rate) }}
                                        =
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.variable_tax) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Fixed Tax</td>
                                    <td class="amount">{{ pesoWithSymbol(currentAnnualTaxDueComputation.fixed_tax) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Annual Tax Due</strong></td>
                                    <td class="amount individual-tax-highlight-pink">
                                        {{ pesoWithSymbol(currentAnnualTaxDueComputation.annual_tax_due) }}
                                    </td>
                                </tr>
                                <tr v-if="currentAnnualTaxDueComputation.remarks">
                                    <td>Remarks</td>
                                    <td class="amount">{{ currentAnnualTaxDueComputation.remarks }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="individual-tax-panel">
                    <div class="individual-tax-heading-row">
                        <h2 class="individual-tax-heading mb-0">Breakdown</h2>

                        <div class="individual-tax-legend" aria-label="Breakdown source legend">
                            <span class="individual-tax-legend-item">
                                <span class="individual-tax-source-dot individual-tax-source-dot--draft"></span>
                                Draft
                            </span>
                            <span class="individual-tax-legend-item">
                                <span class="individual-tax-source-dot individual-tax-source-dot--pending"></span>
                                Pending
                            </span>
                            <span class="individual-tax-legend-item">
                                <span class="individual-tax-source-dot individual-tax-source-dot--approved"></span>
                                Approved
                            </span>
                            <span class="individual-tax-legend-item">
                                <span class="individual-tax-source-dot individual-tax-source-dot--for-releasing"></span>
                                For Releasing
                            </span>
                            <span class="individual-tax-legend-item">
                                <span class="individual-tax-source-dot individual-tax-source-dot--completed"></span>
                                Completed
                            </span>
                            <span class="individual-tax-legend-item">
                                <span class="individual-tax-source-dot individual-tax-source-dot--forecast"></span>
                                Forecasted
                            </span>
                        </div>
                    </div>

                    <table class="individual-tax-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="amount">Basic Salary</th>
                                <th class="amount">Hazard Pay</th>
                                <th class="amount">Longevity</th>
                                <th class="amount">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in currentMonthlyBreakdown" :key="row.month_number">
                                <td>{{ row.month_label }}</td>
                                <td class="amount">
                                    <span
                                        class="individual-tax-amount-with-source"
                                        :title="formatValueSource(row, 'basic_salary')"
                                    >
                                        <span class="individual-tax-amount-value">
                                            <span
                                                class="individual-tax-source-dot"
                                                :class="sourceDotClass(getValueSource(row, 'basic_salary'))"
                                                aria-hidden="true"
                                            ></span>
                                            {{ peso(row.basic_salary) }}
                                        </span>
                                    </span>
                                </td>
                                <td class="amount">
                                    <span
                                        class="individual-tax-amount-with-source"
                                        :title="formatValueSource(row, 'hazard_pay')"
                                    >
                                        <span class="individual-tax-amount-value">
                                            <span
                                                class="individual-tax-source-dot"
                                                :class="sourceDotClass(getValueSource(row, 'hazard_pay'))"
                                                aria-hidden="true"
                                            ></span>
                                            {{ peso(row.hazard_pay) }}
                                        </span>
                                    </span>
                                </td>
                                <td class="amount">
                                    <span
                                        class="individual-tax-amount-with-source"
                                        :title="formatValueSource(row, 'longevity_pay')"
                                    >
                                        <span class="individual-tax-amount-value">
                                            <span
                                                class="individual-tax-source-dot"
                                                :class="sourceDotClass(getValueSource(row, 'longevity_pay'))"
                                                aria-hidden="true"
                                            ></span>
                                            {{ peso(row.longevity_pay) }}
                                        </span>
                                    </span>
                                </td>
                                <td class="amount" :class="{ 'individual-tax-highlight-blue': index === currentMonthlyBreakdown.length - 1 }">
                                    <span
                                        class="individual-tax-amount-with-source"
                                        :title="formatSourceBreakdown(row)"
                                    >
                                        <span class="individual-tax-amount-value">
                                            <span
                                                class="individual-tax-source-dot"
                                                :class="sourceDotClass(row.source)"
                                                aria-hidden="true"
                                            ></span>
                                            {{ peso(row.total) }}
                                        </span>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="amount">{{ peso(currentSummary.annual_basic_salary) }}</th>
                                <th class="amount">{{ peso(currentSummary.annual_hazard_pay) }}</th>
                                <th class="amount">{{ peso(currentSummary.annual_longevity_pay) }}</th>
                                <th class="amount individual-tax-highlight-blue">
                                    {{ peso(currentSummary.gross_taxable_income) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="individual-tax-subsection">
                        <h3 class="individual-tax-subheading">Taxes Breakdown</h3>

                        <table class="individual-tax-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th
                                        v-for="column in currentTaxModuleColumns"
                                        :key="`tax-module-column-${column}`"
                                        class="amount"
                                    >
                                        {{ column }}
                                    </th>
                                    <th class="amount">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, index) in currentTaxModuleBreakdown"
                                    :key="`tax-module-${row.month_number}`"
                                >
                                    <td class="individual-tax-uppercase">{{ row.month_label }}</td>
                                    <td
                                        v-for="column in currentTaxModuleColumns"
                                        :key="`tax-module-${row.month_number}-${column}`"
                                        class="amount"
                                    >
                                        <button
                                            v-if="isTaxModuleItemEditable(row, column)"
                                            type="button"
                                            class="individual-tax-tax-override-trigger"
                                            :title="formatTaxModuleSource(row, column)"
                                            @click="openTaxOverrideModal(row, column)"
                                        >
                                            <span class="individual-tax-amount-value">
                                                <span
                                                    class="individual-tax-source-dot"
                                                    :class="sourceDotClass(getTaxModuleSource(row, column))"
                                                    aria-hidden="true"
                                                ></span>
                                                {{ peso(getTaxModuleAmount(row, column)) }}
                                            </span>
                                        </button>
                                        <span
                                            v-else
                                            class="individual-tax-amount-with-source"
                                            :title="formatTaxModuleSource(row, column)"
                                        >
                                            <span class="individual-tax-amount-value">
                                                <span
                                                    class="individual-tax-source-dot"
                                                    :class="sourceDotClass(getTaxModuleSource(row, column))"
                                                    aria-hidden="true"
                                                ></span>
                                                {{ peso(getTaxModuleAmount(row, column)) }}
                                            </span>
                                        </span>
                                    </td>
                                    <td
                                        class="amount"
                                        :class="{ 'individual-tax-highlight-blue': index === currentTaxModuleBreakdown.length - 1 }"
                                    >
                                        <span
                                            class="individual-tax-amount-with-source"
                                            :title="formatTaxModuleTotalSource(row)"
                                        >
                                            <span class="individual-tax-amount-value">
                                                <span
                                                    class="individual-tax-source-dot"
                                                    :class="sourceDotClass(getTaxModuleTotalSource(row))"
                                                    aria-hidden="true"
                                                ></span>
                                                {{ peso(row.amount) }}
                                            </span>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th
                                        v-for="column in currentTaxModuleColumns"
                                        :key="`tax-module-total-${column}`"
                                        class="amount"
                                    >
                                        {{ peso(getTaxModuleColumnTotal(column)) }}
                                    </th>
                                    <th class="amount individual-tax-highlight-blue">
                                        {{ peso(currentTaxModuleGrandTotal) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="individual-tax-note mt-3">
                            Click a forecasted tax amount to open override tax.
                        </div>
                    </div>
                </section>

                <section class="individual-tax-panel">
                    <h2 class="individual-tax-heading">Government Bonuses</h2>

                    <div class="individual-tax-list">
                        <div
                            v-for="item in currentOtherComponents.government_bonuses"
                            :key="`gov-bonus-${item.name}-${item.month || 'na'}-${item.amount}`"
                            class="individual-tax-list-row"
                        >
                            <span>
                                {{ item.name }}
                                <span v-if="item.month"> ({{ item.month }})</span>
                                <span v-if="item.source">
                                    -
                                    <span
                                        class="individual-tax-source-dot"
                                        :class="sourceDotClass(item.source)"
                                    ></span>
                                    {{ formatStatusLabel(item.source) }}
                                </span>
                            </span>
                            <span>{{ peso(item.amount) }}</span>
                        </div>
                        <div v-if="!currentOtherComponents.government_bonuses?.length" class="individual-tax-list-row">
                            <span>No government bonuses found.</span>
                            <span>{{ peso(0) }}</span>
                        </div>
                        <div class="individual-tax-list-row individual-tax-list-row--total">
                            <span>Total</span>
                            <span>{{ peso(currentSummary.government_bonuses_total) }}</span>
                        </div>
                    </div>

                    <h2 class="individual-tax-heading mt-4">De minimis</h2>

                    <div class="individual-tax-list">
                        <div
                            v-for="item in currentOtherComponents.earnings"
                            :key="`earning-${item.name}`"
                            class="individual-tax-list-row"
                        >
                            <span>{{ item.name }}</span>
                            <span>{{ peso(item.amount) }}</span>
                        </div>
                        <div v-if="!currentOtherComponents.earnings.length" class="individual-tax-list-row">
                            <span>No de minimis entries found.</span>
                            <span>{{ peso(0) }}</span>
                        </div>
                        <div class="individual-tax-list-row individual-tax-list-row--total">
                            <span>Total</span>
                            <span>{{ peso(currentSummary.de_minimis) }}</span>
                        </div>
                    </div>

                    <h2 class="individual-tax-heading mt-4">Allowables</h2>

                    <div class="individual-tax-list">
                        <div
                            v-for="item in currentOtherComponents.allowables"
                            :key="`allowable-${item.name}`"
                            class="individual-tax-list-row"
                        >
                            <span>{{ item.name }}</span>
                            <span>{{ peso(item.amount) }}</span>
                        </div>
                        <div v-if="!currentOtherComponents.allowables?.length" class="individual-tax-list-row">
                            <span>No allowables found.</span>
                            <span>{{ peso(0) }}</span>
                        </div>
                        <div class="individual-tax-list-row individual-tax-list-row--total">
                            <span>Total</span>
                            <span>{{ peso(currentSummary.allowables_total) }}</span>
                        </div>
                    </div>

                    <h2 class="individual-tax-heading mt-4">Tax Withheld</h2>

                    <table class="individual-tax-table individual-tax-table--compact">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th class="amount">Withheld</th>
                                <th class="amount">Balance</th>
                                <th class="amount">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in currentTaxWithheldRows"
                                :key="`tax-withheld-${row.key}`"
                            >
                                <td>{{ row.label }}</td>
                                <td class="amount">{{ peso(row.withheld) }}</td>
                                <td class="amount">{{ peso(row.balance) }}</td>
                                <td class="amount">{{ peso(row.total) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="amount">{{ peso(currentTaxWithheldTotals.withheld) }}</th>
                                <th class="amount">{{ peso(currentTaxWithheldTotals.balance) }}</th>
                                <th class="amount individual-tax-highlight-blue">{{ peso(currentTaxWithheldTotals.total) }}</th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="individual-tax-subsection">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                            <h3 class="individual-tax-subheading mb-0">Employee Portion Overrides</h3>
                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                :disabled="isRecomputingEmployee || !currentEmployee?.employee_no"
                                @click="handleRecomputeEmployee"
                            >
                                <i class="bi bi-arrow-repeat me-2"></i>
                                {{ isRecomputingEmployee ? "Recomputing..." : "Recompute" }}
                            </button>
                        </div>

                        <div class="individual-tax-employee-portion-card">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                <div>
                                    <div class="fw-semibold">{{ employeeName }}</div>
                                    <div class="text-muted small">{{ currentEmployee.employee_no || "No employee selected" }}</div>
                                </div>
                                <span
                                    class="badge"
                                    :class="portionTotal(employeePortionForm) === 100 ? 'text-bg-success' : 'text-bg-danger'"
                                >
                                    {{ formatPercent(portionTotal(employeePortionForm)) }}
                                </span>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label individual-tax-run-label">Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-cash-stack"></i>
                                        </span>
                                        <input
                                            v-model="employeePortionForm.salary"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label individual-tax-run-label">Hazard Pay</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </span>
                                        <input
                                            v-model="employeePortionForm.hazard_pay"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label individual-tax-run-label">Longevity</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-clock-history"></i>
                                        </span>
                                        <input
                                            v-model="employeePortionForm.longevity"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="text-muted small mt-3">
                                This recompute only updates {{ currentEmployee.employee_no || "the selected employee" }} for year {{ selectedYearValue }}.
                            </div>
                        </div>
                    </div>

                    <div class="individual-tax-note">
                        This view combines payroll actuals with forecasted values for missing months in the selected
                        year and automatically shows the first employee that is both <strong>regular</strong> and
                        <strong>active</strong>.
                    </div>
                </section>
            </div>

            <div v-else class="individual-tax-empty-state">
                <div class="individual-tax-empty-card">
                    <h2 class="individual-tax-heading mb-2">No data yet</h2>
                    <p class="mb-0 text-muted">
                        No individual tax record has been created for {{ selectedYearValue }} yet. Use Calculate to set up this year.
                    </p>
                </div>
            </div>
        </div>

        <div
            ref="runModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="individualTaxRunModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content individual-tax-modal">
                    <div class="modal-header">
                        <div>
                            <h5 id="individualTaxRunModalLabel" class="modal-title d-flex align-items-center gap-2">
                                <i class="bi bi-calculator"></i>
                                <span>Calculate Taxation</span>
                            </h5>
                            <div class="individual-tax-modal-subtitle">
                                Employee {{ currentEmployee.employee_no || "-" }} for year {{ selectedYearValue }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" aria-label="Close" @click="closeRunModal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="container-fluid px-0">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body py-2">
                                            <ul class="nav nav-pills individual-tax-run-tabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button
                                                        class="nav-link"
                                                        :class="{ active: activeRunTab === 'A' }"
                                                        type="button"
                                                        role="tab"
                                                        aria-controls="individual-tax-run-tab-a"
                                                        :aria-selected="activeRunTab === 'A'"
                                                        @click="setActiveRunTab('A')"
                                                    >
                                                        <i class="bi bi-ui-checks-grid me-2"></i>
                                                        Tab A
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button
                                                        class="nav-link"
                                                        :class="{ active: activeRunTab === 'B' }"
                                                        type="button"
                                                        role="tab"
                                                        aria-controls="individual-tax-run-tab-b"
                                                        :aria-selected="activeRunTab === 'B'"
                                                        @click="setActiveRunTab('B')"
                                                    >
                                                        <i class="bi bi-list-columns-reverse me-2"></i>
                                                        Tab B
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button
                                                        class="nav-link"
                                                        :class="{ active: activeRunTab === 'C' }"
                                                        type="button"
                                                        role="tab"
                                                        aria-controls="individual-tax-run-tab-c"
                                                        :aria-selected="activeRunTab === 'C'"
                                                        @click="setActiveRunTab('C')"
                                                    >
                                                        <i class="bi bi-check2-square me-2"></i>
                                                        Tab C
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="tab-content individual-tax-run-content">
                                        <div
                                            id="individual-tax-run-tab-a"
                                            class="tab-pane fade"
                                            :class="{ 'show active': activeRunTab === 'A' }"
                                            role="tabpanel"
                                            tabindex="0"
                                        >
                                            <div class="card h-100">
                                                <div class="card-header d-flex align-items-center justify-content-between">
                                                    <div class="fw-semibold">Reference Setup</div>
                                                    <span class="badge text-bg-secondary">Step 1 of 3</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-3">
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label individual-tax-run-label">Year</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">
                                                                    <i class="bi bi-calendar-event"></i>
                                                                </span>
                                                                <input
                                                                    :value="runForm.year"
                                                                    type="text"
                                                                    class="form-control"
                                                                    disabled
                                                                    readonly
                                                                />
                                                            </div>
                                                        </div>

                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label individual-tax-run-label">Train Law</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">
                                                                    <i class="bi bi-journal-text"></i>
                                                                </span>
                                                                <select v-model="runForm.trainLawId" class="form-select">
                                                                    <option value="">-- Select --</option>
                                                                    <option
                                                                        v-for="option in currentTrainLawOptions"
                                                                        :key="option.id"
                                                                        :value="String(option.id)"
                                                                    >
                                                                        {{ option.year }}
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                                                                <label class="form-label individual-tax-run-label mb-0">Employees</label>

                                                                <div class="d-flex align-items-center gap-2">
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-primary"
                                                                        @click="selectAllRunEmployees"
                                                                    >
                                                                        Select All
                                                                    </button>

                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-secondary"
                                                                        @click="deselectAllRunEmployees"
                                                                    >
                                                                        Deselect All
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <select
                                                                ref="runEmployeeSelect"
                                                                v-model="runForm.employee_nos"
                                                                class="form-select"
                                                                multiple
                                                            >
                                                                <option
                                                                    v-for="employeeOption in currentRunEmployees"
                                                                    :key="`run-${employeeOption.employee_no}`"
                                                                    :value="String(employeeOption.employee_no)"
                                                                >
                                                                    {{ employeeOption.display_name }} - {{ employeeOption.employee_no }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div class="col-12">
                                                            <div class="alert alert-secondary mb-0 py-2 px-3">
                                                                <div class="small">
                                                                    Select the tax reference table and employees before proceeding.
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            id="individual-tax-run-tab-b"
                                            class="tab-pane fade"
                                            :class="{ 'show active': activeRunTab === 'B' }"
                                            role="tabpanel"
                                            tabindex="0"
                                        >
                                            <div class="card h-100">
                                                <div class="card-header d-flex align-items-center justify-content-between">
                                                    <div class="fw-semibold">Employee Tax Inputs</div>
                                                    <span class="badge text-bg-secondary">Step 2 of 3</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <div class="border rounded p-3 h-100">
                                                                <div class="fw-bold mb-2">Government Bonus Rules</div>

                                                                <div v-if="isLoadingGovernmentBonuses" class="text-muted small">
                                                                    Loading bonuses...
                                                                </div>

                                                                <div
                                                                    v-else-if="governmentBonuses.length"
                                                                    class="row g-2"
                                                                >
                                                                    <div
                                                                        v-for="bonus in governmentBonuses"
                                                                        :key="bonus.id"
                                                                        class="col-12"
                                                                    >
                                                                        <label
                                                                            class="border rounded px-2 py-2 d-flex align-items-start gap-2 w-100"
                                                                        >
                                                                            <input
                                                                                v-model="runForm.governmentBonuses"
                                                                                class="form-check-input mt-1"
                                                                                type="checkbox"
                                                                                :value="bonus.id"
                                                                            />

                                                                            <span class="d-flex flex-column flex-grow-1 min-w-0">
                                                                                <span class="fw-semibold small">
                                                                                    {{ bonus.name }}
                                                                                </span>
                                                                                <span
                                                                                    v-if="bonus.description"
                                                                                    class="text-body-secondary small"
                                                                                >
                                                                                    {{ bonus.description }}
                                                                                </span>
                                                                                <span class="text-muted x-small">
                                                                                    {{ bonus.metaLabel }}
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <div v-else class="text-muted small">
                                                                    No government bonus rules found.
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            id="individual-tax-run-tab-c"
                                            class="tab-pane fade"
                                            :class="{ 'show active': activeRunTab === 'C' }"
                                            role="tabpanel"
                                            tabindex="0"
                                        >
                                            <div class="card h-100">
                                                <div class="card-header d-flex align-items-center justify-content-between">
                                                    <div class="fw-semibold">Portion</div>
                                                    <span class="badge text-bg-secondary">Step 3 of 3</span>
                                                </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            <div class="col-12">
                                                                <div class="border rounded p-3 h-100">
                                                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                                                        <div>
                                                                            <div class="fw-bold">Default Portion</div>
                                                                            <div class="text-muted small">
                                                                                Used for new employees and fallback values for this year.
                                                                            </div>
                                                                        </div>
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-outline-primary"
                                                                            @click="applyDefaultPortionToSelectedEmployees"
                                                                        >
                                                                            Apply To Selected Employees
                                                                        </button>
                                                                    </div>

                                                                    <div class="row g-3">
                                                                        <div class="col-12 col-md-4">
                                                                            <label class="form-label individual-tax-run-label">Salary</label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">
                                                                                    <i class="bi bi-cash-stack"></i>
                                                                                </span>
                                                                                <input
                                                                                    v-model="runForm.portion.salary"
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="0.01"
                                                                                    class="form-control"
                                                                                    placeholder="0.00"
                                                                                />
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-12 col-md-4">
                                                                            <label class="form-label individual-tax-run-label">Hazard Pay</label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">
                                                                                    <i class="bi bi-exclamation-triangle"></i>
                                                                                </span>
                                                                                <input
                                                                                    v-model="runForm.portion.hazard_pay"
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="0.01"
                                                                                    class="form-control"
                                                                                    placeholder="0.00"
                                                                                />
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-12 col-md-4">
                                                                            <label class="form-label individual-tax-run-label">Longevity</label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">
                                                                                    <i class="bi bi-clock-history"></i>
                                                                                </span>
                                                                                <input
                                                                                    v-model="runForm.portion.longevity"
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="0.01"
                                                                                    class="form-control"
                                                                                    placeholder="0.00"
                                                                                />
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-muted small mt-3">
                                                                        Total: {{ formatPercent(portionTotal(runForm.portion)) }}
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeRunModal">
                            <i class="bi bi-x-lg me-2"></i>
                            Close
                        </button>

                        <button
                            v-if="!isLastRunTab"
                            type="button"
                            class="btn btn-primary"
                            @click="goToNextRunTab"
                        >
                            <i class="bi bi-arrow-right me-2"></i>
                            Next
                        </button>

                        <button
                            v-else
                            type="button"
                            class="btn btn-success"
                            @click="handleCalculate"
                        >
                            <i class="bi bi-calculator me-2"></i>
                            Calculate
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            ref="taxOverrideModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="individualTaxOverrideModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content individual-tax-modal">
                    <div class="modal-header">
                        <div>
                            <h5 id="individualTaxOverrideModalLabel" class="modal-title d-flex align-items-center gap-2">
                                <i class="bi bi-pencil-square"></i>
                                <span>Override Tax</span>
                            </h5>
                            <div class="individual-tax-modal-subtitle">
                                {{ taxOverrideForm.taxType || "Tax Type" }} for {{ taxOverrideSelectedMonthLabel || "Month" }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" aria-label="Close" @click="closeTaxOverrideModal"></button>
                    </div>

                    <div class="modal-body">
                        <div v-if="taxOverrideTypeOptions.length" class="row g-3">
                            <div class="col-12">
                                <label class="form-label individual-tax-run-label">Type</label>
                                <select v-model="taxOverrideForm.taxType" class="form-select">
                                    <option value="">-- Select type --</option>
                                    <option
                                        v-for="option in taxOverrideTypeOptions"
                                        :key="`tax-override-type-${option.value}`"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label individual-tax-run-label">Month</label>
                                <select v-model="taxOverrideForm.monthNumber" class="form-select">
                                    <option value="">-- Select month --</option>
                                    <option
                                        v-for="option in taxOverrideMonthOptions"
                                        :key="`tax-override-month-${option.value}`"
                                        :value="String(option.value)"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label individual-tax-run-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-cash-coin"></i>
                                    </span>
                                    <input
                                        v-model="taxOverrideForm.amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        placeholder="0.00"
                                    />
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="text-muted small">
                                    Only forecasted tax rows are editable. Selected amount defaults to current forecasted or overridden value.
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Month</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="override in currentSelectedEmployeeTaxOverrides"
                                                :key="`tax-override-row-${override.tax_type}-${override.month_number}`"
                                            >
                                                <td>{{ override.tax_type }}</td>
                                                <td>{{ monthLabel(override.month_number) }}</td>
                                                <td class="text-end">{{ peso(override.amount) }}</td>
                                                <td class="text-end">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-danger btn-sm"
                                                        :disabled="isRecomputingTaxOverride"
                                                        @click="handleDeleteTaxOverride(override)"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!currentSelectedEmployeeTaxOverrides.length">
                                                <td colspan="4" class="text-muted text-center py-3">
                                                    No saved overrides for this employee.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeTaxOverrideModal">
                            Close
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="isRecomputingTaxOverride || !canSubmitTaxOverride"
                            @click="handleRecomputeTaxOverride"
                        >
                            <i class="bi bi-arrow-repeat me-2"></i>
                            {{ isRecomputingTaxOverride ? "Recomputing..." : "Recompute" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { Modal } from "bootstrap";

export default {
    props: {
        baseUrl: { type: String, required: true },
        saveUrl: { type: String, required: true },
        apiUrl: { type: String, required: true },
        employee: { type: Object, required: true },
        employees: { type: Array, required: true },
        allEmployees: { type: Array, default: () => [] },
        selectedYear: { type: Number, required: true },
        availableYears: { type: Array, required: true },
        monthlyBreakdown: { type: Array, required: true },
        taxModuleBreakdown: { type: Array, default: () => [] },
        otherComponents: { type: Object, required: true },
        summary: { type: Object, required: true },
        trainLawOptions: { type: Array, default: () => [] },
        selectedTrainLawId: { type: [Number, String], default: null },
        selectedTaxationSettings: { type: Object, default: null },
        selectedEmployeeTaxOverrides: { type: Array, default: () => [] },
        hasTaxationData: { type: Boolean, default: false },
    },

    data() {
        return {
            state: {
                employee: this.employee,
                employees: this.employees,
                allEmployees: this.allEmployees,
                selectedYear: Number(this.selectedYear || new Date().getFullYear()),
                availableYears: this.availableYears,
                monthlyBreakdown: this.monthlyBreakdown,
                taxModuleBreakdown: this.taxModuleBreakdown,
                otherComponents: this.otherComponents,
                summary: this.summary,
                trainLawOptions: this.trainLawOptions,
                selectedTrainLawId: this.selectedTrainLawId,
                selectedTaxationSettings: this.selectedTaxationSettings,
                selectedEmployeeTaxOverrides: this.selectedEmployeeTaxOverrides,
                hasTaxationData: this.hasTaxationData,
            },
            selectedEmployeeNo: String(this.employee?.employee_no || ""),
            selectedYearValue: Number(this.selectedYear || new Date().getFullYear()),
            selectedYearInput: String(this.selectedYear || new Date().getFullYear()),
            isLoading: false,
            isSaving: false,
            isRecomputingEmployee: false,
            isRecomputingTaxOverride: false,
            token: localStorage.getItem("auth_token"),
            runModalInstance: null,
            taxOverrideModalInstance: null,
            activeRunTab: "A",
            governmentBonuses: [],
            isLoadingGovernmentBonuses: false,
            employeePortionForm: {
                hazard_pay: 20,
                salary: 80,
                longevity: 0,
            },
            taxOverrideForm: {
                taxType: "",
                monthNumber: "",
                amount: "",
            },
            runForm: {
                year: String(this.selectedYear || new Date().getFullYear()),
                trainLawId: this.selectedTrainLawId != null ? String(this.selectedTrainLawId) : "",
                employee_nos: this.employee?.employee_no != null ? [String(this.employee.employee_no)] : [],
                governmentBonuses: [],
                portion: {
                    hazard_pay: 20,
                    salary: 80,
                    longevity: 0,
                },
                employeePortions: {},
            },
        };
    },

    computed: {
        currentEmployee() {
            return this.state.employee || {};
        },

        currentEmployees() {
            return this.state.employees || [];
        },

        currentRunEmployees() {
            return this.state.allEmployees || [];
        },
        selectedRunEmployees() {
            const selectedEmployeeNos = new Set((this.runForm.employee_nos || []).map((employeeNo) => String(employeeNo)));

            return this.currentRunEmployees.filter((employeeOption) =>
                selectedEmployeeNos.has(String(employeeOption.employee_no)),
            );
        },

        currentMonthlyBreakdown() {
            return this.state.monthlyBreakdown || [];
        },

        currentTaxModuleBreakdown() {
            return this.state.taxModuleBreakdown || [];
        },

        currentTaxModuleColumns() {
            return ["Salary Tax", "Hazard Pay Tax", "Longevity Tax"];
        },

        currentEditableTaxItems() {
            return this.currentTaxModuleBreakdown.flatMap((row) =>
                (row?.items || [])
                    .filter((item) => ["forecast", "override"].includes(item?.source))
                    .map((item) => ({
                        monthNumber: Number(row?.month_number || 0),
                        monthLabel: row?.month_label || "",
                        taxType: item?.name || "",
                        amount: Number(item?.amount || 0),
                    })),
            );
        },

        taxOverrideTypeOptions() {
            const seen = new Set();

            return this.currentEditableTaxItems
                .filter((item) => {
                    if (!item.taxType || seen.has(item.taxType)) {
                        return false;
                    }

                    seen.add(item.taxType);

                    return true;
                })
                .map((item) => ({
                    value: item.taxType,
                    label: item.taxType,
                }));
        },

        taxOverrideMonthOptions() {
            return this.currentEditableTaxItems
                .filter((item) => item.taxType === this.taxOverrideForm.taxType)
                .map((item) => ({
                    value: item.monthNumber,
                    label: item.monthLabel,
                }));
        },

        taxOverrideSelectedMonthLabel() {
            return this.taxOverrideMonthOptions.find(
                (option) => String(option.value) === String(this.taxOverrideForm.monthNumber),
            )?.label || "";
        },

        canSubmitTaxOverride() {
            return Boolean(
                this.currentEmployee?.employee_no &&
                this.taxOverrideForm.taxType &&
                this.taxOverrideForm.monthNumber &&
                this.taxOverrideForm.amount !== "",
            );
        },

        currentTaxModuleGrandTotal() {
            return this.currentTaxModuleBreakdown.reduce(
                (total, row) => total + Number(row?.amount || 0),
                0,
            );
        },

        currentTaxWithheldRows() {
            const allocationAnnual = this.currentAnnualTaxDueComputation?.allocation?.annual || {};
            const rows = [
                { key: "salary", label: "Salary Pay", column: "Salary Tax" },
                { key: "hazard_pay", label: "Hazard Pay", column: "Hazard Pay Tax" },
                { key: "longevity", label: "Longevity Pay", column: "Longevity Tax" },
            ];

            return rows.map((row) => {
                const total = Number(allocationAnnual?.[row.column] || 0);
                const withheld = this.currentTaxModuleBreakdown.reduce((sum, monthRow) => {
                    const item = this.getTaxModuleItem(monthRow, row.column);

                    if (!item || item.source !== "actual") {
                        return sum;
                    }

                    return sum + Number(item.amount || 0);
                }, 0);
                const balance = Math.max(0, Number((total - withheld).toFixed(2)));

                return {
                    ...row,
                    withheld: Number(withheld.toFixed(2)),
                    balance,
                    total: Number(total.toFixed(2)),
                };
            });
        },

        currentTaxWithheldTotals() {
            return this.currentTaxWithheldRows.reduce(
                (totals, row) => ({
                    withheld: Number((totals.withheld + Number(row.withheld || 0)).toFixed(2)),
                    balance: Number((totals.balance + Number(row.balance || 0)).toFixed(2)),
                    total: Number((totals.total + Number(row.total || 0)).toFixed(2)),
                }),
                { withheld: 0, balance: 0, total: 0 },
            );
        },

        currentOtherComponents() {
            return this.state.otherComponents || { earnings: [], de_minimis: [], government_bonuses: [], allowables: [], taxes: [] };
        },

        currentSummary() {
            return this.state.summary || {};
        },

        currentAnnualTaxDueComputation() {
            return this.currentSummary.annual_tax_due_computation || {};
        },

        currentTrainLawOptions() {
            return this.state.trainLawOptions || [];
        },

        currentSelectedTrainLawId() {
            return this.state.selectedTrainLawId ?? null;
        },

        currentSelectedTaxationSettings() {
            return this.state.selectedTaxationSettings || null;
        },

        currentHasTaxationData() {
            return Boolean(this.state.hasTaxationData);
        },

        currentSelectedEmployeeTaxOverrides() {
            return this.state.selectedEmployeeTaxOverrides || [];
        },

        isLastRunTab() {
            return this.activeRunTab === "C";
        },

        employeeName() {
            if (this.currentEmployee?.display_name) {
                return this.currentEmployee.display_name;
            }

            const lastname = this.currentEmployee?.lastname || "";
            const firstname = this.currentEmployee?.firstname ? `, ${this.currentEmployee.firstname}` : "";
            const middlename = this.currentEmployee?.middlename
                ? ` ${String(this.currentEmployee.middlename).charAt(0).toUpperCase()}.`
                : "";
            const suffix = this.currentEmployee?.suffix ? ` ${this.currentEmployee.suffix}` : "";

            return `${lastname}${firstname}${middlename}${suffix}`.trim();
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.syncSelectionFromState();
            this.initEmployeeSelect();

            if (!this.currentEmployees.length) {
                this.fetchData();
            }
        });
    },

    beforeUnmount() {
        this.closeRunModal();
        this.closeTaxOverrideModal();
        this.runModalInstance = null;
        this.taxOverrideModalInstance = null;
        this.destroyRunEmployeeSelect();
        this.destroyEmployeeSelect();
    },

    watch: {
        currentEmployees: {
            handler() {
                this.$nextTick(() => {
                    this.syncSelectionFromState();
                    this.initEmployeeSelect();
                    this.initRunEmployeeSelect();
                });
            },
            deep: true,
        },
        currentSelectedTaxationSettings: {
            handler() {
                this.syncEmployeePortionForm();
                this.syncTaxOverrideForm();
            },
            deep: true,
        },
        selectedEmployeeNo() {
            this.syncEmployeePortionForm();
            this.syncTaxOverrideForm();
        },
        currentTaxModuleBreakdown: {
            handler() {
                this.syncTaxOverrideForm();
            },
            deep: true,
        },
        "taxOverrideForm.taxType"() {
            this.syncTaxOverrideMonthSelection();
        },
        "taxOverrideForm.monthNumber"() {
            this.syncTaxOverrideAmount();
        },
        "runForm.employee_nos": {
            handler() {
                this.ensureRunEmployeePortions();
            },
            deep: true,
        },
    },

    methods: {
        defaultPortion() {
            return {
                salary: 80,
                hazard_pay: 20,
                longevity: 0,
            };
        },
        normalizePortion(portion = {}) {
            const defaults = this.defaultPortion();

            return {
                salary: Number(portion?.salary ?? defaults.salary),
                hazard_pay: Number(portion?.hazard_pay ?? defaults.hazard_pay),
                longevity: Number(portion?.longevity ?? defaults.longevity),
            };
        },
        portionTotal(portion = {}) {
            return Number(
                (
                    Number(portion?.salary || 0) +
                    Number(portion?.hazard_pay || 0) +
                    Number(portion?.longevity || 0)
                ).toFixed(2),
            );
        },
        getStoredEmployeePortion(employeeNo) {
            return this.currentSelectedTaxationSettings?.employee_portions?.[String(employeeNo)] || null;
        },
        syncEmployeePortionForm() {
            this.employeePortionForm = this.normalizePortion(
                this.getStoredEmployeePortion(this.selectedEmployeeNo) ||
                this.currentSelectedTaxationSettings?.portion,
            );
        },
        syncTaxOverrideForm() {
            const firstType = this.taxOverrideTypeOptions[0]?.value || "";
            const nextType = this.taxOverrideTypeOptions.some((option) => option.value === this.taxOverrideForm.taxType)
                ? this.taxOverrideForm.taxType
                : firstType;
            const monthOptions = this.currentEditableTaxItems
                .filter((item) => item.taxType === nextType)
                .map((item) => ({ value: item.monthNumber }));
            const firstMonth = monthOptions[0]?.value ? String(monthOptions[0].value) : "";
            const nextMonth = monthOptions.some((option) => String(option.value) === String(this.taxOverrideForm.monthNumber))
                ? String(this.taxOverrideForm.monthNumber)
                : firstMonth;
            const selectedItem = this.currentEditableTaxItems.find((item) =>
                item.taxType === nextType && String(item.monthNumber) === String(nextMonth),
            );

            this.taxOverrideForm = {
                taxType: nextType,
                monthNumber: nextMonth,
                amount: selectedItem ? String(selectedItem.amount) : "",
            };
        },
        syncTaxOverrideMonthSelection() {
            const monthOptions = this.currentEditableTaxItems
                .filter((item) => item.taxType === this.taxOverrideForm.taxType)
                .map((item) => String(item.monthNumber));
            const nextMonth = monthOptions.includes(String(this.taxOverrideForm.monthNumber))
                ? String(this.taxOverrideForm.monthNumber)
                : (monthOptions[0] || "");

            this.taxOverrideForm.monthNumber = nextMonth;
            this.syncTaxOverrideAmount();
        },
        syncTaxOverrideAmount() {
            const selectedItem = this.currentEditableTaxItems.find((item) =>
                item.taxType === this.taxOverrideForm.taxType &&
                String(item.monthNumber) === String(this.taxOverrideForm.monthNumber),
            );

            if (!selectedItem) {
                return;
            }

            this.taxOverrideForm.amount = String(selectedItem.amount);
        },
        openTaxOverrideModal(row, column) {
            const item = this.getTaxModuleItem(row, column);

            if (!item || !["forecast", "override"].includes(item.source)) {
                return;
            }

            this.taxOverrideForm = {
                taxType: column,
                monthNumber: String(row?.month_number || ""),
                amount: String(Number(item.amount || 0)),
            };

            this.$nextTick(() => {
                this.getTaxOverrideModalInstance()?.show();
            });
        },
        ensureRunEmployeePortions() {
            const nextPortions = { ...(this.runForm.employeePortions || {}) };

            (this.runForm.employee_nos || []).forEach((employeeNo) => {
                const key = String(employeeNo);

                nextPortions[key] = this.normalizePortion(
                    nextPortions[key] ||
                    this.getStoredEmployeePortion(key) ||
                    this.runForm.portion,
                );
            });

            this.runForm.employeePortions = nextPortions;
        },
        applyDefaultPortionToSelectedEmployees() {
            const defaultPortion = this.normalizePortion(this.runForm.portion);
            const nextPortions = { ...(this.runForm.employeePortions || {}) };

            (this.runForm.employee_nos || []).forEach((employeeNo) => {
                nextPortions[String(employeeNo)] = { ...defaultPortion };
            });

            this.runForm.employeePortions = nextPortions;
        },
        peso(amount) {
            return `P ${Number(amount || 0).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        },
        pesoWithSymbol(amount) {
            return `₱${Number(amount || 0).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        },
        formatPercent(value) {
            return `${Number(value || 0).toLocaleString("en-US", {
                minimumFractionDigits: Number(value || 0) % 1 === 0 ? 0 : 2,
                maximumFractionDigits: 2,
            })}%`;
        },
        monthLabel(monthNumber) {
            const parsedMonth = Number(monthNumber || 0);

            if (!parsedMonth || parsedMonth < 1 || parsedMonth > 12) {
                return "";
            }

            return new Date(2000, parsedMonth - 1, 1).toLocaleString("en-US", { month: "long" });
        },
        sourceDotClass(source) {
            const sourceClassMap = {
                draft: "individual-tax-source-dot--draft",
                pending: "individual-tax-source-dot--pending",
                approved: "individual-tax-source-dot--approved",
                for_releasing: "individual-tax-source-dot--for-releasing",
                completed: "individual-tax-source-dot--completed",
                override: "individual-tax-source-dot--approved",
                forecast: "individual-tax-source-dot--forecast",
            };

            return sourceClassMap[source] || "individual-tax-source-dot--forecast";
        },
        getValueSource(row, key) {
            return row?.source_breakdown?.[key] || "forecast";
        },
        formatValueSource(row, key) {
            const labelMap = {
                basic_salary: "Basic Salary",
                hazard_pay: "Hazard Pay",
                longevity_pay: "Longevity",
            };
            const source = this.getValueSource(row, key);

            return `${labelMap[key] || "Value"}: ${this.formatStatusLabel(source)}`;
        },
        formatSourceBreakdown(row) {
            const sourceBreakdown = row?.source_breakdown || {};

            return [
                `Basic Salary: ${this.formatStatusLabel(sourceBreakdown.basic_salary)}`,
                `Hazard Pay: ${this.formatStatusLabel(sourceBreakdown.hazard_pay)}`,
                `Longevity: ${this.formatStatusLabel(sourceBreakdown.longevity_pay)}`,
            ].join(" | ");
        },
        formatStatusLabel(source) {
            const statusLabelMap = {
                draft: "Draft",
                pending: "Pending",
                approved: "Approved",
                for_releasing: "For Releasing",
                completed: "Completed",
                override: "Overridden",
                forecast: "Forecasted",
            };

            return statusLabelMap[source] || "Forecasted";
        },
        payrollStatusPriority(status) {
            const priorityMap = {
                draft: 1,
                pending: 2,
                approved: 3,
                for_releasing: 4,
                completed: 5,
                override: 0,
            };

            return priorityMap[status] || 99;
        },
        getTaxModuleAmount(row, column) {
            const item = (row?.items || []).find((entry) => entry?.name === column);

            return Number(item?.amount || 0);
        },
        getTaxModuleItem(row, column) {
            return (row?.items || []).find((entry) => entry?.name === column) || null;
        },
        isTaxModuleItemEditable(row, column) {
            return ["forecast", "override"].includes(this.getTaxModuleItem(row, column)?.source);
        },
        getTaxModuleStatusKey(column) {
            const normalized = String(column || "").toLowerCase();

            if (normalized === "salary tax") {
                return "basic_salary";
            }

            if (normalized === "hazard pay tax") {
                return "hazard_pay";
            }

            if (normalized === "longevity tax") {
                return "longevity_pay";
            }

            return "basic_salary";
        },
        getMatchingMonthlyBreakdownRow(monthNumber) {
            return this.currentMonthlyBreakdown.find((entry) => Number(entry?.month_number) === Number(monthNumber)) || null;
        },
        getTaxModuleSource(row, column) {
            const monthlyRow = this.getMatchingMonthlyBreakdownRow(row?.month_number);
            const sourceKey = this.getTaxModuleStatusKey(column);
            const item = this.getTaxModuleItem(row, column);

            if (item?.source === "override") {
                return "override";
            }

            if (item?.source === "forecast") {
                return "forecast";
            }

            if (item?.source === "actual") {
                const resolved = monthlyRow?.source_breakdown?.[sourceKey];

                return resolved && resolved !== "forecast" ? resolved : "completed";
            }

            return monthlyRow?.source_breakdown?.[sourceKey] || "forecast";
        },
        formatTaxModuleSource(row, column) {
            const labelMap = {
                "Salary Tax": "Salary Tax",
                "Hazard Pay Tax": "Hazard Pay Tax",
                "Longevity Tax": "Longevity Tax",
            };

            return `${labelMap[column] || column}: ${this.formatStatusLabel(this.getTaxModuleSource(row, column))}`;
        },
        getTaxModuleTotalSource(row) {
            const sources = this.currentTaxModuleColumns.map((column) => this.getTaxModuleSource(row, column));
            const nonForecastSources = sources.filter((source) => source !== "forecast");

            if (!nonForecastSources.length) {
                return "forecast";
            }

            return nonForecastSources.sort((left, right) => {
                return this.payrollStatusPriority(left) - this.payrollStatusPriority(right);
            })[0];
        },
        formatTaxModuleTotalSource(row) {
            return `Total: ${this.formatStatusLabel(this.getTaxModuleTotalSource(row))}`;
        },
        getTaxModuleColumnTotal(column) {
            return this.currentTaxModuleBreakdown.reduce(
                (total, row) => total + this.getTaxModuleAmount(row, column),
                0,
            );
        },
        applySelectedTaxationSettingsToRunForm() {
            const selectedSettings = this.currentSelectedTaxationSettings;

            this.runForm.year = String(this.selectedYearValue || "");
            this.runForm.employee_nos = this.selectedEmployeeNo ? [String(this.selectedEmployeeNo)] : [];
            this.runForm.trainLawId = this.state?.selectedTrainLawId != null ? String(this.state.selectedTrainLawId) : "";
            this.runForm.governmentBonuses = Array.isArray(selectedSettings?.bonuses)
                ? selectedSettings.bonuses
                    .map((bonus) => String(bonus?.government_bonus_id || ""))
                    .filter(Boolean)
                : [];
            this.runForm.portion = this.normalizePortion(selectedSettings?.portion);
            this.runForm.employeePortions = {};
            this.ensureRunEmployeePortions();
            this.syncEmployeePortionForm();
            this.syncTaxOverrideForm();
        },

        syncSelectionFromState() {
            if (this.currentEmployee?.employee_no != null) {
                this.selectedEmployeeNo = String(this.currentEmployee.employee_no);
            }

            if (this.state?.selectedYear != null) {
                this.selectedYearValue = Number(this.state.selectedYear);
                this.selectedYearInput = String(this.selectedYearValue);
            }

            this.applySelectedTaxationSettingsToRunForm();
        },
        onYearInput(event) {
            const nextValue = String(event?.target?.value || "").replace(/\D/g, "").slice(0, 4);
            this.selectedYearInput = nextValue;

            if (nextValue.length === 4) {
                this.applyYearInput();
            }
        },
        applyYearInput() {
            const sanitizedYear = String(this.selectedYearInput || "").replace(/\D/g, "").slice(0, 4);

            if (sanitizedYear.length !== 4) {
                return;
            }

            const parsedYear = Number(sanitizedYear);

            if (!Number.isInteger(parsedYear) || parsedYear < 1000 || parsedYear > 9999) {
                return;
            }

            this.selectedYearInput = sanitizedYear;

            if (parsedYear !== this.selectedYearValue) {
                this.selectedYearValue = parsedYear;
                this.fetchData();
            }
        },

        initEmployeeSelect() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.employeeSelect;
            const dropdownParent = this.$refs.toolbarForm;

            if (!select || !jq || !this.currentRunEmployees.length) return;

            const $select = jq(select);

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }

            $select.select2({
                width: "resolve",
                dropdownCssClass: "individual-tax-select2-dropdown",
                placeholder: "Search employee name",
                dropdownParent: dropdownParent ? jq(dropdownParent) : undefined,
            });

            if ($select.find(`option[value="${this.selectedEmployeeNo}"]`).length) {
                $select.val(this.selectedEmployeeNo).trigger("change.select2");
            }

            $select.on("change.individual-tax", (event) => {
                this.selectedEmployeeNo = String(event.target.value || "");
                this.fetchData();
            });
        },

        destroyEmployeeSelect() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.employeeSelect;

            if (!select || !jq) return;

            const $select = jq(select);
            $select.off(".individual-tax");

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }
        },
        getRunModalInstance() {
            const modalElement = this.$refs.runModal;

            if (!modalElement) {
                return null;
            }

            this.runModalInstance = this.runModalInstance || Modal.getOrCreateInstance(modalElement);

            return this.runModalInstance;
        },
        getTaxOverrideModalInstance() {
            const modalElement = this.$refs.taxOverrideModal;

            if (!modalElement) {
                return null;
            }

            this.taxOverrideModalInstance = this.taxOverrideModalInstance || Modal.getOrCreateInstance(modalElement);

            return this.taxOverrideModalInstance;
        },
        openRunModal() {
            this.$nextTick(() => {
                this.activeRunTab = "A";
                this.syncRunForm();
                this.fetchGovernmentBonuses();
                this.initRunEmployeeSelect();
                this.getRunModalInstance()?.show();
            });
        },
        setActiveRunTab(tab) {
            this.activeRunTab = tab;
        },
        initRunEmployeeSelect() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.runEmployeeSelect;
            const modalElement = this.$refs.runModal;

            if (!select || !jq || !this.currentRunEmployees.length) return;

            const $select = jq(select);

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }

            $select.select2({
                width: "100%",
                dropdownCssClass: "individual-tax-select2-dropdown",
                placeholder: "Select employees",
                dropdownParent: modalElement ? jq(modalElement) : undefined,
                closeOnSelect: false,
            });

            $select.val(this.runForm.employee_nos || []).trigger("change.select2");

            $select.off("change.individual-tax-run").on("change.individual-tax-run", (event) => {
                const values = jq(event.target).val() || [];
                this.runForm.employee_nos = Array.isArray(values)
                    ? values.map((value) => String(value))
                    : [];
            });
        },
        destroyRunEmployeeSelect() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.runEmployeeSelect;

            if (!select || !jq) return;

            const $select = jq(select);
            $select.off(".individual-tax-run");

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }
        },
        syncRunEmployeeSelectValue() {
            const jq = window.jQuery || window.$;
            const select = this.$refs.runEmployeeSelect;

            if (!select || !jq) return;

            const $select = jq(select);

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.val(this.runForm.employee_nos || []).trigger("change.select2");
            }
        },
        selectAllRunEmployees() {
            this.runForm.employee_nos = this.currentRunEmployees.map((employeeOption) =>
                String(employeeOption.employee_no),
            );
            this.ensureRunEmployeePortions();
            this.$nextTick(() => {
                this.syncRunEmployeeSelectValue();
            });
        },
        deselectAllRunEmployees() {
            this.runForm.employee_nos = [];
            this.$nextTick(() => {
                this.syncRunEmployeeSelectValue();
            });
        },
        goToNextRunTab() {
            if (this.activeRunTab === "A") {
                this.activeRunTab = "B";
                return;
            }

            if (this.activeRunTab === "B") {
                this.activeRunTab = "C";
            }
        },
        buildSavePayload(employeeNos = this.runForm.employee_nos || [], employeePortions = null, taxOverride = null, syncEmployees = null) {
            const resolvedEmployeeNos = Array.isArray(employeeNos)
                ? employeeNos.map((employeeNo) => String(employeeNo))
                : [];
            const resolvedEmployeePortions = employeePortions || this.runForm.employeePortions || {};

            return {
                employee_nos: resolvedEmployeeNos,
                n_taxation: {
                    Year: Number(this.selectedYearValue || 0),
                },
                n_taxation_settings: {
                    train_law_id: this.runForm.trainLawId ? Number(this.runForm.trainLawId) : null,
                    bonuses: (this.runForm.governmentBonuses || []).map((governmentBonusId) => ({
                        government_bonus_id: Number(governmentBonusId),
                    })),
                    portion: {
                        hazard_pay: Number(this.runForm.portion?.hazard_pay || 0),
                        salary: Number(this.runForm.portion?.salary || 0),
                        longevity: Number(this.runForm.portion?.longevity || 0),
                    },
                    employee_portions: resolvedEmployeeNos.reduce((carry, employeeNo) => {
                        const key = String(employeeNo);
                        const portion = this.normalizePortion(resolvedEmployeePortions?.[key] || this.runForm.portion);

                        carry[key] = {
                            hazard_pay: Number(portion.hazard_pay || 0),
                            salary: Number(portion.salary || 0),
                            longevity: Number(portion.longevity || 0),
                        };

                        return carry;
                    }, {}),
                    sync_employees: syncEmployees ?? (taxOverride === null && resolvedEmployeeNos.length > 0),
                    tax_override: taxOverride,
                },
            };
        },

        async handleRecomputeEmployee() {
            if (this.isRecomputingEmployee || !this.currentEmployee?.employee_no) return;

            this.isRecomputingEmployee = true;

            try {
                const employeeNo = String(this.currentEmployee.employee_no);
                const response = await axios.post(
                    this.saveUrl,
                    this.buildSavePayload(
                        [employeeNo],
                        {
                            [employeeNo]: this.normalizePortion(this.employeePortionForm),
                        },
                        null,
                        false,
                    ),
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                await this.fetchData();
                window.SuccessToast?.fire?.({
                    title: response?.data?.message || "Employee portion recomputed successfully.",
                });
            } catch (error) {
                window.ErrorToast?.fire?.({
                    title:
                        error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {})?.[0]?.[0] ||
                        "Unable to recompute employee portion.",
                });
            } finally {
                this.isRecomputingEmployee = false;
            }
        },

        async handleRecomputeTaxOverride() {
            if (this.isRecomputingTaxOverride || !this.canSubmitTaxOverride || !this.currentEmployee?.employee_no) return;

            this.isRecomputingTaxOverride = true;

            try {
                const employeeNo = String(this.currentEmployee.employee_no);
                const response = await axios.post(
                    this.saveUrl,
                    this.buildSavePayload(
                        [employeeNo],
                        {
                            [employeeNo]: this.normalizePortion(this.employeePortionForm),
                        },
                        {
                            employee_no: employeeNo,
                            tax_type: this.taxOverrideForm.taxType,
                            month_number: Number(this.taxOverrideForm.monthNumber || 0),
                            amount: Number(this.taxOverrideForm.amount || 0),
                        },
                        false,
                    ),
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                await this.fetchData();
                this.closeTaxOverrideModal();
                window.SuccessToast?.fire?.({
                    title: response?.data?.message || "Tax override recomputed successfully.",
                });
            } catch (error) {
                window.ErrorToast?.fire?.({
                    title:
                        error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {})?.[0]?.[0] ||
                        "Unable to recompute tax override.",
                });
            } finally {
                this.isRecomputingTaxOverride = false;
            }
        },
        async handleDeleteTaxOverride(override) {
            if (this.isRecomputingTaxOverride || !this.currentEmployee?.employee_no) return;

            this.isRecomputingTaxOverride = true;

            try {
                const employeeNo = String(this.currentEmployee.employee_no);
                const response = await axios.post(
                    this.saveUrl,
                    this.buildSavePayload(
                        [employeeNo],
                        {
                            [employeeNo]: this.normalizePortion(this.employeePortionForm),
                        },
                        {
                            employee_no: employeeNo,
                            tax_type: override.tax_type,
                            month_number: Number(override.month_number || 0),
                            action: "delete",
                        },
                        false,
                    ),
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                await this.fetchData();
                window.SuccessToast?.fire?.({
                    title: response?.data?.message || "Tax override deleted successfully.",
                });
            } catch (error) {
                window.ErrorToast?.fire?.({
                    title:
                        error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {})?.[0]?.[0] ||
                        "Unable to delete tax override.",
                });
            } finally {
                this.isRecomputingTaxOverride = false;
            }
        },
        
        async handleCalculate() {
            if (this.isSaving) return;

            this.isSaving = true;

            try {
                const response = await axios.post(
                    this.saveUrl,
                    this.buildSavePayload(),
                    {
                        headers: this.token
                            ? { Authorization: `Bearer ${this.token}` }
                            : {},
                    },
                );

                await this.fetchData();
                this.closeRunModal();
                window.SuccessToast?.fire?.({
                    title: response?.data?.message || "Individual tax settings saved successfully.",
                });
            } catch (error) {
                window.ErrorToast?.fire?.({
                    title:
                        error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {})?.[0]?.[0] ||
                        "Unable to save individual tax settings.",
                });
            } finally {
                this.isSaving = false;
            }
        },
        closeRunModal() {
            this.getRunModalInstance()?.hide();
        },
        closeTaxOverrideModal() {
            this.getTaxOverrideModalInstance()?.hide();
        },

        syncUrl() {
            const url = new URL(this.baseUrl, window.location.origin);

            if (this.selectedEmployeeNo) {
                url.searchParams.set("employee_no", this.selectedEmployeeNo);
            }

            if (this.selectedYearValue) {
                url.searchParams.set("year", String(this.selectedYearValue));
            }

            window.history.replaceState({}, "", url.toString());
        },

        applyPayload(payload = {}) {
            this.state = {
                employee: payload.employee || {},
                employees: payload.employees || [],
                allEmployees: payload.allEmployees || [],
                selectedYear: Number(payload.selectedYear || this.selectedYearValue),
                availableYears: payload.availableYears || [],
                monthlyBreakdown: payload.monthlyBreakdown || [],
                taxModuleBreakdown: payload.taxModuleBreakdown || [],
                otherComponents: payload.otherComponents || { earnings: [], de_minimis: [], government_bonuses: [], allowables: [], taxes: [] },
                summary: payload.summary || {},
                trainLawOptions: payload.trainLawOptions || [],
                selectedTrainLawId: payload.selectedTrainLawId ?? null,
                selectedTaxationSettings: payload.selectedTaxationSettings || null,
                selectedEmployeeTaxOverrides: payload.selectedEmployeeTaxOverrides || [],
                hasTaxationData: Boolean(payload.hasTaxationData),
            };

            this.syncSelectionFromState();
            this.syncRunForm();
            this.$nextTick(() => {
                this.initEmployeeSelect();
            });
            this.syncUrl();
        },

        syncRunForm() {
            this.runForm.year = String(this.selectedYearValue || "");

            if (
                (!this.runForm.trainLawId ||
                    !this.currentTrainLawOptions.some((option) => String(option.id) === String(this.runForm.trainLawId))) &&
                this.state?.selectedTrainLawId != null
            ) {
                this.runForm.trainLawId = String(this.state.selectedTrainLawId);
            }

            this.ensureRunEmployeePortions();
            this.syncEmployeePortionForm();
        },
        async fetchGovernmentBonuses() {
            this.isLoadingGovernmentBonuses = true;

            try {
                const response = await axios.get(
                    "/admin/payroll/government-bonus-types",
                    {
                        headers: this.token
                            ? {
                                  Accept: "application/json",
                                  Authorization: `Bearer ${this.token}`,
                              }
                            : {
                                  Accept: "application/json",
                              },
                    },
                );

                const rows = Array.isArray(response?.data?.data)
                    ? response.data.data
                    : [];

                this.governmentBonuses = rows.map((item) => ({
                    id: item.id,
                    name: item.name || "Government Bonus Rule",
                    description: item.computation_notes || "",
                    metaLabel: item.slug || item.computation_type || "",
                }));
            } catch (error) {
                this.governmentBonuses = [];
            } finally {
                this.isLoadingGovernmentBonuses = false;
            }
        },

        async fetchData() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const response = await axios.get(this.apiUrl, {
                    params: {
                        employee_no: this.selectedEmployeeNo,
                        year: this.selectedYearValue,
                    },
                    headers: this.token
                        ? { Authorization: `Bearer ${this.token}` }
                        : {},
                });

                this.applyPayload(response.data || {});
            } catch (error) {
                console.error("Failed to load individual tax data:", error);
                window.ErrorToast?.fire?.({
                    title: "Failed to load individual tax data.",
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.individual-tax-sheet {
    // max-width: 1480px;
    margin: 0 auto;
    // background: var(--bs-tertiary-bg);
    // border: 1px solid var(--bs-border-color);
    border-bottom: 1px solid var(--bs-border-color);
    position: relative;
}

.individual-tax-empty-state {
    padding: 1.5rem;
}

.individual-tax-empty-card {
    padding: 2rem 1.5rem;
    border: 1px dashed var(--bs-border-color);
    background: var(--bs-body-bg);
    text-align: center;
}

.individual-tax-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1.125rem 1.375rem;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-secondary-bg);

    &-form {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    &-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
}

.individual-tax-heading-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}

.individual-tax-subsection {
    margin-top: 1.25rem;
}

.individual-tax-subheading {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

.individual-tax-uppercase {
    text-transform: uppercase;
}

.x-small {
    font-size: 0.75rem;
}

.individual-tax-title {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--bs-body-color);
}

.individual-tax-legend {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    color: var(--bs-secondary-color);
    font-size: 0.78rem;
    font-weight: 600;
}

.individual-tax-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.individual-tax-source-dot {
    display: inline-block;
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.18);
}

.individual-tax-source-dot--draft {
    background: #94a3b8;
}

.individual-tax-source-dot--pending {
    background: #f59e0b;
}

.individual-tax-source-dot--approved {
    background: #0ea5e9;
}

.individual-tax-source-dot--for-releasing {
    background: #8b5cf6;
}

.individual-tax-source-dot--completed {
    background: #16a34a;
}

.individual-tax-source-dot--forecast {
    background: #ef4444;
}

.individual-tax-amount-with-source {
    display: inline-flex;
    align-items: center;
}

.individual-tax-tax-override-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    text-align: right;
    cursor: pointer;
}

.individual-tax-tax-override-trigger:hover,
.individual-tax-tax-override-trigger:focus-visible {
    text-decoration: underline;
    text-underline-offset: 0.16rem;
}

.individual-tax-amount-value {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.45rem;
}

.individual-tax-toolbar-btn {
    min-height: 32px;
    padding: 0.3125rem 0.75rem;
    border-radius: 6px;
    border: 1px solid #c8d1dc;
    background: linear-gradient(180deg, #f7f9fc 0%, #edf2f7 100%);
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1.1;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    transition:
        background-color 0.16s ease,
        border-color 0.16s ease,
        box-shadow 0.16s ease,
        color 0.16s ease;

    &:hover:not(:disabled),
    &:focus-visible:not(:disabled) {
        border-color: #b7c4d3;
        background: linear-gradient(180deg, #ffffff 0%, #edf3f8 100%);
        color: #1f2937;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.9),
            0 0 0 0.12rem rgba(59, 89, 152, 0.12);
    }

    &:active:not(:disabled) {
        background: #e8edf4;
        border-color: #aebacd;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08);
    }

    &:disabled {
        opacity: 0.7;
        box-shadow: none;
    }

    .fa-solid {
        font-size: 0.75rem;
    }

    &--primary {
        border-color: #b8c7e0;
        background: linear-gradient(180deg, #eef3fb 0%, #dfe8f6 100%);
        color: #294574;
    }

    &--neutral {
        border-color: #cbd5e1;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        color: #475569;
    }

    &--accent {
        border-color: #bbd4bf;
        background: linear-gradient(180deg, #edf7ee 0%, #dcefdc 100%);
        color: #2f5f38;
    }
}

.individual-tax-loading-bar {
    height: 3px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(var(--bs-primary-rgb), 0.9) 30%,
        rgba(var(--bs-info-rgb), 0.9) 60%,
        transparent 100%
    );
    animation: individual-tax-loading 1.2s linear infinite;
}

.individual-tax-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--bs-secondary-color);
}

.individual-tax-employee-select,
.individual-tax-select {
    min-width: 180px;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-weight: 600;
}

.individual-tax-employee-select {
    min-width: 320px;
}

.individual-tax-toolbar-inline-fields {
    display: flex;
    align-items: flex-end;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.individual-tax-toolbar-stack {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 180px;
}

.individual-tax-toolbar-label {
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
}

.individual-tax-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    padding: 1.125rem 1.375rem 0;

    &-card {
        padding: 0.875rem 1rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
    }

    &-label {
        display: block;
        margin-bottom: 0.375rem;
        font-size: 0.6875rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    &-value {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--bs-body-color);
    }
}

.individual-tax-grid {
    display: grid;
    grid-template-columns: 1.1fr 1.5fr 1fr;
    padding: 1.125rem 1.375rem 1.375rem;
}

.individual-tax-panel {
    min-width: 0;

    & + & {
        margin-left: 1.375rem;
        padding-left: 1.375rem;
        border-left: 1px solid var(--bs-border-color);
    }
}

.individual-tax-heading {
    margin: 0 0 0.75rem;
    font-size: 0.9375rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--bs-body-color);
}

.individual-tax-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;

    th,
    td {
        padding: 0.375rem 0.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        vertical-align: middle;
        color: var(--bs-body-color);
    }

    th {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        background: var(--bs-secondary-bg);
    }

    .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    tfoot th {
        background: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
    }
}

.individual-tax-highlight-blue {
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary-text-emphasis);
    font-weight: 800;
}

.individual-tax-highlight-yellow {
    background: var(--bs-warning-bg-subtle);
    color: var(--bs-warning-text-emphasis);
    font-weight: 800;
}

.individual-tax-highlight-pink {
    background: var(--bs-danger-bg-subtle);
    color: var(--bs-danger-text-emphasis);
    font-weight: 800;
}

.individual-tax-highlight-orange {
    background: var(--bs-warning-bg-subtle);
    color: var(--bs-warning-text-emphasis);
    font-weight: 800;
}

.individual-tax-list {
    display: grid;
    gap: 0.5rem;

    &-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.5rem 0.625rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        font-size: 0.8125rem;
        color: var(--bs-body-color);

        span:last-child {
            font-weight: 700;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
    }

    &-row--total {
        border-color: var(--bs-warning-border-subtle);
        background: var(--bs-warning-bg-subtle);
        font-weight: 800;
    }
}

.individual-tax-note {
    margin-top: 1rem;
    padding: 0.75rem 0.875rem;
    border: 1px dashed var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
}

.individual-tax-modal {
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
}

.individual-tax-modal-subtitle {
    margin-top: 0.125rem;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.individual-tax-run-tabs {
    gap: 0.5rem;
}

.individual-tax-run-tabs .nav-link {
    padding: 0.45rem 0.85rem;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    font-weight: 700;
    font-size: 0.8125rem;
}

.individual-tax-run-tabs .nav-link.active {
    color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle);
    border-color: var(--bs-primary-border-subtle);
}

.individual-tax-run-content {
    padding-top: 0;
}

.individual-tax-run-label {
    margin-bottom: 0.35rem;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
}

.individual-tax-run-content :deep(.card) {
    border-color: var(--bs-border-color);
    background: var(--bs-body-bg);
}

.individual-tax-run-content :deep(.card-header) {
    padding: 0.75rem 1rem;
    background: var(--bs-secondary-bg);
    border-bottom-color: var(--bs-border-color);
}

.individual-tax-run-content :deep(.card-body) {
    padding: 1rem;
}

.individual-tax-run-content :deep(.form-control),
.individual-tax-run-content :deep(.form-select),
.individual-tax-run-content :deep(.input-group-text) {
    min-height: 36px;
    padding: 0.45rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.45rem;
}

.individual-tax-run-content :deep(.input-group-text) {
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    border-color: var(--bs-border-color);
}

.individual-tax-employee-portion-card {
    padding: 1rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.75rem;
    background: var(--bs-body-bg);
}

.individual-tax-run-segmented {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0;
    overflow: hidden;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
}

.individual-tax-run-segment {
    position: relative;
    display: flex;
    align-items: center;
    margin: 0;
    cursor: pointer;
    user-select: none;
    background: var(--bs-body-bg);
    border-right: 1px solid var(--bs-border-color);
    transition:
        background-color 0.16s ease,
        box-shadow 0.16s ease,
        color 0.16s ease;
}

.individual-tax-run-segment:last-child {
    border-right: 0;
}

.individual-tax-run-segment-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.individual-tax-run-segment-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 108px;
    padding: 0.6rem 0.95rem;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: var(--bs-body-color);
}

.individual-tax-run-segment--active {
    background: var(--bs-primary-bg-subtle);
    box-shadow: inset 0 0 0 1px var(--bs-primary);
    z-index: 1;
}

.individual-tax-run-segment--active .individual-tax-run-segment-label {
    color: var(--bs-primary-text-emphasis);
}

.individual-tax-run-segment:hover:not(.individual-tax-run-segment--disabled) {
    background: var(--bs-secondary-bg);
}

.individual-tax-run-segment--disabled {
    cursor: not-allowed;
    opacity: 0.9;
}

.individual-tax-modal :deep(.modal-header) {
    padding: 0.9rem 1rem;
    border-bottom-color: var(--bs-border-color);
}

.individual-tax-modal :deep(.modal-body) {
    padding: 0.9rem 1rem 0.75rem;
}

.individual-tax-modal :deep(.modal-footer) {
    padding: 0.85rem 1rem;
    gap: 0.5rem;
    border-top-color: var(--bs-border-color);
}

.individual-tax-modal :deep(.modal-footer .btn) {
    min-height: 38px;
    padding: 0.45rem 0.9rem;
    font-size: 0.875rem;
    font-weight: 700;
}

/* Select2 */
:deep(.select2-container) {
    min-width: 320px !important;
}

:deep(.select2-selection--single) {
    min-height: 44px;
    border: 1px solid var(--bs-border-color) !important;
    border-radius: var(--bs-border-radius) !important;
    background: var(--bs-body-bg) !important;
}

:deep(.select2-selection__rendered) {
    color: var(--bs-body-color) !important;
    line-height: 42px !important;
    font-weight: 600;
}

:deep(.select2-dropdown) {
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}

:deep(.select2-search--dropdown) {
    padding: 0.625rem;
    background: var(--bs-secondary-bg);
}

:deep(.select2-search__field) {
    border: 1px solid var(--bs-border-color) !important;
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
}

:deep(.select2-results__option--highlighted) {
    background: var(--bs-primary-bg-subtle) !important;
    color: var(--bs-primary-text-emphasis) !important;
}

@keyframes individual-tax-loading {
    0% {
        transform: translateX(-35%);
        opacity: 0.45;
    }

    50% {
        opacity: 1;
    }

    100% {
        transform: translateX(35%);
        opacity: 0.45;
    }
}

@media (max-width: 1200px) {
    .individual-tax-grid {
        grid-template-columns: 1fr;
        gap: 1.125rem;
    }

    .individual-tax-panel + .individual-tax-panel {
        margin-left: 0;
        padding-left: 0;
        padding-top: 1.125rem;
        border-left: 0;
        border-top: 1px solid var(--bs-border-color);
    }
}

@media (max-width: 768px) {
    .individual-tax-toolbar,
    .individual-tax-meta {
        display: grid;
        grid-template-columns: 1fr;
    }

    .individual-tax-toolbar-form {
        width: 100%;
    }

    .individual-tax-employee-select,
    .individual-tax-select {
        width: 100%;
        min-width: 0;
    }

    :deep(.select2-container) {
        width: 100% !important;
        min-width: 0 !important;
    }
}
</style>
