<?php

use App\Http\Controllers\Admin\Hris\ImportEmployeeController;
use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Admin\Settings\TrancheController;
use App\Http\Controllers\Admin\Settings\WeeklyScheduleController;
use App\Http\Controllers\Admin\Payroll\Api\SalaryApiController;
use App\Http\Controllers\Admin\Payroll\SalaryController;
use App\Http\Controllers\Admin\Timekeeping\DailyTimeRecordController;
use App\Http\Controllers\Api\AddTimeApiController;
use App\Http\Controllers\Api\Employee;
use App\Http\Controllers\Api\LeavesApiController;
use App\Http\Controllers\Api\Organization;
use App\Http\Controllers\Employee\LeaveApplicationController;
use App\Http\Controllers\Admin\Timekeeping\UploadTimeLogController;
use App\Http\Controllers\Api\CountriesApiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\timelogs\CheckInOutController;
use App\Models\User;
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

Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout']);

// Protected routes (require Bearer token from Passport)
Route::middleware('auth:sanctum')->group(function () {
    # ORGANIZATION
    Route::get('employment-types', [EmploymentTypesController::class, 'index']);
    Route::get('get-employment-types', [EmploymentTypesController::class, 'getEmploymentTypes']);
    Route::get('tranches', [TrancheController::class, 'tranches']);
    Route::get('compute-salary/{trach_id}/{salary_grade}/{step}', [TrancheController::class, 'compute_salary']);
    Route::get('divisions', [Organization::class, 'division'])
        ->name('api.divisions');
    Route::get('units/{division_id}', [Organization::class, 'unit'])
        ->name('api.units');

    # EMPLOYEE
    Route::prefix('employee')->group(function() {
        # Upload employee file with some details  ##First step in importing employees
        Route::post('upload', [ImportEmployeeController::class, 'upload']);
        Route::post('import', [ImportEmployeeController::class, 'store']);

        Route::get('children', [Employee::class, 'children'])
            ->name('api.employee.children');
    });

    # payroll
    Route::prefix('payroll')->group(function () {
        Route::post('validate-and-fetch-employees', [SalaryApiController::class, 'validateAndGetEmployee']);
    });

    Route::get('/users', function () {
        $users = User::role('hr')->get(); 
        return response()->json($users);
    });

    # PAYROLL
    Route::prefix('payroll')->group(function() {
        Route::post('salary', [SalaryApiController::class, 'getList']);
        
        # Adjustment
        Route::post('adjustments', [SalaryApiController::class, 'getAdjustments']);
    });

    # TIMEKEEPING
    Route::prefix('timekeeping')->group(function() {
        Route::post('import-timelogs', [UploadTimeLogController::class, 'store']);
    });

    # LEAVES
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

});
