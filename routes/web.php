<?php

use App\Http\Controllers\Admin\HrisController;
use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\OrganizationController;
use App\Http\Controllers\Admin\Settings\PositionController;
use App\Http\Controllers\Admin\Settings\RolesAndPermissionController;
use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Employee\AtroController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveApplicationController;
use App\Http\Controllers\Employee\ObsController;
use App\Http\Controllers\Employee\timelogs\CheckInOutController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => false]);


Route::prefix('admin')->middleware(['checkrole:admin'])->group(function () {
    
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::prefix('hris')->group(function() {
        # HRIS
        Route::resource('employee', HrisController::class)
            ->names('hris.employee');
    });

    Route::prefix('settings')->group(function() {
        # ROLES AND PERMISSIONS
        Route::resource('role-and-permission', RolesAndPermissionController::class);
        
        # EMPLOYMENT TYPES
        Route::resource('employment-types', EmploymentTypesController::class)
            ->except('show');

        # ORGANIZATION 
        Route::resource('organization', OrganizationController::class)
            ->except('show');

        # POSITIONS
        Route::get('positions/{employment_type_id?}', [PositionController::class, 'index'])->name('positions.index');
        Route::post('positions/{employment_type_id?}', [PositionController::class, 'store'])->name('positions.store');
        Route::get('positions/{employment_type_id?}/create', [PositionController::class, 'create'])->name('positions.create');
        Route::get('positions/{employment_type_id?}/{id}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('positions/{employment_type_id?}/{id}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('positions/{employment_type_id?}/{id}', [PositionController::class, 'destroy'])->name('positions.destroy');

        # Shift
        Route::resource('shift', ShiftController::class);

    });
});

Route::prefix('employee')->middleware('checkrole:employee')->group(function () {
    Route::resource('dashboard', EmployeeDashboardController::class);
    Route::resource('leaves', LeaveApplicationController::class)->except('edit', 'update');
    Route::resource('overtime', AtroController::class)->except('edit', 'update');
    Route::resource('official-business-slip', ObsController::class)->except('edit', 'update')->names('obs');
    Route::resource('check-in-out', CheckInOutController::class)->only('index', 'store', 'create')->names('checkinout');
    Route::get('check-in-out/today-logs', [CheckInOutController::class, 'todayLogs']);
});
