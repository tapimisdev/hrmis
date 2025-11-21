<?php

namespace App\Services;

use App\Enums\EmploymentTypesEnum;
use Illuminate\Support\Facades\DB;

class TaxService
{
    /**
     * Get all employee tax data for a specific tax and year.
     */
    public function getAll(int $tax_id, int $year_id)
    {

        $regular_id = EmploymentTypesEnum::REGULAR->value;

        $monthNames = [
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        // Fetch the tax deduction record
        $taxDeduction = DB::table('tax_years')
            ->where('tax_id', $tax_id)
            ->where('year', $year_id)
            ->first();


        if (!$taxDeduction) {
            return collect(); // Return empty collection if not found
        }

        $taxDeductionId = $taxDeduction->id;

        // Fetch all regular employees with organization info
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->where('eo.employment_type_id', $regular_id)
            ->select(
                'ei.employee_no',
                'ep.suffix',
                'ep.middlename',
                'ep.lastname',
                'ep.firstname',
                'd.code as division_code',
                'd.name as division_name'
            )
            ->orderBy('ep.lastname', 'asc')
            ->get();

        // Fetch all employee_taxes for this tax deduction in one query
        $employeeTaxes = DB::table('employee_taxes')
            ->where('tax_deduction_id', $taxDeductionId)
            ->get()
            ->groupBy('employee_no');

        // Map employee data with monthly tax amounts
        return $employees->map(function ($employee) use ($employeeTaxes, $taxDeductionId, $monthNames) {
            $taxRecords = $employeeTaxes[$employee->employee_no] ?? [];

            // Initialize month values to 0
            foreach ($monthNames as $month => $monthName) {
                $record = collect($taxRecords)->firstWhere('month', $month);
                $employee->{$monthName} = $record->amount ?? 0;
            }

            $employee->tax_deduction_id = $taxDeductionId;

            return $employee;
        });
    }
}
