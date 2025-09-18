<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class InformationController extends Controller
{
 
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;    
    }

    public function index(Request $request, ?string $employee_no = null)
    {
        $divisions = DB::table('divisions')->get();
        $employment_types = DB::table('employment_types')->get();
        $shifts = DB::table('shifts')->get();
        $schedules = DB::table('work_schedule')->get();
        $tranches = DB::table('tranche')->get();

        if ($request->ajax()) {
            return $this->ajax_request($request);
        }

        $isExists = $employee_no ? $this->employeeService->checkIfEmployeeExists($employee_no) : false;

        if ($employee_no && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $data = $employee_no && $isExists ? $this->employeeService->getEmployee('information', $employee_no) : [];
        
        return view('admin.pages.hris.information', compact('divisions', 'employment_types', 'shifts', 'schedules', 'tranches', 'isExists', 'employee_no', 'data'));
    }

    private function ajax_request($request) {

        $division_id = $request->division_id;
        $employment_type_id = $request->employment_type_id;
        $position_id = $request->position_id;
        $tranche_id = $request->tranche_id;
        $step_id = $request->step_id;

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
            $data = DB::table('positions')
                ->where('employment_type_id', $employment_type_id)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data
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

        if ($tranche_id && $step_id) {
            $stepColumn = 'step_' . $step_id;

            $data = DB::table('tranche_items')
                ->where('tranche_id', $tranche_id)
                ->select($stepColumn . ' as salary') 
                ->first();

            return response()->json([
                'status' => $data ? 'success' : 'error',
                'data'   => $data ?? ['salary' => null],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'no data found'
        ]);
    }

    public function save(Request $request, ? string $employee_no = null)
    {
     
        $request->validate($this->rules($employee_no), $this->messages());          

        DB::beginTransaction();

        try {

            $exists = DB::table('employee_information')
                ->where('employee_no', $request->employee_no)
                ->exists();
            
            $now = Carbon::now()->toDateString();

            DB::table('employee_information')->updateOrInsert(
                ['employee_no' => $request->employee_no],
                [
                    'biometrics_id'   => $request->biometrics_id ?? null,
                    'date_hired'      => $request->date_hired ?? null,
                    'date_resigned'   => $request->date_resigned ?? null,
                    'account_status'  => $request->status ?? null,
                    'salary_method'   => $request->salary_method ?? null,
                    'payroll_account_no' => $request->payroll_account_number ?? null,
                    'updated_at'      => now(),
                ]
            );

            $latestOrg = DB::table('employee_organization')
                ->where('employee_no', $request->employee_no)
                ->latest('effectivity_date')
                ->first();

            if (
                !$latestOrg ||
                $latestOrg->division_id != $request->division_id ||
                $latestOrg->unit_id != $request->unit_id ||
                $latestOrg->employment_type_id != $request->employment_type_id ||
                $latestOrg->position_id != $request->position_id
            ) {
                DB::table('employee_organization')->insert([
                    'employee_no'        => $request->employee_no,
                    'division_id'        => $request->division_id,
                    'unit_id'            => $request->unit_id,
                    'employment_type_id' => $request->employment_type_id,
                    'position_id'        => $request->position_id,
                    'effectivity_date'   => $now,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            $latestSalary = DB::table('employee_salary')
                ->where('employee_no', $request->employee_no)
                ->latest('effectivity_date')
                ->first();

            if (!$latestSalary || $latestSalary->amount != $request->salary) {
                DB::table('employee_salary')->insert([
                    'employee_no'      => $request->employee_no,
                    'tranche_id'       => $request->tranche_id,
                    'step'             => $request->step_id,
                    'amount'           => $request->salary,
                    'effectivity_date' => $now,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            $latestShift = DB::table('employee_shift_work_schedule')
                ->where('employee_no', $request->employee_no)
                ->latest('effectivity_date')
                ->first();

            if (
                !$latestShift ||
                $latestShift->shift_id != $request->shift_id ||
                $latestShift->work_schedule_id != $request->schedule_id
            ) {
                DB::table('employee_shift_work_schedule')->insert([
                    'employee_no'      => $request->employee_no,
                    'shift_id'         => $request->shift_id,
                    'work_schedule_id' => $request->schedule_id,
                    'effectivity_date' => $now,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            $redirect = !$exists ? route('hris.employee.personal', ['employee_no' => $request->employee_no])
                : '';


            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee  #' . $request->employee_no . ' created successfully.',
                'redirect' => $redirect
            ]);
            
        } catch(\Exception $e) {
             DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
        

    }

    public function rules(?string $employee_no = null)
    {
        return [
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
            'status' => 'required|in:active,inactive',
            'date_hired' => 'required|date',
            'division_id' => 'required|exists:divisions,id',
            'unit_id' => 'required|exists:units,id',
            'employment_type_id' => 'required|exists:employment_types,id',
            'position_id' => 'required_if:type,,2|nullable|exists:positions,id|required_without:type',
            'salary_method' => 'required|in:cash,bank transfer,paycheck,e-wallet',
        ];
    }

    public function messages() {
        return [
            'employee_no.required' => 'The employee no is required.',
            'employee_no.unique' => 'The employee no is already taken.',
            'biometrics_id.required' => 'The biometrics ID is required.',
            'biometrics_id.unique' => 'The biometrics ID is already taken.',
            'status.required' => 'The account status is required.',
            'status.in' => 'The status must be either active or inactive.',
            'date_hired.required' => 'The date hired is required',
            'date_hired.date' => 'The date hired must be valid date',
            'division_id.required' => 'The division is required.',
            'division_id.exists' => 'The selected division does not exist.',
            'unit_id.required' => 'The unit is required.',
            'unit_id.exists' => 'The selected unit does not exist.',
            'position_id.required_if' => 'The position field is required when employee type is not job order.',
            'position_id.exists' => 'The selected position is invalid.',
            'position_id.required_without' => 'The position is required unless an employee type is provided.',
            'salary_type.required' => 'The salary type is required',
            'salary_type.in' => 'The salary type must be monthly or daily',
            'salary.required' => 'The salary rate is required',
            'salary.numeric' => 'The salary rate must be numbers',
            'salary.gt' => 'The salary rate must be greater than 1000',
            'salary_method.required' => 'The salary method is required.',
            'salary_method.in' => 'The salary method must be one of the following: cash, bank transfer, paycheck, or e-wallet.',
            'employment_type_id.required' => 'The employment type is required',
            'employment_type_id.exists' => 'The selected employment type does not exist.',
            'employment_type_id.in' => 'The selected employment type does not exist.',
        ];
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
