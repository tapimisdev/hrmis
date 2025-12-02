<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;

Route::prefix('payroll')->group(function() {
    
    # SALARY PAYROLL
    Route::resource('salary', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');

    # HAZARD PAYROLL
    Route::resource('hazard-pay', HazardPayController::class)->only('index', 'create', 'show', 'store', 'destroy');

});