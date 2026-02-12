<?php

use App\Http\Controllers\Admin\Taxation\TaxationController;
use App\Http\Controllers\Admin\Taxation\TrainLawController;
use Illuminate\Support\Facades\Route;
Route::prefix('taxation')->group(function() {

    Route::resource('/', TaxationController::class)->names('taxation');

    Route::resource('train-law', TrainLawController::class)->names('taxation.train-law');
    Route::patch('train-law/{id}/inactive', [TrainLawController::class, 'setInactive'])->name('train-law.inactive');
});