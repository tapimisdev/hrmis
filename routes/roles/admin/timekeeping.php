<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Timekeeping\TimelogController;
use App\Http\Controllers\Admin\Timekeeping\UploadTimeLogController;
use App\Http\Controllers\Admin\Timekeeping\DailyTimeRecordController;
use App\Http\Controllers\Admin\Timekeeping\TimelogCorrectionController;
use App\Http\Controllers\Admin\WebTimeAccess\WebTimeAccessController;
use App\Http\Controllers\Admin\Timekeeping\TimelogStatisticsController;

Route::prefix('timekeeping')->group(function() {


    # TIMELOGS
    Route::get('statistics', [TimelogStatisticsController::class, 'index'])
        ->name('timelogs-statistics');
    Route::resource('timelogs', TimelogController::class)->only('index');
    Route::resource('upload-timelogs', UploadTimeLogController::class)->only('index')->names(['index' => 'import.timelogs.index']);

    Route::resource('timelogs-correction', TimelogCorrectionController::class)->only('index', 'edit');
    route::post('timelogs-correction/{id}/approve', [TimelogCorrectionController::class, 'approve']);
    route::post('timelogs-correction/{id}/reject', [TimelogCorrectionController::class, 'reject']);
    
    # API TIMEKEEPING
    Route::get('daily-time-record/{employee_no}', [DailyTimeRecordController::class, 'index'])
        ->name('daily-time-record.index');
    Route::get('daily-time-record/{employee_no}/show', [DailyTimeRecordController::class, 'show'])
        ->name('daily-time-record.show');
    Route::get('daily-time-record/{employee_no}/employee_information', [DailyTimeRecordController::class, 'employee_information_with_summary']);

    Route::resource('web-time-access', WebTimeAccessController::class)->only('index', 'show', 'store', 'destroy')->names('webtime');

});