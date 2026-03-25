<?php

namespace App\Services\Import;

use App\Enums\EmploymentTypesEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HazardImportService extends BaseImportService
{
    public function cleanRegular(string $filePath): array
    {
        $parsed = $this->parseSheetByHeaders($filePath, 5, [
            'Name' => ['NAME', 'Name', 'Employee'],
            'Monthly Rate' => ['MONTHLY RATE', 'Monthly Rate', 'Rate/Month', 'Rate / Month'],
            'Entitlement' => ['% of Entitlement', '% of Entitle-ment', 'Entitlement', '% Entitlement'],
            'Hazard Pay' => ['Hazard Pay'],
            'Withholding Tax' => ['Withholding Tax', 'Witholding Tax'],
            'Healthcard' => ['Less: Healthcard c/o TAPIEA', 'Healthcard c/o TAPIEA', 'Healthcard', 'Health Card'],
            'Net Pay' => ['Net Pay', 'Net Salary'],
            'Salary Grade' => ['Salary Grade', 'Salary Grade/Step'],
        ], [], ['Salary Grade']);

        $errors = [];
        $hasSalaryGrade = $this->fieldWasParsed($parsed, 'Salary Grade');
        $cleaned = array_filter($parsed['rows'], fn ($row) => !empty(trim((string) ($row['Name'] ?? ''))) && !empty(trim((string) ($row['Monthly Rate'] ?? ''))));

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
                'Entitlement' => '% of Entitlement',
                'Healthcard' => 'Less: Healthcard c/o TAPIEA',
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
        $payrollNo = generateNo('HP-', 4);
        $employmentTypeId = (int) EmploymentTypesEnum::REGULAR->value;
        [$year, $month] = array_map('intval', explode('-', $monthYear));
        $earningsYearId = $this->getTableYearId($year);
        $taxYearId = $this->getTaxYearId($year);

        $noEmployee = 0;
        $payrollTotal = 0;

        DB::transaction(function () use ($label, $monthYear, $rows, $payrollNo, $employmentTypeId, $month, $earningsYearId, $taxYearId, &$noEmployee, &$payrollTotal) {
            $preparedRows = [];

            foreach ($rows as $employee) {
                $employeeNo = trim((string) ($employee['Employee No'] ?? ''));
                $name = trim((string) ($employee['Name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $monthlyRate = $this->toAmount($employee['Monthly Rate'] ?? 0);
                $entitlement = $this->toPercentageDecimal($employee['Entitlement'] ?? 0);
                $hazardPay = $this->toAmount($employee['Hazard Pay'] ?? 0);
                $withholdingTax = $this->toAmount($employee['Withholding Tax'] ?? 0);
                $healthcard = $this->toAmount($employee['Healthcard'] ?? 0);
                $total = $hazardPay - $withholdingTax;
                $netPay = $this->amountFromKeys($employee, ['Net Pay', 'Net Salary']);

                $preparedRows[] = [
                    'employee_no' => $employeeNo,
                    'name' => $name,
                    'position' => trim((string) ($employee['Position'] ?? '')),
                    'monthly_rate' => $monthlyRate,
                    'entitlement' => $entitlement,
                    'hazard_pay' => $hazardPay,
                    'witholding_tax' => $withholdingTax,
                    'healthcard' => $healthcard,
                    'total' => $total,
                    'adjustments' => 0,
                    'net_pay' => $netPay,
                    'remarks' => null,
                ];

                $noEmployee++;
                $payrollTotal += $total;
            }

            $payrollId = DB::table('payroll_hazard_pay')->insertGetId([
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
                DB::table('payroll_hazard_pay_employee')->insert([
                    'payroll_hazard_pay_id' => $payrollId,
                    'employee_no' => $row['employee_no'],
                    'name' => $row['name'],
                    'position' => $row['position'],
                    'monthly_rate' => $row['monthly_rate'],
                    'entitlement' => $row['entitlement'],
                    'hazard_pay' => $row['hazard_pay'],
                    'witholding_tax' => $row['witholding_tax'],
                    'healthcard' => $row['healthcard'],
                    'total' => $row['total'],
                    'adjustments' => $row['adjustments'],
                    'net_pay' => $row['net_pay'],
                    'remarks' => $row['remarks'],
                ]);

                if ($earningsYearId && $row['employee_no'] !== '') {
                    DB::table('employee_payroll_components')->updateOrInsert(
                        ['tax_deduction_id' => $earningsYearId, 'employee_no' => $row['employee_no'], 'month' => $month],
                        ['amount' => $row['net_pay'], 'updated_at' => now(), 'created_at' => now()]
                    );
                }

                if ($taxYearId && $row['employee_no'] !== '') {
                    DB::table('employee_payroll_components')->updateOrInsert(
                        ['tax_deduction_id' => $taxYearId, 'employee_no' => $row['employee_no'], 'month' => $month],
                        ['amount' => $row['witholding_tax'], 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        });

        return ['status' => 'success', 'message' => 'Hazard payroll imported successfully.', 'redirect' => route('hazard-pay.show', ['hazard_pay' => $payrollNo])];
    }

    private function getTaxYearId(int $year): ?int
    {
        $componentId = DB::table('payroll_components_settings')->where('type', TableSettingsEnum::HAZARD_PA->value)->value('tax_id');
        if (!$componentId) {
            return null;
        }

        return DB::table('payroll_components_years')->updateOrInsert(
            ['payroll_component_id' => $componentId, 'year' => $year],
            ['updated_at' => now(), 'created_at' => now()]
        )
            ? DB::table('payroll_components_years')->where('payroll_component_id', $componentId)->where('year', $year)->value('id')
            : null;
    }

    private function getTableYearId(int $year): ?int
    {
        $componentId = DB::table('payroll_components_settings')->where('type', TableSettingsEnum::HAZARD_PA->value)->value('table_id');
        if (!$componentId) {
            return null;
        }

        return DB::table('payroll_components_years')->updateOrInsert(
            ['payroll_component_id' => $componentId, 'year' => $year],
            ['updated_at' => now(), 'created_at' => now()]
        )
            ? DB::table('payroll_components_years')->where('payroll_component_id', $componentId)->where('year', $year)->value('id')
            : null;
    }
}
