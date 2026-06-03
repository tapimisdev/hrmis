<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Timekeeping\TimelogController;
use App\Http\Controllers\Admin\Timekeeping\UploadTimeLogController;
use App\Http\Controllers\Admin\Timekeeping\DailyTimeRecordController;
use App\Http\Controllers\Admin\Timekeeping\TimelogCorrectionController;
use App\Http\Controllers\Admin\Timekeeping\TimelogVerificationController;
use App\Http\Controllers\Admin\WebTimeAccess\WebTimeAccessController;
use App\Http\Controllers\Admin\Timekeeping\TimelogStatisticsController;
use App\Http\Controllers\Admin\Timekeeping\MonitoringController;
use App\Http\Controllers\Admin\Timekeeping\BehavioralNoticeController;

Route::prefix('timekeeping')->group(function() {


    # TIMELOGS
    Route::get('statistics', [TimelogStatisticsController::class, 'index'])
        ->name('timelogs-statistics');
    Route::resource('timelogs', TimelogController::class)->only('index');
    Route::resource('timelog-verification', TimelogVerificationController::class)
        ->only('index')
        ->names('timekeeping.timelog-verification');
    Route::resource('upload-timelogs', UploadTimeLogController::class)->only('index')->names(['index' => 'import.timelogs.index']);

    Route::resource('timelogs-correction', TimelogCorrectionController::class)->only('index', 'edit');
    Route::post('timelogs-correction/{id}/approve', [TimelogCorrectionController::class, 'approve']);
    Route::post('timelogs-correction/{id}/reject', [TimelogCorrectionController::class, 'reject']);
    
    Route::resource('monitoring', MonitoringController::class)
        ->only('index')
        ->names('timekeeping.monitoring');

    # API TIMEKEEPING
    Route::get('daily-time-record/{employee_no}', [DailyTimeRecordController::class, 'index'])
        ->name('daily-time-record.index');
    Route::get('daily-time-record/{employee_no}/show', [DailyTimeRecordController::class, 'show'])
        ->name('daily-time-record.show');
    Route::get('daily-time-record/{employee_no}/employee_information', [DailyTimeRecordController::class, 'employee_information_with_summary']);
    Route::get('accomplishment-report', [DailyTimeRecordController::class, 'downloadDAR']);

    Route::resource('web-time-access', WebTimeAccessController::class)->only('index', 'show', 'store', 'destroy')->names('webtime');

    Route::get('behavioral-notices', [BehavioralNoticeController::class, 'index'])
        ->name('timekeeping.behavioral-notices.index');
    Route::get('behavioral-notices/data', [BehavioralNoticeController::class, 'data'])
        ->name('timekeeping.behavioral-notices.data');
    Route::get('behavioral-notices/employees', [BehavioralNoticeController::class, 'employees'])
        ->name('timekeeping.behavioral-notices.employees');

});
