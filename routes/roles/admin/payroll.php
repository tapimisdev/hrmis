<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\SalaryController;

Route::prefix('payroll')->group(function() {
    # SALARY PAYROLL
    Route::resource('salary', SalaryController::class)->only('index', 'create', 'show', 'store', 'destroy');
});