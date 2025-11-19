<?php

use App\Http\Controllers\Admin\Taxes\SalaryTaxesController;
use Illuminate\Support\Facades\Route;

Route::prefix('taxes')->group(function() {
    #Salary Taxes
    Route::resource('salary', SalaryTaxesController::class)->only('index', 'store', 'edit', 'update')->names('tax.salary');
});