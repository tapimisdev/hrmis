<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{

    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);
    }

    public function transfer(Request $request) {

        $selectedEmployee = $request->employee_no;

        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;

        $employees = $this->employeeService->getEmployees(null, null, null);

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });
        
        $employment_types = DB::table('employment_types')->get();

        return view('admin.pages.hris.transfer', compact(
            'divisions', 'division_id', 'unit_id', 'employees', 'employment_types', 'selectedEmployee'
        ));
    }

    public function update_salary(Request $request) {

        $selectedEmployee = $request->employee_no;

        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;
        $tranches = DB::table('tranche')->get();

        $employees = $this->employeeService->getEmployees(null, null, null);
        $employment_types = DB::table('employment_types')->get();

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });

        if(!empty($selectedEmployee)) {
            $employment_type = DB::table('employee_organization as eo')
                ->where('eo.employee_no', $selectedEmployee)
                ->orderByDesc('eo.effectivity_date')
                ->orderByDesc('eo.id') 
                ->first();

            $employee_salary = DB::table('employee_salary as es')
                ->where('es.employee_no', $selectedEmployee)
                ->orderByDesc('es.effectivity_date')
                ->orderByDesc('es.id')
                ->first();

            $selectedEmployee = [
                'employee_no' => $selectedEmployee,
                'employment_type_id' => $employment_type ? $employment_type->employment_type_id : null,
                'tranche_id' => $employee_salary->tranche_id ?? null,
                'salary_grade' => $employee_salary->salary_grade ?? null,
                'step' => $employee_salary->step ?? null,
                'effectivity_date' => $employee_salary->effectivity_date ?? null,
            ];
        }

        return view('admin.pages.hris.salary', compact(
            'divisions', 'division_id', 'unit_id', 'employees', 'employment_types', 'tranches', 'selectedEmployee'
        ));
    }

    public function rules(string $type, array $payload) 
    {
        if ($type == 'transfer') {
            return [
                'employees'   => ['required', 'array', 'min:1'], 
                'employees.*' => ['required', 'string', 'exists:employee_information,employee_no'],
                'division_id' => ['required', 'string', 'exists:divisions,id'],

                'unit_id' => [
                    'required',
                    'string',
                    Rule::exists('units', 'id')->where(function ($query) use ($payload) {
                        $query->where('division_id', $payload['division_id'] ?? null);
                    }),
                ],

                'employment_type_id' => ['required', 'string', 'exists:employment_types,id'],

                'position_id' => [
                    'required',
                    'string',
                    Rule::exists('positions', 'id')->where(function ($query) use ($payload) {
                        $query->where('employment_type_id', $payload['employment_type_id'] ?? null);
                    }),
                ],
            ];
        }

        if ($type == 'salary') {
            return [
                'employees'   => ['required', 'array', 'min:1'], 
                'employees.*' => ['required', 'string', 'exists:employee_information,employee_no'],
                'tranche_id' => ['required', 'exists:tranche,id'],
                'step_id' => ['required', 'between:1,8'],
                'effectivity_date' => ['required', 'date'],
            ];
        }

        return [];
    }

    public function updateTransfer(Request $request) {
        
        $payload = $request->all();

        $request->validate($this->rules('transfer', $payload));

        DB::beginTransaction();

        try {

            $now = now();

           foreach ($request->employees as $employee_no) {
                DB::table('employee_organization')->insert([
                    'employee_no'        => $employee_no,
                    'division_id'        => $request->division_id,
                    'unit_id'            => $request->unit_id,
                    'employment_type_id' => $request->employment_type_id,
                    'position_id'        => $request->position_id,
                    'effectivity_date'   => $now,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employees ' . implode(', ', $request->employees) . ' was transferred successfully.',
                'redirect' => '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function updateSalary(Request $request) {
        
        $payload = $request->all();

        $request->validate($this->rules('salary', $payload));
    
        DB::beginTransaction();

        try {

            $now = Carbon::parse($request->effectivity_date)->format('Y-m-d');

            $stepColumn = 'step_' . $request->step_id;
            $tranche_id = $request->tranche_id;
            $salary_grade = $request->salary_grade;
            
            $data = DB::table('tranche_items')
                ->where('tranche_id', $tranche_id)
                ->where('salary_grade', $salary_grade)
                ->select($stepColumn . ' as salary') 
                ->first();

            $salary = $data ? str_replace(',', '', $data->salary) : 0;
            $daily_rate = number_format($salary / 22, 2);

            foreach ($request->employees as $employee_no) {
                DB::table('employee_salary')->insert([
                    'employee_no'        => $employee_no,
                    'tranche_id'         => $request->tranche_id,
                    'salary_grade'        => $request->salary_grade,
                    'step'               => $request->step_id,
                    'amount'             => $salary,
                    'daily_rate'         => $daily_rate,
                    'effectivity_date'   => $now,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employees ' . implode(', ', $request->employees) . ' salary was updated successfully.',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
    }
}