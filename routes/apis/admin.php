<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Admin;

 # PREFIX
Route::prefix('admin')->group(function() {
    Route::get('metrics', [Admin::class, 'metrics']);
    Route::get('notifications', [Admin::class, 'getNotifications']);
    Route::post('notifications', [Admin::class, 'saveReadNotification']);
});

