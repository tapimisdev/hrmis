<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Timekeeping\TimelogController;
use App\Http\Controllers\Admin\Timekeeping\UploadTimeLogController;
use App\Http\Controllers\Admin\Timekeeping\DailyTimeRecordController;

Route::prefix('timekeeping')->group(function() {
    # TIMELOGS
    Route::resource('timelogs', TimelogController::class)->only('index');
    Route::resource('upload-timelogs', UploadTimeLogController::class)->only('index')->names(['index' => 'import.timelogs.index']);
    
    # API TIMEKEEPING
    Route::get('daily-time-record/{employee_no}', [DailyTimeRecordController::class, 'index'])
        ->name('daily-time-record.index');
    Route::get('daily-time-record/{employee_no}/show', [DailyTimeRecordController::class, 'show'])
        ->name('daily-time-record.show');
    Route::get('daily-time-record/{employee_no}/employee_information', [DailyTimeRecordController::class, 'employee_information_with_summary']);
    
});