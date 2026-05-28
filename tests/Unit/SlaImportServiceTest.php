<?php

namespace Tests\Unit;

use App\Services\EmployeeService;
use App\Services\Import\SlaImportService;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class SlaImportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_clean_regular_accepts_new_sla_template_headers(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'sla-import-') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['NAME', 'Subsistence Allowance (22 Days)', 'Laundry Allow. P 500', 'GROSS AMOUNT', "Deduction Late/UTs per DOST AO No. 003", 'Food Deductions', 'Less: Health Card c/o TAPIEA', 'TOTAL SLA'],
            ['DOE, JANE', 3300, 500, 3800, 25, 350, 100, 3325],
        ], null, 'A3');

        (new Xlsx($spreadsheet))->save($filePath);

        $employeeService = Mockery::mock(EmployeeService::class);
        $employeeService->shouldReceive('getEmployeeNoBasedOnFullName')
            ->once()
            ->with('DOE, JANE')
            ->andReturn(null);

        try {
            $result = (new SlaImportService($employeeService))->cleanRegular($filePath);
        } finally {
            @unlink($filePath);
        }

        $this->assertCount(1, $result['rows']);
        $this->assertSame('DOE, JANE', $result['rows'][0]['Name']);
        $this->assertSame(3800, $result['rows'][0]['Total SLA']);
        $this->assertSame(350, $result['rows'][0]['Uniform Deduction']);
        $this->assertSame(3325, $result['rows'][0]['Net Amount']);
        $this->assertSame('GROSS AMOUNT', $result['preview_headers']['Total SLA']);
        $this->assertSame('Food Deductions', $result['preview_headers']['Uniform Deduction']);
        $this->assertSame('TOTAL SLA', $result['preview_headers']['Net Amount']);
    }

    public function test_clean_regular_still_reads_existing_sla_template_headers(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'sla-import-') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['No.', 'NAME', 'Subsistence Allowance (22 Days)', 'Laundry Allow. P 500', 'TOTAL SLA', "Deduction Late/UTs per DOST AO No. 003", 'Uniform Deduction', 'Less: Health Card c/o TAPIEA', 'Net Amount'],
            [1, 'DOE, JANE', 3300, 500, 3800, 25, 350, 100, 3375],
        ], null, 'A3');

        (new Xlsx($spreadsheet))->save($filePath);

        $employeeService = Mockery::mock(EmployeeService::class);
        $employeeService->shouldReceive('getEmployeeNoBasedOnFullName')
            ->once()
            ->with('DOE, JANE')
            ->andReturn(null);

        try {
            $result = (new SlaImportService($employeeService))->cleanRegular($filePath);
        } finally {
            @unlink($filePath);
        }

        $this->assertCount(1, $result['rows']);
        $this->assertSame('DOE, JANE', $result['rows'][0]['Name']);
        $this->assertSame(3800, $result['rows'][0]['Total SLA']);
        $this->assertSame(350, $result['rows'][0]['Uniform Deduction']);
        $this->assertSame(3375, $result['rows'][0]['Net Amount']);
    }
}
