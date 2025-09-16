<?php

use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Admin\Settings\WeeklyScheduleController;
use App\Http\Controllers\Api\AddTimeApiController;
use App\Http\Controllers\Api\Employee;
use App\Http\Controllers\Api\LeavesApiController;
use App\Http\Controllers\Api\Organization;
use App\Http\Controllers\Employee\LeaveApplicationController;
use App\Http\Controllers\Api\CountriesApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('divisions', [Organization::class, 'division'])
    ->name('api.divisions');
Route::get('units/{division_id}', [Organization::class, 'unit'])
    ->name('api.units');

# EMPLOYEE
Route::prefix('employee')->group(function() {

    Route::get('children', [Employee::class, 'children'])
        ->name('api.employee.children');

});

Route::get('leaves', [LeavesApiController::class, 'getLeaves'])
        ->name('api.get-leaves');

Route::resource('leaves', LeaveApplicationController::class)->only('store', 'update');

Route::get('countries', [CountriesApiController::class, 'index'])->name('api.countries');
Route::get('education', [Employee::class, 'education'])
    ->name('api.employee.education');

Route::get('civil-service', [Employee::class, 'civil_service'])
    ->name('api.employee.civil-service');

Route::get('work-experience', [Employee::class, 'work_experience'])
    ->name('api.employee.work-experience');

Route::get('voluntary-works', [Employee::class, 'voluntary_works'])
    ->name('api.employee.voluntary-works');

Route::get('trainings', [Employee::class, 'trainings'])
    ->name('api.employee.trainings');

Route::get('skikls', [Employee::class, 'skills'])
    ->name('api.employee.skills');

Route::get('shifts', [ShiftController::class, 'index']);
Route::get('work-schedules', [WeeklyScheduleController::class, 'index']);

Route::get('fetch-timelogs', [AddTimeApiController::class, 'edit']);
Route::post('add-time', [AddTimeApiController::class, 'add_time']);
Route::post('add-overtime', [AddTimeApiController::class, 'add_overtime']);
Route::get('get-overtime', [AddTimeApiController::class, 'getOvertime']);
