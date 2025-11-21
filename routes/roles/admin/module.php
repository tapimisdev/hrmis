<?php

use App\Http\Controllers\Admin\Modules\ModulesController;
use Illuminate\Support\Facades\Route;

# Modules
Route::prefix('modules')->group(function() {
    # slug
    Route::get('/{slug}', [ModulesController::class, 'index'])->name('modules.index');
    Route::post('/{slug}', [ModulesController::class, 'store'])->name('modules.store');
});