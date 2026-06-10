<?php

namespace Tests\Unit\Taxation;

use App\Services\Taxation\IndividualTaxAmountService;
use App\Services\Taxation\IndividualTaxDataService;
use Tests\TestCase;

class IndividualTaxAmountServiceTest extends TestCase
{
    public function test_get_amount_returns_tax_module_breakdown_for_employee_month_and_year(): void
    {
        $dataService = \Mockery::mock(IndividualTaxDataService::class);
        $dataService->shouldReceive('getPagePayload')
            ->once()
            ->with('EMP-001', 2026)
            ->andReturn([
                'employee' => (object) [
                    'employee_no' => 'EMP-001',
                ],
                'monthlyBreakdown' => [
                    [
                        'month_number' => 5,
                        'source_breakdown' => [
                            'basic_salary' => 'completed',
                            'hazard_pay' => 'forecast',
                            'longevity_pay' => 'forecast',
                        ],
                    ],
                ],
                'taxModuleBreakdown' => [
                    [
                        'month_number' => 5,
                        'amount' => 1234.567,
                        'items' => [
                            [
                                'name' => 'Salary Tax',
                                'amount' => 1000.111,
                                'source' => 'actual',
                            ],
                            [
                                'name' => 'Hazard Pay Tax',
                                'amount' => 200.222,
                                'source' => 'forecast',
                            ],
                            [
                                'name' => 'Longevity Tax',
                                'amount' => 34.234,
                                'source' => 'override',
                            ],
                        ],
                    ],
                ],
            ]);

        $service = new IndividualTaxAmountService($dataService);

        $amount = $service->getAmount('EMP-001', 5, 2026);

        $this->assertSame([
            'hazard_pay' => [
                'amount' => 200.22,
                'status' => 'forecasted',
            ],
            'longevity' => [
                'amount' => 34.23,
                'status' => 'override',
            ],
            'salary' => [
                'amount' => 1000.11,
                'status' => 'completed',
            ],
            'total' => 1234.57,
        ], $amount);
    }

    public function test_get_amount_returns_zero_when_employee_no_is_not_resolved(): void
    {
        $dataService = \Mockery::mock(IndividualTaxDataService::class);
        $dataService->shouldReceive('getPagePayload')
            ->once()
            ->with('EMP-999', 2026)
            ->andReturn([
                'employee' => (object) [
                    'employee_no' => 'EMP-001',
                ],
                'monthlyBreakdown' => [],
                'taxModuleBreakdown' => [
                    [
                        'month_number' => 5,
                        'amount' => 999.99,
                    ],
                ],
            ]);

        $service = new IndividualTaxAmountService($dataService);

        $amount = $service->getAmount('EMP-999', 5, 2026);

        $this->assertSame([
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
        ], $amount);
    }

    public function test_get_amount_returns_zero_for_invalid_inputs_or_missing_month(): void
    {
        $dataService = \Mockery::mock(IndividualTaxDataService::class);
        $dataService->shouldReceive('getPagePayload')
            ->once()
            ->with('EMP-001', 2026)
            ->andReturn([
                'employee' => (object) [
                    'employee_no' => 'EMP-001',
                ],
                'taxModuleBreakdown' => [],
            ]);

        $service = new IndividualTaxAmountService($dataService);

        $expected = [
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

        $this->assertSame($expected, $service->getAmount('', 5, 2026));
        $this->assertSame($expected, $service->getAmount('EMP-001', 0, 2026));
        $this->assertSame($expected, $service->getAmount('EMP-001', 13, 2026));
        $this->assertSame($expected, $service->getAmount('EMP-001', 5, 999));
        $this->assertSame($expected, $service->getAmount('EMP-001', 5, 2026));
    }
}
