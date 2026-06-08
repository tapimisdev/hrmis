<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Settings\DeductionController;
use App\Http\Controllers\Admin\Settings\EarningsController;
use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\HolidayController;
use App\Http\Controllers\Admin\Settings\LeaveController;
use App\Http\Controllers\Admin\Settings\LeaveAssignController;
use App\Http\Controllers\Admin\Settings\CreditsController;
use App\Http\Controllers\Admin\Settings\ProjectsController;
use App\Http\Controllers\Admin\Settings\OrganizationController;
use App\Http\Controllers\Admin\Settings\PositionController;
use App\Http\Controllers\Admin\Settings\RolesAndPermissionController;
use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Admin\Settings\WeeklyScheduleController;
use App\Http\Controllers\Admin\Settings\TrancheController;
use App\Http\Controllers\Admin\Settings\ApproverController;
use App\Http\Controllers\Admin\Settings\PayrollComponentsController;
use App\Http\Controllers\Admin\Settings\PayrollSettingsController;
use App\Http\Controllers\Admin\Settings\ViolationController;


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
    Route::prefix('leaves')->name('settings.leaves.')->group(function () {
        Route::get('/assign', [LeaveAssignController::class, 'index'])
            ->name('assign');
        Route::post('/assign', [LeaveAssignController::class, 'save'])
            ->name('assign');
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::get('/{leave}', [LeaveController::class, 'show'])->name('show');
        Route::get('/{leave}/edit', [LeaveController::class, 'edit'])->name('edit');
        Route::put('/{leave}', [LeaveController::class, 'update'])->name('update');
        Route::patch('/{leave}', [LeaveController::class, 'update']);
        Route::delete('/{leave}', [LeaveController::class, 'destroy'])->name('destroy');
    });

    # CREDITS IMPORT / EXPORT
    Route::get('credits/{type}', [CreditsController::class, 'index'])
        ->name('settings.credits.index');
    Route::post('credits/{type}/import', [CreditsController::class, 'save'])
        ->name('settings.credits.import');

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

    # VIOLATIONS
    Route::get('violations', [ViolationController::class, 'index'])->name('settings.violations.index');
    Route::get('violations/create', [ViolationController::class, 'create'])->name('settings.violations.create');
    Route::post('violations', [ViolationController::class, 'store'])->name('settings.violations.store');
    Route::get('violations/{violation}', [ViolationController::class, 'show'])->name('settings.violations.show');
    Route::get('violations/{violation}/edit', [ViolationController::class, 'edit'])->name('settings.violations.edit');
    Route::put('violations/{violation}', [ViolationController::class, 'update'])->name('settings.violations.update');
    Route::delete('violations/{violation}', [ViolationController::class, 'destroy'])->name('settings.violations.destroy');

    # PAYROLL COMPONENTS
    Route::get('payroll-components', [PayrollComponentsController::class, 'index'])->name('settings.payroll-components.index');
    Route::get('payroll-components/create', [PayrollComponentsController::class, 'create'])->name('settings.payroll-components.create');
    Route::post('payroll-components/store', [PayrollComponentsController::class, 'store'])->name('settings.payroll-components.store');
    Route::put('payroll-components/update/{id}', [PayrollComponentsController::class, 'update'])->name('settings.payroll-components.update');
    Route::delete('payroll-components/delete/{id}', [PayrollComponentsController::class, 'destroy'])->name('settings.payroll-components.delete');

    # PAYROLL SETTINGS
    Route::get('payroll-settings', [PayrollSettingsController::class, 'index'])->name('settings.payroll-settings.index');
    Route::post('payroll-settings', [PayrollSettingsController::class, 'save'])->name('settings.payroll-settings.save');

});
