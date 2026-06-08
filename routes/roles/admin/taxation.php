<?php

use App\Http\Controllers\Admin\Taxation\TaxationController;
use App\Http\Controllers\Admin\Taxation\TaxationEmployeeController;
use App\Http\Controllers\Admin\Taxation\IndividualTaxController;
use App\Http\Controllers\Admin\Taxation\TrainLawController;
use App\Http\Controllers\Admin\Taxation\TrainLawItemController;
use Illuminate\Support\Facades\Route;
Route::prefix('taxation')->group(function() {

    Route::get('/individual-tax', [IndividualTaxController::class, 'index'])
        ->name('taxation.individual-tax.index');
    Route::post('/individual-tax/save', [IndividualTaxController::class, 'save'])
        ->name('taxation.individual-tax.save');

    // Route::resource('/', TaxationController::class)->names('taxation');
    // Route::get('/status', [TaxationController::class, 'status']);
    // Route::delete('/{taxation_id}/delete', [TaxationController::class, 'destroy']);
    // Route::post('/apply-to-payroll-preview', [TaxationController::class, 'applyToPayrollPreview']);
    // Route::post('/apply-to-payroll', [TaxationController::class, 'applyToPayroll']);
    // Route::post('/compute-cumulative', [TaxationController::class, 'computeCumulative']);

    Route::get('/breakdowns/{taxation_employee_id}', [TaxationEmployeeController::class, 'breakdowns']);
    Route::get('/edit-inputs/{taxation_employee_id}', [TaxationEmployeeController::class, 'edit']);
    Route::post('/save/{taxation_employee_id}', [TaxationEmployeeController::class, 'update']);
    Route::get('/recompute/{taxation_employee_id}', [TaxationEmployeeController::class, 'recompute']);

    Route::resource('train-law', TrainLawController::class)->names('taxation.train-law');
    Route::patch('train-law/{id}/inactive', [TrainLawController::class, 'setInactive'])->name('train-law.inactive');
    
    Route::get('train-law/{trainLawId}/items', [TrainLawItemController::class, 'index'])
        ->name('taxation.train-law-items.index');

    Route::post('train-law/{trainLawId}/items', [TrainLawItemController::class, 'store'])
        ->name('taxation.train-law-items.store');
    
});
