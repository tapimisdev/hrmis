<?php

use App\Http\Controllers\Admin\DashboardController;
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
use App\Http\Controllers\Admin\Services\EventsController;
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
use App\Http\Controllers\Admin\Settings\TrancheController;
use App\Http\Controllers\Admin\Settings\ApproverController;
use App\Http\Controllers\Admin\Timekeeping\DailyTimeRecordController;
use App\Http\Controllers\Admin\Timekeeping\SalaryPayrollController;
use App\Http\Controllers\Admin\Timekeeping\TimelogController;
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
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::prefix('hris')->group(function() {

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

    });


    # SERVICES
    Route::prefix('service')->group(function() {

        # EVENTS AND ANNOUNCEMENTS
        route::resource('events', EventsController::class)->names('services.events');

    });

    Route::prefix('timekeeping')->group(function() {
        # TIMELOGS
        Route::resource('timelogs', TimelogController::class)->only('index');
        
        # API TIMEKEEPING
        Route::get('daily-time-record/{id}', [DailyTimeRecordController::class, 'index'])
            ->name('daily-time-record.index');
        Route::get('daily-time-record/{id}/show', [DailyTimeRecordController::class, 'show'])
            ->name('daily-time-record.show');
        Route::get('daily-time-record/{id}/employee_information', [DailyTimeRecordController::class, 'employee_information_with_summary']);
        
    });

    Route::prefix('payroll')->group(function() {
        # SALARY PAYROLL
        Route::resource('salary', SalaryPayrollController::class);
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

        # TRANCHES
        Route::get('tranche', [TrancheController::class, 'index'])->name('settings.tranche.index');
        Route::get('tranche/{id}/show', [TrancheController::class, 'show'])->name('settings.tranche.show');
        Route::get('tranche/create', [TrancheController::class, 'create'])->name('settings.tranche.create');
        Route::post('tranche/create', [TrancheController::class, 'store'])->name('settings.tranche.store');
        Route::get('tranche/{id}/edit', [TrancheController::class, 'edit'])->name('settings.tranche.edit');
        Route::put('tranche/{id}/edit', [TrancheController::class, 'update'])->name('settings.tranche.update');
        Route::any('tranche/{id}/destroy', [TrancheController::class, 'destroy'])->name('settings.tranche.destroy');

        # APPROVERS
        Route::resource('approvers', ApproverController::class)->names('settings.approvers');

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
    Route::resource('check-in-out', CheckInOutController::class)->only('index', 'store')->names('checkinout');
    Route::get('check-in-out/today-logs', [CheckInOutController::class, 'todayLogs']);

});
