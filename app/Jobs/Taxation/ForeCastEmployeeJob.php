<?php

namespace App\Jobs\Taxation;

use App\Services\Taxation\ForecastComputationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ForeCastEmployeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $taxation_id;
    private $employee_no;
    private array $payload;

    private int $hazardTaxId, $salaryTaxId, $longevityTaxId, $trainLawId;

    /**
     * Create a new job instance.
     */

    public function __construct($taxation_id, $employee_no, $payload)
    {
        $this->taxation_id = $taxation_id;
        $this->employee_no = $employee_no;
        $this->payload = $payload;

        $this->hazardTaxId     = $payload['hazardTaxId'];
        $this->salaryTaxId     = $payload['salaryTaxId'];
        $this->longevityTaxId  = $payload['longevityTaxId'];
        $this->trainLawId      = $payload['trainLawId'];
    }

    /**
     * Execute the job.
     */
    public function handle(ForecastComputationService $service): void
    {
        $year = (int) ($this->payload['year'] ?? now()->year);

        $basicPays = $service->annualSalaryTotalByMonth($this->employee_no, $year);

        $annualBasicPay = (float) ($basicPays['annual_total'] ?? 0);
        $monthlySalary  = (float) ($basicPays['monthly_salary'] ?? 0);

        // Hazard pay monthly (service already computed; fallback just in case)
        $monthlyHazardPay = (float) ($basicPays['hazard_pay'] ?? ($monthlySalary * 0.15));
        $monthsCovered    = (int) ($basicPays['months_covered'] ?? 0);

        // Totals
        $totalMidYear        = 0.0;
        $totalYearEnd        = 0.0;
        $totalLongevity      = 0.0;
        $totalHazardPay      = 0.0;
        $totalOtherEarnings  = 0.0;
        $totalOtherDeductions = 0.0;

        // Optional payload sections (avoid undefined index issues)
        $this->payload['assumptions']     = $this->payload['assumptions'] ?? [];
        $this->payload['othersEarnings']  = $this->payload['othersEarnings'] ?? [];
        $this->payload['othersDeductions'] = $this->payload['othersDeductions'] ?? [];

        // MIDYEAR
        $mid = $basicPays['midyear'] ?? null;
        if (($this->payload['assumptions']['midYear'] ?? false) === true) {
            if (!empty($mid['eligible'])) {
                $totalMidYear = (float) ($mid['amount'] ?? 0);
            } else {
                $this->payload['assumptions']['midYear'] = false;
            }
        }

        // YEAR-END
        $ye = $basicPays['year_end'] ?? null;
        if (($this->payload['assumptions']['yearEnd'] ?? false) === true) {
            if (!empty($ye['eligible'])) {
                $totalYearEnd = (float) ($ye['amount'] ?? 0);
            } else {
                $this->payload['assumptions']['yearEnd'] = false;
            }
        }

        // LONGEVITY
        if (($this->payload['assumptions']['longevity'] ?? false) === true) {
            $longevity = $service->ComputeLongevity(
                $this->employee_no,
                $year,
                $this->longevityTaxId
            );

            $totalLongevity = (float) ($longevity['longevity_total'] ?? 0);
        }

        // HAZARD PAY (annual)
        if (($this->payload['assumptions']['hazardPay'] ?? false) === true) {
            $totalHazardPay = $monthlyHazardPay * $monthsCovered;
        }

        // OTHER EARNINGS total
        foreach ($this->payload['othersEarnings'] as $other) {
            $totalOtherEarnings += (float) ($other['amount'] ?? 0);
        }

        // Round core amounts
        $annualBasicPay      = round($annualBasicPay, 2);
        $monthlySalary       = round($monthlySalary, 2);
        $monthlyHazardPay    = round($monthlyHazardPay, 2);

        $totalMidYear        = round($totalMidYear, 2);
        $totalYearEnd        = round($totalYearEnd, 2);
        $totalLongevity      = round($totalLongevity, 2);
        $totalHazardPay      = round($totalHazardPay, 2);
        $totalOtherEarnings  = round($totalOtherEarnings, 2);

        $totalBonuses =
            $totalMidYear +
            $totalYearEnd +
            $totalLongevity +
            $totalHazardPay +
            $totalOtherEarnings;

        // Less BIR RR 3-2015 (90k exemption)
        if (($this->payload['assumptions']['lessBirRR32015'] ?? false) === true) {
            $totalBonuses = max($totalBonuses - 90000, 0);
        }

        // Allowables (GSIS/PAGIBIG/PHILHEALTH)
        $allowables = $service->getAllowablesDeductions($this->employee_no, $year);

        $totalGsis       = (float) ($allowables['gsis'] ?? 0);
        $totalPagibig    = (float) ($allowables['pagibig'] ?? 0);
        $totalPhilhealth = (float) ($allowables['philhealth'] ?? 0);

        // OTHER DEDUCTIONS total
        foreach ($this->payload['othersDeductions'] as $other) {
            $totalOtherDeductions += (float) ($other['amount'] ?? 0);
        }
        $totalOtherDeductions = round($totalOtherDeductions, 2);

        // Append standard deductions (so UI can show them)
        $this->payload['othersDeductions'][] = ['name' => 'GSIS', 'amount' => round($totalGsis, 2)];
        $this->payload['othersDeductions'][] = ['name' => 'PAGIBIG', 'amount' => round($totalPagibig, 2)];
        $this->payload['othersDeductions'][] = ['name' => 'PHILHEALTH', 'amount' => round($totalPhilhealth, 2)];

        $totalAllowableDeduction =
            ($totalGsis + $totalPagibig + $totalPhilhealth) + $totalOtherDeductions;

        $taxableIncome = ($annualBasicPay + $totalBonuses) - $totalAllowableDeduction;

        $computedAnnualTax = $service->computeAnnualTax(
            $this->employee_no,
            $taxableIncome,
            $this->payload['trainLawId'],
            $this->payload['allocation']
        );

        // Build final payload
        $payload = $this->payload;

        $payload['amounts']['annualTaxable']           = round($taxableIncome, 2);
        $payload['amounts']['annualTax']              = (float) ($computedAnnualTax['tax'] ?? 0);
        $payload['amounts']['monthlyTax']             = (float) ($computedAnnualTax['monthly_tax'] ?? 0);
        $payload['amounts']['annualTotalAllowables']  = round($totalAllowableDeduction, 2);

        $payload['amounts']['annualTotal']            = round($annualBasicPay + $totalBonuses, 2);
        $payload['amounts']['midYearBonus']           = $totalMidYear;
        $payload['amounts']['yearEndBonus']           = $totalYearEnd;
        $payload['amounts']['longevityPay']           = $totalLongevity;
        $payload['amounts']['hazardPay']              = $totalHazardPay;
        $payload['amounts']['otherEarnings']          = $totalOtherEarnings;
        $payload['amounts']['otherDeductions']        = $totalOtherDeductions;

        $payload['amounts']['portionHazardPay']       = (float) ($computedAnnualTax['allocation']['monthly']['hazard_pay'] ?? 0);
        $payload['amounts']['portionBasicPay']        = (float) ($computedAnnualTax['allocation']['monthly']['basic_pay'] ?? 0);
        $payload['amounts']['portionLongevityPay']    = (float) ($computedAnnualTax['allocation']['monthly']['longevity_pay'] ?? 0);

        $payload['midyear']  = $mid;
        $payload['year_end'] = $ye;

        // Save
        $service->createTaxationEmployees(
            $payload,
            $this->taxation_id,
            $this->employee_no,
            $computedAnnualTax ?? null
        );
    }
}
