<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;
use App\Http\Controllers\Admin\Payroll\SLAPay\SLAPayController;
use App\Http\Controllers\Admin\Payroll\PeraRata\PeraRataController;
use App\Http\Controllers\Admin\Payroll\LongevityPay\LongevityPayController;
use App\Http\Controllers\Admin\Payroll\GovernmentBonus\GovernmentBonusController;
use App\Http\Controllers\Admin\Payroll\GovernmentBonusType\GovernmentBonusTypeController;
use App\Http\Controllers\Admin\Payroll\Import\SalaryRegistryController;
use App\Http\Controllers\Admin\Payroll\Import\HazardRegistryController;
use App\Http\Controllers\Admin\Payroll\Import\PeraRataRegistryController;
use App\Http\Controllers\Admin\Payroll\Import\SLARegistryController;
use App\Http\Controllers\Admin\Payroll\Import\LongevityRegistryController;
use App\Http\Controllers\Admin\Payroll\PayrollGroupController;
use App\Http\Controllers\Admin\Payroll\PayrollGroupEmployeesController;
use App\Http\Controllers\Admin\Payroll\SubsistenceAllowanceController;
use App\Http\Controllers\Admin\Payroll\MonthlyPayrollSummaryController;

Route::prefix('payroll')->group(function() {
    
    # GROUPS
    Route::resource('groups', PayrollGroupController::class)->names('payroll.group');
    Route::resource('groups/{id}/employees', PayrollGroupEmployeesController::class)->names('payroll.group.employees');

    # IMPORTING 
    Route::get('import/salary-pay', [SalaryRegistryController::class, 'index'])
        ->name('registry.salary.index');
    Route::post('import/salary-pay', [SalaryRegistryController::class, 'store'])
        ->name('registry.salary.store');
    Route::get('import/hazard-pay', [HazardRegistryController::class, 'index'])
        ->name('registry.hazard.index');
    Route::post('import/hazard-pay', [HazardRegistryController::class, 'store'])
        ->name('registry.hazard.store');
    Route::get('import/pera-rata', [PeraRataRegistryController::class, 'index'])
        ->name('registry.pera-rata.index');
    Route::post('import/pera-rata', [PeraRataRegistryController::class, 'store'])
        ->name('registry.pera-rata.store');
    Route::get('import/sla-pay', [SLARegistryController::class, 'index'])
        ->name('registry.sla.index');
    Route::post('import/sla-pay', [SLARegistryController::class, 'store'])
        ->name('registry.sla.store');
    Route::get('import/longevity-pay', [LongevityRegistryController::class, 'index'])
        ->name('registry.longevity.index');
    Route::post('import/longevity-pay', [LongevityRegistryController::class, 'store'])
        ->name('registry.longevity.store');

    # SALARY PAYROLL
    Route::resource('salary-pay', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # HAZARD PAYROLL
    Route::resource('hazard-pay', HazardPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # SLA PAYROLL
    Route::get('subsistence-allowance', [SubsistenceAllowanceController::class, 'index'])
        ->name('subsistence-allowance.index');
    Route::resource('sla-pay', SLAPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # RATA PAYROLL
    Route::resource('pera-rata', PeraRataController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # LONGEVITY PAYROLL
    Route::resource('longevity-pay', LongevityPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # GOVERNMENT BONUS PAYROLL
    Route::resource('government-bonuses', GovernmentBonusController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # MONTHLY PAYROLL SUMMARY
    Route::get('monthly-summary', [MonthlyPayrollSummaryController::class, 'index'])
        ->name('payroll.monthly-summary.index');

    # GOVERNMENT BONUS RULES
    Route::resource('government-bonus-types', GovernmentBonusTypeController::class)->only('index', 'store', 'update', 'destroy');

});
