<?php

namespace Tests\Feature\Taxation;

use App\Models\Bir2316;
use App\Services\Taxation\Bir2316GenerationService;
use App\Services\Taxation\Bir2316PdfService;
use App\Services\Taxation\Bir2316Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Bir2316GenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_bir_2316_snapshot_from_existing_taxation_data(): void
    {
        $employeeId = $this->seedTaxationEmployee('EMP-001');

        $result = app(Bir2316GenerationService::class)->generate([
            'taxable_year' => 2026,
            'employee_ids' => [$employeeId],
        ], 1);

        $this->assertSame('Generated 1 BIR 2316 record(s).', $result['message']);
        $this->assertDatabaseHas('bir_2316', [
            'employee_id' => $employeeId,
            'taxable_year' => 2026,
            'employee_no' => 'EMP-001',
            'status' => 'generated',
        ]);

        $record = Bir2316::query()->where('employee_id', $employeeId)->firstOrFail();

        $this->assertGreaterThan(0, (float) $record->gross_compensation_income);
        $this->assertGreaterThanOrEqual(0, (float) $record->annual_tax_due);
        $this->assertSame('EMP-001', data_get($record->snapshot_data, 'employee.employee_no'));
        $this->assertSame('Technology Application and Promotion Institute', $record->employer_name);
    }

    public function test_it_rejects_generation_when_no_annual_tax_computation_exists(): void
    {
        $employeeId = $this->seedEmployeeOnly('EMP-404');

        try {
            app(Bir2316GenerationService::class)->generate([
                'taxable_year' => 2026,
                'employee_ids' => [$employeeId],
            ], 1);

            $this->fail('Expected validation exception for missing annual tax computation.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'No annual tax computation exists for EMP-404 in 2026.',
                $exception->errors()['employee_ids'][0]
            );
        }
    }

    public function test_locked_records_cannot_be_regenerated_until_unlocked(): void
    {
        $employeeId = $this->seedTaxationEmployee('EMP-002');
        $generationService = app(Bir2316GenerationService::class);
        $bir2316Service = app(Bir2316Service::class);

        $generationService->generate([
            'taxable_year' => 2026,
            'employee_ids' => [$employeeId],
        ], 1);

        $record = Bir2316::query()->where('employee_id', $employeeId)->firstOrFail();
        $bir2316Service->lock($record, 1);

        try {
            $generationService->generate([
                'taxable_year' => 2026,
                'employee_ids' => [$employeeId],
            ], 1);

            $this->fail('Expected validation exception for locked record.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                sprintf('BIR 2316 for %s (%s) is locked and cannot be regenerated.', $record->employee_name, $record->employee_no),
                $exception->errors()['employee_ids'][0]
            );
        }
    }

    public function test_it_generates_a_pdf_from_the_existing_template(): void
    {
        $employeeId = $this->seedTaxationEmployee('EMP-003');

        app(Bir2316GenerationService::class)->generate([
            'taxable_year' => 2026,
            'employee_ids' => [$employeeId],
        ], 1);

        $record = Bir2316::query()->where('employee_id', $employeeId)->firstOrFail();
        $pdfPath = app(Bir2316PdfService::class)->generate($record);

        $this->assertFileExists($pdfPath);
        $this->assertGreaterThan(0, filesize($pdfPath));
    }

    private function seedTaxationEmployee(string $employeeNo): int
    {
        $employeeId = $this->seedEmployeeOnly($employeeNo);

        DB::table('agency')->insert([
            'code' => 'TAPI',
            'name' => 'Technology Application and Promotion Institute',
            'description' => 'DOST Bicutan, Taguig City',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('train_law')->insert([
            'id' => 1,
            'year' => '2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('train_law_items')->insert([
            'train_law_id' => 1,
            'income_from' => 250000,
            'income_to' => 800000,
            'fixed_tax' => 0,
            'tax_rate' => 15,
            'excess_over' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('n_taxation')->insert([
            'id' => 1,
            'Year' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('n_taxation_settings')->insert([
            'id' => 1,
            'n_taxation_id' => 1,
            'train_law_id' => 1,
        ]);

        DB::table('n_taxation_setting_portion')->insert([
            'n_taxation_setting_id' => 1,
            'salary' => 80,
            'hazard_pay' => 20,
            'longevity' => 0,
        ]);

        DB::table('n_taxation_employees')->insert([
            'n_taxation_id' => 1,
            'employee_no' => $employeeNo,
            'salary' => 80,
            'hazard_pay' => 20,
            'longevity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $processedById = DB::table('users')->insertGetId([
            'name' => 'Processor',
            'email' => strtolower($employeeNo) . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 12) as $month) {
            $payrollId = DB::table('payroll_salary')->insertGetId([
                'label' => 'Salary Payroll',
                'payroll_no' => sprintf('SAL-%02d', $month),
                'employment_type_id' => 1,
                'cutoff' => 'second_cutoff',
                'period_covered' => sprintf('2026-%02d', $month),
                'no_employee' => 1,
                'gross_amount' => 50000,
                'deduction_amount' => 0,
                'netpay_amount' => 50000,
                'payroll_date' => sprintf('2026-%02d-28', $month),
                'processed_by_id' => $processedById,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payroll_salary_permanent_employees')->insert([
                'payroll_salary_id' => $payrollId,
                'employee_no' => $employeeNo,
                'name' => 'Employee',
                'position' => 'Tax Analyst',
                'monthly_rate' => 50000,
                'salary_grade' => '24',
                'ut' => 0,
                'absences' => 0,
                'overtime' => 0,
                'holiday' => 0,
                'total_deductions' => 0,
                'net_pay' => 50000,
                'salary_adjustment' => 0,
                'remarks' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $employeeId;
    }

    private function seedEmployeeOnly(string $employeeNo): int
    {
        DB::table('employment_types')->insert([
            'id' => 1,
            'code' => 'REG',
            'name' => 'Regular',
            'description' => 'Regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('divisions')->insert([
            'id' => 1,
            'name' => 'Finance',
            'code' => 'FIN',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('units')->insert([
            'id' => 1,
            'name' => 'Tax Unit',
            'code' => 'TAX',
            'description' => null,
            'division_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'id' => 1,
            'employment_type_id' => 1,
            'code' => 'TAX-AN',
            'name' => 'Tax Analyst',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeId = DB::table('employee_information')->insertGetId([
            'employee_no' => $employeeNo,
            'biometrics_id' => null,
            'account_status' => 'active',
            'date_hired_organization' => '2025-01-01',
            'date_hired_company' => '2025-01-01',
            'date_resigned' => null,
            'payroll_account_no' => null,
            'isDeleted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_personal')->insert([
            'employee_no' => $employeeNo,
            'firstname' => 'Taylor',
            'middlename' => 'A',
            'lastname' => 'Rivera',
            'suffix' => null,
            'tin_no' => '123-456-789-000',
            'present_city' => 'Taguig City',
            'present_province' => 'Metro Manila',
            'present_zip' => '1631',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_organization')->insert([
            'employee_no' => $employeeNo,
            'division_id' => 1,
            'unit_id' => 1,
            'employment_type_id' => 1,
            'position_id' => 1,
            'effectivity_date' => '2025-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $employeeId;
    }
}
