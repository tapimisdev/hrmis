<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\DirectMessageController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\Admin\PatchNotesController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ApplicantPortalController;

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

Route::get('/', [HomeController::class, 'index']);

Auth::routes([
    'register' => false,      // disable registration
    'reset' => true,          // allow forgot password (reset link request)
    'verify' => false,        // disable email verification
    'confirm' => false        // disable password confirmation
]);

Route::get('today-birthday', [BirthdayController::class, 'index']);

Route::get('/patch-notes', [PatchNotesController::class, 'index'])
    ->name('patch-notes');

Route::get('/direct-messages/{message}/attachment', [DirectMessageController::class, 'attachment'])
    ->middleware('auth')
    ->name('direct-messages.attachment');

Route::any('/iclock/cdata', [\App\Http\Controllers\ZktecoController::class, 'cdata'])
    ->middleware('biometric.ip');

Route::get('test', [TestController::class, 'index']);

Route::get('/careers', [ApplicantPortalController::class, 'jobs'])->name('careers.jobs');
Route::middleware('guest')->group(function () {
    Route::get('/careers/register', [ApplicantPortalController::class, 'register'])->name('applicant.register');
    Route::post('/careers/register', [ApplicantPortalController::class, 'storeRegistration'])->name('applicant.register.store');
});
Route::middleware('auth')->prefix('applicant')->name('applicant.')->group(function () {
    Route::get('/dashboard', [ApplicantPortalController::class, 'dashboard'])->name('dashboard');
    Route::post('/jobs/{job}/apply', [ApplicantPortalController::class, 'apply'])->name('apply');
    Route::post('/applications/{application}/signed-offer', [ApplicantPortalController::class, 'uploadSignedOffer'])->name('offer.sign');
    Route::post('/applications/{application}/requirements/{requirement}', [ApplicantPortalController::class, 'uploadRequirement'])->name('requirements.upload');
});
