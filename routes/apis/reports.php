
<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ReportsApiController;
use App\Http\Controllers\Api\PDSApiController;

# REPORTS
Route::prefix('reports')->group(function() {
    Route::get('employee', [ReportsApiController::class, 'index'])
        ->name('reports.employee');
    Route::post('employee', [ReportsApiController::class, 'index'])
        ->name('reports.employee');
    Route::post('employee/download', [ReportsApiController::class, 'download'])
        ->name('reports.employee.download');
    Route::get('employee/pds/{employee_no}', [PDSApiController::class, 'index'])
        ->name('reports.employee.pds');
});