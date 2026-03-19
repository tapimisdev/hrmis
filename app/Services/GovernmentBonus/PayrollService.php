<?php

namespace App\Services\GovernmentBonus;

use App\Services\SalaryEmloyeeService;
use App\Jobs\Admin\Payroll\GovernmentBonusReport;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    protected $salaryEmployeeService;
    public $monthYear;
    private $eligible = [];
    private $not_eligible = [];
    private $bonusType;

    public function __construct(SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_government_bonus as pgb')
            ->leftJoin('employment_types as et', 'pgb.employment_type_id', '=', 'et.id')
            ->leftJoin('government_bonus_types as gbt', 'pgb.government_bonus_type_id', '=', 'gbt.id')
            ->select(
                'pgb.*',
                'et.name as employment_name',
                'et.code as employment_code',
                'gbt.name as bonus_type_name'
            );

        if (!empty($payload['employment_type'])) {
            $query->where('pgb.employment_type_id', $payload['employment_type']);
        }

        if (!empty($payload['government_bonus_type_id'])) {
            $query->where('pgb.government_bonus_type_id', $payload['government_bonus_type_id']);
        }

        if (!empty($payload['year'])) {
            $query->where('pgb.month', 'LIKE', $payload['year'] . '%');
        }

        if (!empty($payload['month'])) {
            $query->where('pgb.month', 'LIKE', $payload['month'] . '%');
        }

        if (!empty($payload['status'])) {
            $query->where('pgb.status', $payload['status']);
        }

        return $query->orderByDesc('pgb.created_at')->get();
    }

    public function getActiveBonusTypes()
    {
        return DB::table('government_bonus_types')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name', 'slug')
            ->get();
    }

    public function getEligibleEmployees($payload)
    {
        $this->monthYear = $payload['month'];
        $this->eligible = [];
        $this->not_eligible = [];
        $this->bonusType = DB::table('government_bonus_types')
            ->where('id', $payload['government_bonus_type_id'])
            ->first();

        if (!$this->bonusType) {
            throw new \Exception('Government bonus type not found.', 404);
        }

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
                'ei.account_status',
                'ei.date_hired_company',
                'ei.date_hired_organization'
            )
            ->get();

        if ($employees->isEmpty()) {
            throw new \Exception('No employees found for this employment type.', 409);
        }

        foreach ($employees as $employee) {
            $this->checkEligibility($employee);
        }

        return [
            'eligible' => $this->eligible,
            'not_eligible' => $this->not_eligible,
            'bonus_type' => [
                'id' => $this->bonusType->id,
                'name' => $this->bonusType->name,
            ],
        ];
    }

    private function checkEligibility($employee)
    {
        $ruleRemarks = [];
        $profileUrl = route('hris.employee.information', ['employee_no' => $employee->employee_no]);

        if ((bool) $this->bonusType->require_active_account && $employee->account_status !== 'active') {
            $ruleRemarks[] = [
                'text' => 'Employee is inactive.',
                'url' => $profileUrl,
            ];
        }

        if ((bool) $this->bonusType->require_work_shift && !$this->hasWorkAndShift($employee->employee_no)) {
            $ruleRemarks[] = [
                'text' => 'Employee has no work or shift schedule during the payroll month.',
                'url' => $profileUrl,
            ];
        }

        if ((bool) $this->bonusType->require_information && !$this->hasInformation($employee->employee_no)) {
            $ruleRemarks[] = [
                'text' => 'Employee record is incomplete.',
                'url' => $profileUrl,
            ];
        }

        if ((bool) $this->bonusType->require_salary && !$this->hasSalary($employee->employee_no)) {
            $ruleRemarks[] = [
                'text' => 'No valid salary record found for this employee as of the payroll month.',
                'url' => $profileUrl,
            ];
        }

        if (!$this->meetsMinimumYearsOfService($employee)) {
            $ruleRemarks[] = [
                'text' => $this->minimumServiceRemark(),
                'url' => $profileUrl,
            ];
        }

        $employee->selected = empty($ruleRemarks);
        $employee->can_override = true;
        $employee->remarks = $ruleRemarks;

        if ($employee->selected) {
            $this->eligible[] = $employee;
            return;
        }

        $this->not_eligible[] = $employee;
    }

    private function minimumServiceRemark(): string
    {
        $years = $this->bonusType->min_years_of_service;
        $months = $this->bonusType->min_months_of_service;

        if (is_null($years) && is_null($months)) {
            return 'Employee does not meet the minimum service requirements for this bonus.';
        }

        if (!is_null($years) && !is_null($months) && $months > 0) {
            return sprintf(
                'Employee does not meet the minimum %d year(s) and %d month(s) of service required for this bonus.',
                (int) $years,
                (int) $months
            );
        }

        if (!is_null($years) && $years > 0) {
            return sprintf(
                'Employee does not meet the minimum %d year(s) of service required for this bonus.',
                (int) $years
            );
        }

        if (!is_null($months) && $months > 0) {
            return sprintf(
                'Employee does not meet the minimum %d month(s) of service required for this bonus.',
                (int) $months
            );
        }

        return 'Employee does not meet the minimum service requirements for this bonus.';
    }

    private function meetsMinimumYearsOfService($employee): bool
    {
        $minYears = $this->bonusType->min_years_of_service;
        $minMonths = $this->bonusType->min_months_of_service;

        if (is_null($minYears) && is_null($minMonths)) {
            return true;
        }

        $column = $this->bonusType->service_date_basis === 'company'
            ? 'date_hired_company'
            : 'date_hired_organization';

        $serviceDate = data_get($employee, $column);

        if (!$serviceDate) {
            return false;
        }

        $serviceMonths = Carbon::parse($serviceDate)
            ->diffInMonths(Carbon::parse($this->monthYear . '-01')->endOfMonth());

        $requiredMonths = 0;

        if (!is_null($minYears)) {
            $requiredMonths += (int) $minYears * 12;
        }

        if (!is_null($minMonths)) {
            $requiredMonths += (int) $minMonths;
        }

        return $serviceMonths >= $requiredMonths;
    }

    private function hasWorkAndShift($employeeNo): bool
    {
        [$year, $month] = explode('-', $this->monthYear);
        $endDate = date('Y-m-t', strtotime("$year-$month-01"));

        return !is_null(
            $this->salaryEmployeeService
                ->activeShift($employeeNo, $endDate)
                ->leftJoin('shifts as s', 'sw1.shift_id', '=', 's.id')
                ->select('sw1.id')
                ->first()
        );
    }

    private function hasInformation($employeeNo): bool
    {
        $info = DB::table('employee_organization')
            ->leftJoin('employee_information', 'employee_organization.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->where('employee_organization.employee_no', $employeeNo)
            ->select(
                'employee_information.id as employee_information_id',
                'employee_personal.id as employee_personal_id',
                'positions.id as positions_id',
                'users.id as users_id'
            )
            ->first();

        return $info
            && $info->employee_information_id
            && $info->employee_personal_id
            && $info->positions_id
            && $info->users_id;
    }

    private function hasSalary($employeeNo): bool
    {
        [$year, $month] = explode('-', $this->monthYear);
        $endDate = date('Y-m-t', strtotime("$year-$month-01"));

        return !is_null(
            $this->salaryEmployeeService->activeSalary($employeeNo, $endDate)->first()
        );
    }

    public function createPayroll($payload)
    {
        $payrollNo = generateNo('GB-', 4);

        $payrollId = DB::table('payroll_government_bonus')->insertGetId([
            'label' => $payload['label'],
            'payroll_no' => $payrollNo,
            'month' => $payload['month'],
            'no_employee' => 0,
            'employment_type_id' => $payload['employment_type_id'],
            'government_bonus_type_id' => $payload['government_bonus_type_id'],
            'total' => 0,
            'status' => 'draft',
            'processed_by_id' => auth('sanctum')->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        collect($payload['approved_by'])
            ->flatMap(function ($approvers, $level) use ($payrollId) {
                return collect($approvers)->map(function ($userId) use ($payrollId, $level) {
                    return [
                        'payroll_government_bonus_id' => $payrollId,
                        'user_id' => $userId,
                        'level' => (int) $level,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
            })
            ->pipe(function ($records) {
                DB::table('payroll_government_bonus_approvers')->insert($records->toArray());
            });

        return [
            'payroll_no' => $payrollNo,
            'payroll_id' => $payrollId,
        ];
    }

    public function createReport($payload, $payrollId)
    {
        $selectedEmployees = collect($payload['employees']['eligible'] ?? [])
            ->merge($payload['employees']['not_eligible'] ?? [])
            ->where('selected', true)
            ->values();

        if ($selectedEmployees->isEmpty()) {
            Log::warning("No selected employees found for payroll ID: {$payrollId}");
            return null;
        }

        $batch = Bus::batch([])
            ->then(function (Batch $batch) {
            })
            ->catch(function (Batch $batch, \Throwable $e) {
            })
            ->name("Government Bonus Payroll Report #{$payrollId}")
            ->dispatch();

        DB::table('payroll_government_bonus')
            ->where('id', $payrollId)
            ->update(['batch_id' => $batch->id]);

        foreach ($selectedEmployees as $employee) {
            $batch->add(new GovernmentBonusReport($employee, $payrollId));
        }

        return $batch->id;
    }
}
