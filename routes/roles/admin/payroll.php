<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;
use App\Http\Controllers\Admin\Payroll\SLAPay\SLAPayController;
use App\Http\Controllers\Admin\Payroll\PeraRata\PeraRataController;
use App\Http\Controllers\Admin\Payroll\ImportRegistryController;
use App\Http\Controllers\Admin\Payroll\PayrollGroupController;
use App\Http\Controllers\Admin\Payroll\PayrollGroupEmployeesController;

Route::prefix('payroll')->group(function() {
    
    # GROUPS
    Route::resource('groups', PayrollGroupController::class)->names('payroll.group');
    Route::resource('groups/{id}/employees', PayrollGroupEmployeesController::class)->names('payroll.group.employees');

    # IMPORTING 
    Route::resource('import/registry', ImportRegistryController::class)->only('index', 'store', 'update');

    # SALARY PAYROLL
    Route::resource('salary-pay', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # HAZARD PAYROLL
    Route::resource('hazard-pay', HazardPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # SLA PAYROLL
    Route::resource('sla-pay', SLAPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # RATA PAYROLL
    Route::resource('pera-rata', PeraRataController::class)->only('index', 'create', 'show', 'store', 'destroy');

});