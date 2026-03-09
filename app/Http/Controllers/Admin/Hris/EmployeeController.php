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
        $this->middleware('permission:hr.hris.transfer_unit')->only(['transfer', 'updateTransfer']);
        $this->middleware('permission:hr.hris.update_salary')->only(['update_salary', 'updateSalary']);
    }

    public function transfer(Request $request) {

        $selectedEmployee = $request->employee_no;

        $shifts = DB::table('shifts')->get();
        $schedules = DB::table('work_schedule')->get();
        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;

        $employees = $this->employeeService->getEmployees(null, null, null, null);

        $employees = collect($employees)
            ->groupBy('division_name')
            ->map(function ($divisionGroup) {
                return $divisionGroup->groupBy('unit_name');
            });
        
        $employment_types = DB::table('employment_types')->get();

        return view('admin.pages.hris.transfer', compact(
            'divisions', 'division_id', 'unit_id', 'employees', 'employment_types', 'selectedEmployee',
            'shifts', 'schedules'
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
                'employment_type_id' => 'required|exists:employment_types,id',
                'position_id' => 'required_if:type,,2|nullable|exists:positions,id|required_without:type',
                'shift_id' => 'required|exists:shifts,id',
                'schedule_id' => 'required|exists:work_schedule,id',
                'employment_effectivity_date' => [
                    'required',
                    'date'
                ],
                'tranche_id' => 'required|exists:tranche,id',
                'step_id' => 'required|between:1,8',
                'salary_grade' => 'required|numeric',
                'salary_frequency' => 'required|in:once,twice',
                'deduction_applied' => 'required|in:first_cutoff,second_cutoff,both',
                'salary_method' => 'required|in:cash,bank transfer,paycheck,e-wallet',
                'salary_cutoff' => 'required_if:salary_frequency,once|nullable|in:first_cutoff,second_cutoff',
                'salary_effectivity_date' => [
                    'required',
                    'date'
                ],
            ];
        }

        if ($type == 'salary') {
            return [
                'employees'   => ['required', 'array', 'min:1'], 
                'employees.*' => ['required', 'string', 'exists:employee_information,employee_no'],
                'tranche_id' => ['required', 'exists:tranche,id'],
                'step_id' => ['required', 'between:1,8'],
                'effectivity_date' => ['required|date_format:Y-m'],
            ];
        }

        return [];
    }

    public function updateTransfer(Request $request) {
        
        $payload = $request->all();

        $request->validate($this->rules('transfer', $payload));

        DB::beginTransaction();

        try {

            $salary = $this->getSalary(
                $request->tranche_id,
                $request->step_id,
                $request->salary_grade
            );

            $salary_cutoff = $request->salary_frequency === 'twice'
                    ? 'both'
                    : $request->salary_cutoff;

            foreach ($request->employees as $employee_no) {

                $employment_effectivity_date = $request->employment_effectivity_date . '-01';
                $salary_effectivity_date = $request->salary_effectivity_date . '-01';

                DB::table('employee_organization')->insert(
                    [
                        'employee_no'      => $employee_no,
                        'effectivity_date' => $employment_effectivity_date,
                        'division_id'        => $request->division_id,
                        'unit_id'            => $request->unit_id,
                        'employment_type_id' => $request->employment_type_id,
                        'position_id'        => $request->position_id,
                        'updated_at'         => now(),
                        'created_at'         => now(), 
                    ]
                );

                DB::table('employee_salary')->insert(
                    [
                        'employee_no'      => $employee_no,
                        'effectivity_date' => $salary_effectivity_date,
                        'tranche_id'        => $request->tranche_id,
                        'salary_grade'      => $request->salary_grade,
                        'step'              => $request->step_id,
                        'salary_frequency'  => $request->salary_frequency,
                        'salary_cutoff'     => $salary_cutoff,
                        'deduction_applied' => $request->deduction_applied,
                        'salary_basis'      => $request->salary_basis ?? null,
                        'salary_method'     => $request->salary_method,
                        'amount'            => $salary->amount,
                        'daily_rate'        => $salary->daily_rate,
                        'updated_at'        => now(),
                        'created_at'        => now(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employees ' . implode(', ', $request->employees) . ' was transferred successfully.',
                'redirect' => route('hris.employee.transfer')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
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

}