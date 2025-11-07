
<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Employee;
use App\Http\Controllers\Employee\LeaveApplicationController;
use App\Http\Controllers\Api\CountriesApiController;

Route::resource('leaves', LeaveApplicationController::class)
    ->only('store', 'update');

Route::get('countries', [CountriesApiController::class, 'index'])
    ->name('api.countries');

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

Route::get('skills', [Employee::class, 'skills'])
    ->name('api.employee.skills');