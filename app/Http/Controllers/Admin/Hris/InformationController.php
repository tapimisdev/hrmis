<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Enums\EmploymentTypesEnum;
use App\Events\RefreshData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class InformationController extends Controller
{
 
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;    
        $this->middleware('permission:hr.hris.view')->only('index');
        $this->middleware('permission:hr.hris.edit')->only('save');
        $this->middleware('permission:hr.hris.delete')->only('destroy');
    }

    public function index(Request $request, ?string $employee_no = null)
    {
        $divisions = DB::table('divisions')->get();
        $employment_types = DB::table('employment_types')->get();
        $shifts = DB::table('shifts')->get();
        $schedules = DB::table('work_schedule')->get();

        if ($request->ajax()) {
            return $this->ajax_request($request);
        }

        $isExists = $employee_no ? $this->employeeService->checkIfEmployeeExists($employee_no) : false;

        if ($employee_no && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $data = $employee_no && $isExists ? $this->employeeService->getEmployee('information', $employee_no) : [];
        return view('admin.pages.hris.information', compact('divisions', 'employment_types', 'shifts', 'schedules', 'isExists', 'employee_no', 'data'));
    }

    private function ajax_request($request) {

        $division_id = $request->division_id;
        $employment_type_id = $request->employment_type_id;
        $position_id = $request->position_id;
        $tranche_id = $request->tranche_id;
        $step_id = $request->step_id;
        $role_id = $request->role_id;
        $forSalaryGrade = $request->forSalaryGrade;
        $salary_grade = $request->salary_grade;

        if ($division_id) { 
            $data = DB::table('units')
                ->where('division_id', $division_id)
                ->get();
            return response()->json([
                'status' => 'success',
                'data' => $data ?? []
            ]);
        }

        if ($employment_type_id) {
            $positions = DB::table('positions')
                ->where('employment_type_id', $employment_type_id)
                ->get();

            $tranches = DB::table('tranche')
                ->where('employment_type_id', $employment_type_id)
                ->get();

            $employees = DB::table('employee_personal as ep')
                ->leftJoinSub(
                    DB::table('employee_organization')
                        ->select('employee_no', 'employment_type_id')
                        ->whereIn('id', function ($query) {
                            $query->select(DB::raw('MAX(id)'))
                                ->from('employee_organization as eo2')
                                ->whereColumn('eo2.employee_no', 'employee_organization.employee_no')
                                ->groupBy('eo2.employee_no');
                        }),
                    'eo',
                    'ep.employee_no',
                    '=',
                    'eo.employee_no'
                )
                ->where('eo.employment_type_id', $employment_type_id)
                ->select('ep.employee_no', 'ep.firstname', 'ep.lastname')
                ->get();

            return response()->json([
                'status' => 'success',
                'positions' => $positions,
                'tranches'  => $tranches,
                'employees' => $employees
            ]);
        }

        if ($position_id) {
            $data = DB::table('positions')
                ->where('id', $position_id)
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        }

        if ($role_id) {
            $role = DB::table('roles')
                ->where('name', $role_id)
                ->first();

            if ($role) {
                $users = DB::table('model_has_roles as mhr')
                    ->join('users as u', 'mhr.model_id', '=', 'u.id')
                    ->where('mhr.role_id', $role->id)
                    ->where('mhr.model_type', 'App\Models\User')
                    ->select('u.id', 'u.name', 'u.email')
                    ->get();

                $data = (array) $role;
                $data['users'] = $users;
            } else {
                $data = ['salary' => null, 'users' => []];
            }

            return response()->json([
                'status' => $role ? 'success' : 'error',
                'data'   => $data,
            ]);
        }

        if($forSalaryGrade) {

           $data = DB::table('tranche_items')
                ->where('tranche_id', $tranche_id)
                ->pluck('salary_grade');

            return response()->json([
                'status' => $data ? 'success' : 'error',
                'data'   => $data,
            ]);
        }

        if ($tranche_id && $step_id && $salary_grade) {
            $stepColumn = 'step_' . $step_id;

            $data = DB::table('tranche_items')
                ->where('tranche_id', $tranche_id)
                ->where('salary_grade', $salary_grade)
                ->select($stepColumn . ' as salary') 
                ->first();

            $salary = $data ? str_replace(',', '', $data->salary) : 0;

            $data->salary = $salary;

            return response()->json([
                'status' => $data ? 'success' : 'error',
                'data'   => $data ?? null,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'no data found'
        ]);
    }

    public function getSalary($tranch_id, $step_id, $salary_grade) {
        
        $stepColumn = 'step_' . $step_id;

        $data = DB::table('tranche_items')
            ->where('tranche_id', $tranch_id)
            ->where('salary_grade', $salary_grade)
            ->select($stepColumn . ' as salary') 
            ->first();

        $salary = $data ? str_replace(',', '', $data->salary) : 0;
        $daily_rate = $salary / 22;

        return (object) [
            'amount' => number_format($salary, 2),
            'daily_rate' => number_format($daily_rate, 2)
        ];
    }

    public function save(Request $request, ?string $employee_no = null)
{
    $isExists = $employee_no &&
        DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->exists();

    $request->validate($this->rules($employee_no, $isExists));

    DB::beginTransaction();

    try {

        $now = Carbon::now()->toDateString();
        $employmentTypeId = $request->employment_type_id;

        /**
         * -------------------------------------------------
         * CREATE OR GET USER
         * -------------------------------------------------
         */
        if (!$isExists) {
            $existingUser = DB::table('users')
                ->where('email', $request->email)
                ->first();

            if ($existingUser) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Email already exists for another user.'
                ], 422);
            }

            $user_id = DB::table('users')->insertGetId([
                'name'       => $request->firstname . ' ' . $request->lastname,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ✅ generate employee no ONLY for new
            $service = app(EmployeeService::class);
            $employeeNo = $service->generateEmployeeNo(
                $request->date_hired_company ?? now()
            );

        } else {
            // ✅ existing employee → reuse employee_no
            $employeeNo = $employee_no;

            $user_id = DB::table('employee_information')
                ->where('employee_no', $employeeNo)
                ->value('user_id');
        }

        $toChangePassword = (bool) $request->input('toUpdatePassword', 0);

        /**
         * -------------------------------------------------
         * EMPLOYEE INFORMATION
         * -------------------------------------------------
         */
        DB::table('employee_information')->updateOrInsert(
            ['employee_no' => $employeeNo],
            [
                'biometrics_id'           => $request->biometrics_id ?? null,
                'date_hired_company'      => $request->date_hired_company ?? null,
                'date_hired_organization' => $request->date_hired_organization ?? null,
                'date_resigned'           => $request->date_resigned ?? null,
                'account_status'          => $request->status ?? null,
                'salary_method'           => $request->salary_method ?? null,
                'payroll_account_no'      => $request->payroll_account_number ?? null,
                'user_id'                 => $user_id,
                'toUpdatePassword'        => $toChangePassword,
                'updated_at'              => now(),
                'created_at'              => $isExists ? DB::raw('created_at') : now(),
            ]
        );

        /**
         * -------------------------------------------------
         * PERSONAL INFORMATION (NEW ONLY)
         * -------------------------------------------------
         */
        if (!$isExists) {
            DB::table('employee_personal')->insert([
                'employee_no' => $employeeNo,
                'firstname'   => $request->firstname,
                'middlename'  => $request->middlename,
                'lastname'    => $request->lastname,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        /**
         * -------------------------------------------------
         * ORGANIZATION
         * -------------------------------------------------
         */
        $latestOrg = DB::table('employee_organization')
            ->where('employee_no', $employeeNo)
            ->latest('effectivity_date')
            ->first();

        if (
            !$latestOrg ||
            $latestOrg->division_id != $request->division_id ||
            $latestOrg->unit_id != $request->unit_id ||
            $latestOrg->employment_type_id != $employmentTypeId ||
            $latestOrg->position_id != $request->position_id
        ) {
            DB::table('employee_organization')->insert([
                'employee_no'        => $employeeNo,
                'division_id'        => $request->division_id,
                'unit_id'            => $request->unit_id,
                'employment_type_id' => $employmentTypeId,
                'position_id'        => $request->position_id,
                'effectivity_date'   => $now,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        /**
         * -------------------------------------------------
         * SALARY
         * -------------------------------------------------
         */
        $latestSalary = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->latest('effectivity_date')
            ->first();

        $salary = $this->getSalary(
            $request->tranche_id,
            $request->step_id,
            $request->salary_grade
        );

        if (!$latestSalary || $latestSalary->amount != $salary->amount) {

            $salary_cutoff = $request->salary_frequency === 'twice'
                ? 'both'
                : $request->salary_cutoff;

            DB::table('employee_salary')->insert([
                'employee_no'       => $employeeNo,
                'tranche_id'        => $request->tranche_id,
                'salary_grade'      => $request->salary_grade,
                'step'              => $request->step_id,
                'salary_frequency'  => $request->salary_frequency,
                'salary_cutoff'     => $salary_cutoff,
                'deduction_applied' => $request->deduction_applied,
                'salary_basis'      => $request->salary_basis ?? null,
                'amount'            => $salary->amount,
                'daily_rate'        => $salary->daily_rate,
                'effectivity_date'  => $now,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        /**
         * -------------------------------------------------
         * SHIFT / WORK SCHEDULE
         * -------------------------------------------------
         */
        $latestShift = DB::table('employee_shift_work_schedule')
            ->where('employee_no', $employeeNo)
            ->latest('effectivity_date')
            ->first();

        if (
            !$latestShift ||
            $latestShift->shift_id != $request->shift_id ||
            $latestShift->work_schedule_id != $request->schedule_id
        ) {
            DB::table('employee_shift_work_schedule')->insert([
                'employee_no'      => $employeeNo,
                'shift_id'         => $request->shift_id,
                'work_schedule_id' => $request->schedule_id,
                'effectivity_date' => $now,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        /**
         * -------------------------------------------------
         * ROLE ASSIGNMENT
         * -------------------------------------------------
         */
        $user = User::findOrFail($user_id);

        if ($employmentTypeId === EmploymentTypesEnum::COS->value) {
            $user->assignRole('emp_contractual');
        } elseif ($employmentTypeId === EmploymentTypesEnum::REGULAR->value) {
            $user->assignRole('emp_regular');
        }

        DB::commit();

        broadcast(new \App\Events\RefreshData());

        return response()->json([
            'status'   => 'success',
            'message'  => 'Employee #' . $employeeNo . ' saved successfully.',
            'redirect' => !$isExists
                ? route('hris.employee.personal', ['employee_no' => $employeeNo])
                : null
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status'  => 'error',
            'message' => 'Error Occurred: ' . $e->getMessage()
        ], 500);
    }
}




    public function rules(?string $employee_no = null, bool $isExists)
    {
        $rules = [
            'employee_no' => [
                'required',
                Rule::unique('employee_information', 'employee_no')
                    ->ignore($employee_no, 'employee_no')
            ],

            'biometrics_id' => [
                'nullable',
                Rule::unique('employee_information', 'biometrics_id')
                    ->ignore($employee_no, 'employee_no')
            ],

            'date_hired_company' => 'required|date',
            'date_hired_organization' => 'required|date',
            'status' => 'required|in:active,inactive',
            'division_id' => 'required|exists:divisions,id',
            'unit_id' => 'required|exists:units,id',
            'employment_type_id' => 'required|exists:employment_types,id',
            'position_id' => 'required_if:type,,2|nullable|exists:positions,id|required_without:type',
            'shift_id' => 'required|exists:shifts,id',
            'schedule_id' => 'required|exists:work_schedule,id',
            'tranche_id' => 'required|exists:tranche,id',
            'step_id' => 'required|between:1,8',
            'salary_grade' => 'required|numeric',
            'salary_frequency' => 'required|in:once,twice',
            'deduction_applied' => 'required|in:first_cutoff,second_cutoff,both',
            'salary_method' => 'required|in:cash,bank transfer,paycheck,e-wallet',
            'salary_cutoff' => 'required_if:salary_frequency,once|nullable|in:first_cutoff,second_cutoff',
            'payroll_account_number' => 'nullable|string|max:100',
        ];

        if (!$isExists) {
            $rules['firstname'] = 'required|string|max:255';
            $rules['lastname']  = 'required|string|max:255';
            $rules['middlename']  = 'nullable|string|max:255';
            $rules['email']  = 'required|unique:users|string|max:255';
            $rules['password'] = 'required|min:8|same:confirm_password';
            $rules['confirm_password'] = 'required|min:8';
        }

        return $rules;
    }

    
    public function destroy(string $employee_no, Request $request)
    {

        DB::beginTransaction();

        try {

            DB::table('employee_information')
                ->where('employee_no', $employee_no)
                ->where('id', $request->id)
                ->update([
                    'account_status' => 'archived'
                ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'skill or hobby record has been deleted.',
                'redirect' => ''
            ]);

        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }

}
