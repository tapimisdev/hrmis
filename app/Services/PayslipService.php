<?php

namespace App\Services;

use App\DTO\PayslipData;
use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Services\SalaryPay\PayrollService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PayslipService
{
    public function __construct(private PayrollService $payrollService)
    {
    }

    public function generatePayslip(PayslipData $data): array
    {
        $approvedPayrolls = DB::table('payroll_salary')
            ->select('id', 'payroll_no', 'period_covered', 'payroll_date', 'cutoff')
            ->whereMonth('payroll_date', $data->month)
            ->whereYear('payroll_date', $data->year)
            ->where('employment_type_id', $data->employee_type)
            ->when($data->cutoff, fn ($query) => $query->where('cutoff', $data->cutoff))
            ->whereIn('status', [
                PayrollStatusEnum::Approved->value,
                PayrollStatusEnum::Completed->value,
            ])
            ->orderBy('payroll_date')
            ->orderBy('id')
            ->get();

        if ($approvedPayrolls->isEmpty()) {
            throw new HttpException(
                422,
                'Oops! There is no approved or completed payroll for this month yet. Kindly contact HR for more details.'
            );
        }

        $payslipData = [];

        if ((string) $data->employee_type === EmploymentTypesEnum::COS->value) {
            foreach ($approvedPayrolls as $payroll) {
                $logic = $this->cosLogic($payroll, $data);
                if (!$logic) {
                    continue;
                }

                $logic['period_covered'] = $payroll->period_covered;
                $logic['payroll_date'] = $payroll->payroll_date;
                $logic['cutoff'] = $payroll->cutoff;

                $payslipData[] = $logic;
            }
        }

        // Future: Permanent employee logic
        if (empty($payslipData)) {
            throw new HttpException(
                422,
                'Your payroll record for this period is not available. Please contact HR for assistance.'
            );
        }

        return $payslipData;
    }


    private function cosLogic(object $payroll, PayslipData $data): ?array
    {
        $payrollDetails = $this->payrollService->payrollDetails($payroll->payroll_no);
        $registry = json_decode(
            $this->payrollService->getPayrollRegistry($payrollDetails, $payrollDetails->id, false)->getContent(),
            true
        );

        $employee = collect($registry)->firstWhere('employee_no', $data->employee_no);

        if (!$employee) {
            return null;
        }

        $deductions = array_merge(
            [[
                'description' => 'Absences/Lates/Undertime',
                'amount' => (float) ($employee['aut'] ?? 0),
            ]],
            $this->computedCosDeductions($employee)
        );

        return [
            'name' => $employee['name'] ?? '',
            'position' => $employee['position'] ?? '',
            'employee_no' => $employee['employee_no'] ?? '',
            'employment_type_id' => $data->employee_type,
            'monthly_rate' => $employee['monthly_rate'] ?? 0,
            'aut' => $employee['aut'] ?? 0,
            'earnings' => [[
                'description' => 'Monthly Salary',
                'amount' => $employee['monthly_rate'] ?? 0,
            ]],
            'deductions' => $deductions,
            'adjustments' => $employee['adjustment'] ?? 0,
            'gross_pay' => $employee['total_salary'] ?? $employee['gross_pay'] ?? 0,
            'net_pay' => $employee['net_salary'] ?? $employee['net_pay'] ?? 0,
            'remarks' => $employee['remarks'] ?? '',
        ];
    }

    private function computedCosDeductions(array $employee): array
    {
        return collect([
            ['description' => 'EWT 2%', 'amount' => (float) ($employee['ewt_2'] ?? 0)],
            ['description' => 'Percentage Tax 3%', 'amount' => (float) ($employee['percentage_tax_3'] ?? 0)],
            ['description' => 'Tax EWT 5%', 'amount' => (float) ($employee['tax_ewt_5'] ?? 0)],
            ['description' => 'HMO', 'amount' => (float) ($employee['hmo'] ?? 0)],
        ])->values()->all();
    }
}
