<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Timekeeping\DailyTimeRecordController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Employee\AnnouncementsController;
use App\Http\Controllers\Employee\AtroController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveApplicationController;
use App\Http\Controllers\Employee\OffsetApplicationController;
use App\Http\Controllers\Employee\AtroApprovalController;
use App\Http\Controllers\Employee\LeaveApprovalController;
use App\Http\Controllers\Employee\ObsApprovalController;
use App\Http\Controllers\Employee\ObsController;
use App\Http\Controllers\Employee\CreditsController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\timelogs\CheckInOutController;

Route::prefix('employee')->middleware(['auth'])->group(function () {

    # EMPLOYEE DASHBOARD
    Route::resource('dashboard', EmployeeDashboardController::class);
    Route::get('get-stats', [EmployeeDashboardController::class, 'get_stats']);
    Route::get('get-pendings', [EmployeeDashboardController::class, 'get_pending_applications']);
    Route::get('get-announcements', [EmployeeDashboardController::class, 'get_announcements']);

    # EMPLOYEE LEAVES, OVERTIME, AND OBS
    Route::resource('leaves', LeaveApplicationController::class)->except('edit', 'update');
    Route::resource('offset', OffsetApplicationController::class)->except('edit', 'update');
    Route::resource('overtime', AtroController::class)->except('edit', 'update');
    Route::resource('pass-slip', ObsController::class)->except('edit', 'update')->names('obs');

    # EMPLOYEE TIMELOGS
    Route::resource('check-in-out', CheckInOutController::class)->only('index', 'store')->names('checkinout');
    Route::get('employee-timelogs/{employee_no}/get', [DailyTimeRecordController::class, 'show']);

    Route::get('check-in-out/today-logs', [CheckInOutController::class, 'todayLogs']);

    Route::prefix('credits')->group(function() {
        Route::get('leave', [CreditsController::class, 'leave'])
            ->name('leave-credits.index');
        Route::get('offset', [CreditsController::class, 'offset'])
            ->name('offset-credits.index');
    });

    Route::resource('payslip', PayslipController::class)->only('index')->names('payslip');
    Route::get('payslip/data', [PayslipController::class, 'fetch_payslip'])->name('payslip.fetch');

    # ANNOUNCEMENTS 
    Route::get('announcements', [AnnouncementsController::class, 'index'])->name('announcement.index');
    Route::get('announcements/{slug}', [AnnouncementsController::class, 'show'])->name('announcement.show');

    # EMPLOYEE LEAVES, OVERTIME, AND OBS -- APPROVAL --
    Route::get('approval-leaves/{level?}', [LeaveApprovalController::class, 'index'])
        ->name('approval-leave.index');
    Route::get('approval-leaves/{level?}/view', [LeaveApprovalController::class, 'view'])
        ->name('approval-leave.view');
    Route::get('approval-leaves/{level}/{id}', [LeaveApprovalController::class, 'show'])
        ->name('approval-leave.show');
    Route::post('approval-leaves/{level}/{id}/save', [LeaveApprovalController::class, 'save'])
        ->name('approval-leave.save');

    // Route::get('approval-pass-slip/{level?}', [ObsApprovalController::class, 'index'])
    //     ->name('approval-obs.index');
    // Route::get('approval-pass-slip/{level?}/view', [ObsApprovalController::class, 'view'])
    //     ->name('approval-obs.view');
    // Route::get('approval-pass-slip/{level}/{id}', [ObsApprovalController::class, 'show'])
    //     ->name('approval-obs.show');
    // Route::post('approval-pass-slip/{level}/{id}/save', [ObsApprovalController::class, 'save'])
    //     ->name('approval-obs.save');

    Route::get('approval-overtime/{level?}', [AtroApprovalController::class, 'index'])
        ->name('approval-overtime.index');
    Route::get('approval-overtime/{level?}/view', [AtroApprovalController::class, 'view'])
        ->name('approval-overtime.view');
    Route::get('approval-overtime/{level}/{id}', [AtroApprovalController::class, 'show'])
        ->name('approval-overtime.show');
    Route::post('approval-overtime/{level}/{id}/save', [AtroApprovalController::class, 'save'])
        ->name('approval-overtime.save');

    Route::get('profile', [ProfileController::class, 'index'])->name('employee.profile');
    Route::post('profile', [ProfileController::class, 'update'])->name('employee.profile.update');

    Route::put('change-password', [ChangePasswordController::class, 'change']);

});