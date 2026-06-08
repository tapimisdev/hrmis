<?php

namespace Tests\Unit\Taxation;

use App\Services\Taxation\IndividualTaxAmountService;
use App\Services\Taxation\IndividualTaxDataService;
use App\Services\Taxation\IndividualTaxMonthlyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class IndividualTaxMonthlyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_page_payload_builds_rows_from_shared_amount_service(): void
    {
        $dataService = \Mockery::mock(IndividualTaxDataService::class);
        $amountService = \Mockery::mock(IndividualTaxAmountService::class);

        $dataService->shouldReceive('getAvailableTaxationYears')
            ->once()
            ->andReturn(collect([2026]));
        $dataService->shouldReceive('resolveSelectedYear')
            ->once()
            ->with(2026, \Mockery::type(Collection::class))
            ->andReturn(2026);
        $dataService->shouldReceive('getPagePayload')
            ->once()
            ->with(null, 2026)
            ->andReturn([
                'employees' => [
                    (object) [
                        'employee_no' => 'EMP-001',
                        'display_name' => 'ALICE CRUZ',
                        'position' => 'Accountant',
                        'division_name' => 'Finance Division',
                        'unit_name' => 'Payroll Unit',
                    ],
                ],
                'hasTaxationData' => true,
            ]);
        $dataService->shouldReceive('buildMonthlyBreakdown')
            ->once()
            ->with('EMP-001', 2026)
            ->andReturn(collect([
                [
                    'month_number' => 3,
                    'basic_salary' => 35000,
                ],
            ]));
        $amountService->shouldReceive('getAmount')
            ->once()
            ->with('EMP-001', 3, 2026)
            ->andReturn([
                'salary' => [
                    'amount' => 22045.52,
                    'status' => 'completed',
                ],
                'hazard_pay' => [
                    'amount' => 0,
                    'status' => 'forecasted',
                ],
                'longevity' => [
                    'amount' => 0,
                    'status' => 'forecasted',
                ],
                'total' => 22045.52,
            ]);

        $service = new IndividualTaxMonthlyReportService($dataService, $amountService);

        $payload = $service->getPagePayload(3, 2026);

        $this->assertTrue($payload['hasTaxationData']);
        $this->assertSame(2026, $payload['selectedYear']);
        $this->assertSame(3, $payload['selectedMonth']);
        $this->assertCount(1, $payload['rows']);
        $this->assertSame('EMP-001', $payload['rows'][0]['employee_no']);
        $this->assertSame('ALICE CRUZ', $payload['rows'][0]['employee_name']);
        $this->assertSame('Php 35,000.00', $payload['rows'][0]['salary_display']);
        $this->assertSame(22045.52, $payload['rows'][0]['salary_tax']['amount']);
        $this->assertSame('completed', $payload['rows'][0]['salary_tax']['status']);
        $this->assertSame('Completed', $payload['rows'][0]['salary_tax']['status_label']);
        $this->assertSame(0.0, $payload['rows'][0]['hazard_pay_tax']['amount']);
        $this->assertSame('forecasted', $payload['rows'][0]['hazard_pay_tax']['status']);
        $this->assertSame(22045.52, $payload['rows'][0]['total_tax']);
    }

    public function test_get_page_payload_returns_empty_rows_when_taxation_has_no_employees(): void
    {
        $dataService = \Mockery::mock(IndividualTaxDataService::class);
        $amountService = \Mockery::mock(IndividualTaxAmountService::class);

        $dataService->shouldReceive('getAvailableTaxationYears')
            ->once()
            ->andReturn(collect([2026]));
        $dataService->shouldReceive('resolveSelectedYear')
            ->once()
            ->andReturn(2026);
        $dataService->shouldReceive('getPagePayload')
            ->once()
            ->with(null, 2026)
            ->andReturn([
                'employees' => [],
                'hasTaxationData' => true,
            ]);

        $service = new IndividualTaxMonthlyReportService($dataService, $amountService);

        $payload = $service->getPagePayload(3, 2026);

        $this->assertTrue($payload['hasTaxationData']);
        $this->assertSame([], $payload['rows']);
    }

    public function test_resolve_selected_month_defaults_to_current_month(): void
    {
        $service = new IndividualTaxMonthlyReportService(
            app(IndividualTaxDataService::class),
            app(IndividualTaxAmountService::class)
        );

        $this->assertSame((int) now()->month, $service->resolveSelectedMonth(null));
    }
}
