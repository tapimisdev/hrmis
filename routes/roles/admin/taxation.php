<?php

use App\Http\Controllers\Admin\Taxation\TaxationController;
use App\Http\Controllers\Admin\Taxation\TaxationEmployeeController;
use App\Http\Controllers\Admin\Taxation\Bir2316Controller;
use App\Http\Controllers\Admin\Taxation\IndividualTaxController;
use App\Http\Controllers\Admin\Taxation\IndividualTaxMonthlyReportController;
use App\Http\Controllers\Admin\Taxation\TrainLawController;
use App\Http\Controllers\Admin\Taxation\TrainLawItemController;
use Illuminate\Support\Facades\Route;
Route::prefix('taxation')->group(function() {

    Route::get('/individual-tax', [IndividualTaxController::class, 'index'])
        ->name('taxation.individual-tax.index');
    Route::post('/individual-tax/save', [IndividualTaxController::class, 'save'])
        ->name('taxation.individual-tax.save');
    Route::get('/individual-tax-report', [IndividualTaxMonthlyReportController::class, 'index'])
        ->name('taxation.individual-tax-report.index');
    Route::get('/bir-2316', [Bir2316Controller::class, 'index'])
        ->name('taxation.bir-2316.index');
    Route::post('/bir-2316/generate', [Bir2316Controller::class, 'generate'])
        ->name('taxation.bir-2316.generate');
    Route::get('/bir-2316/{id}', [Bir2316Controller::class, 'show'])
        ->name('taxation.bir-2316.show');
    Route::post('/bir-2316/{id}/lock', [Bir2316Controller::class, 'lock'])
        ->name('taxation.bir-2316.lock');
    Route::post('/bir-2316/{id}/unlock', [Bir2316Controller::class, 'unlock'])
        ->name('taxation.bir-2316.unlock');
    Route::get('/bir-2316/{id}/print', [Bir2316Controller::class, 'print'])
        ->name('taxation.bir-2316.print');
    Route::get('/bir-2316/{id}/pdf', [Bir2316Controller::class, 'pdf'])
        ->name('taxation.bir-2316.pdf');

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
