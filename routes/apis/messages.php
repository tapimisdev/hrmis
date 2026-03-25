<?php

use App\Http\Controllers\Api\DirectMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('direct-messages')->group(function () {
    Route::get('{user}', [DirectMessageController::class, 'index']);
    Route::post('/', [DirectMessageController::class, 'store']);
    Route::post('{user}/seen', [DirectMessageController::class, 'seen']);
});
