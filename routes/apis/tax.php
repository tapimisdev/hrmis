<?php

use App\Http\Controllers\Api\Taxation\RunForecastApiController;
use App\Http\Controllers\Api\Taxation\Bir2316ApiController;
use App\Http\Controllers\Api\Taxation\IndividualTaxApiController;
use App\Http\Controllers\Api\Taxation\IndividualTaxMonthlyReportApiController;
use App\Http\Controllers\Api\Taxation\TaxationEmployeesApiController;
use App\Http\Controllers\Api\Taxation\TaxationSetupApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('tax')->group(function() {

    Route::get('hazard-tax-lists', [TaxationSetupApiController::class, 'hazard_tax_list']);
    Route::get('salary-tax-lists', [TaxationSetupApiController::class, 'salary_tax_list']);
    Route::get('longevity-tax-lists', [TaxationSetupApiController::class, 'longevity_tax_list']);
    Route::get('train-law-lists', [TaxationSetupApiController::class, 'train_law_list']);

    Route::post('run-forecast', [RunForecastApiController::class, 'run']);
    Route::get('individual-tax', [IndividualTaxApiController::class, 'index']);
    Route::get('individual-tax-monthly-report', [IndividualTaxMonthlyReportApiController::class, 'index']);
    Route::get('bir-2316', [Bir2316ApiController::class, 'index']);
    Route::get('bir-2316/{id}', [Bir2316ApiController::class, 'show']);

    Route::prefix('breakdown')->group(function() {
        Route::get('/{taxation_employee_id}', [TaxationEmployeesApiController::class, 'breakdowns']);
    });
});
