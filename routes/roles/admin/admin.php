<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessagesController;
use App\Http\Controllers\Admin\IDMakerController;
use App\Http\Controllers\Admin\TrailsController;

Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/messages/{conversationToken?}', [MessagesController::class, 'index'])
        ->where('conversationToken', '.*')
        ->name('admin.messages');
    Route::get('id-maker', [IDMakerController::class, 'index']);
    Route::post('id-maker', [IDMakerController::class, 'save_configuration'])
        ->name('id-maker.save_configuration');

    require __DIR__ . '/hris.php';
    require __DIR__ . '/timekeeping.php';
    require __DIR__ . '/service.php';
    require __DIR__ . '/payroll.php';
    require __DIR__ . '/earnings.php';
    require __DIR__ . '/reports.php';
    require __DIR__ . '/maintenance.php';
    require __DIR__ . '/deductions.php';
    require __DIR__ . '/users.php';

    Route::middleware(['auth'])->group(function () {
        Route::get('trails', [TrailsController::class, 'index'])
            ->name('trails.index');

        Route::get('trails/{auditTrail}', [TrailsController::class, 'show'])
            ->name('trails.show');
    });

});
