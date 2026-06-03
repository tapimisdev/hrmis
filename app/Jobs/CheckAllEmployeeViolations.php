<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckAllEmployeeViolations
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public function __construct(
        public ?int $year = null,
        public array $monthExemptions = []
    ) {
        $this->year ??= now()->year;
    }

    public function handle(): void
    {
        $startedAt = microtime(true);
        $periods = $this->periods();
        $exemptedMonths = $this->exemptedMonths();

        if ($exemptedMonths !== []) {
            DB::table('employee_violations')
                ->where('year', $this->year)
                ->whereIn('month', $exemptedMonths)
                ->whereNotNull('generated_at')
                ->delete();
        }

        $totalEmployees = DB::table('employee_information')
            ->whereNotNull('user_id')
            ->where('isDeleted', false)
            ->where('account_status', 'active')
            ->count();
        $checkedEmployees = 0;
        $failedEmployees = 0;

        Log::info('Employee violation scan started.', [
            'year' => $this->year,
            'periods' => $periods,
            'month_exemptions' => $this->monthExemptions,
            'total_employees' => $totalEmployees,
        ]);

        DB::table('employee_information')
            ->select(['id', 'user_id', 'employee_no'])
            ->whereNotNull('user_id')
            ->where('isDeleted', false)
            ->where('account_status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($employees) use (&$checkedEmployees, &$failedEmployees, $totalEmployees, $periods) {
                foreach ($employees as $employee) {
                    $employeeStartedAt = microtime(true);
                    $position = $checkedEmployees + $failedEmployees + 1;

                    Log::info('Employee violation check started.', [
                        'periods' => $periods,
                        'position' => $position,
                        'total_employees' => $totalEmployees,
                        'employee_no' => $employee->employee_no,
                        'user_id' => $employee->user_id,
                    ]);

                    try {
                        foreach ($periods as $period) {
                            GenerateEmployeeViolations::dispatchSync(
                                (int) $employee->user_id,
                                $employee->employee_no,
                                $period,
                                false,
                                $this->monthExemptions
                            );
                        }

                        $checkedEmployees++;

                        Log::info('Employee violation check completed.', [
                            'periods' => $periods,
                            'position' => $position,
                            'total_employees' => $totalEmployees,
                            'checked_employees' => $checkedEmployees,
                            'failed_employees' => $failedEmployees,
                            'employee_no' => $employee->employee_no,
                            'user_id' => $employee->user_id,
                            'elapsed_seconds' => round(microtime(true) - $employeeStartedAt, 2),
                        ]);
                    } catch (Throwable $exception) {
                        $failedEmployees++;

                        Log::error('Employee violation check failed.', [
                            'periods' => $periods,
                            'position' => $position,
                            'total_employees' => $totalEmployees,
                            'checked_employees' => $checkedEmployees,
                            'failed_employees' => $failedEmployees,
                            'employee_no' => $employee->employee_no,
                            'user_id' => $employee->user_id,
                            'message' => $exception->getMessage(),
                            'elapsed_seconds' => round(microtime(true) - $employeeStartedAt, 2),
                        ]);
                    }
                }
            });

        Log::info('Employee violation scan completed.', [
            'year' => $this->year,
            'periods' => $periods,
            'month_exemptions' => $this->monthExemptions,
            'total_employees' => $totalEmployees,
            'checked_employees' => $checkedEmployees,
            'failed_employees' => $failedEmployees,
            'elapsed_seconds' => round(microtime(true) - $startedAt, 2),
        ]);
    }

    public function uniqueId(): string
    {
        return $this->year . ':' . implode(',', $this->monthExemptions);
    }

    private function periods(): array
    {
        $lastMonth = $this->year === now()->year ? now()->month : 12;

        return collect(range(1, $lastMonth))
            ->reject(fn (int $month) => $this->isMonthExempted($month))
            ->map(fn (int $month) => Carbon::create($this->year, $month, 1)->format('Y-m'))
            ->values()
            ->all();
    }

    private function exemptedMonths(): array
    {
        return collect(range(1, 12))
            ->filter(fn (int $month) => $this->isMonthExempted($month))
            ->values()
            ->all();
    }

    private function isMonthExempted(int $month): bool
    {
        return collect($this->monthExemptions)->contains(function ($exemption) use ($month) {
            if (is_numeric($exemption)) {
                return (int) $exemption === $month;
            }

            return (string) $exemption === Carbon::create($this->year, $month, 1)->format('Y-m');
        });
    }
}
