<?php

namespace App\Jobs\Taxation;

use App\Services\Taxation\ForecastComputationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
    public function handle(ForecastComputationService $run_forecast_computation_service): void
    {
        $basic_pays = $run_forecast_computation_service
            ->annualSalaryTotalByMonth($this->employee_no, (int) $this->payload['year']);

        $_TOTAL_ANNUAL_BASIC_PAY = (float) ($basic_pays['annual_total'] ?? 0);

        // Avg monthly salary based on months covered (already computed by service)
        $salary = (float) ($basic_pays['monthly_salary'] ?? 0);

        // Hazard pay monthly (service already computed; fallback just in case)
        $hazard_pay = (float) ($basic_pays['hazard_pay'] ?? ($salary * 0.15));

        // Init totals
        $_TOTAL_MID_YEAR   = 0.00;
        $_TOTAL_YEAR_END   = 0.00;
        $_TOTAL_LONGEVITY  = 0.00;
        $_TOTAL_HAZARD_PAY = 0.00;

        $_TOTAL_GSIS       = 0.00;
        $_TOTAL_PAGIBIG    = 0.00;
        $_TOTAL_PHILHEALTH = 0.00;

        /**
         * MIDYEAR
         */
        if (!empty($this->payload['assumptions']['midYear']) && $this->payload['assumptions']['midYear'] === true) {
            $mid = $basic_pays['midyear'] ?? null;

            if (!empty($mid) && !empty($mid['eligible'])) {
                $_TOTAL_MID_YEAR = (float) ($mid['amount'] ?? 0);
            } else {
                // turn off assumption if not eligible
                $this->payload['assumptions']['midYear'] = false;
            }
        }

        /**
         * YEAR-END (FIXED: correct key year_end)
         */
        if (!empty($this->payload['assumptions']['yearEnd']) && $this->payload['assumptions']['yearEnd'] === true) {
            $ye = $basic_pays['year_end'] ?? null;

            if (!empty($ye) && !empty($ye['eligible'])) {
                $_TOTAL_YEAR_END = (float) ($ye['amount'] ?? 0);
            } else {
                $this->payload['assumptions']['yearEnd'] = false;
            }
        }

        /**
         * LONGEVITY
         */
        if (!empty($this->payload['assumptions']['longevity']) && $this->payload['assumptions']['longevity'] === true) {
            $longevity = $run_forecast_computation_service->ComputeLongevity(
                $this->employee_no,
                (int) $this->payload['year'],
                $this->longevityTaxId
            );

            $_TOTAL_LONGEVITY = (float) ($longevity['longevity_total'] ?? 0);
        }

        /**
         * HAZARD PAY (annual total hazard = hazard monthly * months covered)
         */
        if (!empty($this->payload['assumptions']['hazardPay']) && $this->payload['assumptions']['hazardPay'] === true) {
            $monthsCovered = (int) ($basic_pays['months_covered'] ?? 0);
            $_TOTAL_HAZARD_PAY = (float) ($hazard_pay * $monthsCovered);
        }

        /**
         * Ensure 2 decimals (important for final inserts / logs)
         */
        $_TOTAL_ANNUAL_BASIC_PAY = round($_TOTAL_ANNUAL_BASIC_PAY, 2);
        $salary                 = round($salary, 2);
        $hazard_pay             = round($hazard_pay, 2);

        $_TOTAL_MID_YEAR          = round($_TOTAL_MID_YEAR, 2);
        $_TOTAL_YEAR_END          = round($_TOTAL_YEAR_END, 2);
        $_TOTAL_LONGEVITY         = round($_TOTAL_LONGEVITY, 2);
        $_TOTAL_HAZARD_PAY        = round($_TOTAL_HAZARD_PAY, 2);


        $TOTAL_BONUSES =
            $_TOTAL_MID_YEAR +
            $_TOTAL_YEAR_END +
            $_TOTAL_LONGEVITY +
            $_TOTAL_HAZARD_PAY;

        if (
            !empty($this->payload['assumptions']['lessBirRR32015'])
            && $this->payload['assumptions']['lessBirRR32015'] === true
        ) {

            $TOTAL_BONUSES = max($TOTAL_BONUSES - 90000, 0);
        }

        /**
         * get allowables, GIS, PAGIBIG, PHILHEALTH
         */
        $allowables = $run_forecast_computation_service->getAllowablesDeductions($this->employee_no, $this->payload['year']);

        $_TOTAL_GSIS = $allowables['gsis'];
        $_TOTAL_PAGIBIG = $allowables['pagibig'];
        $_TOTAL_PHILHEALTH = $allowables['philhealth'];

        $TOTAL_ALLOWABLE_DEDUCTION = $_TOTAL_GSIS + $_TOTAL_PAGIBIG + $_TOTAL_PHILHEALTH;

        $TAXABLE_INCOME = $_TOTAL_ANNUAL_BASIC_PAY + $TOTAL_BONUSES - $TOTAL_ALLOWABLE_DEDUCTION;

        Log::info($TAXABLE_INCOME);
        Log::info('----------------------------------------------');


        $computedAnnualTax = $run_forecast_computation_service->computeAnnualTax($this->employee_no, $TAXABLE_INCOME, $this->payload['trainLawId']);

        Log::info($computedAnnualTax);

        // $this->payload['deductions']['gsis'] = $_TOTAL_GSIS;
        // $this->payload['deductions']['philhealth'] = $_TOTAL_PAGIBIG;
        // $this->payload['deductions']['pagibig'] = $_TOTAL_PHILHEALTH;


        /**
         * Create taxation employee row
         */
        $taxation_employee_id = $run_forecast_computation_service->createTaxationEmployees(
            $this->payload,
            $this->taxation_id,
            $this->employee_no
        );

        /**
         * Debug logging
         */
        // $this->logForecastDebugBreakdown(
        //     $taxation_employee_id,
        //     $basic_pays,
        //     $TOTAL_ANNUAL_BASIC_PAY,
        //     $salary,
        //     $hazard_pay,
        //     $TOTAL_MID_YEAR,
        //     $TOTAL_YEAR_END,
        //     $TOTAL_LONGEVITY,
        //     $TOTAL_HAZARD_PAY
        // );
    }


    private function logForecastDebugBreakdown(
        $taxation_employee_id,
        array $basic_pays,
        float $TOTAL_ANNUAL_BASIC_PAY,
        float $salary,
        float $hazard_pay,
        float $TOTAL_MID_YEAR,
        float $TOTAL_YEAR_END,
        float $TOTAL_LONGEVITY,
        float $TOTAL_HAZARD_PAY
    ): void {
        $monthsCovered = $basic_pays['months_covered'] ?? 'N/A';

        $midYearFlag   = !empty($this->payload['assumptions']['midYear']) ? 'TRUE' : 'FALSE';
        $yearEndFlag   = !empty($this->payload['assumptions']['yearEnd']) ? 'TRUE' : 'FALSE';
        $longevityFlag = !empty($this->payload['assumptions']['longevity']) ? 'TRUE' : 'FALSE';
        $hazardFlag    = !empty($this->payload['assumptions']['hazardPay']) ? 'TRUE' : 'FALSE';

        $midYearAmount = $basic_pays['midyear']['amount'] ?? 'N/A';
        $midYearElig   = $basic_pays['midyear']['eligible'] ?? 'N/A';

        $yearEndAmount = $basic_pays['year_end']['amount'] ?? 'N/A';
        $yearEndElig   = $basic_pays['year_end']['eligible'] ?? 'N/A';

        Log::info("
        ================= FORECAST DEBUG BREAKDOWN =================

        [IDENTIFIERS]
        Taxation Employee ID : {$taxation_employee_id}
        Taxation ID          : {$this->taxation_id}
        Employee No          : {$this->employee_no}
        Year                 : {$this->payload['year']}

        ------------------------------------------------------------

        [RAW BASIC PAY DATA]
        Annual Total Basic Pay   : {$TOTAL_ANNUAL_BASIC_PAY}
        Monthly Salary (Adjusted): {$salary}
        Hazard Pay (Monthly)     : {$hazard_pay}
        Months Covered           : {$monthsCovered}

        ------------------------------------------------------------

        [ASSUMPTIONS FLAGS]
        MidYear     : {$midYearFlag}
        YearEnd     : {$yearEndFlag}
        Longevity   : {$longevityFlag}
        HazardPay   : {$hazardFlag}

        ------------------------------------------------------------

        [MIDYEAR BREAKDOWN]
        Midyear Amount      : {$midYearAmount}
        Midyear Eligibility : {$midYearElig}

        ------------------------------------------------------------

        [YEAREND BREAKDOWN]
        YearEnd Amount      : {$yearEndAmount}
        YearEnd Eligibility : {$yearEndElig}

        ------------------------------------------------------------

        [LONGETIVITY RAW]
        Longevity Tax ID : {$this->longevityTaxId}

        ------------------------------------------------------------

        [COMPUTED TOTALS]
        TOTAL_MID_YEAR    : {$TOTAL_MID_YEAR}
        TOTAL_YEAR_END    : {$TOTAL_YEAR_END}
        TOTAL_LONGEVITY   : {$TOTAL_LONGEVITY}
        TOTAL_HAZARD_PAY  : {$TOTAL_HAZARD_PAY}

        ------------------------------------------------------------

        ============================================================
        ");
    }
}
