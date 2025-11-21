<?php

use App\Http\Controllers\Admin\Taxes\TaxesController;
use App\Http\Controllers\Admin\Taxes\TaxesEmployeeController;
use Illuminate\Support\Facades\Route;

// Taxes routes
Route::prefix('tax/{slug}')->group(function () {
    Route::get('/', [TaxesController::class, 'index'])->name('tax.index');
    Route::get('/{year}', [TaxesController::class, 'show'])->name('tax.show');
    Route::post('/', [TaxesController::class, 'store'])->name('tax.store');
    Route::put('/{year}', [TaxesController::class, 'update'])->name('tax.update');

    // Employees routes
    Route::get('employees/{year}', [TaxesEmployeeController::class, 'index'])->name('tax.employees.index');
    Route::post('employees/{year}', [TaxesEmployeeController::class, 'store'])->name('tax.employees.store');
});
