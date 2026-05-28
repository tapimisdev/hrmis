<?php

namespace Tests\Feature;

use App\DTO\PayslipData;
use App\Enums\EmploymentTypesEnum;
use App\Services\PayslipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayslipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_cos_payroll_is_visible_in_employee_payslip(): void
    {
        DB::table('employment_types')->insert([
            'id' => EmploymentTypesEnum::COS->value,
            'code' => 'COS',
            'name' => 'Contract of Service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Payroll Processor',
            'email' => 'processor@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payrollId = DB::table('payroll_salary')->insertGetId([
            'label' => 'May 2026 COS Payroll',
            'payroll_no' => 'PAY-2026-05',
            'employment_type_id' => EmploymentTypesEnum::COS->value,
            'cutoff' => 'first_cutoff',
            'period_covered' => 'May 1-15, 2026',
            'no_employee' => 1,
            'gross_amount' => 10000,
            'deduction_amount' => 500,
            'netpay_amount' => 9500,
            'payroll_date' => '2026-05-15',
            'processed_by_id' => $userId,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payroll_salary_employee')->insert([
            'payroll_salary_id' => $payrollId,
            'employee_no' => 'EMP-001',
            'name' => 'Juan Dela Cruz',
            'position' => 'Project Staff',
            'salary_grade' => 'N/A',
            'ut' => 0,
            'absences' => 0,
            'overtime' => 0,
            'holiday' => 0,
            'gsis' => 0,
            'philhealth' => 0,
            'pagibig' => 0,
            'w_tax' => 500,
            'total_deductions' => 500,
            'total_earnings' => 10000,
            'monthly_rate' => 20000,
            'basic_pay' => 10000,
            'gross_pay' => 10000,
            'net_pay' => 9500,
            'salary_adjustment' => 0,
            'remarks' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payslip = app(PayslipService::class)->generatePayslip(new PayslipData(
            employee_no: 'EMP-001',
            month: 5,
            year: 2026,
            employee_type: EmploymentTypesEnum::COS->value,
        ));

        $this->assertCount(1, $payslip);
        $this->assertSame('May 1-15, 2026', $payslip[0]['period_covered']);
        $this->assertSame('EMP-001', $payslip[0]['employee_no']);
        $this->assertEquals(9500, $payslip[0]['net_pay']);
    }

    public function test_employee_payslip_search_skips_other_april_batches(): void
    {
        DB::table('employment_types')->insert([
            'id' => EmploymentTypesEnum::COS->value,
            'code' => 'COS',
            'name' => 'Contract of Service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Payroll Processor',
            'email' => 'processor@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payroll_salary')->insertGetId([
            'label' => '1-15 April 2026 Salary',
            'payroll_no' => 'SL-20260416-WG4O',
            'employment_type_id' => EmploymentTypesEnum::COS->value,
            'cutoff' => 'first_cutoff',
            'period_covered' => 'April 1-15, 2026',
            'no_employee' => 39,
            'gross_amount' => 10000,
            'deduction_amount' => 500,
            'netpay_amount' => 9500,
            'payroll_date' => '2026-04-16',
            'processed_by_id' => $userId,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchingPayrollId = DB::table('payroll_salary')->insertGetId([
            'label' => '1-15 April 2026 Salary',
            'payroll_no' => 'SL-20260416-SAAJ',
            'employment_type_id' => EmploymentTypesEnum::COS->value,
            'cutoff' => 'first_cutoff',
            'period_covered' => 'April 1-15, 2026',
            'no_employee' => 2,
            'gross_amount' => 10000,
            'deduction_amount' => 500,
            'netpay_amount' => 9500,
            'payroll_date' => '2026-04-16',
            'processed_by_id' => $userId,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payroll_salary_employee')->insert([
            'payroll_salary_id' => $matchingPayrollId,
            'employee_no' => 'EMP-APRIL',
            'name' => 'April Employee',
            'position' => 'Project Staff',
            'salary_grade' => 'N/A',
            'ut' => 0,
            'absences' => 0,
            'overtime' => 0,
            'holiday' => 0,
            'gsis' => 0,
            'philhealth' => 0,
            'pagibig' => 0,
            'w_tax' => 500,
            'total_deductions' => 500,
            'total_earnings' => 10000,
            'monthly_rate' => 20000,
            'basic_pay' => 10000,
            'gross_pay' => 10000,
            'net_pay' => 9500,
            'salary_adjustment' => 0,
            'remarks' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payslip = app(PayslipService::class)->generatePayslip(new PayslipData(
            employee_no: 'EMP-APRIL',
            month: 4,
            year: 2026,
            employee_type: EmploymentTypesEnum::COS->value,
        ));

        $this->assertCount(1, $payslip);
        $this->assertSame('EMP-APRIL', $payslip[0]['employee_no']);
        $this->assertSame('April 1-15, 2026', $payslip[0]['period_covered']);
    }
}
