<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Services\CreditsController;
use App\Http\Controllers\Admin\Services\EventsController;
use App\Http\Controllers\Admin\Services\SuspensionController;
use App\Http\Controllers\Admin\Services\LeaveApplicationController as AdminLeaveApplicationController;
use App\Http\Controllers\Admin\Services\OffsetApplicationController as AdminOffsetApplicationController;
use App\Http\Controllers\Admin\Services\PassSlipController as AdminPassSlipController;
use App\Http\Controllers\Admin\Services\OvertimeController as AdminOvertimeController;
use App\Http\Controllers\Admin\Services\SpecialOrderController as AdminSpecialOrderController;
use App\Http\Controllers\Admin\Services\LtoController as AdminLtoController;

# SERVICES
Route::prefix('service')->group(function() {

    # CREDITS 
    route::get('credits', [CreditsController::class, 'index'])
        ->name('services.credits');

    # EVENTS AND ANNOUNCEMENTS
    route::resource('events', EventsController::class)->names('services.events');

    # SUSPENSIONS
    Route::resource('suspensions', SuspensionController::class)->names('services.suspensions');
    Route::delete('suspensions-dates/{id}', [SuspensionController::class, 'deleteOnlyDate']);

    # LEAVE APPLICATIONS
    route::get('leave/application', [AdminLeaveApplicationController::class, 'index'])->name('services.leaves.index');
    route::get('leave/application/{application}', [AdminLeaveApplicationController::class, 'show'])->name('services.leaves.show');
    route::post('leave/application/{application}/save', [AdminLeaveApplicationController::class, 'save'])->name('services.leaves.save');

    # OFFSET APPLICATIONS
    route::get('offset/application', [AdminOffsetApplicationController::class, 'index'])->name('services.offset.index');
    route::get('offset/application/{application}', [AdminOffsetApplicationController::class, 'show'])->name('services.offset.show');
    route::post('offset/application/{application}/save', [AdminOffsetApplicationController::class, 'save'])->name('services.offset.save');

    # PASS SLIP APPLICATIONS
    route::get('pass-slip/application', [AdminPassSlipController::class, 'index'])->name('services.pass_slip.index');
    route::get('pass-slip/application/{application}', [AdminPassSlipController::class, 'show'])->name('services.pass_slip.show');
    route::post('pass-slip/application/{application}/save', [AdminPassSlipController::class, 'save'])->name('services.pass_slip.save');

    # OVERTIME APPLICATION
    route::get('overtime/application', [AdminOvertimeController::class, 'index'])->name('services.overtime.index');
    route::get('overtime/application/{application}', [AdminOvertimeController::class, 'show'])->name('services.overtime.show');
    route::post('overtime/application/{application}/save', [AdminOvertimeController::class, 'save'])->name('services.overtime.save');

    # SPECIAL ORDER APPLICATION
    route::get('special-order/application', [AdminSpecialOrderController::class, 'index'])->name('services.special_order.index');
    route::get('special-order/application/{application}', [AdminSpecialOrderController::class, 'show'])->name('services.special_order.show');
    route::post('special-order/application/{application}/save', [AdminSpecialOrderController::class, 'save'])->name('services.special_order.save');

    # LOCAL TRAVEL ORDER APPLICATION
    route::get('local-travel-order/application', [AdminLtoController::class, 'index'])->name('services.lto.index');
    route::get('local-travel-order/application/{application}', [AdminLtoController::class, 'show'])->name('services.lto.show');
    route::post('local-travel-order/application/{application}/save', [AdminLtoController::class, 'save'])->name('services.lto.save');

});
