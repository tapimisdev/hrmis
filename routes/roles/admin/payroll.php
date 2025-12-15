<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;
use App\Http\Controllers\Admin\Payroll\SLAPay\SLAPayController;
use App\Http\Controllers\Admin\Payroll\PeraRata\PeraRataController;

Route::prefix('payroll')->group(function() {
    
    # SALARY PAYROLL
    Route::resource('salary-pay', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # HAZARD PAYROLL
    Route::resource('hazard-pay', HazardPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # SLA PAYROLL
    Route::resource('sla-pay', SLAPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # RATA PAYROLL
    Route::resource('pera-rata', PeraRataController::class)->only('index', 'create', 'show', 'store', 'destroy');

});