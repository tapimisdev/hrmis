<?php

use App\Http\Controllers\Admin\Taxes\SalaryTaxesController;
use App\Http\Controllers\Admin\Taxes\SalaryTaxesEmployeesController;
use Illuminate\Support\Facades\Route;

Route::prefix('deductions')->group(function() {
    #Salary Taxes
    Route::resource('salary', SalaryTaxesController::class)
        ->names('tax.salary');
    Route::resource('salary/{salary_tax}/employees', SalaryTaxesEmployeesController::class)
        ->only('index', 'store')
        ->names('tax.salary.employees');
});