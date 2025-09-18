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

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });

        return view('admin.pages.hris.salary', compact(
            'divisions', 'division_id', 'unit_id', 'employees', 'tranches', 'selectedEmployee'
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
                'step_id' => ['required', 'between:1,8']
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

            $now = now();

            $stepColumn = 'step_' . $request->step_id;

            $salary = DB::table('tranche_items')
                ->where('tranche_id', $request->tranche_id)
                ->select($stepColumn . ' as salary') 
                ->value('salary') ?? 0;

            foreach ($request->employees as $employee_no) {
                DB::table('employee_salary')->insert([
                    'employee_no'        => $employee_no,
                    'tranche_id'         => $request->tranche_id,
                    'step'               => $request->step_id,
                    'amount'             => $salary,
                    'effectivity_date'   => $now,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employees ' . implode(', ', $request->employees) . ' salary was updated successfully.',
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

}