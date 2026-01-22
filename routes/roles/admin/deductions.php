<?php

use App\Http\Controllers\Admin\Modules\ModulesController;
use App\Http\Controllers\Admin\Modules\ModuleTabEmployeeController;
use Illuminate\Support\Facades\Route;

# Earnings
Route::prefix('modules')->group(function() {

    Route::post('/store-employees', [ModuleTabEmployeeController::class, 'store'])
            ->name('module.employee.store');
    Route::post('/bulk/store-employees', [ModuleTabEmployeeController::class, 'bulkStore'])
            ->name('module.employee.bulk.store');
   Route::post('/bulk/philhealth/store-employees', [ModuleTabEmployeeController::class, 'PhilhealthBulkStore'])
            ->name('module.employee.ph.bulk.store');

    # slug
    Route::get('/{slug}', [ModulesController::class, 'index'])->name('modules.index');
    Route::post('/{slug}', [ModulesController::class, 'store'])->name('modules.store');
    
    Route::get('/employees/{slug}/{tab}/{year}', [ModuleTabEmployeeController::class, 'index'])->name('modules.employees.employee');
});