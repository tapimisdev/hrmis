<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IDMakerController;


Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
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

});