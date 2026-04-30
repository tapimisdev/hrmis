<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\Api\SalaryApiController;
use App\Http\Controllers\Admin\Payroll\Salary\SalaryController;
use App\Http\Controllers\Admin\Payroll\Salary\SalaryItemController;

use App\Http\Controllers\Admin\Payroll\Api\HazardApiController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardPayController;
use App\Http\Controllers\Admin\Payroll\HazardPay\HazardItemController;

use App\Http\Controllers\Admin\Payroll\Api\SLAApiController;
use App\Http\Controllers\Admin\Payroll\SLAPay\SLAPayController;
use App\Http\Controllers\Admin\Payroll\SLAPay\SLAItemController;

use App\Http\Controllers\Admin\Payroll\Api\PeraRataApiController;
use App\Http\Controllers\Admin\Payroll\PayrollGroupController;
use App\Http\Controllers\Admin\Payroll\PeraRata\PeraRataController;
use App\Http\Controllers\Admin\Payroll\PeraRata\PeraRataItemController;
use App\Http\Controllers\Admin\Payroll\Api\LongevityApiController;
use App\Http\Controllers\Admin\Payroll\LongevityPay\LongevityPayController;
use App\Http\Controllers\Admin\Payroll\LongevityPay\LongevityPayItemController;
use App\Http\Controllers\Admin\Payroll\Api\GovernmentBonusApiController;
use App\Http\Controllers\Admin\Payroll\GovernmentBonus\GovernmentBonusController;
use App\Http\Controllers\Admin\Payroll\GovernmentBonus\GovernmentBonusItemController;

use App\Http\Controllers\Admin\Payroll\ReportsController;

