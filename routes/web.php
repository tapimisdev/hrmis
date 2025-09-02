<?php

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
use App\Http\Controllers\Admin\Settings\DeductionController;
use App\Http\Controllers\Admin\Settings\EarningsController;
use App\Http\Controllers\Admin\Settings\EmploymentTypesController;
use App\Http\Controllers\Admin\Settings\HolidayController;
use App\Http\Controllers\Admin\Settings\LeaveController;
use App\Http\Controllers\Admin\Settings\OrganizationController;
use App\Http\Controllers\Admin\Settings\PositionController;
use App\Http\Controllers\Admin\Settings\RolesAndPermissionController;
use App\Http\Controllers\Admin\Settings\ShiftController;
use App\Http\Controllers\Admin\Settings\WeeklyScheduleController;
use App\Http\Controllers\Employee\AtroController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveApplicationController;
use App\Http\Controllers\Employee\ObsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Employee\timelogs\CheckInOutController;
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
    return redirect()->route('login');
});

Auth::routes(['register' => false]);


Route::prefix('admin')->middleware(['checkrole:admin'])->group(function () {
    
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('hris')->group(function() {

        # INDEX
        Route::get('employee', [IndexController::class, 'index'])
            ->name('hris.employee.index');
        Route::any('employee/remove/{employee_no}', [IndexController::class, 'remove'])
            ->name('hris.employee.remove');
         Route::any('employee/restore/{employee_no}', [IndexController::class, 'restore'])
            ->name('hris.employee.restore');

        # INFORMATION
        Route::get('employee/information/{employee_no?}', [InformationController::class, 'index'])
            ->name('hris.employee.information');
        Route::post('employee/information/{employee_no?}', [InformationController::class, 'save'])
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

        # EDUCATION
        Route::get('employee/education/{employee_no}', [EducationController::class, 'index'])
            ->name('hris.employee.education');
        Route::post('employee/education/{employee_no}', [EducationController::class, 'save'])
            ->name('hris.employee.education');

        # CIVIL SERVICE
        Route::get('employee/civil-service/{employee_no}', [CivilServiceController::class, 'index'])
            ->name('hris.employee.civil-service');
        Route::post('employee/civil-service/{employee_no}', [CivilServiceController::class, 'save'])
            ->name('hris.employee.civil-service');

        # WORK EXPERIENCE
        Route::get('employee/work-experience/{employee_no}', [WorkExperienceController::class, 'index'])
            ->name('hris.employee.work-experience');
        Route::post('employee/work-experience/{employee_no}', [WorkExperienceController::class, 'save'])
            ->name('hris.employee.work-experience');

        # VOLUNTARY WORKS
        Route::get('employee/voluntary-works/{employee_no}', [VoluntaryWorksController::class, 'index'])
            ->name('hris.employee.voluntary-works');
        Route::post('employee/voluntary-works/{employee_no}', [VoluntaryWorksController::class, 'save'])
            ->name('hris.employee.voluntary-works');

        # TRAININGS
        Route::get('employee/trainings/{employee_no}', [TrainingsController::class, 'index'])
            ->name('hris.employee.trainings');
        Route::post('employee/trainings/{employee_no}', [TrainingsController::class, 'save'])
            ->name('hris.employee.trainings');

        # SKILLS
        Route::get('employee/skills/{employee_no}se', [SkillsController::class, 'index'])
            ->name('hris.employee.skills');
        Route::post('employee/skills/{employee_no}', [SkillsController::class, 'save'])
            ->name('hris.employee.skills');

    });

    Route::prefix('settings')->group(function() {
        # ROLES AND PERMISSIONS
        Route::resource('role-and-permission', RolesAndPermissionController::class);
        
        # EMPLOYMENT TYPES
        Route::resource('employment-types', EmploymentTypesController::class)
            ->except('show');

        # ORGANIZATION 
        Route::resource('organization', OrganizationController::class)
            ->except('show');

        # POSITIONS
        Route::get('positions/{employment_type_id?}', [PositionController::class, 'index'])->name('positions.index');
        Route::post('positions/{employment_type_id?}', [PositionController::class, 'store'])->name('positions.store');
        Route::get('positions/{employment_type_id?}/create', [PositionController::class, 'create'])->name('positions.create');
        Route::get('positions/{employment_type_id?}/{id}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('positions/{employment_type_id?}/{id}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('positions/{employment_type_id?}/{id}', [PositionController::class, 'destroy'])->name('positions.destroy');

        # SHIFTS
        Route::resource('shift', ShiftController::class);
        
        # WEEKLY SCHEDULES
        Route::resource('weekly-schedules', WeeklyScheduleController::class);

        # HOLIDAYS
        Route::resource('holiday', HolidayController::class);

        # EARNINGS
        Route::resource('earnings', EarningsController::class);

        # DEDUCTIONS
        Route::resource('deductions', DeductionController::class);

        # LEAVES
        Route::resource('leaves', LeaveController::class)->names('settings.leaves');
    });
});

Route::prefix('employee')->middleware('checkrole:employee')->group(function () {

    # EMPLOYEE DASHBOARD
    Route::resource('dashboard', EmployeeDashboardController::class);

    # EMPLOYEE LEAVES, OVERTIME, AND OBS
    Route::resource('leaves', LeaveApplicationController::class)->except('edit', 'update');
    Route::resource('overtime', AtroController::class)->except('edit', 'update');
    Route::resource('official-business-slip', ObsController::class)->except('edit', 'update')->names('obs');

    #EMPLOYEE TIMELOGS
    Route::resource('check-in-out', CheckInOutController::class)->only('index', 'store', 'create')->names('checkinout');
    Route::get('check-in-out/today-logs', [CheckInOutController::class, 'todayLogs']);

});
