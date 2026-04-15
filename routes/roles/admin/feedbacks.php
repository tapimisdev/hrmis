<?php

use App\Http\Controllers\Admin\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::get('feedbacks', [FeedbackController::class, 'index'])
    ->middleware(['auth'])
    ->name('feedbacks.index');

Route::get('feedbacks/{feedback}', [FeedbackController::class, 'show'])
    ->middleware(['auth'])
    ->name('feedbacks.show');

Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('feedbacks.destroy');
