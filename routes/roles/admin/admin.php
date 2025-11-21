<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;


Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    require __DIR__ . '/hris.php';
    require __DIR__ . '/timekeeping.php';
    require __DIR__ . '/service.php';
    require __DIR__ . '/payroll.php';
    require __DIR__ . '/reports.php';
    require __DIR__ . '/maintenance.php';
    require __DIR__ . '/deductions.php';
    require __DIR__ . '/module.php';

});