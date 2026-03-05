<?php

namespace App\Jobs\Taxation;

use App\Services\Taxation\ForecastComputationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ForeCastEmployeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $taxation_id;
    private $employee_no;
    private array $payload;

    private int $hazardTaxId, $salaryTaxId, $longevityTaxId, $trainLawId;

    private $peraTableId, $transportationAllowanceId, $representationAllowanceId;

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
        if ($this->batch()?->cancelled()) {
            return;
        }

        $year = (int) ($this->payload['year'] ?? now()->year);

        // -----------------------------
        // Normalize payload structure
        // -----------------------------
        $payload = $this->payload;

        $payload['assumptions']      = $payload['assumptions'] ?? [];
        $payload['othersEarnings']   = $payload['othersEarnings'] ?? [];
        $payload['othersDeductions'] = $payload['othersDeductions'] ?? [];
        $payload['amounts']          = $payload['amounts'] ?? [];

        $payload['remarks']          = $payload['remarks'] ?? [];

        $assume = fn(string $key): bool => (bool) ($payload['assumptions'][$key] ?? false);

        $addRemark = function (string $message) use (&$payload): void {
            $message = trim($message);
            if ($message === '') {
                return;
            }

            // avoid duplicates
            if (!in_array($message, $payload['remarks'], true)) {
                $payload['remarks'][] = $message;
            }
        };

        $sumAmounts = function (array $rows): float {
            $total = 0.0;
            foreach ($rows as $row) {
                $total += (float) ($row['amount'] ?? 0);
            }
            return $total;
        };

        // -----------------------------
        // Basic pays
        // -----------------------------
        $basicPays = $service->annualSalaryTotalByMonth($this->employee_no, $year);

        if ($basicPays['remarks'] ?? null) {
            $addRemark("Basic Pay: " . ($basicPays['remarks'] ?? ''));
        }

        $annualBasicPay    = round((float) ($basicPays['annual_total'] ?? 0), 4);
        $monthlySalary     = round((float) ($basicPays['monthly_salary'] ?? 0), 4);
        $monthsCovered     = (int) ($basicPays['months_covered'] ?? 0);
        $monthlyHazardPay  = round((float) ($basicPays['hazard_pay'] ?? ($monthlySalary * 0.15)), 4);

        $mid = $basicPays['midyear'] ?? null;
        $ye  = $basicPays['year_end'] ?? null;

        // -----------------------------
        // Bonuses / earnings (taxable components)
        // -----------------------------
        $midYearBonus = 0.0;
        if ($assume('midYear')) {
            if (!empty($mid['eligible'])) {
                $midYearBonus = (float) ($mid['amount'] ?? 0);
            } else {
                $payload['assumptions']['midYear'] = false;

                $reason = (string) ($mid['reason'] ?? 'Not eligible for Mid-Year Bonus.');
                $asOf   = (string) ($mid['as_of'] ?? null);

                $addRemark(
                    "Mid-Year Bonus was enabled but employee is not eligible. {$reason}" .
                        ($asOf ? " (as of {$asOf})." : '')
                );
            }
        }

        $yearEndBonus = 0.0;
        if ($assume('yearEnd')) {
            if (!empty($ye['eligible'])) {
                $yearEndBonus = (float) ($ye['amount'] ?? 0);
            } else {
                $payload['assumptions']['yearEnd'] = false;

                $reason = (string) ($ye['reason'] ?? 'Not eligible for Year-End Bonus.');
                $asOf   = (string) ($ye['as_of'] ?? null);

                $addRemark(
                    "Year-End Bonus was enabled but employee is not eligible. {$reason}" .
                        ($asOf ? " (as of {$asOf})." : '')
                );
            }
        }

        $longevityPay = 0.0;
        if ($assume('longevity')) {

            $longevity = $service->ComputeLongevity(
                $this->employee_no,
                $year
            );


            $longevityPay = (float) ($longevity['longevity_total'] ?? 0);

            if (round($longevityPay, 4) <= 0) {
                $reason = (string) ($longevity['reason'] ?? 'Computed longevity pay is 0.');
                $addRemark("Longevity Pay was enabled but resulted to 0. {$reason}");
            }
        }

        $hazardPayAnnual = 0.0;
        if ($assume('hazardPay')) {
            if ($monthsCovered > 0 && $monthlyHazardPay > 0) {
                $hazardPayAnnual = $monthlyHazardPay * $monthsCovered;
            } else {
                $payload['assumptions']['hazardPay'] = false;
                $addRemark("Hazard Pay was enabled but cannot be computed (months covered or monthly hazard pay is zero).");
            }
        }

        // -----------------------------
        // Get Non Taxable allowances // PERA RATA
        // -----------------------------
        $payload['othersEarnings'] = array_merge(
            $payload['othersEarnings'] ?? [],
            $service->getNonTaxableAllowance($this->employee_no, $year)
        );

        // -----------------------------
        // Other earnings (split taxable/non-taxable)
        // -----------------------------
        $result = $this->buildOtherEarningsComputation($payload);

        $payload = $result['payload'];
        $otherEarningsTaxable = $result['otherEarningsTaxable'];
        $otherEarningsNonTaxable = $result['otherEarningsNonTaxable'];

        // Round totals
        $midYearBonus            = round($midYearBonus, 4);
        $yearEndBonus            = round($yearEndBonus, 4);
        $longevityPay            = round($longevityPay, 4);
        $hazardPayAnnual         = round($hazardPayAnnual, 4);
        $otherEarningsTaxable    = round($otherEarningsTaxable, 4);
        $otherEarningsNonTaxable = round($otherEarningsNonTaxable, 4);

        // sendYearToParent This is the amount that may be affected by RR 3-2015
        $benefitsEligibleFor90k =
            $midYearBonus +
            $yearEndBonus +
            $otherEarningsTaxable;

        $taxableBonusesExcess = 0;

        $exempt = 0.0;
        if ($assume('lessBirRR32015')) {
            $before = $benefitsEligibleFor90k;

            $taxableBonusesExcess = max($benefitsEligibleFor90k - 90000, 0);
            $exempt = $before - $taxableBonusesExcess;

            $addRemark("BIR RR 3-2015 (₱90,000) exemption applied. Exempted amount: ₱" . number_format($exempt, 2) . ".");
        } else {
            // If you’re not applying the exemption rule, then the whole bucket is taxable (depends on your business rules)
            $taxableBonusesExcess = $benefitsEligibleFor90k;
        }

        // taxable bucket should be:
        $taxableBonusesAndEarnings =
            $longevityPay +
            $hazardPayAnnual +
            $taxableBonusesExcess;

        // -----------------------------
        // Deductions
        // -----------------------------
        $allowables = $service->getAllowablesDeductions($this->employee_no, $year);

        $totalGsis       = (float) ($allowables['gsis'] ?? 0);
        $totalPagibig    = (float) ($allowables['pagibig'] ?? 0);
        $totalPhilhealth = (float) ($allowables['philhealth'] ?? 0);

        $otherDeductions = $sumAmounts($payload['othersDeductions']);

        // Append standard deductions (so UI can show them)
        $payload['othersDeductions'][] = ['name' => 'GSIS', 'amount' => round($totalGsis, 4)];
        $payload['othersDeductions'][] = ['name' => 'PAGIBIG', 'amount' => round($totalPagibig, 4)];
        $payload['othersDeductions'][] = ['name' => 'PHILHEALTH', 'amount' => round($totalPhilhealth, 4)];

        $totalAllowableDeduction = round(
            ($totalGsis + $totalPagibig + $totalPhilhealth) + $otherDeductions,
            4
        );

        $res = $this->buildAllowablesDeductionsComputation(
            $payload,
            $allowables, // pass this so we can show monthly steps
            $totalGsis,
            $totalPagibig,
            $totalPhilhealth,
            $otherDeductions,
            $totalAllowableDeduction
        );

        $allowablesComputations = $res['allowablesComputations']; // one entry

        // -----------------------------
        // Taxable income + tax
        // -----------------------------
        $taxableIncome = round(
            ($annualBasicPay - $totalAllowableDeduction) + $taxableBonusesAndEarnings,
            4
        );

        if ($taxableIncome < 0) {
            $addRemark("Computed taxable income is negative. Forced to 0.00 for tax computation.");
            $taxableIncome = 0.0;
        }

        $computedAnnualTax = $service->computeAnnualTax(
            $this->employee_no,
            $taxableIncome,
            $payload['trainLawId'],
            $payload['allocation']
        );

        $gross = round(
            $annualBasicPay +
                $midYearBonus +
                $yearEndBonus +
                $longevityPay +
                $hazardPayAnnual +
                $otherEarningsTaxable +
                $otherEarningsNonTaxable,
            4
        );

        // -----------------------------
        // Build final payload (saved)
        // -----------------------------
        $payload['amounts']['annualTaxable']         = $taxableIncome;
        $payload['amounts']['annualTax']             = (float) ($computedAnnualTax['tax'] ?? 0);
        $payload['amounts']['monthlyTax']            = (float) ($computedAnnualTax['monthly_tax'] ?? 0);
        $payload['amounts']['annualTotalAllowables'] = $totalAllowableDeduction;

        // Totals (for reporting)
        $payload['amounts']['gross']                 = $gross;
        $payload['amounts']['midYearBonus']          = $midYearBonus;
        $payload['amounts']['yearEndBonus']          = $yearEndBonus;
        $payload['amounts']['longevityPay']          = $longevityPay;
        $payload['amounts']['hazardPay']             = $hazardPayAnnual;

        $payload['amounts']['amount_total_bonuses']  = round($exempt, 4);
        $payload['amounts']['amount_bonuses_exempt'] = round($benefitsEligibleFor90k, 4);

        $payload['amounts']['otherEarningsTaxable']  = $otherEarningsTaxable;
        $payload['amounts']['otherEarningsNonTaxable'] = $otherEarningsNonTaxable;

        $payload['amounts']['otherDeductions']       = round($otherDeductions, 4);

        // Allocation (monthly portions)
        $payload['amounts']['portionHazardPay']    = (float) ($computedAnnualTax['allocation']['monthly']['hazard_pay'] ?? 0);
        $payload['amounts']['portionBasicPay']     = (float) ($computedAnnualTax['allocation']['monthly']['basic_pay'] ?? 0);
        $payload['amounts']['portionLongevityPay'] = (float) ($computedAnnualTax['allocation']['monthly']['longevity_pay'] ?? 0);

        $payload['amounts']['amount_basic_salary'] = $basicPays['monthly_salary'];
        $payload['months_covered'] = $basicPays['months_covered'];
        $payload['amounts']['amount_anual_total_basic_salary'] = $annualBasicPay;

        // Keep computed eligibility info
        $payload['midyear']  = $mid;
        $payload['year_end'] = $ye;

        $payload['computations'] = array_merge(
            $payload['computations'] ?? [],
            $basicPays['computations'] ?? [],
            $longevity['computations'] ?? [],
            $allowablesComputations ?? []
        );

        // Save
        $service->createTaxationEmployees(
            $payload,
            $this->taxation_id,
            $this->employee_no,
            $computedAnnualTax ?? null
        );
    }

    private function buildOtherEarningsComputation(array $payload): array
    {
        $otherEarningsTaxable = 0.0;
        $otherEarningsNonTaxable = 0.0;
        $allOtherComputations = [];

        foreach (($payload['othersEarnings'] ?? []) as $i => $other) {

            $amount  = (float) ($other['amount'] ?? 0);
            $taxType = $other['tax_type'] ?? 'taxable';

            $isNonTax = in_array($taxType, ['non_taxable', 'exempt'], true);

            if ($isNonTax) {
                $otherEarningsNonTaxable += $amount;
            } else {
                $otherEarningsTaxable += $amount;
            }

            if (empty($payload['othersEarnings'][$i]['computations'])) {

                $payload['othersEarnings'][$i]['computations'] = [[
                    "key" => "other_earning_item",
                    "label" => (string) ($other['name'] ?? 'Other earning'),
                    "formula" => "Direct amount entry",
                    "inputs" => [
                        "name" => (string) ($other['name'] ?? ''),
                        "tax_type" => $taxType,
                        "amount" => $amount,
                    ],
                    "steps" => [
                        ["label" => "Tax type", "value" => $taxType],
                        ["label" => "Amount", "value" => number_format($amount, 2)],
                    ],
                    "result_raw" => $amount,
                    "result" => number_format($amount, 2),
                    "meta" => [
                        "type" => "other_earning_item",
                        "is_non_taxable" => $isNonTax,
                    ],
                ]];
            }

            foreach (($payload['othersEarnings'][$i]['computations'] ?? []) as $c) {
                if (is_array($c)) {
                    $allOtherComputations[] = $c;
                }
            }
        }

        if (!empty($allOtherComputations)) {

            $itemsStepRows = array_map(function ($c) {

                $name = $c['label'] ?? ($c['inputs']['name'] ?? 'Item');
                $tax  = $c['inputs']['tax_type'] ?? '-';
                $amt  = (float) ($c['result_raw'] ?? 0);

                return [
                    "label" => "{$name} (" . strtoupper((string) $tax) . ")",
                    "value" => number_format($amt, 2),
                ];
            }, $allOtherComputations);

            $grandTotal = $otherEarningsTaxable + $otherEarningsNonTaxable;

            $payload['computations'][] = [
                "key" => "other_earnings",
                "label" => "Other Earnings",
                "formula" => "Sum of other earnings (taxable + non-taxable)",
                "inputs" => [
                    "taxable_total" => $otherEarningsTaxable,
                    "non_taxable_total" => $otherEarningsNonTaxable,
                    "grand_total" => $grandTotal,
                    "items_count" => count($allOtherComputations),
                ],
                "steps" => [
                    [
                        "label" => "Items",
                        "value" => $itemsStepRows,
                    ],
                    [
                        "label" => "Taxable total",
                        "value" => number_format($otherEarningsTaxable, 2),
                    ],
                    [
                        "label" => "Non-taxable total",
                        "value" => number_format($otherEarningsNonTaxable, 2),
                    ],
                    [
                        "label" => "Grand total",
                        "value" => number_format($grandTotal, 2),
                    ],
                ],
                "result_raw" => $grandTotal,
                "result" => number_format($grandTotal, 2),
                "meta" => [
                    "type" => "other_earnings",
                ],
                "computations" => $allOtherComputations
            ];
        }

        return [
            'payload' => $payload,
            'otherEarningsTaxable' => $otherEarningsTaxable,
            'otherEarningsNonTaxable' => $otherEarningsNonTaxable,
        ];
    }

    private function buildAllowablesDeductionsComputation(
        array $payload,
        array $allowables,
        float $totalGsis,
        float $totalPagibig,
        float $totalPhilhealth,
        float $otherDeductions,
        float $totalAllowableDeduction
    ): array {
        $allItemComputations = [];

        $norm = function (string $name): string {
            $k = strtolower(trim($name));
            return str_replace([' ', '_', '-'], '', $k);
        };

        $monthLabel = function (int $m): string {
            return date('F', mktime(0, 0, 0, $m, 1));
        };

        $monthlyFromAllowables = $allowables['monthly'] ?? [];

        // helper to build monthly steps
        $buildMonthlySteps = function (array $rows) use ($monthLabel): array {
            // rows could be: [ ['month'=>1,'label'=>'January','amount'=>...], ... ]
            $out = [];
            foreach ($rows as $r) {
                $label = (string) ($r['label'] ?? ($r['month'] ?? null ? $monthLabel((int) $r['month']) : 'Month'));
                $amt   = (float) ($r['amount'] ?? 0);
                $out[] = ["label" => $label, "value" => number_format($amt, 2)];
            }
            return $out;
        };

        // Build a STANDARD computation (GSIS/PAGIBIG/PHILHEALTH) with monthly breakdown if available
        $buildStandardComputation = function (string $label, string $stdKey, float $total) use ($monthlyFromAllowables, $buildMonthlySteps): array {
            $monthly = $monthlyFromAllowables[$stdKey] ?? [];

            $steps = [];
            if (!empty($monthly)) {
                $steps[] = [
                    "label" => "{$label} monthly (Jan–Dec)",
                    "value" => $buildMonthlySteps($monthly),
                ];
            }

            $steps[] = ["label" => "{$label} total", "value" => number_format($total, 2)];

            return [
                "key" => "allowable_deduction_standard",
                "label" => $label,
                "formula" => "Sum of monthly amounts",
                "inputs" => [
                    "name" => $label,
                    "module" => "allowables",
                    "monthly" => $monthly,
                    "total" => $total,
                ],
                "steps" => $steps,
                "result_raw" => $total,
                "result" => number_format($total, 2),
                "meta" => [
                    "type" => "allowable_deduction_standard",
                ],
            ];
        };

        // 1) Build computations for OTHER items (direct)
        foreach (($payload['othersDeductions'] ?? []) as $i => $row) {

            $name   = (string) ($row['name'] ?? 'Deduction');
            $amount = (float) ($row['amount'] ?? 0);
            $key    = $norm($name);

            // Skip standards here; we'll add them using module computations below
            if (in_array($key, ['gsis', 'pagibig', 'philhealth'], true)) {
                continue;
            }

            if (empty($payload['othersDeductions'][$i]['computations'])) {
                $payload['othersDeductions'][$i]['computations'] = [[
                    "key" => "allowable_deduction_item",
                    "label" => $name,
                    "formula" => "Direct amount entry",
                    "inputs" => [
                        "name" => $name,
                        "amount" => $amount,
                    ],
                    "steps" => [
                        ["label" => "Amount", "value" => number_format($amount, 2)],
                    ],
                    "result_raw" => $amount,
                    "result" => number_format($amount, 2),
                    "meta" => [
                        "type" => "allowable_deduction_item",
                    ],
                ]];
            }

            foreach (($payload['othersDeductions'][$i]['computations'] ?? []) as $c) {
                if (is_array($c)) {
                    $allItemComputations[] = $c;
                }
            }
        }

        // 2) Add STANDARD computations (with monthly)
        $allItemComputations[] = $buildStandardComputation('GSIS', 'gsis', $totalGsis);
        $allItemComputations[] = $buildStandardComputation('PAGIBIG', 'pagibig', $totalPagibig);
        $allItemComputations[] = $buildStandardComputation('PHILHEALTH', 'philhealth', $totalPhilhealth);

        // 3) Parent computation (ONE entry)
        $itemsStepRows = array_map(function ($c) {
            $name = $c['label'] ?? ($c['inputs']['name'] ?? 'Item');
            $amt  = (float) ($c['result_raw'] ?? 0);

            return [
                "label" => (string) $name,
                "value" => number_format($amt, 2),
            ];
        }, $allItemComputations);

        $parent = [
            "key" => "allowables_deductions",
            "label" => "Allowable Deductions",
            "formula" => "Other deductions + GSIS + PAGIBIG + PHILHEALTH",
            "inputs" => [
                "gsis" => $totalGsis,
                "pagibig" => $totalPagibig,
                "philhealth" => $totalPhilhealth,
                "other_deductions" => $otherDeductions,
                "grand_total" => $totalAllowableDeduction,
                "items_count" => count($allItemComputations),
            ],
            "steps" => [
                ["label" => "Items", "value" => $itemsStepRows],
                ["label" => "Other deductions total", "value" => number_format($otherDeductions, 2)],
                ["label" => "GSIS total", "value" => number_format($totalGsis, 2)],
                ["label" => "PAGIBIG total", "value" => number_format($totalPagibig, 2)],
                ["label" => "PHILHEALTH total", "value" => number_format($totalPhilhealth, 2)],
                ["label" => "Total allowables", "value" => number_format($totalAllowableDeduction, 2)],
            ],
            "result_raw" => $totalAllowableDeduction,
            "result" => number_format($totalAllowableDeduction, 2),
            "meta" => [
                "type" => "allowables",
            ],
            // drill-down computations per item (how each is computed)
            "computations" => $allItemComputations,
        ];

        $payload['computations'][] = $parent;

        return [
            'payload' => $payload,
            // ONE entry only for your external merge
            'allowablesComputations' => [$parent],
        ];
    }
}
