<?php

use App\Http\Controllers\Admin\RecruitmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment')->name('recruitment.')->group(function () {
    Route::get('jobs', [RecruitmentController::class, 'jobs'])->name('jobs');
    Route::post('jobs', [RecruitmentController::class, 'storeJob'])->name('jobs.store');
    Route::put('jobs/{job}', [RecruitmentController::class, 'updateJob'])->name('jobs.update');
    Route::delete('jobs/{job}', [RecruitmentController::class, 'destroyJob'])->name('jobs.destroy');
    Route::get('applicants', [RecruitmentController::class, 'applicants'])->name('applicants');
    Route::get('hiring-process', [RecruitmentController::class, 'process'])->name('process');
    Route::get('interviews-exams', [RecruitmentController::class, 'assessments'])->name('assessments');
    Route::get('applications/{application}', [RecruitmentController::class, 'showApplication'])->name('applications.show');
    Route::patch('applications/{application}/stage', [RecruitmentController::class, 'updateStage'])->name('applications.stage');
    Route::post('applications/{application}/assessments', [RecruitmentController::class, 'storeAssessment'])->name('applications.assessments');
    Route::post('applications/{application}/offer', [RecruitmentController::class, 'prepareOffer'])->name('applications.offer');
    Route::post('applications/{application}/offer/send', [RecruitmentController::class, 'sendOffer'])->name('applications.offer.send');
    Route::patch('requirements/{requirement}', [RecruitmentController::class, 'verifyRequirement'])->name('requirements.verify');
    Route::post('applications/{application}/hire', [RecruitmentController::class, 'hire'])->name('applications.hire');
});
