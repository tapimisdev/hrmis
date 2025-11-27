<?php

use App\Http\Controllers\Admin\Modules\PayrollComponentsController;
use App\Http\Controllers\Admin\Modules\PayrollComponentsEmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll-components/{slug}')->group(function () {
    Route::get('/', [PayrollComponentsController::class, 'index'])->name('payroll-components.index');
    Route::get('/{year}', [PayrollComponentsController::class, 'show'])->name('payroll-components.show');
    Route::post('/', [PayrollComponentsController::class, 'store'])->name('payroll-components.store');
    Route::put('/{year}', [PayrollComponentsController::class, 'update'])->name('payroll-components.update');

    Route::get('employees/{year}', [PayrollComponentsEmployeeController::class, 'index'])->name('payroll-employee-components.index');
    Route::post('employees/{year}', [PayrollComponentsEmployeeController::class, 'store'])->name('payroll-employee-components.index');
});
