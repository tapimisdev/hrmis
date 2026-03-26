<?php

namespace App\Services\Import;

use App\Enums\EmploymentTypesEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LongevityImportService extends BaseImportService
{
    public function cleanRegular(string $filePath): array
    {
        $parsed = $this->parseSheetByHeaders($filePath, 4, [
            'Name' => ['NAME OF EMPLOYEE', 'Name of Employee', 'Name'],
            'Rate/Month' => ['Rate/Month as of January 2025', 'Rate/Month', 'Rate / Month', 'Monthly Rate'],
            'Net Amount' => ['NET AMOUNT', 'Net Amount', 'Net Pay'],
            'Salary Grade' => ['Salary Grade', 'Salary Grade/Step'],
        ], ['Name' => 1, 'Rate/Month' => 12, 'Net Amount' => 25], ['Salary Grade']);

        $errors = [];
        $hasSalaryGrade = $this->fieldWasParsed($parsed, 'Salary Grade');
        $cleaned = array_filter($parsed['rows'], fn ($row) => !empty(trim((string) ($row['Name'] ?? ''))) && $this->toAmount($row['Net Amount'] ?? 0) > 0);

        foreach ($cleaned as &$row) {
            [$name] = $this->extractNamePositionBlock((string) ($row['Name'] ?? ''));
            $employeeNo = $this->employeeService->getEmployeeNoBasedOnFullName($name);

            $row['Name'] = $name;
            $row['Employee No'] = $employeeNo ?? '';

            if ($employeeNo) {
                $employee = $this->employeeService->getEmployee('information', $employeeNo);
                if ($employee && ($employee->account_status ?? null) === 'active' && !((bool) ($employee->isDeleted ?? false))) {
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

        $fieldOrder = ['Employee No', 'Name'];
        $previewHeaders = ['Employee No' => 'Employee No', 'Name' => 'Name'];
        if ($hasSalaryGrade) {
            $fieldOrder[] = 'Salary Grade';
            $previewHeaders['Salary Grade'] = 'Salary Grade';
        }
        $fieldOrder[] = 'Rate/Month';
        $fieldOrder[] = 'Net Amount';
        $previewHeaders['Rate/Month'] = 'Rate/Month';
        $previewHeaders['Net Amount'] = 'Net Amount';

        return [
            'rows' => array_values($cleaned),
            'preview_headers' => $previewHeaders,
            'field_order' => $fieldOrder,
            'errors' => $errors,
        ];
    }

    public function importRegular(array $data): array
    {
        $label = $data['label'];
        $monthYear = $data['month'];
        $rows = $data['data'] ?? [];
        $payrollNo = generateNo('LG-', 4);
        $employmentTypeId = (int) EmploymentTypesEnum::REGULAR->value;
        [$year, $month] = array_map('intval', explode('-', $monthYear));
        $componentYearId = $this->getComponentYearId($year);
        $noEmployee = 0;
        $payrollTotal = 0;

        DB::transaction(function () use ($label, $monthYear, $rows, $payrollNo, $employmentTypeId, $month, $componentYearId, &$noEmployee, &$payrollTotal) {
            $preparedRows = [];

            foreach ($rows as $employee) {
                $employeeNo = trim((string) ($employee['Employee No'] ?? ''));
                $name = trim((string) ($employee['Name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $netAmount = $this->amountFromKeys($employee, ['Net Amount', 'Net Pay', 'Net Salary']);
                $employeeInfo = $employeeNo !== ''
                    ? $this->employeeService->getEmployee('information', $employeeNo)
                    : null;

                $preparedRows[] = [
                    'employee_no' => $employeeNo,
                    'name' => $name,
                    'position' => (string) ($employeeInfo->position_name ?? ''),
                    'longevity_amount' => $netAmount,
                    'total' => $netAmount,
                    'adjustments' => 0,
                    'net_pay' => $netAmount,
                    'remarks' => null,
                ];

                $noEmployee++;
                $payrollTotal += $netAmount;
            }

            $payrollId = DB::table('payroll_longevity_pay')->insertGetId([
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
                DB::table('payroll_longevity_pay_employee')->insert([
                    'payroll_longevity_pay_id' => $payrollId,
                    'employee_no' => $row['employee_no'],
                    'name' => $row['name'],
                    'position' => $row['position'],
                    'longevity_amount' => $row['longevity_amount'],
                    'total' => $row['total'],
                    'adjustments' => $row['adjustments'],
                    'net_pay' => $row['net_pay'],
                    'remarks' => $row['remarks'],
                ]);

                if ($componentYearId && $row['employee_no'] !== '') {
                    DB::table('employee_payroll_components')->updateOrInsert(
                        ['tax_deduction_id' => $componentYearId, 'employee_no' => $row['employee_no'], 'month' => $month],
                        ['amount' => $row['longevity_amount'], 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        });

        return ['status' => 'success', 'message' => 'Longevity payroll imported successfully.', 'redirect' => route('longevity-pay.show', ['longevity_pay' => $payrollNo])];
    }

    private function getComponentYearId(int $year): ?int
    {
        $componentId = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::LONGETIVITY->value)
            ->value('table_id');

        if (!$componentId) {
            return null;
        }

        DB::table('payroll_components_years')->updateOrInsert(
            ['payroll_component_id' => $componentId, 'year' => $year],
            ['updated_at' => now(), 'created_at' => now()]
        );

        return DB::table('payroll_components_years')
            ->where('payroll_component_id', $componentId)
            ->where('year', $year)
            ->value('id');
    }
}
