<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Hris\EmployeeController;
use App\Http\Controllers\Admin\Hris\ChildrenController;
use App\Http\Controllers\Admin\Hris\CivilServiceController;
use App\Http\Controllers\Admin\Hris\EducationController;
use App\Http\Controllers\Admin\Hris\FamilyController;
use App\Http\Controllers\Admin\Hris\IndexController;
use App\Http\Controllers\Admin\Hris\InformationController;
use App\Http\Controllers\Admin\Hris\ManualController;
use App\Http\Controllers\Admin\Hris\PersonalController;
use App\Http\Controllers\Admin\Hris\SkillsController;
use App\Http\Controllers\Admin\Hris\TrainingsController;
use App\Http\Controllers\Admin\Hris\VoluntaryWorksController;
use App\Http\Controllers\Admin\Hris\WorkExperienceController;
use App\Http\Controllers\Admin\Hris\AccountController;
use App\Http\Controllers\Admin\Hris\LeaveCreditController;
use App\Http\Controllers\Admin\Hris\EarningsController as HrisEarningsController;
use App\Http\Controllers\Admin\Hris\DeductionsController as HrisDeductionsController;
use App\Http\Controllers\Admin\Hris\ImportEmployeeController;

Route::prefix('hris')->group(function() {

    Route::resource('employee/import', ImportEmployeeController::class)->names('hris.import');

    # INDEX
    Route::get('employee', [IndexController::class, 'index'])
        ->name('hris.employee.index');
    Route::any('employee/remove/{employee_no}', [IndexController::class, 'remove'])
        ->name('hris.employee.remove');
    Route::any('employee/restore/{employee_no}', [IndexController::class, 'restore'])
        ->name('hris.employee.restore');
    
    # TRANSFER EMPLOYEE
    Route::get('employee/transfer', [EmployeeController::class, 'transfer'])
        ->name('hris.employee.transfer');
    Route::post('employee/transfer', [EmployeeController::class, 'updateTransfer'])
        ->name('hris.employee.transfer');

    # UPDATE SALARY
    Route::get('employee/update-salary', [EmployeeController::class, 'update_salary'])
        ->name('hris.employee.salary');
    Route::post('employee/update-salary', [EmployeeController::class, 'updateSalary'])
        ->name('hris.employee.salary');

    # INFORMATION
    Route::get('employee/information/{employee_no?}', [InformationController::class, 'index'])
        ->name('hris.employee.information');
    Route::post('employee/information/{employee_no?}', [InformationController::class, 'save'])
        ->name('hris.employee.information');
    Route::delete('employee/information/{employee_no?}', [InformationController::class, 'destroy'])
        ->name('hris.employee.information');

    # PERSONAL
    Route::get('employee/personal/{employee_no?}', [PersonalController::class, 'index'])
        ->name('hris.employee.personal');
    Route::post('employee/personal/{employee_no}', [PersonalController::class, 'save'])
        ->name('hris.employee.personal');

    # FAMILY
    Route::get('employee/family/{employee_no}', [FamilyController::class, 'index'])
        ->name('hris.employee.family');
    Route::post('employee/family/{employee_no}', [FamilyController::class, 'save'])
        ->name('hris.employee.family');

    # CHILDREN
    Route::get('employee/children/{employee_no}', [ChildrenController::class, 'index'])
        ->name('hris.employee.children');
    Route::post('employee/children/{employee_no}', [ChildrenController::class, 'save'])
        ->name('hris.employee.children');
    Route::delete('employee/children/{employee_no}', [ChildrenController::class, 'destroy'])
        ->name('hris.employee.children');

    # EDUCATION
    Route::get('employee/education/{employee_no}', [EducationController::class, 'index'])
        ->name('hris.employee.education');
    Route::post('employee/education/{employee_no}', [EducationController::class, 'save'])
        ->name('hris.employee.education');
    Route::delete('employee/education/{employee_no}', [EducationController::class, 'destroy'])
        ->name('hris.employee.education');

    # CIVIL SERVICE
    Route::get('employee/civil-service/{employee_no}', [CivilServiceController::class, 'index'])
        ->name('hris.employee.civil-service');
    Route::post('employee/civil-service/{employee_no}', [CivilServiceController::class, 'save'])
        ->name('hris.employee.civil-service');
    Route::delete('employee/civil-service/{employee_no}', [CivilServiceController::class, 'destroy'])
        ->name('hris.employee.civil-service');

    # WORK EXPERIENCE
    Route::get('employee/work-experience/{employee_no}', [WorkExperienceController::class, 'index'])
        ->name('hris.employee.work-experience');
    Route::post('employee/work-experience/{employee_no}', [WorkExperienceController::class, 'save'])
        ->name('hris.employee.work-experience');
    Route::delete('employee/work-experience/{employee_no}', [WorkExperienceController::class, 'destroy'])
        ->name('hris.employee.work-experience');

    # VOLUNTARY WORKS
    Route::get('employee/voluntary-works/{employee_no}', [VoluntaryWorksController::class, 'index'])
        ->name('hris.employee.voluntary-works');
    Route::post('employee/voluntary-works/{employee_no}', [VoluntaryWorksController::class, 'save'])
        ->name('hris.employee.voluntary-works');
    Route::delete('employee/voluntary-works/{employee_no}', [VoluntaryWorksController::class, 'destroy'])
        ->name('hris.employee.voluntary-works');

    # TRAININGS
    Route::get('employee/trainings/{employee_no}', [TrainingsController::class, 'index'])
        ->name('hris.employee.trainings');
    Route::post('employee/trainings/{employee_no}', [TrainingsController::class, 'save'])
        ->name('hris.employee.trainings');
    Route::delete('employee/trainings/{employee_no}', [TrainingsController::class, 'destroy'])
        ->name('hris.employee.trainings');

    # SKILLS
    Route::get('employee/skills/{employee_no}', [SkillsController::class, 'index'])
        ->name('hris.employee.skills');
    Route::post('employee/skills/{employee_no}', [SkillsController::class, 'save'])
        ->name('hris.employee.skills');
    Route::delete('employee/skills/{employee_no}', [SkillsController::class, 'destroy'])
        ->name('hris.employee.skills');

    # ACCOUNT
    Route::get('employee/account/{employee_no}', [AccountController::class, 'index'])
        ->name('hris.employee.account');
    Route::put('employee/account/{employee_no}', [AccountController::class, 'save'])
        ->name('hris.employee.account');

    # LEAVE CREDITS
    Route::get('employee/leave-credits/{employee_no}', [LeaveCreditController::class, 'leave_credits'])
        ->name('hris.employee.leave-credits');
    Route::put('employee/leave-credits/{employee_no}/{leave_id?}', [LeaveCreditController::class, 'save_credits'])
        ->name('hris.employee.leave-credits');
    Route::delete('employee/leave-credits/{employee_no}/{leave_id?}', [LeaveCreditController::class, 'delete_credits'])
        ->name('hris.employee.leave-credits');

    # LEAVE CARD
    Route::get('employee/leave-card/{employee_no}/{leave_id}', [LeaveCreditController::class, 'leave_card'])
        ->name('hris.employee.leave-card');
    Route::put('employee/leave-card/{employee_no}/{leave_id}', [LeaveCreditController::class, 'add_year'])
        ->name('hris.employee.leave-card.add_year');
    Route::put('employee/leave-card/{employee_no}/{leave_id}/{year}/remove', [LeaveCreditController::class, 'remove_year'])
        ->name('hris.employee.leave-card.remove_year');
    Route::put('employee/leave-card/{employee_no}/{leave_id}/save', [LeaveCreditController::class, 'save_changes'])
        ->name('hris.employee.leave-card.save');


    # EARNINGS
    // Route::get('employee/earnings/{employee_no}', [HrisEarningsController::class, 'index'])
    //     ->name('hris.employee.earnings');
    // Route::post('employee/earnings/{employee_no}', [HrisEarningsController::class, 'save'])
    //     ->name('hris.employee.earnings');

    // # DEDUCTIONS
    // Route::get('employee/deductions/{employee_no}', [HrisDeductionsController::class, 'index'])
    //     ->name('hris.employee.deductions');
    // Route::post('employee/deductions/{employee_no}', [HrisDeductionsController::class, 'save'])
    //     ->name('hris.employee.deductions');

});