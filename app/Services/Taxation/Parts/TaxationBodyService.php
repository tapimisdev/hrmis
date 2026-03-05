<?php

namespace App\Services\Taxation\Parts;

use Illuminate\Database\ConnectionInterface;

class TaxationBodyService
{
    public function __construct(
        private readonly ConnectionInterface $db
    ) {}

    public function getEmployees(int $id): ?object
    {
        $employees = $this->db->table('taxation_employees as te')

            ->leftJoin('employee_personal as ep', 'te.employee_no', '=', 'ep.employee_no')

            // Join max effectivity date per employee
            ->leftJoin(
                $this->db->raw("
                    (
                        SELECT employee_no, MAX(effectivity_date) AS max_effectivity_date
                        FROM employee_organization
                        GROUP BY employee_no
                    ) AS eomax
                "),
                'te.employee_no',
                '=',
                'eomax.employee_no'
            )

            // Join the actual latest record
            ->leftJoin('employee_organization as eo', function ($join) {
                $join->on('te.employee_no', '=', 'eo.employee_no')
                    ->on('eo.effectivity_date', '=', 'eomax.max_effectivity_date');
            })
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->leftJoin('units as u', 'eo.unit_id', '=', 'u.id')
            ->select(
                'te.id',
                'te.employee_no',

                $this->db->raw("
                    CONCAT(
                        UPPER(ep.lastname), ', ',
                        ep.firstname,
                        IF(ep.middlename IS NOT NULL AND ep.middlename != '', CONCAT(' ', LEFT(ep.middlename, 1), '.'), ''),
                        IF(ep.suffix IS NOT NULL AND ep.suffix != '', CONCAT(' ', ep.suffix), '')
                    ) as full_name
                "),

                'd.code as division',
                'p.name as position',
                'u.code as unit',

                'te.mid_year',
                'te.year_end',
                'te.longevity',
                'te.hazard_pay',

                'te.amount_basic_salary',
                'te.months_covered',
                'te.amount_anual_total_basic_salary',

                'te.less_bir_rr3_2015',
                
                'te.amount_mid_year_bonus',
                'te.amount_year_end_bonus',
                'te.amount_longevity_pay',
                'te.amount_hazard_pay',

                'te.amount_other_earnings_taxable',
                'te.amount_other_earnings_non_taxable',
                'te.amount_other_deductions',
                'te.amount_annual_total_allowables',

                'te.amount_total_bonuses',
                'te.amount_bonuses_exempt',

                'te.amount_gross',

                'te.amount_annual_taxable',
                'te.amount_annual_tax',
                'te.amount_monthly_tax',

                'te.portion_hazard_pay',
                'te.portion_basic_pay',
                'te.portion_longevity_pay',

                'te.amount_portion_hazard_pay',
                'te.amount_portion_basic_pay',
                'te.amount_portion_longevity_pay',

                'remarks'

            )
            ->where('te.taxation_id', $id)
            ->get()
            ->map(function ($employee) {

            $employee->amount_less =
                    $employee->amount_other_earnings_non_taxable
                    + $employee->amount_annual_total_allowables
                    + $employee->amount_bonuses_exempt;

                $employee->tax_computation = $this->db->table('tax_computation_logs')
                    ->select(
                        'bracket_from',
                        'bracket_to',
                        'annual_income',
                        'fixed_tax',
                        'tax_rate',
                        'excess_over',
                        'excess_amount',
                        'tax',
                        'monthly_tax',
                        'remarks',
                        )
                    ->where('taxation_employee_id', $employee->id)
                    ->orderByDesc('id')
                    ->first();

                // Money fields
                return $employee;
            });
        if ($employees->isEmpty()) {
            return null;
        }

        return $employees;
    }
}
