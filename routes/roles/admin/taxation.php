<?php

use App\Http\Controllers\Admin\Taxation\TaxationController;
use App\Http\Controllers\Admin\Taxation\TrainLawController;
use App\Http\Controllers\Admin\Taxation\TrainLawItemController;
use Illuminate\Support\Facades\Route;
Route::prefix('taxation')->group(function() {

    Route::resource('/', TaxationController::class)->names('taxation');
    Route::get('/status', [TaxationController::class, 'status']);

    Route::resource('train-law', TrainLawController::class)->names('taxation.train-law');
    Route::patch('train-law/{id}/inactive', [TrainLawController::class, 'setInactive'])->name('train-law.inactive');
    
    Route::get('train-law/{trainLawId}/items', [TrainLawItemController::class, 'index'])
        ->name('taxation.train-law-items.index');

    Route::post('train-law/{trainLawId}/items', [TrainLawItemController::class, 'store'])
        ->name('taxation.train-law-items.store');
    
});