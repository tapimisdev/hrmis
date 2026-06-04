<?php

namespace Tests\Unit\Taxation;

use App\Services\Taxation\IndividualTaxDataService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class IndividualTaxDataServiceTest extends TestCase
{
    public function test_tax_module_breakdown_uses_payroll_withholding_tax_and_forecasts_missing_payroll(): void
    {
        $service = new IndividualTaxDataService();

        $breakdown = $service->buildTaxModuleBreakdown(
            'EMP-001',
            2026,
            $this->monthlyBreakdown(),
            ['salary' => 50, 'hazard_pay' => 30, 'longevity' => 20],
            ['annual_tax_due' => 550.00]
        );

        $this->assertCount(12, $breakdown);

        $january = $breakdown->firstWhere('month_number', 1);
        $february = $breakdown->firstWhere('month_number', 2);
        $march = $breakdown->firstWhere('month_number', 3);

        $this->assertSame(150.25, $january['amount']);
        $this->assertSame(289.75, $february['amount']);
        $this->assertSame(110.00, $march['amount']);
        $april = $breakdown->firstWhere('month_number', 4);
        $this->assertSame(0.00, $april['amount']);

        $this->assertSame(100.25, $this->itemAmount($january['items'], 'Salary Tax'));
        $this->assertSame(50.00, $this->itemAmount($january['items'], 'Hazard Pay Tax'));
        $this->assertSame(0.00, $this->itemAmount($january['items'], 'Longevity Tax'));

        $this->assertSame(174.75, $this->itemAmount($february['items'], 'Salary Tax'));
        $this->assertSame(115.00, $this->itemAmount($february['items'], 'Hazard Pay Tax'));
        $this->assertSame(0.00, $this->itemAmount($february['items'], 'Longevity Tax'));

        $this->assertSame(0.00, $this->itemAmount($march['items'], 'Salary Tax'));
        $this->assertSame(0.00, $this->itemAmount($march['items'], 'Hazard Pay Tax'));
        $this->assertSame(110.00, $this->itemAmount($march['items'], 'Longevity Tax'));

        $this->assertSame(0.00, $this->itemAmount($april['items'], 'Salary Tax'));
        $this->assertSame(0.00, $this->itemAmount($april['items'], 'Hazard Pay Tax'));
        $this->assertSame(0.00, $this->itemAmount($april['items'], 'Longevity Tax'));

        $this->assertSame('actual', $this->itemSource($january['items'], 'Salary Tax'));
        $this->assertSame('actual', $this->itemSource($january['items'], 'Hazard Pay Tax'));
        $this->assertSame('forecast', $this->itemSource($february['items'], 'Salary Tax'));
        $this->assertSame('forecast', $this->itemSource($february['items'], 'Hazard Pay Tax'));
        $this->assertSame('forecast', $this->itemSource($march['items'], 'Longevity Tax'));
        $this->assertSame('forecast', $this->itemSource($april['items'], 'Hazard Pay Tax'));

        $this->assertSame(275.00, round($this->sumItemAmounts($breakdown, 'Salary Tax'), 2));
        $this->assertSame(165.00, round($this->sumItemAmounts($breakdown, 'Hazard Pay Tax'), 2));
        $this->assertSame(110.00, round($this->sumItemAmounts($breakdown, 'Longevity Tax'), 2));
        $this->assertSame(550.00, round($breakdown->sum('amount'), 2));
    }

    public function test_tax_module_breakdown_forecasts_salary_when_no_payroll_withholding_tax_exists(): void
    {
        $service = new IndividualTaxDataService();

        $breakdown = $service->buildTaxModuleBreakdown(
            'EMP-001',
            2026,
            $this->emptyMonthlyBreakdown(),
            ['salary' => 70, 'hazard_pay' => 20, 'longevity' => 10],
            ['annual_tax_due' => 120.00]
        );

        $december = $breakdown->firstWhere('month_number', 12);

        $this->assertSame(84.00, round($this->sumItemAmounts($breakdown, 'Salary Tax'), 2));
        $this->assertSame(24.00, round($this->sumItemAmounts($breakdown, 'Hazard Pay Tax'), 2));
        $this->assertSame(12.00, round($this->sumItemAmounts($breakdown, 'Longevity Tax'), 2));
        $this->assertSame(120.00, $december['amount']);
        $this->assertSame('forecast', $this->itemSource($december['items'], 'Salary Tax'));
    }

    private function monthlyBreakdown(): Collection
    {
        $rows = collect(range(1, 12))->map(function (int $month) {
            return [
                'month_number' => $month,
                'basic_salary' => 0.0,
                'hazard_pay' => 0.0,
                'longevity_pay' => 0.0,
                'salary_tax_withheld' => 0.0,
                'hazard_tax_withheld' => 0.0,
                'longevity_tax_withheld' => 0.0,
                'source_breakdown' => [
                    'basic_salary' => 'forecast',
                    'hazard_pay' => 'forecast',
                    'longevity_pay' => 'forecast',
                ],
            ];
        });

        $rows[0] = [
            'month_number' => 1,
            'basic_salary' => 100.0,
            'hazard_pay' => 50.0,
            'longevity_pay' => 0.0,
            'salary_tax_withheld' => 100.25,
            'hazard_tax_withheld' => 50.0,
            'longevity_tax_withheld' => 0.0,
            'source_breakdown' => [
                'basic_salary' => 'completed',
                'hazard_pay' => 'completed',
                'longevity_pay' => 'forecast',
            ],
        ];

        $rows[1] = [
            'month_number' => 2,
            'basic_salary' => 300.0,
            'hazard_pay' => 50.0,
            'longevity_pay' => 0.0,
            'salary_tax_withheld' => 0.0,
            'hazard_tax_withheld' => 0.0,
            'longevity_tax_withheld' => 0.0,
            'source_breakdown' => [
                'basic_salary' => 'forecast',
                'hazard_pay' => 'forecast',
                'longevity_pay' => 'forecast',
            ],
        ];

        $rows[2] = [
            'month_number' => 3,
            'basic_salary' => 0.0,
            'hazard_pay' => 0.0,
            'longevity_pay' => 50.0,
            'salary_tax_withheld' => 0.0,
            'hazard_tax_withheld' => 0.0,
            'longevity_tax_withheld' => 0.0,
            'source_breakdown' => [
                'basic_salary' => 'forecast',
                'hazard_pay' => 'forecast',
                'longevity_pay' => 'forecast',
            ],
        ];

        return $rows->values();
    }

    private function emptyMonthlyBreakdown(): Collection
    {
        return collect(range(1, 12))->map(fn (int $month) => [
            'month_number' => $month,
            'basic_salary' => 0.0,
            'hazard_pay' => 0.0,
            'longevity_pay' => 0.0,
            'salary_tax_withheld' => 0.0,
            'hazard_tax_withheld' => 0.0,
            'longevity_tax_withheld' => 0.0,
            'source_breakdown' => [
                'basic_salary' => 'forecast',
                'hazard_pay' => 'forecast',
                'longevity_pay' => 'forecast',
            ],
        ]);
    }

    private function itemAmount(array $items, string $name): float
    {
        return (float) collect($items)->firstWhere('name', $name)['amount'];
    }

    private function itemSource(array $items, string $name): string
    {
        return (string) collect($items)->firstWhere('name', $name)['source'];
    }

    private function sumItemAmounts(Collection $breakdown, string $name): float
    {
        return (float) $breakdown->sum(function (array $row) use ($name) {
            return $this->itemAmount((array) ($row['items'] ?? []), $name);
        });
    }
}
