<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\UsersController;


Route::resource('users', UsersController::class)
    ->middleware(['auth'])
    ->names('users');
