<?php

namespace App\Services\PeraRata;

use App\Enums\EmploymentTypesEnum;
use App\Models\User;
use App\Notifications\PayrollBatchCompleted;
use App\Jobs\Admin\Payroll\PeraRataReport;
use App\Services\SalaryEmloyeeService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Throwable;

class PayrollService {

    protected $salaryEmployeeService;

    public $monthYear;

    private $eligible;
    private $not_eligible;

    public function __construct(SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_pera_rata as ps')
            ->leftJoin('employment_types as et', 'ps.employment_type_id', '=', 'et.id')
            ->select('ps.*', 'et.name as employment_name', 'et.code as employment_code');

        if (!empty($payload['employment_type'])) {
            $query->where('ps.employment_type_id', $payload['employment_type']);
        }

        if (!empty($payload['year'])) {
            $query->where('ps.month', 'LIKE', $payload['year'] . '%');
        }

        if (!empty($payload['month'])) {
            $query->where('ps.month', 'LIKE', $payload['month'] . '%');
        }

        if (!empty($payload['status'])) {
            $query->where('ps.status', $payload['status']);
        }

        return $query->get();
    }


    public function getEligibleEmployees($payload)
    {

        $this->monthYear = $payload['month'];

        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('positions', 'eo.position_id', '=', 'positions.id')
            ->leftJoin('divisions', 'eo.division_id', '=', 'divisions.id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('eo.employment_type_id', $payload['employment_type_id'])
            ->select(
                'ep.firstname', 
                'ep.middlename', 
                'ep.lastname', 
                'ep.suffix', 

                'positions.name as position',
                'divisions.name as division',

                'eo.employment_type_id',

                'ei.user_id', 
                'ei.employee_no', 
                'ei.account_status')
            ->get();
        
        
        if ($employees->isEmpty()) {
            throw new \Exception('No employees found for this employment type.', 409);
        }

        foreach ($employees as $emp) {
            $this->checkEligibility($emp);
        }

        $seperatedEmployee = [
            'eligible' => $this->eligible ?? [],
            'not_eligible' => $this->not_eligible ?? [],
        ];

        return $seperatedEmployee;
    }

    private function checkEligibility($employee)
    {
        $remarks = [];
        $eligibleRemarks = [];

        if ($employee->account_status !== 'active') {
            $remarks[] = [
                'text' => 'This Employee is Inactive',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (!$this->hasWorkAndShift($employee->employee_no)) {
            $remarks[] = [
                'text' => 'This Employee has no work or shift schedule during this payroll date',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (!$this->hasInformation($employee->employee_no)) {
            $remarks[] = [
                'text' => 'Employee record is incomplete. Please verify account, personal, organizational, and position details.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (!$this->hasSalary($employee->employee_no)) {
            $remarks[] = [
                'text' => 'No valid salary record found for this employee as of the payroll date. Please update their salary details.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (!$this->hasProject($employee->employee_no) 
            && $employee->employment_type_id == EmploymentTypesEnum::COS->value) {
            
            $eligibleRemarks[] = [
                'text' => 'COS employee has no assigned project during the payroll date. Please update.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        $employee->remarks = $remarks ?: $eligibleRemarks;

        if (empty($remarks)) {
            $employee->selected = true;
            $this->eligible[] = $employee;
        } else {
            $this->not_eligible[] = $employee;
        }
    }

    private function hasWorkAndShift($emp_no)
    {
        [$year, $month] = explode('-', $this->monthYear);
        $startDate = "$year-$month-01";
        $endDate   = date("Y-m-t", strtotime($startDate));

        $schedule = $this->salaryEmployeeService
                    ->activeShift($emp_no, $endDate)
                    ->leftJoin('shifts as s', 'sw1.shift_id', '=', 's.id')
                    ->select(
                        'sw1.id'
                    )
                    ->first();

        return $schedule ? true : false;
    }

    private function hasInformation($emp_no)
    {
        $info = DB::table('employee_organization')
            ->leftJoin('employee_information', 'employee_organization.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->where('employee_organization.employee_no', $emp_no)
            ->where('account_status', 'active')
            ->select('employee_information.id as employee_information_id', 'employee_personal.id as employee_personal_id', 'positions.id as positions_id', 'users.id as users_id')
            ->first();

        Log::info('------------------ INFOR -------------------------');
        Log::info('INFO DATA:', (array) $info);

        // Make sure all critical relationships exist
        return $info && $info->employee_information_id && $info->employee_personal_id && $info->positions_id && $info->users_id;
    }

    private function hasSalary($emp_no)
    {
        [$year, $month] = explode('-', $this->monthYear);
        $startDate = "$year-$month-01";
        $endDate   = date("Y-m-t", strtotime($startDate));

        $employee_salary = $this->salaryEmployeeService
            ->activeSalary($emp_no, $endDate)
            ->first();

        return !is_null($employee_salary);
    }

    private function hasProject($emp_no)
    {
        [$year, $month] = explode('-', $this->monthYear);
        $startDate = "$year-$month-01";
        $endDate   = date("Y-m-t", strtotime($startDate));

        $projects_employee = DB::table('employee_projects')
            ->where('employee_no', $emp_no)
            ->whereDate('start_date', '<=', $endDate)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('end_date', '>=', $startDate)
                    ->orWhereNull('end_date');
            })
            ->orderByDesc('start_date')
            ->first();

        return !is_null($projects_employee);
    }

    public function createPayroll($payload)
    {
        $payroll_no = generateNo('PR-', 4);

        // Insert payroll and get its ID
        $payroll_id = DB::table('payroll_pera_rata')->insertGetId([
            'label' => $payload['label'],
            'payroll_no' => $payroll_no,
            'month' => $payload['month'],
            'no_employee' => 0,
            'employment_type_id' => $payload['employment_type_id'],
            'total' => 0,
            'processed_by_id' => auth('sanctum')->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert approvers for this payroll
        collect($payload['approved_by'])
            ->flatMap(function ($approvers, $level) use ($payroll_id) {
                return collect($approvers)->map(function ($user_id) use ($payroll_id, $level) {
                    return [
                        'payroll_pera_rata_id' => $payroll_id,
                        'user_id' => $user_id,
                        'level' => (int) $level,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
            })
            ->pipe(function ($records) {
                DB::table('payroll_pera_rata_approvers')->insert($records->toArray());
            });

        // Return inserted payroll ID or record
        return [
            'payroll_no' => $payroll_no,
            'payroll_id' => $payroll_id,
        ];
    }

    public function createReport($payload, $payroll_id)
    {
        $eligibleEmployees = collect($payload['employees']['eligible'] ?? [])
            ->where('selected', true)
            ->values();

        if ($eligibleEmployees->isEmpty()) {
            Log::warning("No eligible employees found for payroll ID: {$payroll_id}");
            return null;
        }
        $batch = Bus::batch([])
            ->then(function (Batch $batch) {
                // $admin = \App\Models\User::role('admin')->first();
                // if ($admin) {
                //     $admin->notify(new \App\Notifications\PayrollBatchCompleted($batch, 'success'));
                // } else {
                //     Log::warning('Admin not found while notifying payroll batch success.');
                // }
            })
            ->catch(function (Batch $batch, \Throwable $e) {
                // $admin = \App\Models\User::role('admin')->first();
                // if ($admin) {
                //     $admin->notify(new \App\Notifications\PayrollBatchCompleted($batch, 'failed', $e));
                // } else {
                //     Log::error('Admin not found while notifying payroll batch failure.');
                // }
                // Log::error("Payroll batch failed: {$e->getMessage()}");
            })
            ->name("Subsistence and Allowance Payroll Report #{$payroll_id}")
            ->dispatch();

        DB::table('payroll_pera_rata')
            ->where('id', $payroll_id)
            ->update(['batch_id' => $batch->id]);

        foreach ($eligibleEmployees as $employee) {
            $batch->add(new PeraRataReport($employee, $payroll_id));
        }
        
        return $batch->id;
    }

}
