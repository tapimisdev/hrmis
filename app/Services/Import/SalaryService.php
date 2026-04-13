<?php

namespace App\Services\Import;

use App\Enums\TableSettingsEnum;
use App\Jobs\Import\SalaryJobImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalaryService extends BaseImportService
{
    public function cleanCOS($filePath): array
    {
        $parsed = $this->parseSheetByHeaders($filePath, 7, [
            'Name' => ['NAME / POSITION', 'NAME / Position', 'Name / Position', 'Name', 'Employee'],
            'Monthly Rate' => ['MONTHLY RATE', 'Monthly Rate', 'Rate/Month', 'Rate / Month'],
            'Salary Earned' => ['SALARY EARNED', 'Salary Earned', 'Basic Pay'],
            'AUT' => ['ABSENCES/ LATES/ UNDERTIME', 'ABSENCES / LATES / UNDERTIME', 'AUT', 'A.U.T.'],
            'Total Salary' => ['TOTAL SALARY', 'Total Salary', 'Gross Pay'],
            'Two Percent' => ['EWT (2%)', 'EWT 2%', 'Two Percent', '2%', 'Two %'],
            'Three Percent' => ['PERCENTAGE TAX (3%)', 'Percentage Tax (3%)', 'Percentage Tax', 'Three Percent', '3%', 'Three %'],
            'Five Percent' => ['TAX (EWT 5%)', 'TAX (EWT: 5%)', 'Tax (EWT 5%)', 'Tax (EWT: 5%)', 'Five Percent', '5%', 'Five %'],
            'Healthcard' => ['HEALTHCARD (c/o TAPIEA)', 'HEALTHCARD c/o TAPIEA', 'Healthcard (c/o TAPIEA)', 'Healthcard c/o TAPIEA', 'Healthcard', 'Health Card', 'HMO'],
            'Net Salary' => ['NET SALARY', 'Net Salary', 'Net Pay'],
            'Salary Grade' => ['Salary Grade', 'Salary Grade/Step'],
        ], ['Name' => 1], ['Salary Grade']);

        $cleaned = array_filter($parsed['rows'], fn ($row) => stripos((string) $row['Name'], 'TOTAL') === false);

        foreach ($cleaned as $i => $row) {
            if (stripos((string) $row['Name'], 'Grand Total') !== false) {
                $cleaned = array_slice($cleaned, 0, $i);
                break;
            }
        }

        $errors = [];
        $hasSalaryGrade = $this->fieldWasParsed($parsed, 'Salary Grade');

        foreach ($cleaned as &$row) {
            [$name, $uploadedPosition] = $this->extractNamePositionBlock((string) $row['Name']);
            $employeeNo = $this->employeeService->getEmployeeNoBasedOnFullName($name);

            $row['Name'] = $name;
            $row['Employee No'] = $employeeNo ?? '';
            $row['Position'] = $uploadedPosition;

            if ($employeeNo) {
                $employee = $this->employeeService->getEmployee('information', $employeeNo);
                if ($employee) {
                    $row['Position'] = $uploadedPosition ?: ($employee->position_name ?? '');
                }
            } elseif ($name !== '') {
                $errors[] = ['name' => $name, 'reason' => 'Unknown employee no'];
            }

            if (!$hasSalaryGrade) {
                unset($row['Salary Grade']);
            }
        }

        $cleaned = array_filter($cleaned, fn ($row) => !empty($row['Name']) && !empty($row['Monthly Rate']));
        $leadingFields = ['Employee No', 'Name', 'Position'];
        $previewHeaders = ['Employee No' => 'Employee No', 'Name' => 'Name', 'Position' => 'Position'];

        if ($hasSalaryGrade) {
            $leadingFields[] = 'Salary Grade';
            $previewHeaders['Salary Grade'] = 'Salary Grade';
        }

        return [
            'rows' => array_values($cleaned),
            'preview_headers' => $this->overridePreviewHeaders($previewHeaders + $parsed['preview_headers'], [
                'Salary Earned' => 'Salary Earned',
                'AUT' => 'ABSENCES/ LATES/ UNDERTIME',
            ]),
            'field_order' => $this->prependFields($leadingFields, $this->availableFieldOrder($parsed)),
            'errors' => $errors,
        ];
    }

    public function cleanRegular($filePath): array
    {
        $parsed = $this->parseSheetByHeaders($filePath, 7, [
            'No.' => ['No.', 'No'],
            'Employee' => ['Employee', 'Name'],
            'Position' => ['Position'],
            'Rate/Month' => ['Rate/Month', 'Rate / Month', 'Monthly Rate'],
            'Salary Grade' => ['Salary Grade', 'Salary Grade/Step'],
            'GSIS' => ['GSIS'],
            'Withholding Tax' => ['Withholding Tax', 'Witholding Tax'],
            'PAG-IBIG' => ['PAG-IBIG', 'Pag-ibig'],
            'PHIL-HEALTH' => ['PHIL-HEALTH', 'PhilHealth', 'Phil-Health'],
            'Pag-ibig MP2 (Savings)' => ['Pag-ibig MP2 (Savings)', 'Pag-Ibig MP 2 (Savings)'],
            'Pag-ibig Calamity Loan' => ['Pag-ibig Calamity Loan', 'Pag-Ibig Calamity Loan'],
            'Pag-ibig MPL' => ['Pag-ibig MPL', 'Pag-Ibig MPL'],
            'GSIS Financial Assistance Loan' => ['GSIS Financial Assistance Loan'],
            'GSIS MPL' => ['GSIS MPL'],
            'GSIS Policy Loan' => ['GSIS Policy Loan'],
            'GSIS Emer. Loan' => ['GSIS Emer. Loan', 'GSIS Emergency Loan'],
            'GSIS Conso Loan' => ['GSIS Conso Loan', 'GSIS Consol Loan', 'GSIS Consolidated Loan'],
            'GSIS Optional Prem' => ['GSIS Optional Prem', 'GSIS Optional Premium'],
            'GSIS MPL LITE' => ['GSIS MPL LITE'],
            'GSIS Educ' => ['GSIS Educ', 'GSIS Education', 'GSIS Educational Loan'],
            'Real Estate' => ['Real Estate', 'Real Estate Loan'],
            'Landbank' => ['Landbank'],
            'Computer Loan' => ['Computer Loan'],
            'OPTICAL c/o TAPIEA' => ['OPTICAL c/o TAPIEA', 'Optical c/o TAPIEA', 'Optical'],
            'HMO c/o TAPIEA' => ['HMO c/o TAPIEA', 'HMO C/O TAPIEA', 'HMO'],
            'Total Deductions' => ['Total Deductions'],
            'Net Pay' => ['Net Pay', 'Net Salary'],
            'Net Salary 15th' => ['Net Salary 15th', 'Net Salary'],
            'Net Salary 31st' => ['Net Salary 31st', 'Net Salary'],
        ], [], [
            'Salary Grade',
            'GSIS Conso Loan',
            'GSIS Optional Prem',
            'GSIS Educ',
            'Real Estate',
            'Computer Loan',
            'OPTICAL c/o TAPIEA',
            'HMO c/o TAPIEA',
        ]);

        $cleaned = array_filter($parsed['rows'], function ($row) {
            $employee = (string) ($row['Employee'] ?? '');
            $rate = (string) ($row['Rate/Month'] ?? '');
            return stripos($employee, 'TOTAL') === false && stripos($rate, 'TOTAL') === false;
        });

        foreach ($cleaned as $i => $row) {
            $employee = (string) ($row['Employee'] ?? '');
            $rate = (string) ($row['Rate/Month'] ?? '');
            if (stripos($employee, 'Grand Total') !== false || stripos($rate, 'Grand Total') !== false) {
                $cleaned = array_slice($cleaned, 0, $i);
                break;
            }
        }

        $errors = [];
        $hasSalaryGrade = $this->fieldWasParsed($parsed, 'Salary Grade');

        foreach ($cleaned as &$row) {
            [$name, $uploadedPosition] = $this->extractNamePositionBlock((string) ($row['Employee'] ?? ''));
            $employeeNo = $this->employeeService->getEmployeeNoBasedOnFullName($name);

            $row['Employee'] = $name;
            $row['Position'] = $uploadedPosition ?: (string) ($row['Position'] ?? '');
            $row['Employee No'] = $employeeNo ?? '';

            if ($employeeNo) {
                $employee = $this->employeeService->getEmployee('information', $employeeNo);
                if ($employee) {
                    $row['Position'] = $row['Position'] ?: ($employee->position_name ?? '');
                }
            } elseif ($name !== '') {
                $errors[] = ['name' => $name, 'reason' => 'Unknown employee no'];
            }

            if (!$hasSalaryGrade) {
                unset($row['Salary Grade']);
            }
        }

        $cleaned = array_filter($cleaned, fn ($row) => !empty($row['Employee']) && !empty($row['Rate/Month']));
        $leadingFields = ['Employee No', 'Employee', 'Position'];
        $previewHeaders = ['Employee No' => 'Employee No', 'Employee' => 'Employee', 'Position' => 'Position'];

        if ($hasSalaryGrade) {
            $leadingFields[] = 'Salary Grade';
            $previewHeaders['Salary Grade'] = 'Salary Grade';
        }

        return [
            'rows' => array_values($cleaned),
            'preview_headers' => $previewHeaders + $parsed['preview_headers'],
            'field_order' => $this->prependFields($leadingFields, $this->availableFieldOrder($parsed)),
            'errors' => $errors,
        ];
    }

    public function importCOS(array $data)
    {
        $label = $data['label'];
        $payrollNo = generateNo('SL-', 4);
        $employmentTypeId = $data['employment_type'];
        $cutoff = $data['cutoff'];
        $periodCovered = $data['period_covered'];
        $payrollDate = $data['date'];
        $noEmployee = count($data['data']);

        $grossAmount = $deductionAmount = $netPayAmount = 0;
        foreach ($data['data'] as $employee) {
            $grossAmount += $employee['Total Salary'] ?? 0;
            $deductionAmount += ($employee['AUT'] ?? 0) + ($employee['Two Percent'] ?? 0) + ($employee['Three Percent'] ?? 0) + ($employee['Five Percent'] ?? 0) + ($employee['Healthcard'] ?? 0);
            $netPayAmount += $employee['Net Salary'] ?? 0;
        }

        $payrollId = DB::table('payroll_salary')->insertGetId([
            'batch_id' => null,
            'label' => $label,
            'payroll_no' => $payrollNo,
            'employment_type_id' => $employmentTypeId,
            'cutoff' => $cutoff,
            'period_covered' => $periodCovered,
            'no_employee' => $noEmployee,
            'gross_amount' => $grossAmount,
            'deduction_amount' => $deductionAmount,
            'netpay_amount' => $netPayAmount,
            'payroll_date' => $payrollDate,
            'processed_by_id' => Auth::id(),
            'status' => 'approved',
            'is_aut_deducted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jobs = [];
        foreach ($data['data'] as $employee) {
            $jobs[] = new SalaryJobImport($employee, $payrollId);
        }

        $batch = Bus::batch($jobs)
            ->then(fn () => DB::table('payroll_salary')->where('id', $payrollId)->update(['status' => 'completed']))
            ->catch(fn ($batch, \Throwable $e) => DB::table('payroll_salary')->where('id', $payrollId)->update(['status' => 'failed']))
            ->dispatch();

        DB::table('payroll_salary')->where('id', $payrollId)->update(['batch_id' => $batch->id]);

        return [
            'status' => 'success',
            'message' => 'Importing Started!',
            'redirect' => route('salary-pay.show', ['salary_pay' => $payrollNo, 'batch_id' => $batch->id]),
        ];
    }

    public function importRegular(array $data)
    {
        $label = $data['label'];
        $payrollNo = generateNo('SL-', 4);
        $employmentTypeId = $data['employment_type'];
        $cutoff = $data['cutoff'];
        $periodCovered = $data['period_covered'];
        $payrollDate = $data['date'];
        $noEmployee = count($data['data']);
        [$year, $month] = array_map('intval', explode('-', substr($payrollDate, 0, 7)));
        $rows = $data['data'] ?? [];

        $grossAmount = 0;
        $deductionAmount = 0;
        $netPayAmount = 0;

        foreach ($rows as $employee) {
            $rowTotalDeductions = $this->toAmount($employee['Total Deductions'] ?? 0);
            $rowNetPay = $this->amountFromKeys($employee, ['Net Pay', 'Net Salary']);
            $grossAmount += $rowNetPay + $rowTotalDeductions;
            $deductionAmount += $rowTotalDeductions;
            $netPayAmount += $rowNetPay;
        }

        $moduleTabs = $this->getRegularModuleTabsByHeader();
        $salaryTaxYearId = $this->getSalaryTaxYearId($year);

        DB::transaction(function () use ($label, $payrollNo, $employmentTypeId, $cutoff, $periodCovered, $payrollDate, $noEmployee, $grossAmount, $deductionAmount, $netPayAmount, $rows, $year, $month, $moduleTabs, $salaryTaxYearId) {
            $payrollId = DB::table('payroll_salary')->insertGetId([
                'batch_id' => null,
                'label' => $label,
                'payroll_no' => $payrollNo,
                'employment_type_id' => $employmentTypeId,
                'cutoff' => $cutoff,
                'period_covered' => $periodCovered,
                'no_employee' => $noEmployee,
                'gross_amount' => $grossAmount,
                'deduction_amount' => $deductionAmount,
                'netpay_amount' => $netPayAmount,
                'payroll_date' => $payrollDate,
                'processed_by_id' => Auth::id(),
                'status' => 'completed',
                'is_aut_deducted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($rows as $employee) {
                $employeeNo = trim((string) ($employee['Employee No'] ?? ''));
                [$name, $uploadedPosition] = $this->extractNamePositionBlock((string) ($employee['Employee'] ?? ''));
                $name = $name ?: ($employee['Employee'] ?? null);

                if (!$name) {
                    continue;
                }

                $rowDeductions = $this->extractRegularDeductions($employee, $moduleTabs);
                $totalDeductions = $this->toAmount($employee['Total Deductions'] ?? 0);
                $netPay = $this->amountFromKeys($employee, ['Net Pay', 'Net Salary']);

                $pspeId = DB::table('payroll_salary_permanent_employees')->insertGetId([
                    'payroll_salary_id' => $payrollId,
                    'employee_no' => $employeeNo,
                    'name' => $name,
                    'position' => ($employee['Position'] ?? '') ?: $uploadedPosition,
                    'monthly_rate' => $this->toAmount($employee['Rate/Month'] ?? 0),
                    'salary_grade' => (string) ($employee['Salary Grade'] ?? ''),
                    'ut' => 0,
                    'absences' => 0,
                    'overtime' => 0,
                    'holiday' => 0,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                    'salary_adjustment' => 0,
                    'remarks' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($rowDeductions as $deduction) {
                    DB::table('payroll_salary_permanents_employee_deductions')->insert([
                        'pspe_id' => $pspeId,
                        'deduction_type' => $deduction['label'],
                        'amount' => $deduction['amount'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($employeeNo !== '' && $deduction['type'] === 'module_tab' && !empty($deduction['module_tab_id'])) {
                        $this->incrementExistingMonthlyDeduction(
                            'module_tab_employees',
                            ['module_tab_id' => $deduction['module_tab_id'], 'employee_no' => $employeeNo, 'year' => $year, 'month' => $month],
                            $deduction['amount']
                        );
                    }

                    if ($employeeNo !== '' && $deduction['type'] === 'salary_tax' && $salaryTaxYearId) {
                        $this->incrementExistingMonthlyDeduction(
                            'employee_payroll_components',
                            ['tax_deduction_id' => $salaryTaxYearId, 'employee_no' => $employeeNo, 'month' => $month],
                            $deduction['amount']
                        );
                    }
                }
            }
        });

        return [
            'status' => 'success',
            'message' => 'Regular payroll imported successfully.',
            'redirect' => route('salary-pay.show', ['salary_pay' => $payrollNo]),
        ];
    }

    private function extractRegularDeductions(array $employee, array $moduleTabs = []): array
    {
        $deductions = [];
        foreach ($employee as $header => $value) {
            if (in_array($header, $this->getRegularStaticHeaders(), true)) {
                continue;
            }

            $resolved = $this->resolveRegularDeduction((string) $header, $moduleTabs);
            $amount = $this->toAmount($value);

            if ($resolved['type'] === null || $amount <= 0) {
                continue;
            }

            $deductions[] = [
                'header' => (string) $header,
                'label' => $resolved['label'],
                'type' => $resolved['type'],
                'module_tab_id' => $resolved['module_tab_id'],
                'amount' => $amount,
            ];
        }

        return $deductions;
    }

    private function getRegularStaticHeaders(): array
    {
        return ['No.', 'Employee No', 'Employee', 'Position', 'Salary Grade', 'Rate/Month', 'Total Deductions', 'Net Pay', 'Net Salary', 'Net Salary 15th', 'Net Salary 31st'];
    }

    private function getRegularDirectHeaderDeductions(): array
    {
        return [
            'GSIS Conso Loan' => ['GSIS Conso Loan', 'GSIS Consol Loan', 'GSIS Consolidated Loan'],
            'GSIS Optional Prem' => ['GSIS Optional Prem', 'GSIS Optional Premium'],
            'GSIS Educ' => ['GSIS Educ', 'GSIS Education', 'GSIS Educational Loan'],
            'Real Estate' => ['Real Estate', 'Real Estate Loan'],
            'Computer Loan' => ['Computer Loan'],
            'OPTICAL c/o TAPIEA' => ['OPTICAL c/o TAPIEA', 'Optical c/o TAPIEA', 'Optical'],
            'HMO c/o TAPIEA' => ['HMO c/o TAPIEA', 'HMO C/O TAPIEA', 'HMO'],
        ];
    }

    private function getRegularModuleTabsByHeader(): array
    {
        $tabs = DB::table('module_tabs as mt')
            ->leftJoin('modules as m', 'mt.module_id', '=', 'm.id')
            ->select('mt.id', 'mt.tab_name', 'm.module_name')
            ->get();

        $mappedTabs = [];

        foreach ($tabs as $tab) {
            $labels = array_filter([$tab->tab_name, str_replace(($tab->module_name ? $tab->module_name . ' ' : ''), '', $tab->tab_name)]);

            foreach ($labels as $label) {
                foreach ($this->buildRegularDeductionAliases($label) as $alias) {
                    $mappedTabs[$this->normalizeDeductionKey($alias)] = [
                        'id' => $tab->id,
                        'label' => str_replace(($tab->module_name ? $tab->module_name . ' ' : ''), '', $tab->tab_name),
                        'tab_name' => $tab->tab_name,
                    ];
                }
            }
        }

        return $mappedTabs;
    }

    private function buildRegularDeductionAliases(string $label): array
    {
        $aliases = [
            $label,
            str_replace(['(', ')'], '', $label),
            str_replace(['-', '.'], ' ', $label),
            preg_replace('/\s+/', ' ', $label),
            str_ireplace('Pag-Ibig', 'Pagibig', $label),
            str_ireplace('Pagibig', 'Pag-Ibig', $label),
            str_ireplace('PhilHealth', 'Phil Health', $label),
            str_ireplace('Phil Health', 'PhilHealth', $label),
            str_ireplace('Emergency', 'Emer.', $label),
            str_ireplace('Emer.', 'Emergency', $label),
            str_ireplace('Consolidated', 'Conso', $label),
            str_ireplace('Conso', 'Consolidated', $label),
            str_ireplace('Premium', 'Prem', $label),
            str_ireplace('Prem', 'Premium', $label),
            str_ireplace('Educational', 'Educ', $label),
            str_ireplace('Educ', 'Educational', $label),
            str_ireplace('MP 2', 'MP2', $label),
            str_ireplace('MP2', 'MP 2', $label),
            str_ireplace('Savings', '', $label),
            str_ireplace('c/o', '', $label),
            str_ireplace('TAPIEA', '', $label),
        ];

        return array_values(array_unique(array_filter(array_map(fn ($value) => trim(preg_replace('/\s+/', ' ', (string) $value)), $aliases))));
    }

    private function resolveRegularDeduction(string $header, array $moduleTabs): array
    {
        $normalizedHeader = $this->normalizeDeductionKey($header);

        if (in_array($normalizedHeader, [$this->normalizeDeductionKey('Withholding Tax'), $this->normalizeDeductionKey('Witholding Tax')], true)) {
            return ['type' => 'salary_tax', 'module_tab_id' => null, 'label' => 'Withholding tax'];
        }

        $moduleTab = $moduleTabs[$normalizedHeader] ?? null;

        if (!$moduleTab) {
            foreach ($this->buildRegularDeductionAliases($header) as $alias) {
                $moduleTab = $moduleTabs[$this->normalizeDeductionKey($alias)] ?? null;
                if ($moduleTab) {
                    break;
                }
            }
        }

        if (!$moduleTab) {
            foreach ($moduleTabs as $tabKey => $tab) {
                if ($this->regularDeductionFamily($tabKey) !== $this->regularDeductionFamily($normalizedHeader)) {
                    continue;
                }
                if (str_contains($tabKey, $normalizedHeader) || str_contains($normalizedHeader, $tabKey)) {
                    $moduleTab = $tab;
                    break;
                }
            }
        }

        foreach ($this->getRegularDirectHeaderDeductions() as $label => $aliases) {
            $candidates = array_merge($aliases, $this->buildRegularDeductionAliases($label));

            foreach ($candidates as $alias) {
                if ($normalizedHeader === $this->normalizeDeductionKey($alias)) {
                    return ['type' => 'known_header', 'module_tab_id' => null, 'label' => $label];
                }
            }
        }

        return $moduleTab
            ? ['type' => 'module_tab', 'module_tab_id' => $moduleTab['id'], 'label' => $moduleTab['label']]
            : ['type' => null, 'module_tab_id' => null, 'label' => $header];
    }

    private function getSalaryTaxYearId(int $year): ?int
    {
        $componentId = DB::table('payroll_components_settings')
            ->where('type', TableSettingsEnum::SALARY_ID->value)
            ->value('tax_id');

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

    private function incrementExistingMonthlyDeduction(string $table, array $attributes, float $amount): void
    {
        $existing = DB::table($table)->where($attributes)->first();

        if ($existing) {
            DB::table($table)
                ->where('id', $existing->id)
                ->update([
                    'amount' => $this->toAmount($existing->amount ?? 0) + $amount,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table($table)->insert($attributes + [
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
