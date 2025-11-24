<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Settings\DeductionController;
use App\Http\Controllers\Admin\Settings\EarningsController;
use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\HolidayController;
use App\Http\Controllers\Admin\Settings\LeaveController;
use App\Http\Controllers\Admin\Settings\ProjectsController;
use App\Http\Controllers\Admin\Settings\OrganizationController;
use App\Http\Controllers\Admin\Settings\PositionController;
use App\Http\Controllers\Admin\Settings\RolesAndPermissionController;
use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Admin\Settings\WeeklyScheduleController;
use App\Http\Controllers\Admin\Settings\TrancheController;
use App\Http\Controllers\Admin\Settings\ApproverController;
use App\Http\Controllers\Admin\Settings\TaxesController;


 Route::prefix('maintenance')->group(function() {
    # ROLES AND PERMISSIONS
    Route::resource('role-and-permission', RolesAndPermissionController::class);

    # PROJECTS 
    Route::resource('projects', ProjectsController::class)
        ->except('show');

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

    # SHIFTS
    Route::resource('shift', ShiftController::class);

    # WEEKLY SCHEDULES
    Route::resource('weekly-schedules', WeeklyScheduleController::class);

    # HOLIDAYS
    Route::resource('holiday', HolidayController::class);

    # EARNINGS
    Route::resource('earnings', EarningsController::class);

    # DEDUCTIONS
    Route::resource('deductions', DeductionController::class);

    # LEAVES
    Route::resource('leaves', LeaveController::class)->names('settings.leaves');

    # TRANCHES
    Route::get('tranche', [TrancheController::class, 'index'])->name('settings.tranche.index');
    Route::get('tranche/{id}/show', [TrancheController::class, 'show'])->name('settings.tranche.show');
    Route::get('tranche/create', [TrancheController::class, 'create'])->name('settings.tranche.create');
    Route::post('tranche/create', [TrancheController::class, 'store'])->name('settings.tranche.store');
    Route::get('tranche/{id}/edit', [TrancheController::class, 'edit'])->name('settings.tranche.edit');
    Route::put('tranche/{id}/edit', [TrancheController::class, 'update'])->name('settings.tranche.update');
    Route::any('tranche/{id}/destroy', [TrancheController::class, 'destroy'])->name('settings.tranche.destroy');

    # APPROVERS
    Route::get('approvers', [ApproverController::class, 'index'])->name('settings.approvers.index');
    Route::get('approvers/all', [ApproverController::class, 'view'])->name('settings.approvers.view');
    Route::get('approvers/create', [ApproverController::class, 'create'])->name('settings.approvers.create');
    Route::post('approvers', [ApproverController::class, 'store'])->name('settings.approvers.store');
    Route::get('approvers/{approver}', [ApproverController::class, 'show'])->name('settings.approvers.show');
    Route::get('approvers/{approver}/edit', [ApproverController::class, 'edit'])->name('settings.approvers.edit');
    Route::put('approvers/{approver}', [ApproverController::class, 'update'])->name('settings.approvers.update');
    Route::delete('approvers/{approver}', [ApproverController::class, 'destroy'])->name('settings.approvers.destroy');

    # TAX MAPPING
    Route::get('taxes', [TaxesController::class, 'index'])->name('settings.taxes.index');
    Route::post('taxes', [TaxesController::class, 'save'])->name('settings.taxes.save');

});