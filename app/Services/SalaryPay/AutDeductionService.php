<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use App\Enums\LeaveEnum;
use App\Services\DailyTimeRecordService;
use App\Services\SalaryEmloyeeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AutDeductionService
{
    public function __construct(
        protected SalaryEmloyeeService $salaryEmployeeService,
        protected DailyTimeRecordService $dailyTimeRecordService,
    ) {
    }

    public function getRegularPayroll(string $payrollId): object
    {
        $payroll = DB::table('payroll_salary')
            ->select('id', 'payroll_no', 'payroll_date', 'cutoff', 'period_covered', 'employment_type_id', 'status', 'is_aut_deducted')
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
        if (!empty($payroll->is_aut_deducted)) {
            return $this->buildStoredAutDeductionRows($payroll);
        }

        return $this->buildLiveAutDeductionRows($payroll);
    }

    private function buildLiveAutDeductionRows(object $payroll): Collection
    {
        $employees = DB::table('payroll_salary_permanent_employees as pspe')
            ->leftJoin('employee_information as ei', 'ei.employee_no', '=', 'pspe.employee_no')
            ->where('pspe.payroll_salary_id', $payroll->id)
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
                'ei.user_id'
            )
            ->orderBy('pspe.name')
            ->get();

        return $employees->map(function ($employee) use ($payroll) {
            $remarkAut = $this->parseAutRemark($employee->remarks);
            $computation = $this->computeAutDeductionValues($employee, $payroll);

            if ($remarkAut) {
                $computation['equivalent_leave_credits'] = round((float) $remarkAut['equivalent_leave_credits'], 3);
                $computation['remarks'] = $remarkAut['remark'];
            }

            if ((int) $computation['total_minutes'] <= 0 && !$remarkAut) {
                return null;
            }

            return [
                'pspe_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name' => $employee->name,
                'position' => $employee->position,
                'monthly_rate' => round((float) $employee->monthly_rate, 2),
                'aut' => round((float) $computation['aut_amount'], 2),
                'equivalent' => round((float) $computation['equivalent_leave_credits'], 3),
                'daily_rate' => round((float) $computation['daily_rate'], 4),
                'working_hours' => (float) $computation['working_hours'],
                'total_minutes' => (int) $computation['total_minutes'],
                'remarks' => $computation['remarks'],
                'as_of' => $computation['as_of'],
                'already_applied' => (bool) ($payroll->is_aut_deducted ?? false),
                'is_saved' => !empty($remarkAut),
            ];
        })->filter()->values();
    }

    private function buildStoredAutDeductionRows(object $payroll): Collection
    {
        $rows = DB::table('payroll_salary_aut_deductions as staged')
            ->where('staged.payroll_salary_id', $payroll->id)
            ->select(
                'staged.payroll_salary_permanent_employee_id as pspe_id',
                'staged.employee_no',
                'staged.name',
                'staged.position',
                'staged.monthly_rate',
                'staged.aut_amount as aut',
                'staged.equivalent_leave_credits as equivalent',
                'staged.daily_rate',
                'staged.working_hours',
                'staged.total_minutes',
                'staged.remarks',
                'staged.as_of',
                'staged.applied_at'
            )
            ->orderBy('staged.name')
            ->get()
            ->map(fn ($row) => [
                'pspe_id' => $row->pspe_id,
                'employee_no' => $row->employee_no,
                'name' => $row->name,
                'position' => $row->position,
                'monthly_rate' => round((float) $row->monthly_rate, 2),
                'aut' => round((float) $row->aut, 2),
                'equivalent' => round((float) $row->equivalent, 3),
                'daily_rate' => round((float) $row->daily_rate, 4),
                'working_hours' => (float) $row->working_hours,
                'total_minutes' => (int) $row->total_minutes,
                'remarks' => (string) $row->remarks,
                'as_of' => $row->as_of,
                'already_applied' => true,
                'is_saved' => !is_null($row->applied_at),
            ])
            ->values();

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        return DB::table('payroll_salary_aut_leave_credit_deductions as staged')
            ->join('payroll_salary_permanent_employees as pspe', 'pspe.id', '=', 'staged.payroll_salary_permanent_employee_id')
            ->where('staged.payroll_salary_id', $payroll->id)
            ->select(
                'pspe.id as pspe_id',
                'staged.employee_no',
                'pspe.name',
                'pspe.position',
                'pspe.monthly_rate',
                'staged.aut_amount as aut',
                'staged.equivalent_leave_credits as equivalent',
                'staged.daily_rate',
                'staged.working_hours',
                'staged.total_minutes',
                'staged.remarks',
                'staged.as_of',
                'staged.applied_at'
            )
            ->orderBy('pspe.name')
            ->get()
            ->map(fn ($row) => [
                'pspe_id' => $row->pspe_id,
                'employee_no' => $row->employee_no,
                'name' => $row->name,
                'position' => $row->position,
                'monthly_rate' => round((float) $row->monthly_rate, 2),
                'aut' => round((float) $row->aut, 2),
                'equivalent' => round((float) $row->equivalent, 3),
                'daily_rate' => round((float) $row->daily_rate, 4),
                'working_hours' => (float) $row->working_hours,
                'total_minutes' => (int) $row->total_minutes,
                'remarks' => (string) $row->remarks,
                'as_of' => $row->as_of,
                'already_applied' => true,
                'is_saved' => !is_null($row->applied_at),
            ])
            ->values();
    }

    public function getPayrollRangeMeta(object $payroll): array
    {
        [$startDate, $endDate] = $this->resolvePayrollRange($payroll);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $this->formatPayrollRangeLabel($startDate, $endDate),
        ];
    }

    public function applyAutDeductions(object $payroll, array $payloadRows = []): int
    {
        if (!empty($payroll->is_aut_deducted)) {
            abort(422, 'AUT changes have already been processed for this payroll.');
        }

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
                $this->storeAutDeduction($payroll, $row);
                $this->applyPayrollAutAdjustment($row);
                $this->applyLeaveCreditDeduction($row);
            }

            if ($rows->isNotEmpty()) {
                DB::table('payroll_salary')
                    ->where('id', $payroll->id)
                    ->update([
                        'is_aut_deducted' => true,
                        'updated_at' => now(),
                    ]);
            }
        });

        return $rows->count();
    }

    public function applyPendingAutDeductions(object $payroll): int
    {
        $rows = $this->buildAutDeductionRows($payroll)
            ->filter(fn ($row) => !empty($row['is_saved']))
            ->values();

        if ($rows->isEmpty()) {
            return 0;
        }

        $appliedCount = 0;

        DB::transaction(function () use ($payroll, $rows) {
            foreach ($rows as $row) {
                $this->applyLeaveCreditDeduction($row);
            }
        });

        foreach ($rows as $row) {
            if ($this->leaveCreditRemarkExists($row['employee_no'], LeaveEnum::VL->value, $row['as_of'], $row['remarks'])) {
                $appliedCount++;
            }
        }

        return $appliedCount;
    }

    private function computeAutDeductionValues(object $employee, object $payroll): array
    {
        $shift = $this->salaryEmployeeService
            ->activeShift($employee->employee_no, $payroll->payroll_date)
            ->leftJoin('shifts as s', 'sw1.shift_id', '=', 's.id')
            ->select('s.working_hours')
            ->first();

        $salary = $this->salaryEmployeeService
            ->activeSalary($employee->employee_no, $payroll->payroll_date)
            ->select('daily_rate')
            ->first();

        $workingHours = (float) ($shift->working_hours ?? 8);
        $dailyRate = $salary
            ? (float) filter_var($salary->daily_rate, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
            : round(((float) $employee->monthly_rate) / 22, 4);
        $minuteRate = $workingHours > 0 ? ($dailyRate / $workingHours) / 60 : 0;
        [$startDate, $endDate] = $this->resolvePayrollRange($payroll);

        $summary = [];

        if (!empty($employee->user_id)) {
            $dtr = $this->dailyTimeRecordService->getDTR([
                'user_id' => $employee->user_id,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);

            $summary = $dtr['payroll_value'] ?? [];
        }

        $absentDays = (float) ($summary['absent'] ?? 0);
        $lateUndertime = (int) ($summary['late_undertime'] ?? 0);
        $totalMinutes = (int) round(($absentDays * $workingHours * 60) + $lateUndertime);
        $autAmount = round(($absentDays * $dailyRate) + ($lateUndertime * $minuteRate), 2);
        $equivalent = round($totalMinutes / 480, 3);

        return [
            'daily_rate' => $dailyRate,
            'working_hours' => $workingHours,
            'aut_amount' => $autAmount,
            'total_minutes' => $totalMinutes,
            'equivalent_leave_credits' => $equivalent,
            'remarks' => $this->formatAutRemark($totalMinutes, $workingHours, $equivalent),
            'as_of' => Carbon::parse($payroll->payroll_date)->format('Y-m'),
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

    private function applyLeaveCreditDeduction(array $row): void
    {
        if ($this->leaveCreditRemarkExists(
            $row['employee_no'],
            $row['leave_id'] ?? LeaveEnum::VL->value,
            $row['as_of'],
            $row['remarks']
        )) {
            return;
        }

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

    private function storeAutDeduction(object $payroll, array $row): void
    {
        DB::table('payroll_salary_aut_deductions')->updateOrInsert(
            [
                'payroll_salary_id' => $payroll->id,
                'payroll_salary_permanent_employee_id' => $row['pspe_id'],
            ],
            [
                'employee_no' => $row['employee_no'],
                'name' => $row['name'],
                'position' => $row['position'],
                'monthly_rate' => round((float) $row['monthly_rate'], 2),
                'leave_id' => $row['leave_id'] ?? LeaveEnum::VL->value,
                'as_of' => $row['as_of'],
                'daily_rate' => round((float) $row['daily_rate'], 4),
                'working_hours' => round((float) $row['working_hours'], 3),
                'aut_amount' => round((float) $row['aut'], 2),
                'equivalent_leave_credits' => round((float) $row['equivalent'], 3),
                'total_minutes' => (int) $row['total_minutes'],
                'remarks' => $row['remarks'],
                'applied_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
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

        $existingAutRemark = $this->parseAutRemark($employee->remarks);

        $updates = [
            'remarks' => $this->replaceAutRemark($employee->remarks, $row['remarks']),
            'updated_at' => now(),
        ];

        if (!$existingAutRemark) {
            $updates['ut'] = 0;
            $updates['absences'] = 0;
            $updates['total_deductions'] = round((float) $employee->total_deductions - (float) $row['aut'], 2);
            $updates['net_pay'] = round((float) $employee->net_pay + (float) $row['aut'], 2);
        }

        DB::table('payroll_salary_permanent_employees')
            ->where('id', $row['pspe_id'])
            ->update($updates);
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

    private function replaceAutRemark(?string $existingRemarks, string $newRemark): string
    {
        $existingRemarks = trim((string) $existingRemarks);

        if ($existingRemarks === '') {
            return $newRemark;
        }

        $lines = collect(preg_split('/\r\n|\r|\n/', $existingRemarks) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->reject(fn ($line) => preg_match('/^AUT\s+\d+\s+days?\s+\d+\s+hours?\s+\d+\s+mins?\s*=\s*[0-9]+(?:\.[0-9]+)?\s+VL$/i', $line))
            ->values();

        $lines->push($newRemark);

        return $lines->implode(PHP_EOL);
    }

    private function leaveCreditRemarkExists(string $employeeNo, int $leaveId, string $asOf, string $remark): bool
    {
        $remarks = DB::table('leave_credits')
            ->where('employee_no', $employeeNo)
            ->where('leave_id', $leaveId)
            ->where('as_of', $asOf)
            ->value('remarks');

        if (!$remarks) {
            return false;
        }

        return collect(preg_split('/\r\n|\r|\n/', (string) $remarks) ?: [])
            ->contains(fn ($line) => trim($line) === trim($remark));
    }

    private function resolvePayrollRange(object $payroll): array
    {
        $payrollDate = Carbon::parse($payroll->payroll_date);

        if (($payroll->cutoff ?? null) === 'first_cutoff') {
            return [
                $payrollDate->copy()->startOfMonth()->format('Y-m-d'),
                $payrollDate->copy()->day(15)->format('Y-m-d'),
            ];
        }

        return [
            $payrollDate->copy()->day(16)->format('Y-m-d'),
            $payrollDate->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }

    private function formatPayrollRangeLabel(string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->isSameMonth($end)) {
            return sprintf(
                '%s %d-%d, %d',
                $start->format('F'),
                $start->day,
                $end->day,
                $start->year
            );
        }

        return sprintf(
            '%s %d, %d - %s %d, %d',
            $start->format('F'),
            $start->day,
            $start->year,
            $end->format('F'),
            $end->day,
            $end->year
        );
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
