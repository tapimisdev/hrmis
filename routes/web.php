<?php

use App\Http\Controllers\Admin\HrisController;
use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\RolesAndPermissionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix('admin')->group(function () {
    Route::prefix('hris')->group(function() {
        Route::resource('employee', HrisController::class)
            ->names('hris.employee');
    });

    Route::prefix('settings')->group(function() {
        Route::resource('role-and-permission', RolesAndPermissionController::class);
        Route::resource('employment-types', EmploymentTypesController::class);
    });

});