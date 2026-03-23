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
use App\Http\Controllers\Admin\Payroll\PayrollGroupController;
use App\Http\Controllers\Admin\Payroll\PayrollGroupEmployeesController;

Route::prefix('payroll')->group(function() {
    
    # GROUPS
    Route::resource('groups', PayrollGroupController::class)->names('payroll.group');
    Route::resource('groups/{id}/employees', PayrollGroupEmployeesController::class)->names('payroll.group.employees');

    # IMPORTING 
    Route::get('import/salary-pay', [SalaryRegistryController::class, 'index'])
        ->name('registry.salary.index');
    Route::post('import/salary-pay', [SalaryRegistryController::class, 'store'])
        ->name('registry.salary.store');

    # SALARY PAYROLL
    Route::resource('salary-pay', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # HAZARD PAYROLL
    Route::resource('hazard-pay', HazardPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # SLA PAYROLL
    Route::resource('sla-pay', SLAPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # RATA PAYROLL
    Route::resource('pera-rata', PeraRataController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # LONGEVITY PAYROLL
    Route::resource('longevity-pay', LongevityPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # GOVERNMENT BONUS PAYROLL
    Route::resource('government-bonuses', GovernmentBonusController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # GOVERNMENT BONUS RULES
    Route::resource('government-bonus-types', GovernmentBonusTypeController::class)->only('index', 'store', 'update', 'destroy');

});
