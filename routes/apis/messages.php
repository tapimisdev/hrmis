<?php

use App\Http\Controllers\Api\DirectMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('direct-messages')->group(function () {
    Route::get('{user}', [DirectMessageController::class, 'index']);
    Route::post('/', [DirectMessageController::class, 'store']);
    Route::patch('{message}', [DirectMessageController::class, 'update']);
    Route::delete('{message}', [DirectMessageController::class, 'destroy']);
    Route::patch('{message}/reaction', [DirectMessageController::class, 'react']);
    Route::patch('{message}/pin', [DirectMessageController::class, 'pin']);
    Route::post('{user}/seen', [DirectMessageController::class, 'seen']);
    Route::post('{user}/typing', [DirectMessageController::class, 'typing']);
});
