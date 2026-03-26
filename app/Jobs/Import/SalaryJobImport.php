<?php

namespace App\Jobs\Import;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SalaryJobImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public array $employee;
    public int $payroll_id;

    public function __construct(array $employee, int $payroll_id)
    {
        $this->employee = $employee;
        $this->payroll_id = $payroll_id;
    }

    public function handle(): void
    {
        $employeeNo     = $this->employee['Employee No'] ?? null;
        [$nameFromBlock, $positionFromBlock] = $this->extractNameAndPosition((string) ($this->employee['Name'] ?? ''));
        $name           = $nameFromBlock ?: ($this->employee['Name'] ?? null);
        $position       = $this->employee['Position'] ?? $positionFromBlock;
        $salaryGrade    = $this->employee['Salary Grade'] ?? null;

        $basicSalary    = $this->amountFromKeys(['Monthly Rate']);
        $salaryEarned   = $this->amountFromKeys(['Salary Earned']);
        $netSalary      = $this->amountFromKeys(['Net Salary', 'Net salary', 'NET SALARY']);

        $threePercent   = $this->amountFromKeys(['Three Percent']);
        $twoPercent     = $this->amountFromKeys(['Two Percent']);
        $fivePercent    = $this->amountFromKeys(['Five Percent']);

        $hmo            = $this->amountFromKeys(['Healthcard', 'Health Card', 'HMO']);
        $aut            = $this->amountFromKeys(['AUT']);

        if (!$employeeNo || !$name) {
            return; 
        }

        $withholdingTax = $twoPercent + $threePercent + $fivePercent;

        DB::table('payroll_salary_employee')->updateOrInsert(
            [
                'payroll_salary_id' => $this->payroll_id,
                'employee_no'       => $employeeNo,
            ],
            [
                'name'              => $name,
                'position'          => $position,
                'salary_grade'      => $salaryGrade,
                'monthly_rate'      => $basicSalary,
                'basic_pay'         => $salaryEarned,
                'gross_pay'         => $salaryEarned,
                'net_pay'           => $netSalary,

                'ut'                => $aut,
                'absences'          => 0,
                'overtime'          => 0,
                'holiday'           => 0,

                'gsis'              => 0,
                'philhealth'        => 0,
                'pagibig'           => 0,

                'ewt_2'             => $twoPercent,
                'percentage_tax_3'  => $threePercent,
                'tax_ewt_5'         => $fivePercent,
                'w_tax'             => $withholdingTax,

                'hmo'               => $hmo,
                'total_deductions'  => $withholdingTax + $hmo,
                'total_earnings'    => $salaryEarned,

                'salary_adjustment' => 0,
                'updated_at'        => now(),
                'created_at'        => now(),
            ]
        );
    }

    private function extractNameAndPosition(string $value): array
    {
        $normalizedValue = preg_replace('/<br\s*\/?>/i', "\n", $value);
        $normalizedValue = str_replace(["\r\n", "\r"], "\n", $normalizedValue);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $normalizedValue)), fn ($line) => $line !== ''));

        $name = $lines[0] ?? '';
        $position = count($lines) > 1 ? implode("\n", array_slice($lines, 1)) : '';

        return [$name, $position];
    }

    private function toAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return 0.0;
        }

        return (float) $normalized;
    }

    private function amountFromKeys(array $keys): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->employee)) {
                return $this->toAmount($this->employee[$key]);
            }
        }

        return 0.0;
    }
}
