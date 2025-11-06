<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Payroll\ReportsController;


# REPORTS
Route::get('reports/{employment_type}', [ReportsController::class, 'index'])
    ->name('reports.index');