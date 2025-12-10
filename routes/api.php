<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);

    require __DIR__ . '/apis/maintenance.php';
    require __DIR__ . '/apis/employee.php';
    require __DIR__ . '/apis/payroll.php';
    require __DIR__ . '/apis/user.php';
    require __DIR__ . '/apis/timekeeping.php';
    require __DIR__ . '/apis/dashboard.php';
    require __DIR__ . '/apis/hris.php';
    require __DIR__ . '/apis/shift-work.php';
    require __DIR__ . '/apis/reports.php';

});

Route::any('/iclock/cdata', [\App\Http\Controllers\ZktecoController::class, 'cdata'])
    ->middleware('biometric.ip');