<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardApiController;

 # PREFIX
Route::prefix('dashboard')->group(function() {
    Route::get('metrics', [DashboardApiController::class, 'metrics']);
    Route::get('birthdays', [DashboardApiController::class, 'birthdays']);
    Route::get('attendances', [DashboardApiController::class, 'attendances']);
    Route::get('employment-types', [DashboardApiController::class, 'employment_types']);
    Route::get('employee-movement', [DashboardApiController::class, 'employee_movement']);
});