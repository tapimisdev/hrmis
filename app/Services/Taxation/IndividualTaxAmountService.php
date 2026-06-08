<?php

namespace App\Services\Taxation;

class IndividualTaxAmountService
{
    public function __construct(
        private readonly IndividualTaxDataService $individualTaxDataService
    ) {}

    public function getAmount(string $employeeNo, int $month, int $year): array
    {
        $normalizedEmployeeNo = trim($employeeNo);

        if ($normalizedEmployeeNo === '' || $month < 1 || $month > 12 || $year < 1000 || $year > 9999) {
            return $this->emptyBreakdown();
        }

        $payload = $this->individualTaxDataService->getPagePayload($normalizedEmployeeNo, $year);
        $resolvedEmployeeNo = (string) data_get($payload, 'employee.employee_no', '');

        if ($resolvedEmployeeNo !== $normalizedEmployeeNo) {
            return $this->emptyBreakdown();
        }

        $taxModuleRow = collect((array) ($payload['taxModuleBreakdown'] ?? []))
            ->firstWhere('month_number', $month);
        $monthlyBreakdownRow = collect((array) ($payload['monthlyBreakdown'] ?? []))
            ->firstWhere('month_number', $month);

        return [
            'hazard_pay' => $this->buildComponentPayload(
                (array) $taxModuleRow,
                (array) $monthlyBreakdownRow,
                'Hazard Pay Tax',
                'hazard_pay'
            ),
            'longevity' => $this->buildComponentPayload(
                (array) $taxModuleRow,
                (array) $monthlyBreakdownRow,
                'Longevity Tax',
                'longevity'
            ),
            'salary' => $this->buildComponentPayload(
                (array) $taxModuleRow,
                (array) $monthlyBreakdownRow,
                'Salary Tax',
                'salary'
            ),
            'total' => (float) round((float) data_get($taxModuleRow, 'amount', 0), 2),
        ];
    }

    private function buildComponentPayload(
        array $taxModuleRow,
        array $monthlyBreakdownRow,
        string $name,
        string $componentKey
    ): array {
        $item = collect((array) ($taxModuleRow['items'] ?? []))
            ->firstWhere('name', $name);
        $amount = (float) round((float) data_get($item, 'amount', 0), 2);
        $itemSource = strtolower(trim((string) data_get($item, 'source', 'forecast')));
        $sourceBreakdownKey = match ($componentKey) {
            'hazard_pay' => 'hazard_pay',
            'longevity' => 'longevity_pay',
            default => 'basic_salary',
        };
        $payrollStatus = strtolower(trim((string) data_get($monthlyBreakdownRow, "source_breakdown.{$sourceBreakdownKey}", '')));
        $status = $payrollStatus !== '' && $payrollStatus !== 'forecast'
            ? $payrollStatus
            : $this->formatIndividualTaxStatus($itemSource);

        return [
            'amount' => $amount,
            'status' => $status,
        ];
    }

    private function formatIndividualTaxStatus(string $source): string
    {
        return match ($source) {
            'actual' => 'actual',
            'override' => 'override',
            default => 'forecasted',
        };
    }

    private function emptyBreakdown(): array
    {
        return [
            'hazard_pay' => [
                'amount' => 0.0,
                'status' => 'forecasted',
            ],
            'longevity' => [
                'amount' => 0.0,
                'status' => 'forecasted',
            ],
            'salary' => [
                'amount' => 0.0,
                'status' => 'forecasted',
            ],
            'total' => 0.0,
        ];
    }
}
