<?php

namespace App\Services\Import;

use App\Enums\EmploymentTypesEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SlaImportService extends BaseImportService
{
    public function cleanRegular(string $filePath): array
    {
        $parsed = $this->parseSheetByHeaders($filePath, 3, [
            'Name' => ['NAME', 'Name', 'Employee'],
            'Subsistence Allowance' => ['Subsistence Allowance (22 Days)', 'Subsistence Allowance', 'Subsistence'],
            'Laundry Allowance' => ['Laundry Allow. P 500', 'Laundry Allowance', 'Laundry Allow.', 'Laundry'],
            'Total SLA' => ['GROSS AMOUNT', 'Gross Amount', 'TOTAL SLA', 'Total SLA'],
            'UT Deductions' => ['Deduction Late/UTs per DOST AO No. 003', 'Deduction Late/UTs', "Deduction Late/UT's perDOST AO No. 003", "Deduction Late/UT's per DOST AO No. 003", "Deduction Late/UT's", 'UT Deductions'],
            'Uniform Deduction' => ['Food Deductions', 'Food Deduction', 'Uniform Deduction', 'Uniform'],
            'Healthcard' => ['Less: Health Card c/o TAPIEA', 'Less: Healthcard c/o TAPIEA', 'Healthcard c/o TAPIEA', 'Healthcard'],
            'Net Amount' => ['Net Amount', 'Net Pay', 'Net Salary', 'TOTAL SLA', 'Total SLA'],
            'Salary Grade' => ['Salary Grade', 'Salary Grade/Step'],
        ], [], ['Salary Grade']);

        $errors = [];
        $hasSalaryGrade = $this->fieldWasParsed($parsed, 'Salary Grade');
        $cleaned = array_filter($parsed['rows'], fn ($row) => !empty(trim((string) ($row['Name'] ?? ''))) && ($this->toAmount($row['Total SLA'] ?? 0) > 0 || $this->toAmount($row['Net Amount'] ?? 0) > 0));

        foreach ($cleaned as &$row) {
            [$name, $uploadedPosition] = $this->extractNamePositionBlock((string) ($row['Name'] ?? ''));
            $employeeNo = $this->employeeService->getEmployeeNoBasedOnFullName($name);

            $row['Name'] = $name;
            $row['Position'] = $uploadedPosition ?: (string) ($row['Position'] ?? '');
            $row['Employee No'] = $employeeNo ?? '';

            if ($employeeNo) {
                $employee = $this->employeeService->getEmployee('information', $employeeNo);
                if ($employee && ($employee->account_status ?? null) === 'active' && !((bool) ($employee->isDeleted ?? false))) {
                    $row['Position'] = $row['Position'] ?: ($employee->position_name ?? '');
                } elseif ($name !== '') {
                    $errors[] = ['name' => $name, 'reason' => 'Inactive employee'];
                }
            } elseif ($name !== '') {
                $errors[] = ['name' => $name, 'reason' => 'Unknown employee no'];
            }

            if (!$hasSalaryGrade) {
                unset($row['Salary Grade']);
            }
        }

        $leadingFields = ['Employee No', 'Name', 'Position'];
        $previewHeaders = ['Employee No' => 'Employee No', 'Name' => 'Name', 'Position' => 'Position'];
        if ($hasSalaryGrade) {
            $leadingFields[] = 'Salary Grade';
            $previewHeaders['Salary Grade'] = 'Salary Grade';
        }

        return [
            'rows' => array_values($cleaned),
            'preview_headers' => $this->overridePreviewHeaders($previewHeaders + $parsed['preview_headers'], [
                'Subsistence Allowance' => 'Subsistence Allowance (22 Days)',
                'Laundry Allowance' => 'Laundry Allow. P 500',
                'Total SLA' => 'GROSS AMOUNT',
                'UT Deductions' => "Deduction Late/UT's per DOST AO No. 003",
                'Uniform Deduction' => 'Food Deductions',
                'Healthcard' => 'Less: Health Card c/o TAPIEA',
                'Net Amount' => 'TOTAL SLA',
            ]),
            'field_order' => $this->prependFields($leadingFields, $this->availableFieldOrder($parsed)),
            'errors' => $errors,
        ];
    }

    public function importRegular(array $data): array
    {
        $label = $data['label'];
        $monthYear = $data['month'];
        $rows = $data['data'] ?? [];
        $payrollNo = generateNo('SLA-', 4);
        $employmentTypeId = (int) EmploymentTypesEnum::REGULAR->value;
        $noEmployee = 0;
        $payrollTotal = 0;

        DB::transaction(function () use ($label, $monthYear, $rows, $payrollNo, $employmentTypeId, &$noEmployee, &$payrollTotal) {
            $preparedRows = [];

            foreach ($rows as $employee) {
                $employeeNo = trim((string) ($employee['Employee No'] ?? ''));
                $name = trim((string) ($employee['Name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $totalSla = $this->toAmount($employee['Total SLA'] ?? 0);
                $utDeductions = $this->toAmount($employee['UT Deductions'] ?? 0);
                $uniformDeduction = $this->toAmount($employee['Uniform Deduction'] ?? 0);
                $total = max($totalSla - $utDeductions - $uniformDeduction, 0);

                $preparedRows[] = [
                    'employee_no' => $employeeNo,
                    'name' => $name,
                    'position' => trim((string) ($employee['Position'] ?? '')),
                    'subsistence_allowance' => $this->toAmount($employee['Subsistence Allowance'] ?? 0),
                    'laundry_allowance' => $this->toAmount($employee['Laundry Allowance'] ?? 0),
                    'total_sla' => $totalSla,
                    'ut_deductions' => $utDeductions,
                    'uniform_deduction' => $uniformDeduction,
                    'total' => $total,
                    'healthcard' => $this->toAmount($employee['Healthcard'] ?? 0),
                    'adjustments' => 0,
                    'net_pay' => $this->amountFromKeys($employee, ['Net Amount', 'Net Pay', 'Net Salary']),
                    'remarks' => null,
                ];

                $noEmployee++;
                $payrollTotal += $total;
            }

            $payrollId = DB::table('payroll_sla_pay')->insertGetId([
                'batch_id' => null,
                'label' => $label,
                'payroll_no' => $payrollNo,
                'month' => $monthYear,
                'no_employee' => $noEmployee,
                'employment_type_id' => $employmentTypeId,
                'total' => $payrollTotal,
                'processed_by_id' => Auth::id(),
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($preparedRows as $row) {
                DB::table('payroll_sla_pay_employee')->insert([
                    'payroll_sla_pay_id' => $payrollId,
                    'employee_no' => $row['employee_no'],
                    'name' => $row['name'],
                    'position' => $row['position'],
                    'subsistence_allowance' => $row['subsistence_allowance'],
                    'laundry_allowance' => $row['laundry_allowance'],
                    'total_sla' => $row['total_sla'],
                    'ut_deductions' => $row['ut_deductions'],
                    'uniform_deduction' => $row['uniform_deduction'],
                    'total' => $row['total'],
                    'healthcard' => $row['healthcard'],
                    'adjustments' => $row['adjustments'],
                    'net_pay' => $row['net_pay'],
                    'remarks' => $row['remarks'],
                ]);
            }
        });

        return ['status' => 'success', 'message' => 'SLA payroll imported successfully.', 'redirect' => route('sla-pay.show', ['sla_pay' => $payrollNo])];
    }
}
