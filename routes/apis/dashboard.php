<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardApiController;

 # PREFIX
Route::prefix('dashboard')->group(function() {
    Route::get('metrics', [DashboardApiController::class, 'metrics']);
});