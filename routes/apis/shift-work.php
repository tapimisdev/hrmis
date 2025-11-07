<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Admin\Settings\WeeklyScheduleController;


Route::get('shifts', [ShiftController::class, 'index']);
Route::get('work-schedules', [WeeklyScheduleController::class, 'index']);