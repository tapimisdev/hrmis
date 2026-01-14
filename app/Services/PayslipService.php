<?php

namespace App\Services;

use App\DTO\PayslipData;
use App\Enums\EmploymentTypesEnum;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PayslipService
{
    public function generatePayslip(PayslipData $data): array
    {
        $approvedPayrolls = DB::table('payroll_salary')
            ->select('id', 'period_covered')
            ->whereMonth('payroll_date', $data->month)
            ->whereYear('payroll_date', $data->year)
            ->where('employment_type_id', $data->employee_type)
            ->where('status', 'approved')
            ->get();

        if ($approvedPayrolls->isEmpty()) {
            throw new HttpException(
                422,
                'Oops! There is no approved payroll for this month yet. Kindly contact HR for more details.'
            );
        }

        $payslipData = [];

        if ($data->employee_type === EmploymentTypesEnum::COS->value) {
            foreach ($approvedPayrolls as $payroll) {
                $logic = $this->cosLogic($payroll->id, $data);
                $logic['period_covered'] = $payroll->period_covered;

                $payslipData[] = $logic;
            }
        }

        // Future: Permanent employee logic

        return $payslipData;
    }


    private function cosLogic(int $payrollId, PayslipData $data): array
    {
        $pse = DB::table('payroll_salary_employee')
            ->select(
                'id',
                'name',
                'position',
                'employee_no',
                'monthly_rate',
                'basic_pay as basic_salary',
                'overtime',
                'holiday',
                'gross_pay',
                DB::raw('(ut + absences) as uat'),
                'net_pay',
                'salary_adjustment',
                'remarks'
            )
            ->where('payroll_salary_id', $payrollId)
            ->where('employee_no', $data->employee_no)
            ->first();

        if (!$pse) {
            throw new \Exception(
                'Your payroll record for this period is not available. Please contact HR for assistance.',
                422
            );
        }

        $earnings = [
            [
                'description' => 'Basic Salary',
                'amount' => $pse->basic_salary ?? 0,
            ],
            [
                'description' => 'Overtime',
                'amount' => $pse->overtime ?? 0,
            ],
            [
                'description' => 'Holiday Pay',
                'amount' => $pse->holiday ?? 0,
            ],
        ];

        $deductions = DB::table('payroll_salary_employee_edeductions')
            ->where('payroll_se_id', $pse->id)
            ->get()
            ->map(fn ($deduction) => [
                'description' => $deduction->deduction_type,
                'amount' => $deduction->amount,
            ])
            ->toArray();

        return [
            'name' => $pse->name,
            'position' => $pse->position,
            'employee_no' => $pse->employee_no,
            'monthly_rate' => $pse->monthly_rate,
            'aut' => $pse->uat,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'adjustments' => $pse->salary_adjustment,
            'gross_pay' => $pse->gross_pay,
            'net_pay' => $pse->net_pay,
            'remarks' => $pse->remarks,
        ];
    }

}