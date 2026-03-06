<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Timekeeping\UploadTimeLogController;
use App\Http\Controllers\Api\Timekeeping\AddTimeApiController;
use App\Http\Controllers\Api\Timekeeping\AddOvertimeApiController;
use App\Http\Controllers\Api\Timekeeping\MarkAsAbsentApiController;
use App\Http\Controllers\Api\Timekeeping\CancelLeaveApiController;
use App\Http\Controllers\Api\Timekeeping\CancelOffsetApiController;
use App\Http\Controllers\Api\Timekeeping\CancelSOApiController;
use App\Http\Controllers\Api\Timekeeping\CancelPassSlipController;
use App\Http\Controllers\Api\Timekeeping\CancelLTOController;
use App\Http\Controllers\Api\Timekeeping\MarkAsAbsentController;
use App\Http\Controllers\Api\Timekeeping\MarkAsSoApiController;
use App\Http\Controllers\Api\Timekeeping\MarkAsLTOApiController;
use App\Http\Controllers\Api\LeavesApiController;
use App\Http\Controllers\Employee\timelogs\CorrectionTimelogController;

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
Route::post('mark-as-so', [MarkAsSoApiController::class, 'mark_as_so']);
Route::post('mark-as-lto', [MarkAsLTOApiController::class, 'mark_as_lto']);
Route::post('cancel-leave', [CancelLeaveApiController::class, 'cancel']);
Route::post('cancel-offset', [CancelOffsetApiController::class, 'cancel']);
Route::post('cancel-special-order', [CancelSOApiController::class, 'cancel']);
Route::post('cancel-pass-slip', [CancelPassSlipController::class, 'cancel']);
Route::post('cancel-lto', [CancelLTOController::class, 'cancel']);

Route::get('view-correction', [CorrectionTimelogController::class, 'index']);
Route::get('request-correction', [CorrectionTimelogController::class, 'edit']);
Route::post('request-correction', [CorrectionTimelogController::class, 'store']);