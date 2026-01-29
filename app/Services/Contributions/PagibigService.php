<?php

namespace App\Services\Contributions;

class PagibigService
{
    /**
     * Calculate the Pag-IBIG contribution based on the given salary.
     *
     * @param  float  $salary
     * @return float
     */
    public static function calculateContribution(float $basicSalary): array
    {
        $salaryBase = min($basicSalary, 5000);

        $eeRate = $basicSalary <= 1500 ? 0.01 : 0.02;
        $erRate = 0.02;

        $employee = min($salaryBase * $eeRate, 200);
        $employer = min($salaryBase * $erRate, 200);

        return [
            'employee' => round($employee, 2),
            'employer' => round($employer, 2),
            'total'    => round($employee + $employer, 2),
        ];
    }
}