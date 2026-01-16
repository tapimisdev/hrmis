
<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Hris\ImportEmployeeController;
use App\Http\Controllers\Employee\LogsController;
use App\Http\Controllers\Api\Employee;

# EMPLOYEE
Route::prefix('employee')->group(function() {
    # Upload employee file with some details  ##First step in importing employees
    Route::post('upload', [ImportEmployeeController::class, 'upload']);
    Route::post('import', [ImportEmployeeController::class, 'store']);

    Route::get('children', [Employee::class, 'children'])
        ->name('api.employee.children');

    Route::get('incomplete-logs', [LogsController::class, 'getIncompleteLogs']);
    Route::get('current-logs', [LogsController::class, 'getCurrentTimelog']);

});