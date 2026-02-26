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
        $name           = $this->employee['Name'] ?? null;
        $position       = $this->employee['Position'] ?? null;
        $salaryGrade    = $this->employee['Salary Grade'] ?? null;

        $basicSalary    = (float) ($this->employee['Monthly Rate'] ?? 0);
        $salaryEarned   = (float) ($this->employee['Salary Earned'] ?? 0);
        $netSalary      = (float) ($this->employee['Net salary'] ?? 0);

        $threePercent   = (float) ($this->employee['Three Percent'] ?? 0);
        $twoPercent     = (float) ($this->employee['Two Percent'] ?? 0);
        $fivePercent    = (float) ($this->employee['Five Percent'] ?? 0);

        $hmo            = (float) ($this->employee['Healthcard'] ?? 0);
        $aut            = (float) ($this->employee['AUT'] ?? 0);

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
}