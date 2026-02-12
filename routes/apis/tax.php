
<?php

use App\Http\Controllers\Api\Taxation\RunForecastApiController;
use App\Http\Controllers\Api\Taxation\TaxationSetupApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('tax')->group(function() {

    Route::get('hazard-tax-lists', [TaxationSetupApiController::class, 'hazard_tax_list']);
    Route::get('salary-tax-lists', [TaxationSetupApiController::class, 'salary_tax_list']);
    Route::get('longevity-tax-lists', [TaxationSetupApiController::class, 'longevity_tax_list']);
    Route::get('train-law-lists', [TaxationSetupApiController::class, 'train_law_list']);

    Route::post('run-forecast', [RunForecastApiController::class, 'run']);
});