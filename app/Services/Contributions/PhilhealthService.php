<?php

namespace App\Services\Contributions;

use Illuminate\Support\Facades\DB;

/**
 * Class PhilhealthService
 *
 * Handles the computation of PhilHealth contributions for employees
 * based on a configurable computation string.
 *
 * The computation string follows the format:
 *   rate,floor,ceiling
 *
 * Example:
 *   "5,10000,100000"
 *
 * Meaning:
 * - rate    : contribution rate in percent (e.g. 5 = 5%)
 * - floor   : minimum salary base for computation
 * - ceiling : maximum salary base for computation
 *
 * This service:
 * - Retrieves the employee's latest salary
 * - Applies floor and ceiling rules
 * - Computes the PhilHealth contribution
 * - Returns the employee share (half of total contribution)
 */
class PhilhealthService
{
    /**
     * Compute the PhilHealth contribution for a single employee.
     *
     * Process:
     * 1. Parse computation string (rate,floor,ceiling)
     * 2. Convert rate from percentage to decimal
     * 3. Retrieve the employee's latest salary
     * 4. Clamp salary within floor and ceiling
     * 5. Multiply salary base by rate
     * 6. Divide by 2 to return employee share
     *
     * @param  string  $employee_no   Employee number
     * @param  string  $computation   Computation string (rate,floor,ceiling)
     * @return float                 Employee PhilHealth contribution (rounded)
     */
    public function compute(string $employee_no, string $computation): float
    {
        // Remove spaces
        $computation = trim((string) $computation);

        [$rate, $floor, $ceiling] = array_map('trim', explode(',', $computation));

        // Convert values
        $rate    = (float) $rate / 100; // percentage → decimal
        $floor   = (float) $floor;
        $ceiling = (float) $ceiling;

        // Get employee's latest salary
        $salary = $this->getEmployeeSalary($employee_no);

        // Determine salary base within floor and ceiling
        if ($salary < $floor) {
            $salaryBase = $floor;
        } elseif ($salary > $ceiling) {
            $salaryBase = $ceiling;
        } else {
            $salaryBase = $salary;
        }

        // Compute total contribution
        $contribution = $salaryBase * $rate;

        // Return employee share (50%)
        return round($contribution, 2) / 2;
    }

    /**
     * Retrieve the employee's latest basic salary.
     *
     * The latest salary is determined by the most recent
     * effectivity_date in the employee_salary table.
     *
     * If no salary record exists, this method returns 0.00.
     *
     * @param  string  $employee_no
     * @return float
     */
    private function getEmployeeSalary(string $employee_no): float
    {
        $salary = DB::table('employee_salary')
            ->where('employee_no', $employee_no)
            ->orderByDesc('effectivity_date')
            ->value('amount');

        return $salary ? (float) $salary : 0.0;
    }
}
