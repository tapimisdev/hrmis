<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\BirthdayController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

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

    if (!Auth::check()) {
        return redirect('/login');
    }

    $user = Auth::user();
    $roles = $user->getRoleNames();

    if ($roles->contains(fn ($role) => str_starts_with($role, 'emp'))) {
        return redirect('employee/dashboard');
    }

    return redirect('admin/dashboard');

});

Auth::routes([
    'register' => false,      // disable registration
    'reset' => true,          // allow forgot password (reset link request)
    'verify' => false,        // disable email verification
    'confirm' => false        // disable password confirmation
]);

Route::get('today-birthday', [BirthdayController::class, 'index']);

Route::any('/iclock/cdata', [\App\Http\Controllers\ZktecoController::class, 'cdata'])
    ->middleware('biometric.ip');

Route::get('test', [TestController::class, 'index']);
