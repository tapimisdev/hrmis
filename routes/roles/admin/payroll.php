<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;
use App\Http\Controllers\Admin\Payroll\SLAPay\SLAPayController;

Route::prefix('payroll')->group(function() {
    
    # SALARY PAYROLL
    Route::resource('salary', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # HAZARD PAYROLL
    Route::resource('hazard-pay', HazardPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # SLA PAYROLL
    Route::resource('sla-pay', SLAPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

});