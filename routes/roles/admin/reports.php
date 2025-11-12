<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\ReportsController;


# REPORTS
Route::get('reports', [ReportsController::class, 'index'])
    ->name('reports.index');