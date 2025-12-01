<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Timekeeping\UploadTimeLogController;
use App\Http\Controllers\Api\Timekeeping\AddTimeApiController;
use App\Http\Controllers\Api\Timekeeping\AddOvertimeApiController;
use App\Http\Controllers\Api\Timekeeping\MarkAsAbsentApiController;
use App\Http\Controllers\Timekeeping\MarkAsAbsentController;
use App\Http\Controllers\Api\LeavesApiController;

Route::prefix('timekeeping')->group(function() {
    Route::get('leaves', [LeavesApiController::class, 'getLeaves']);
    Route::post('import-timelogs', [UploadTimeLogController::class, 'store']);
});

// timelogs adjustment feature
Route::get('fetch-timelogs', [AddTimeApiController::class, 'index']);
Route::post('add-time', [AddTimeApiController::class, 'store']);
Route::post('add-overtime', [AddOvertimeApiController::class, 'store']);
Route::get('get-overtime', [AddOvertimeApiController::class, 'show']);
Route::post('mark-as-absent', [MarkAsAbsentApiController::class, 'mark_as_absent']);