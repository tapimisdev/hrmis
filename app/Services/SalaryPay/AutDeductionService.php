<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use App\Enums\LeaveEnum;
use App\Services\SalaryEmloyeeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AutDeductionService
{
    public function __construct(
        protected SalaryEmloyeeService $salaryEmployeeService
    ) {
    }

    public function getRegularPayroll(string $payrollId): object
    {
        $payroll = DB::table('payroll_salary')
            ->select('id', 'payroll_no', 'payroll_date', 'employment_type_id', 'status')
            ->where('id', $payrollId)
            ->first();

        if (!$payroll) {
            abort(404, 'Payroll not found.');
        }

        if ((int) $payroll->employment_type_id !== (int) EmploymentTypesEnum::REGULAR->value) {
            abort(422, 'AUT deductions are only available for regular payroll.');
        }

        return $payroll;
    }

    public function buildAutDeductionRows(object $payroll): Collection
    {
        $employees = DB::table('payroll_salary_permanent_employees as pspe')
            ->leftJoin(
                'payroll_salary_aut_leave_credit_deductions as staged',
                'staged.payroll_salary_permanent_employee_id',
                '=',
                'pspe.id'
            )
            ->where('pspe.payroll_salary_id', $payroll->id)
            ->where(function ($query) {
                $query->whereRaw('(COALESCE(pspe.ut, 0) + COALESCE(pspe.absences, 0)) > 0')
                    ->orWhereNotNull('staged.id')
                    ->orWhere('pspe.remarks', 'like', '%AUT % VL%');
            })
            ->select(
                'pspe.id',
                'pspe.employee_no',
                'pspe.name',
                'pspe.position',
                'pspe.monthly_rate',
                'pspe.ut',
                'pspe.absences',
                'pspe.remarks',
                'pspe.total_deductions',
                'pspe.net_pay',
                'staged.id as staged_id',
                'staged.applied_at as staged_applied_at',
                'staged.aut_amount as staged_aut_amount',
                'staged.equivalent_leave_credits as staged_equivalent_leave_credits',
                'staged.total_minutes as staged_total_minutes',
                'staged.remarks as staged_remarks',
                'staged.daily_rate as staged_daily_rate',
                'staged.working_hours as staged_working_hours',
                'staged.as_of as staged_as_of'
            )
            ->orderBy('pspe.name')
            ->get();

        return $employees->map(function ($employee) use ($payroll) {
            $remarkAut = $this->parseAutRemark($employee->remarks);
            $autAmount = round((float) ($employee->staged_aut_amount ?? ((float) $employee->ut + (float) $employee->absences)), 2);

            $computation = $employee->staged_id
                ? [
                    'daily_rate' => (float) $employee->staged_daily_rate,
                    'working_hours' => (float) $employee->staged_working_hours,
                    'total_minutes' => (int) $employee->staged_total_minutes,
                    'equivalent_leave_credits' => round((float) $employee->staged_equivalent_leave_credits, 3),
                    'remarks' => $employee->staged_remarks,
                    'as_of' => $employee->staged_as_of,
                ]
                : ($remarkAut
                    ? $this->computeAutDeductionFromRemark(
                        $employee->employee_no,
                        $payroll->payroll_date,
                        $remarkAut,
                        $autAmount
                    )
                    : $this->computeAutDeductionValues(
                        $employee->employee_no,
                        $payroll->payroll_date,
                        $autAmount
                    ));

            return [
                'pspe_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name' => $employee->name,
                'position' => $employee->position,
                'monthly_rate' => round((float) $employee->monthly_rate, 2),
                'aut' => $autAmount,
                'equivalent' => round((float) $computation['equivalent_leave_credits'], 3),
                'daily_rate' => round((float) $computation['daily_rate'], 4),
                'working_hours' => (float) $computation['working_hours'],
                'total_minutes' => (int) $computation['total_minutes'],
                'remarks' => $computation['remarks'],
                'as_of' => $computation['as_of'],
                'already_applied' => !empty($employee->staged_applied_at),
                'is_saved' => !empty($employee->staged_id),
            ];
        });
    }

    public function saveAutDeductions(object $payroll, array $payloadRows = []): int
    {
        $overrides = collect($payloadRows)->keyBy(fn ($row) => (int) $row['pspe_id']);
        $rows = $this->buildAutDeductionRows($payroll)
            ->map(function ($row) use ($overrides) {
                $override = $overrides->get((int) $row['pspe_id']);

                if (!$override || !empty($row['already_applied'])) {
                    return $row;
                }

                $row['equivalent'] = round((float) $override['equivalent'], 3);
                $row['remarks'] = $this->formatAutRemark(
                    (int) $row['total_minutes'],
                    (float) $row['working_hours'],
                    (float) $row['equivalent']
                );

                return $row;
            })
            ->values();

        DB::transaction(function () use ($payroll, $rows) {
            foreach ($rows as $row) {
                $this->stageAutDeduction($payroll, $row);
            }
        });

        return $rows->count();
    }

    public function applyPendingAutDeductions(object $payroll): int
    {
        $rows = DB::table('payroll_salary_aut_leave_credit_deductions as staged')
            ->join(
                'payroll_salary_permanent_employees as pspe',
                'pspe.id',
                '=',
                'staged.payroll_salary_permanent_employee_id'
            )
            ->where('staged.payroll_salary_id', $payroll->id)
            ->whereNull('staged.applied_at')
            ->select(
                'pspe.id as pspe_id',
                'pspe.employee_no',
                'pspe.ut',
                'pspe.absences',
                'pspe.total_deductions',
                'pspe.net_pay',
                'pspe.remarks as payroll_remarks',
                'staged.id as staged_id',
                'staged.leave_id',
                'staged.as_of',
                'staged.aut_amount as aut',
                'staged.equivalent_leave_credits as equivalent',
                'staged.total_minutes',
                'staged.remarks'
            )
            ->orderBy('pspe.name')
            ->get()
            ->map(fn ($row) => [
                'pspe_id' => $row->pspe_id,
                'employee_no' => $row->employee_no,
                'leave_id' => (int) $row->leave_id,
                'as_of' => $row->as_of,
                'aut' => round((float) $row->aut, 3),
                'equivalent' => round((float) $row->equivalent, 3),
                'total_minutes' => (int) $row->total_minutes,
                'remarks' => (string) $row->remarks,
            ])
            ->values();

        if ($rows->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($payroll, $rows) {
            foreach ($rows as $row) {
                $this->applyLeaveCreditDeduction($row);
                $this->applyPayrollAutAdjustment($row);
                DB::table('payroll_salary_aut_leave_credit_deductions')
                    ->where('payroll_salary_permanent_employee_id', $row['pspe_id'])
                    ->update([
                        'applied_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        return $rows->count();
    }

    private function computeAutDeductionFromRemark(
        string $employeeNo,
        string $payrollDate,
        array $remarkAut,
        float $autAmount
    ): array {
        $baseComputation = $this->computeAutDeductionValues($employeeNo, $payrollDate, $autAmount);

        return [
            'daily_rate' => $baseComputation['daily_rate'],
            'working_hours' => $baseComputation['working_hours'],
            'total_minutes' => (int) round(
                ($remarkAut['days'] * $baseComputation['working_hours'] * 60)
                + ($remarkAut['hours'] * 60)
                + $remarkAut['minutes']
            ),
            'equivalent_leave_credits' => round((float) $remarkAut['equivalent_leave_credits'], 3),
            'remarks' => $remarkAut['remark'],
            'as_of' => Carbon::parse($payrollDate)->format('Y-m'),
        ];
    }

    private function computeAutDeductionValues(string $employeeNo, string $payrollDate, float $autAmount): array
    {
        $shift = $this->salaryEmployeeService
            ->activeShift($employeeNo, $payrollDate)
            ->leftJoin('shifts as s', 'sw1.shift_id', '=', 's.id')
            ->select('s.working_hours')
            ->first();

        $salary = $this->salaryEmployeeService
            ->activeSalary($employeeNo, $payrollDate)
            ->select('daily_rate')
            ->first();

        if (!$shift || !$salary) {
            abort(422, "Unable to compute AUT deduction for employee {$employeeNo}.");
        }

        $workingHours = (float) $shift->working_hours;
        $dailyRate = (float) filter_var(
            $salary->daily_rate,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
        $minuteRate = $workingHours > 0 ? ($dailyRate / $workingHours) / 60 : 0;
        $totalMinutes = $minuteRate > 0 ? (int) round($autAmount / $minuteRate) : 0;
        $equivalent = round($totalMinutes / 480, 3);

        return [
            'daily_rate' => $dailyRate,
            'working_hours' => $workingHours,
            'total_minutes' => $totalMinutes,
            'equivalent_leave_credits' => $equivalent,
            'remarks' => $this->formatAutRemark($totalMinutes, $workingHours, $equivalent),
            'as_of' => Carbon::parse($payrollDate)->format('Y-m'),
        ];
    }

    public function formatAutRemark(int $totalMinutes, float $workingHours, float $equivalent): string
    {
        $minutesPerDay = max(1, (int) round($workingHours * 60));
        $days = intdiv($totalMinutes, $minutesPerDay);
        $remainingMinutes = $totalMinutes % $minutesPerDay;
        $hours = intdiv($remainingMinutes, 60);
        $mins = $remainingMinutes % 60;

        return sprintf(
            'AUT %d day%s %d hour%s %d min%s = %.3f VL',
            $days,
            $days !== 1 ? 's' : '',
            $hours,
            $hours !== 1 ? 's' : '',
            $mins,
            $mins !== 1 ? 's' : '',
            $equivalent
        );
    }

    private function parseAutRemark(?string $remarks): ?array
    {
        $remarks = trim((string) $remarks);

        if ($remarks === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $remarks) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (!preg_match(
                '/^AUT\s+(\d+)\s+days?\s+(\d+)\s+hours?\s+(\d+)\s+mins?\s*=\s*([0-9]+(?:\.[0-9]+)?)\s+VL$/i',
                $line,
                $matches
            )) {
                continue;
            }

            return [
                'days' => (int) $matches[1],
                'hours' => (int) $matches[2],
                'minutes' => (int) $matches[3],
                'equivalent_leave_credits' => round((float) $matches[4], 3),
                'remark' => $line,
            ];
        }

        return null;
    }

    private function stageAutDeduction(object $payroll, array $row): void
    {
        $existingAppliedAt = DB::table('payroll_salary_aut_leave_credit_deductions')
            ->where('payroll_salary_permanent_employee_id', $row['pspe_id'])
            ->value('applied_at');

        DB::table('payroll_salary_aut_leave_credit_deductions')->updateOrInsert(
            ['payroll_salary_permanent_employee_id' => $row['pspe_id']],
            [
                'payroll_salary_id' => $payroll->id,
                'employee_no' => $row['employee_no'],
                'leave_id' => LeaveEnum::VL->value,
                'as_of' => $row['as_of'],
                'daily_rate' => $row['daily_rate'],
                'working_hours' => $row['working_hours'],
                'aut_amount' => $row['aut'],
                'equivalent_leave_credits' => $row['equivalent'],
                'total_minutes' => $row['total_minutes'],
                'remarks' => $row['remarks'],
                'applied_at' => $existingAppliedAt,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function applyLeaveCreditDeduction(array $row): void
    {
        $existing = DB::table('leave_credits')
            ->where('employee_no', $row['employee_no'])
            ->where('leave_id', $row['leave_id'] ?? LeaveEnum::VL->value)
            ->where('as_of', $row['as_of'])
            ->first();

        $previousBalance = $existing
            ? (float) $existing->previous
            : (float) (
                DB::table('leave_credits')
                    ->where('employee_no', $row['employee_no'])
                    ->where('leave_id', $row['leave_id'] ?? LeaveEnum::VL->value)
                    ->where('as_of', '<', $row['as_of'])
                    ->orderByDesc('as_of')
                    ->value('balance') ?? 0
            );

        $earned = $existing ? (float) $existing->earned : 0;
        $deducted = $existing ? (float) $existing->deducted : 0;
        $newDeducted = round($deducted + (float) $row['equivalent'], 2);
        $balance = round($previousBalance + $earned - $newDeducted, 2);

        DB::table('leave_credits')->updateOrInsert(
            [
                'employee_no' => $row['employee_no'],
                'leave_id' => $row['leave_id'] ?? LeaveEnum::VL->value,
                'as_of' => $row['as_of'],
            ],
            [
                'previous' => round($previousBalance, 2),
                'earned' => round($earned, 2),
                'deducted' => $newDeducted,
                'balance' => $balance,
                'remarks' => $this->appendRemark($existing->remarks ?? null, $row['remarks']),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->recalculateFutureCredits(
            $row['employee_no'],
            $row['leave_id'] ?? LeaveEnum::VL->value,
            $row['as_of'],
            $balance
        );
    }

    private function applyPayrollAutAdjustment(array $row): void
    {
        $employee = DB::table('payroll_salary_permanent_employees')
            ->where('id', $row['pspe_id'])
            ->lockForUpdate()
            ->first();

        if (!$employee) {
            return;
        }

        DB::table('payroll_salary_permanent_employees')
            ->where('id', $row['pspe_id'])
            ->update([
                'ut' => 0,
                'absences' => 0,
                'total_deductions' => round((float) $employee->total_deductions - (float) $row['aut'], 2),
                'net_pay' => round((float) $employee->net_pay + (float) $row['aut'], 2),
                'remarks' => $this->appendRemark($employee->remarks, $row['remarks']),
                'updated_at' => now(),
            ]);
    }

    private function appendRemark(?string $existingRemarks, string $newRemark): string
    {
        $existingRemarks = trim((string) $existingRemarks);
        $newRemark = trim($newRemark);

        if ($existingRemarks === '') {
            return $newRemark;
        }

        $lines = preg_split('/\r\n|\r|\n/', $existingRemarks) ?: [];

        if (collect($lines)->contains(fn ($line) => trim($line) === $newRemark)) {
            return $existingRemarks;
        }

        return $existingRemarks . PHP_EOL . $newRemark;
    }

    private function recalculateFutureCredits(
        string $employeeNo,
        int $leaveId,
        string $asOf,
        float $startingBalance
    ): void {
        $futureCredits = DB::table('leave_credits')
            ->where('employee_no', $employeeNo)
            ->where('leave_id', $leaveId)
            ->where('as_of', '>', $asOf)
            ->orderBy('as_of')
            ->get();

        $runningBalance = round($startingBalance, 2);

        foreach ($futureCredits as $credit) {
            $earned = round((float) $credit->earned, 2);
            $deducted = round((float) $credit->deducted, 2);
            $newBalance = round($runningBalance + $earned - $deducted, 2);

            DB::table('leave_credits')
                ->where('id', $credit->id)
                ->update([
                    'previous' => $runningBalance,
                    'balance' => $newBalance,
                    'updated_at' => now(),
                ]);

            $runningBalance = $newBalance;
        }
    }
}
