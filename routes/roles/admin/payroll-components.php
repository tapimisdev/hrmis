<?php

use App\Http\Controllers\Admin\Modules\PayrollComponentsController;
use App\Http\Controllers\Admin\Modules\PayrollComponentsEmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll-components/{slug}')->group(function () {
    Route::get('/', [PayrollComponentsController::class, 'index'])->name('tax.index');
    Route::get('/{year}', [PayrollComponentsController::class, 'show'])->name('tax.show');
    Route::post('/', [PayrollComponentsController::class, 'store'])->name('tax.store');
    Route::put('/{year}', [PayrollComponentsController::class, 'update'])->name('tax.update');

    Route::get('employees/{year}', [PayrollComponentsEmployeeController::class, 'index'])->name('tax.employees.index');
    Route::post('employees/{year}', [PayrollComponentsEmployeeController::class, 'store'])->name('tax.employees.store');
});
