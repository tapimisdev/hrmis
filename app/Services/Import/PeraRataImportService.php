<?php

namespace App\Services\Import;

use App\Enums\EmploymentTypesEnum;
use App\Enums\TableSettingsEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeraRataImportService extends BaseImportService
{
    public function cleanRegular(string $filePath): array
    {
        $parsed = $this->parseSheetByHeaders($filePath, 5, [
            'Name' => ['NAME', 'Name', 'Employee'],
            'Designation' => ['DESIGNATION', 'Designation', 'Position'],
            'PERA' => ['PERA'],
            'Representation Allowance' => ['Rep. Allow.', 'Rep Allow', 'Representation Allowance'],
            'Transportation Allowance' => ['Transpo. Allow.', 'Transpo Allow', 'Transportation Allowance'],
            'Total Amount' => ['Total Amount', 'Total'],
            'Healthcard' => ['Less: Healthcard c/o TAPIEA', 'Healthcard c/o TAPIEA', 'Healthcard', 'Health Card'],
            'Net Amount' => ['Net Amount', 'Net Pay', 'Net Salary'],
            'Salary Grade' => ['Salary Grade', 'Salary Grade/Step'],
        ], [], ['Salary Grade']);

        $errors = [];
        $hasSalaryGrade = $this->fieldWasParsed($parsed, 'Salary Grade');
        $cleaned = array_filter($parsed['rows'], fn ($row) => !empty(trim((string) ($row['Name'] ?? ''))) && ($this->toAmount($row['PERA'] ?? 0) > 0 || $this->toAmount($row['Total Amount'] ?? 0) > 0 || $this->toAmount($row['Net Amount'] ?? 0) > 0));

        foreach ($cleaned as &$row) {
            [$name, $uploadedPosition] = $this->extractNamePositionBlock((string) ($row['Name'] ?? ''));
            $employeeNo = $this->employeeService->getEmployeeNoBasedOnFullName($name);

            $row['Name'] = $name;
            $row['Designation'] = trim((string) ($row['Designation'] ?? '')) ?: $uploadedPosition;
            $row['Employee No'] = $employeeNo ?? '';

            if ($employeeNo) {
                $employee = $this->employeeService->getEmployee('information', $employeeNo);
                if ($employee && ($employee->account_status ?? null) === 'active' && !((bool) ($employee->isDeleted ?? false))) {
                    $row['Designation'] = $row['Designation'] ?: ($employee->position_name ?? '');
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

        $leadingFields = ['Employee No', 'Name', 'Designation'];
        $previewHeaders = ['Employee No' => 'Employee No', 'Name' => 'Name', 'Designation' => 'Designation'];
        if ($hasSalaryGrade) {
            $leadingFields[] = 'Salary Grade';
            $previewHeaders['Salary Grade'] = 'Salary Grade';
        }

        return [
            'rows' => array_values($cleaned),
            'preview_headers' => $this->overridePreviewHeaders($previewHeaders + $parsed['preview_headers'], [
                'Representation Allowance' => 'Rep. Allow.',
                'Transportation Allowance' => 'Transpo. Allow.',
                'Healthcard' => 'Less: Healthcard c/o TAPIEA',
                'Net Amount' => 'Net Amount',
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
        $payrollNo = generateNo('PR-', 4);
        $employmentTypeId = (int) EmploymentTypesEnum::REGULAR->value;
        [$year, $month] = array_map('intval', explode('-', $monthYear));
        $componentYearIds = $this->getComponentYearIds($year);
        $noEmployee = 0;
        $payrollTotal = 0;

        DB::transaction(function () use ($label, $monthYear, $rows, $payrollNo, $employmentTypeId, $month, $componentYearIds, &$noEmployee, &$payrollTotal) {
            $preparedRows = [];

            foreach ($rows as $employee) {
                $employeeNo = trim((string) ($employee['Employee No'] ?? ''));
                $name = trim((string) ($employee['Name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $totalAmount = $this->toAmount($employee['Total Amount'] ?? 0);
                $preparedRows[] = [
                    'employee_no' => $employeeNo,
                    'name' => $name,
                    'position' => trim((string) ($employee['Designation'] ?? '')),
                    'pera' => $this->toAmount($employee['PERA'] ?? 0),
                    'representation_allowance' => $this->toAmount($employee['Representation Allowance'] ?? 0),
                    'transportion_allowance' => $this->toAmount($employee['Transportation Allowance'] ?? 0),
                    'absences' => 0,
                    'ut_deductions' => 0,
                    'total' => $totalAmount,
                    'healthcard' => $this->toAmount($employee['Healthcard'] ?? 0),
                    'adjustments' => 0,
                    'net_pay' => $this->amountFromKeys($employee, ['Net Amount', 'Net Pay', 'Net Salary']),
                    'remarks' => null,
                ];

                $noEmployee++;
                $payrollTotal += $totalAmount;
            }

            $payrollId = DB::table('payroll_pera_rata')->insertGetId([
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
                DB::table('payroll_pera_rata_employee')->insert([
                    'payroll_pera_rata_id' => $payrollId,
                    'employee_no' => $row['employee_no'],
                    'name' => $row['name'],
                    'position' => $row['position'],
                    'pera' => $row['pera'],
                    'representation_allowance' => $row['representation_allowance'],
                    'transportion_allowance' => $row['transportion_allowance'],
                    'absences' => $row['absences'],
                    'ut_deductions' => $row['ut_deductions'],
                    'total' => $row['total'],
                    'healthcard' => $row['healthcard'],
                    'adjustments' => $row['adjustments'],
                    'net_pay' => $row['net_pay'],
                    'remarks' => $row['remarks'],
                ]);

                if ($row['employee_no'] !== '') {
                    $componentAmounts = [
                        TableSettingsEnum::PERA->value => $row['pera'],
                        'representation_allowance' => $row['representation_allowance'],
                        'transportation_allowance' => $row['transportion_allowance'],
                    ];

                    foreach ($componentAmounts as $type => $amount) {
                        $yearId = $componentYearIds[$type] ?? null;
                        if (!$yearId) {
                            continue;
                        }

                        DB::table('employee_payroll_components')->updateOrInsert(
                            ['tax_deduction_id' => $yearId, 'employee_no' => $row['employee_no'], 'month' => $month],
                            ['amount' => $amount, 'updated_at' => now(), 'created_at' => now()]
                        );
                    }

                    $rataYearId = $componentYearIds[TableSettingsEnum::RATA->value] ?? null;
                    if ($rataYearId) {
                        DB::table('employee_payroll_components')->updateOrInsert(
                            ['tax_deduction_id' => $rataYearId, 'employee_no' => $row['employee_no'], 'month' => $month],
                            ['amount' => $row['representation_allowance'] + $row['transportion_allowance'], 'updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }
            }
        });

        return ['status' => 'success', 'message' => 'PERA & RATA payroll imported successfully.', 'redirect' => route('pera-rata.show', ['pera_ratum' => $payrollNo])];
    }

    private function getComponentYearIds(int $year): array
    {
        $types = [
            TableSettingsEnum::PERA->value,
            TableSettingsEnum::RATA->value,
            'representation_allowance',
            'transportation_allowance',
        ];

        $componentIds = DB::table('payroll_components_settings')
            ->whereIn('type', $types)
            ->whereNotNull('table_id')
            ->pluck('table_id', 'type');

        $yearIds = [];

        foreach ($componentIds as $type => $componentId) {
            DB::table('payroll_components_years')->updateOrInsert(
                ['payroll_component_id' => $componentId, 'year' => $year],
                ['updated_at' => now(), 'created_at' => now()]
            );

            $yearIds[$type] = DB::table('payroll_components_years')
                ->where('payroll_component_id', $componentId)
                ->where('year', $year)
                ->value('id');
        }

        return $yearIds;
    }
}
