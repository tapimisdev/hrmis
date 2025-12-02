<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Api\SalaryApiController;
use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\Salary\SalaryItemController;

use App\Http\Controllers\Admin\Payroll\Api\HazardApiController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;

Route::prefix('payroll')->group(function() {
    Route::post('validate-and-fetch-employees', [SalaryApiController::class, 'validateAndGetEmployee']);
    Route::post('salary', [SalaryApiController::class, 'getList']);
    Route::get('salary/{payroll_id}', [SalaryApiController::class, 'getPayrollRegistry']);
    Route::post('generate-salary-payroll', [SalaryController::class, 'store']);

    Route::delete('delete-payroll/{id}', [SalaryController::class, 'destroy']);

    Route::post('salary-item/{id}', [SalaryItemController::class, 'update']);

    # Adjustment
    Route::post('adjustments', [SalaryApiController::class, 'getAdjustments']);
    Route::get('approvers', [SalaryApiController::class, 'approvers']);

    Route::get('/progress/{batchId}', [SalaryController::class, 'getBatchProgress']);
    Route::post('/cancel/{batchId}', [SalaryController::class, 'cancelBatch']);

    # DOWNLOADS
    Route::get('salary/{payroll_no}/download', [SalaryApiController::class, 'downloadPayrollRegistry'])
        ->name('api.payroll.salary.download');
    Route::get('absences-leaves/{payroll_no}/download', [SalaryApiController::class, 'downloadAbsencesLeaves'])
        ->name('api.payroll.absences-leaves.download');
    Route::get('payslip/{payroll_no}/download', [SalaryApiController::class, 'downloadPayslip'])
        ->name('api.payroll.payslip.download');


    # Hazard Payroll

    Route::prefix('hazard-pay')->group(function() {
        Route::post('validate-and-fetch-employees', [HazardApiController::class, 'validateAndGetEmployee']);
        Route::get('{payroll_id}', [HazardApiController::class, 'getHazardPay']);
        Route::post('generate', [HazardPayController::class, 'store']);
    });

});