Route::prefix('payroll')->group(function() {

    Route::get('approvers', [ReportsController::class, 'getApprovers']);
    Route::get('/progress/{batchId}', [ReportsController::class, 'getBatchProgress']);
    Route::post('/cancel/{batchId}', [ReportsController::class, 'cancelBatch']);

    Route::get('groups/{id}', [PayrollGroupController::class, 'get_groups'])->name('get.groups');

    # Salary Payroll

    Route::prefix('salary-pay')->group(function() {

    
        Route::post('items/{payroll_id}/{payroll_emp_id}', [SalaryItemController::class, 'update']);
        Route::post('check-employees', [SalaryApiController::class, 'validateAndGetEmployee']);
        Route::get('deduction-options', [SalaryApiController::class, 'deductionOptions']);
        Route::post('adjustments', [SalaryApiController::class, 'getAdjustments']);
        Route::post('processed', [SalaryApiController::class, 'getList']);
        Route::get('{payroll_id}/aut-deductions/preview', [SalaryApiController::class, 'previewAutDeductions']);
        Route::post('{payroll_id}/aut-deductions/apply', [SalaryApiController::class, 'applyAutDeductions']);
        Route::get('{payroll_id}', [SalaryApiController::class, 'getPayrollData']);
        Route::post('generate', [SalaryController::class, 'store']);
        Route::delete('{id}/delete', [SalaryController::class, 'destroy']);

        Route::delete('{id}/{employment_type}', 
            [SalaryController::class, 'deleteEmployeePayroll']
        );

        Route::patch('{id}/status', [SalaryController::class, 'updateStatus']);

        # DOWNLOAD

        Route::get('{payroll_no}/download', [SalaryApiController::class, 'downloadPayrollRegistry'])
            ->name('api.payroll.salary.download');
        Route::get('absences-leaves/{payroll_no}/download', [SalaryApiController::class, 'downloadAbsencesLeaves'])
            ->name('api.payroll.absences-leaves.download');
        Route::get('payslip/{payroll_no}/download', [SalaryApiController::class, 'downloadPayslip'])
            ->name('api.payroll.payslip.download');

    });

    # Hazard Payroll
    Route::prefix('hazard-pay')->group(function() {
        Route::post('items/{payroll_id}/{payroll_emp_id}', [HazardItemController::class, 'update']);
        Route::post('check-employees', [HazardApiController::class, 'validateAndGetEmployee']);
        Route::post('processed', [HazardApiController::class, 'getList']);
        Route::get('{payroll_id}', [HazardApiController::class, 'getPayrollData']);
        Route::post('generate', [HazardPayController::class, 'store']);
        Route::delete('{id}/delete', [HazardPayController::class, 'destroy']);
        Route::delete('{id}/{employment_type}', [HazardPayController::class, 'deleteEmployeePayroll']);
        Route::patch('{id}/status', [HazardPayController::class, 'updateStatus']);
        Route::get('{payroll_no}/download', [HazardApiController::class, 'downloadPayrollRegistry'])
            ->name('api.payroll.hazard.download');
    });

    # SLA Payroll
    Route::prefix('sla-pay')->group(function() {
        Route::post('items/{payroll_id}/{payroll_emp_id}', [SLAItemController::class, 'update']);
        Route::post('check-employees', [SLAApiController::class, 'validateAndGetEmployee']);
        Route::post('processed', [SLAApiController::class, 'getList']);
        Route::get('{payroll_id}', [SLAApiController::class, 'getPayrollData']);
        Route::get('download/registry/{payroll_no}', [SLAApiController::class, 'downloadPayrollRegistry']);
        Route::post('generate', [SLAPayController::class, 'store']);
        Route::delete('{id}/delete', [SLAPayController::class, 'destroy']);
        Route::delete('{id}/{employment_type}', [SLAPayController::class, 'deleteEmployeePayroll']);
        Route::patch('{id}/status', [SLAPayController::class, 'updateStatus']);
    });

    # PERA RATA Payroll
    Route::prefix('pera-rata')->group(function() {
        Route::post('items/{payroll_id}/{payroll_emp_id}', [PeraRataItemController::class, 'update']);
        Route::post('check-employees', [PeraRataApiController::class, 'validateAndGetEmployee']);
        Route::post('processed', [PeraRataApiController::class, 'getList']);
        Route::get('{payroll_id}', [PeraRataApiController::class, 'getPayrollData']);
        Route::get('download/registry/{payroll_no}', [PeraRataApiController::class, 'downloadPayrollRegistry']);
        Route::post('generate', [PeraRataController::class, 'store']);
        Route::delete('{id}/delete', [PeraRataController::class, 'destroy']);
        Route::delete('{id}/{employment_type}', [PeraRataController::class, 'deleteEmployeePayroll']);
        Route::patch('{id}/status', [PeraRataController::class, 'updateStatus']);
    });

    # LONGEVITY Payroll
    Route::prefix('longevity-pay')->group(function() {
        Route::post('items/{payroll_id}/{payroll_emp_id}', [LongevityPayItemController::class, 'update']);
        Route::post('check-employees', [LongevityApiController::class, 'validateAndGetEmployee']);
        Route::post('processed', [LongevityApiController::class, 'getList']);
        Route::get('{payroll_id}', [LongevityApiController::class, 'getPayrollData']);
        Route::get('{payroll_no}/download', [LongevityApiController::class, 'downloadPayrollRegistry'])
            ->name('api.payroll.longevity.download');
        Route::post('generate', [LongevityPayController::class, 'store']);
        Route::delete('{id}/delete', [LongevityPayController::class, 'destroy']);
        Route::delete('{id}/{employment_type}', [LongevityPayController::class, 'deleteEmployeePayroll']);
        Route::patch('{id}/status', [LongevityPayController::class, 'updateStatus']);
    });

    # GOVERNMENT BONUS Payroll
    Route::prefix('government-bonuses')->group(function() {
        Route::get('bonus-types', [GovernmentBonusApiController::class, 'bonusTypes']);
        Route::post('items/{payroll_id}/{payroll_emp_id}', [GovernmentBonusItemController::class, 'update']);
        Route::post('check-employees', [GovernmentBonusApiController::class, 'validateAndGetEmployee']);
        Route::post('processed', [GovernmentBonusApiController::class, 'getList']);
        Route::get('{payroll_id}', [GovernmentBonusApiController::class, 'getPayrollData']);
        Route::post('generate', [GovernmentBonusController::class, 'store']);
        Route::delete('{id}/delete', [GovernmentBonusController::class, 'destroy']);
        Route::delete('{id}/{employment_type}', [GovernmentBonusController::class, 'deleteEmployeePayroll']);
        Route::patch('{id}/status', [GovernmentBonusController::class, 'updateStatus']);
    });

});
