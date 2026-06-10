<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\TrancheController;
use App\Http\Controllers\Api\Organization;

# ORGANIZATION
Route::get('employment-types', [EmploymentTypesController::class, 'index']);
Route::get('get-employment-types', [EmploymentTypesController::class, 'getEmploymentTypes']);
Route::get('tranches', [TrancheController::class, 'tranches']);
Route::get('compute-salary/{trach_id}/{salary_grade}/{step}', [TrancheController::class, 'compute_salary']);
Route::get('divisions', [Organization::class, 'division'])
    ->name('api.divisions');
Route::get('units/{division_id}', [Organization::class, 'unit'])
    ->name('api.units');
