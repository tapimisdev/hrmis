<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payroll\Steps\ValidateCreatePayrollRequest;
use App\Services\SalaryPayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryApiController extends Controller
{

    protected $salary_payroll_service;

    public function __construct(SalaryPayrollService $salary_payroll_service)
    {
        $this->salary_payroll_service = $salary_payroll_service;
    }

    public function getList(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'month' => 'required|integer|min:1|max:12',
            'cutoff' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:draft,pending,approved,for_releasing,completed,cancelled',
        ]);

        $list = $this->salary_payroll_service->getPayrolls($validated);

        return response(['data' => $list, 'status' => 'success'], 200);
    }

    public function validateAndGetEmployee(ValidateCreatePayrollRequest $request)
    {
        $validatedData = $request->validated();

        $employees = $this->salary_payroll_service->getEligibleEmployees($validatedData);

        return response(['data' => $employees, 'success'], 200);
    }

    public function getAdjustments(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $holidays = $this->salary_payroll_service->getHolidays($validated);
        $suspensions = $this->salary_payroll_service->getSuspensions($validated);

        $events = $holidays->merge($suspensions);

        return response()->json($events);
    }

    public function getPayrollRegistry(string $payroll_id, bool $isGrouped = false) 
    {
        $payroll_date = DB::table('payroll_salary')
            ->where('id', $payroll_id)
            ->value('payroll_date');

        $pse = DB::table('payroll_salary_employee as pse')
            ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('payroll_salary_id', $payroll_id)
            ->select('pse.*', 'ps.payroll_date')
            ->get();

        // Get all projects for this payroll date
        $projects = DB::table('employee_projects as ep')
            ->join('projects', 'ep.project_id', '=', 'projects.id')
            ->whereDate('start_date', '<=', $payroll_date)
            ->where(function ($query) use ($payroll_date) {
                $query->whereDate('end_date', '>=', $payroll_date)
                    ->orWhereNull('end_date');
            })
            ->select('projects.id', 'projects.name')
            ->get()->unique('id');

        $enriched = $pse->map(function ($d) use ($payroll_date) {

            $deductions = DB::table('payroll_salary_employee_edeductions')
                ->where('payroll_se_id', $d->id)
                ->get();

            $earnings = DB::table('payroll_salary_employee_earnings')
                ->where('payroll_se_id', $d->id)
                ->get();

            $project_id = DB::table('employee_projects')
                ->where('employee_no', $d->employee_no)
                ->whereDate('start_date', '<=', $payroll_date)
                ->where(function ($query) use ($payroll_date) {
                    $query->whereDate('end_date', '>=', $payroll_date)
                        ->orWhereNull('end_date');
                })
                ->orderByDesc('start_date')
                ->value('project_id');

            return (object) [
                'employee_no' => $d->employee_no,
                'name' => strtoupper($d->name),
                'position' => ucfirst($d->position),
                'monthly_rate' => $d->monthly_rate,
                'salary_grade' => $d->salary_grade,
                'ut' => $d->ut,
                'absences' => $d->absences,
                'overtime' => $d->overtime,
                'holiday' => $d->holiday,
                'gsis' => $d->gsis,
                'philhealth' => $d->philhealth,
                'pagibig' => $d->pagibig,
                'w_tax' => $d->w_tax,
                'total_deductions' => $d->total_deductions,
                'total_earnings' => $d->total_earnings,
                'basic_pay' => $d->basic_pay,
                'gross_pay' => $d->gross_pay,
                'net_pay' => $d->net_pay,
                'salary_adjustment' => $d->salary_adjustment,
                'deductions' => $deductions,
                'earnings' => $earnings,
                'project_id' => $project_id
            ];
        });

        if ($isGrouped) {
            // Group employees by project
            $projectGroups = [];

            foreach ($enriched as $employee) {
                $emp_project = $projects->firstWhere('id', $employee->project_id);

                $projectId = $emp_project->id ?? 'others';
                $projectName = $emp_project->name ?? 'No Projects';

                if (!isset($projectGroups[$projectId])) {
                    $projectGroups[$projectId] = [
                        'name' => $projectName,
                        'employees' => []
                    ];
                }

                $projectGroups[$projectId]['employees'][] = [
                    'employee_no' => $employee->employee_no,
                    'name' => $employee->name,
                    'position' => $employee->position,
                    'monthly_rate' => $employee->monthly_rate,
                    'salary_earned' => $employee->basic_pay,
                    'ut' => $employee->ut + $employee->absences,
                    'overtime' => $employee->overtime,
                    'holiday' => $employee->holiday,
                    'total_salary' => $employee->gross_pay,
                    'deductions' => $employee->deductions,
                    'earnings' => $employee->earnings,
                    'adjustment' => $employee->salary_adjustment,
                    'net_salary' => $employee->net_pay
                ];
            }

            return response()->json(array_values($projectGroups));

        } else {
            // Return flat list without grouping
            $flatList = $enriched->map(function ($employee) {
                return [
                    'employee_no' => $employee->employee_no,
                    'name' => $employee->name,
                    'position' => $employee->position,
                    'monthly_rate' => $employee->monthly_rate,
                    'salary_earned' => $employee->basic_pay,
                    'ut' => $employee->ut + $employee->absences,
                    'overtime' => $employee->overtime,
                    'holiday' => $employee->holiday,
                    'total_salary' => $employee->gross_pay,
                    'deductions' => $employee->deductions,
                    'earnings' => $employee->earnings,
                    'adjustment' => $employee->salary_adjustment,
                    'net_salary' => $employee->net_pay,
                    'project_id' => $employee->project_id
                ];
            });

            return response()->json($flatList);
        }
    }

    public function approvers()
    {
        $approver_id = DB::table('application_approver')
                        ->where('type', 'payroll')
                        ->value('id');

        $user_approvers = DB::table('application_approver_users as au')
                            ->leftJoin('employee_information as ei', 'au.user_id', '=', 'ei.user_id')
                            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
                            ->where('au.application_approver_id', $approver_id)
                            ->select('ei.employee_no', 'au.level', 'ep.firstname', 'ep.lastname', 'ep.middlename', 'ei.user_id')
                            ->get();

        // dd($user_approvers);
        return response()->json($user_approvers);
    }

    private function payrollDetails($payroll_no)
    {
        $payroll = DB::table('payroll_salary')
                    ->where('payroll_no', $payroll_no)
                    ->first();

        return $payroll;
    }

    public function downloadPayrollRegistry($payroll_no)
    {
        $data = $this->payrollDetails($payroll_no);        

        if ($data->employment_type_id == 2) {
            $payroll_id = $data->id;
            return $this->COSRegistry($payroll_id, $data);
        } else {
            return response()->json(['message' => 'Regular Registry download not implemented yet.'], 501);
        }

    }
   
    public function COSRegistry($payroll_id, $data)
    {
        [$month, $year, $period] = explode(' ', $data->period_covered);
        $cutoff = "$period $month $year";

        $registry = json_decode($this->getPayrollRegistry($payroll_id, true)->getContent(), true);

        $templatePath = public_path('templates/cos/payroll_registry.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        /** ---------- STYLE DEFINITIONS ---------- */
        $headerStyle = [
            'font' => [
                'name' => 'Calibri',
                'bold' => false,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $fillStyles = [
            'deduction' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFC7CE']],
            'netSalary' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'DCE6F1']],
            'project'   => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDE9D9']],
            'salary'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEBF1DE']],
            'white'     => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
        ];

        $applyStyle = fn($range, $style) => $sheet->getStyle($range)->applyFromArray($style);

        /** ---------- DEDUCTIONS ---------- */
        $deductionTypes = collect($registry)
            ->flatMap(fn($proj) => collect($proj['employees'])
            ->flatMap(fn($emp) => collect($emp['deductions'] ?? [])->pluck('deduction_type')))
            ->unique()
            ->filter()
            ->map(fn($t) => trim($t))
            ->values()
            ->toArray();

        $numDeductions = count($deductionTypes);
        $baseCol = 'G';
        $lastCol = $baseCol;

        if ($numDeductions > 0) {
            // Insert deduction columns before column G
            $sheet->insertNewColumnBefore($baseCol, $numDeductions);

            $col = $baseCol;
            foreach ($deductionTypes as $deduction) {
                $cell = "{$col}7";
                $sheet->setCellValue($cell, strtoupper($deduction));
                $applyStyle($cell, array_merge_recursive($headerStyle, ['fill' => $fillStyles['deduction']]));
                $lastCol = $col;
                $col++;
            }

            // After inserting deductions, move to the next column for net salary
            $nextAfterDeductions = $col;
        } else {
            // If no deductions, net salary goes right after F (no column insertion)
            $nextAfterDeductions = 'G';
        }

        /** ---------- NET SALARY HEADER ---------- */
        $sheet->setCellValue("{$nextAfterDeductions}7", 'NET SALARY');
        $applyStyle("{$nextAfterDeductions}7", array_merge_recursive($headerStyle, ['fill' => $fillStyles['netSalary']]));

        /** ---------- EMPLOYEE DATA ---------- */
        $employeeCount = 1;
        $row = 8;
        $projectTotalRows = [];

        # PERIOD
        $sheet->setCellValue("A5", $period);
        $sheet->setCellValue("B5", $month . ' ' . $year);

        foreach ($registry as $project) {
            $sheet->insertNewRowBefore($row, 1);
            $projectName = strtoupper($project['name'] ?? 'UNTITLED PROJECT');

            $mergeRange = "A{$row}:{$nextAfterDeductions}{$row}";
            $sheet->mergeCells($mergeRange);
            $sheet->setCellValue("A{$row}", $projectName);
            $sheet->getStyle($mergeRange)->applyFromArray([
                'font' => ['name' => 'Calibri', 'bold' => true, 'italic' => true, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => $fillStyles['project'],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(26);
            $row++;

            $startEmployeeRow = $row;

            foreach ($project['employees'] ?? [] as $employee) {
                $sheet->insertNewRowBefore($row, 1);
                $sheet->getRowDimension($row)->setRowHeight(30);

                $richText = new RichText();
                $nameRun = $richText->createTextRun(strtoupper($employee['name'] ?? ''));
                $nameRun->getFont()->setBold(true);
                if (!empty($employee['position'])) {
                    $richText->createText("\n");
                    $posRun = $richText->createTextRun($employee['position']);
                    $posRun->getFont()->setItalic(true);
                }

                $sheet->setCellValue("A{$row}", $employeeCount);
                $sheet->setCellValueExplicit("B{$row}", $richText, DataType::TYPE_INLINE);
                $sheet->getStyle("B{$row}")->getAlignment()
                    ->setWrapText(true)
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue("C{$row}", $employee['monthly_rate'] ?? '0.00');
                $sheet->setCellValue("D{$row}", $employee['salary_earned'] ?? '0.00');
                $sheet->setCellValue("E{$row}", $employee['uat'] ?? '0.00');
                $sheet->setCellValue("F{$row}", $employee['total_salary'] ?? '0.00');

                $sheet->getStyle("C{$row}:{$nextAfterDeductions}{$row}")->getFont()->setBold(false);
                $applyStyle("A{$row}:E{$row}", ['fill' => $fillStyles['white']]);
                $applyStyle("F{$row}", ['fill' => $fillStyles['salary']]);

                // Deductions (if any)
                $deductionValues = collect($employee['deductions'] ?? [])->pluck('amount', 'deduction_type')->toArray();
                $col = $baseCol;
                foreach ($deductionTypes as $deduction) {
                    $cell = "{$col}{$row}";
                    $sheet->setCellValue($cell, $deductionValues[$deduction] ?? '-');
                    $applyStyle($cell, ['fill' => $fillStyles['deduction']]);
                    $col++;
                }

                // Net Salary
                $netSalaryCell = "{$nextAfterDeductions}{$row}";
                $sheet->setCellValue($netSalaryCell, $employee['net_salary'] ?? '0.00');
                $applyStyle($netSalaryCell, ['fill' => $fillStyles['netSalary']]);

                $employeeCount++;
                $row++;
            }

            /** ---------- PROJECT TOTAL ---------- */
            $sheet->insertNewRowBefore($row, 1);
            $totalRow = $row;
            $projectTotalRows[] = $totalRow;

            $sheet->mergeCells("A{$totalRow}:B{$totalRow}");
            $sheet->setCellValue("A{$totalRow}", "TOTAL: {$projectName}");
            $sheet->getRowDimension($totalRow)->setRowHeight(26);
            $sheet->getStyle("A{$totalRow}:{$nextAfterDeductions}{$totalRow}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 10],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => $fillStyles['white'],
            ]);

            $columnsToSum = ['C', 'D', 'E', 'F'];
            foreach ($columnsToSum as $col) {
                $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}{$startEmployeeRow}:{$col}" . ($totalRow - 1) . ")");
            }

            $col = $baseCol;
            foreach ($deductionTypes as $deduction) {
                $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}{$startEmployeeRow}:{$col}" . ($totalRow - 1) . ")");
                $col++;
            }

            $sheet->setCellValue("{$nextAfterDeductions}{$totalRow}", "=SUM({$nextAfterDeductions}{$startEmployeeRow}:{$nextAfterDeductions}" . ($totalRow - 1) . ")");
            $row += 2;
        }

        /** ---------- GRAND TOTAL ---------- */
        if (!empty($projectTotalRows)) {
            $sheet->insertNewRowBefore($row, 1);
            $grandTotalRow = $row;

            $sheet->mergeCells("A{$grandTotalRow}:B{$grandTotalRow}");
            $sheet->setCellValue("A{$grandTotalRow}", "GRAND TOTAL:");
            $sheet->getStyle("A{$grandTotalRow}:{$nextAfterDeductions}{$grandTotalRow}")
                ->applyFromArray([
                    'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                        'inside' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                    'fill' => $fillStyles['white'],
                ]);

            $columnsToSum = ['C', 'D', 'E', 'F'];
            foreach ($columnsToSum as $col) {
                $sumFormula = collect($projectTotalRows)->map(fn($r) => "{$col}{$r}")->implode('+');
                $sheet->setCellValue("{$col}{$grandTotalRow}", "={$sumFormula}");
            }

            $col = $baseCol;
            foreach ($deductionTypes as $deduction) {
                $sumFormula = collect($projectTotalRows)->map(fn($r) => "{$col}{$r}")->implode('+');
                $sheet->setCellValue("{$col}{$grandTotalRow}", "={$sumFormula}");
                $col++;
            }

            $sumFormula = collect($projectTotalRows)->map(fn($r) => "{$nextAfterDeductions}{$r}")->implode('+');
            $sheet->setCellValue("{$nextAfterDeductions}{$grandTotalRow}", "={$sumFormula}");
        }

        /** ---------- COLUMN WIDTHS ---------- */
        foreach ($sheet->getColumnIterator() as $column) {
            $colLetter = $column->getColumnIndex();
            if (in_array($colLetter, ['A', 'B'])) continue;
            $sheet->getColumnDimension($colLetter)->setWidth(15);
        }

        /** ---------- SAVE & DOWNLOAD ---------- */
        $filename = 'Payroll_Registry_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path("app/public/{$filename}");

        (new Xlsx($spreadsheet))->save($tempPath);
        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function downloadAbsencesLeaves($payroll_no)
    {

        $templatePath = public_path('templates/cos/absences-leaves.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $payroll = $this->payrollDetails($payroll_no);
        $data = $this->getEmployeeRates($payroll->id);

        [$month, $year, $period] = explode(' ', $payroll->period_covered);
        $cutoff = "$period $month $year";

        $startRow = 10;
        $templateStart = 10;
        $templateEnd = 15;

        $sheet->setCellValue("A8", strtoupper($cutoff));
        $sheet->getStyle("A8")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 12,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);


        foreach ($data as $unitName => $employees) {

            // -------------------------------
            // UNIT NAME ROW (RED FILL)
            // -------------------------------
            $sheet->insertNewRowBefore($startRow, 2);

            // Set unit name in column A only
            $sheet->setCellValue("A{$startRow}", strtoupper($unitName));
            $sheet->getRowDimension($startRow)->setRowHeight(21);

            // Apply styles
            $sheet->getStyle("A{$startRow}:L{$startRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'name' => 'Arial',
                    'underline' => Font::UNDERLINE_SINGLE,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'F2DCDB'],
                ],
            ]);


            $startRow++;

            foreach ($employees as $employee) {

                // Duplicate template rows
                $sheet->insertNewRowBefore($startRow, ($templateEnd - $templateStart + 1));
                
                // Duplicate template rows
                for ($i = 0; $i <= ($templateEnd - $templateStart); $i++) {
                    $sourceRow = $templateStart + $i;
                    $targetRow = $startRow + $i;
                    // Apply white fill to all employee rows
                    $sheet->getStyle("A{$targetRow}:L{$targetRow}")
                        ->applyFromArray([
                            'font' => [
                                'name' => 'Calibri', 
                                'bold' => true, 'size' => 10,
                                'underline' => Font::UNDERLINE_NONE,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                            'borders' => [
                                'outline' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['argb' => 'D9D9D9'],
                                ],
                                'inside' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['argb' => 'D9D9D9'],
                                ],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFFFFFFF'], // WHITE fill
                            ],
                        ]);
                }

                $font = [
                    'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
                ];
                
                $alignLeft = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];
                $alignRight = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
                $alignCenter = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];

                // Name - bold, left-aligned
                $sheet->setCellValue("A{$startRow}", $employee['name']);
                $sheet->getStyle("A{$startRow}")->applyFromArray($font);
                $sheet->getStyle("A{$startRow}")->applyFromArray($alignLeft);
                $sheet->getStyle("A{$startRow}")->getFont()->setBold(true);

                // Row 1 - Monthly rate & days - right-aligned
                $sheet->setCellValue("B{$startRow}", 'Php' . number_format($employee['monthly_rate'], 2));
                $sheet->setCellValue("D{$startRow}", 22);
                $sheet->getStyle("B{$startRow}:C{$startRow}")->applyFromArray($alignRight);
                $sheet->getStyle("B{$startRow}:C{$startRow}")->applyFromArray($font);

                // Position - italic, left-aligned
                $sheet->setCellValue("A" . ($startRow + 1), $employee['position']);
                $sheet->getStyle("A" . ($startRow + 1))->applyFromArray($font);
                $sheet->getStyle("A" . ($startRow + 1))->applyFromArray($alignLeft);
                $sheet->getStyle("A" . ($startRow + 1))->getFont()->setItalic(true);

                // Row 3: Rate/day
                $sheet->setCellValue("A" . ($startRow + 2), "Rate/day");
                $sheet->setCellValue("B" . ($startRow + 2), $employee['monthly_rate']);
                $sheet->setCellValue("C" . ($startRow + 2), "/");
                $sheet->setCellValue("D" . ($startRow + 2), 22);
                $sheet->setCellValue("E" . ($startRow + 2), "/8");
                $sheet->setCellValue("F" . ($startRow + 2), "=");
                $sheet->setCellValue("G" . ($startRow + 2), $employee['daily_rate']);
                $sheet->setCellValue("H" . ($startRow + 2), "X");
                $sheet->setCellValue("I" . ($startRow + 2), 0);
                $sheet->setCellValue("J" . ($startRow + 2), "---------");
                $sheet->setCellValue("K" . ($startRow + 2), "₱");
                $sheet->setCellValue("L" . ($startRow + 2), number_format(0 * 2, 2));
                $sheet->getStyle("A" . ($startRow + 2) . ":L" . ($startRow + 2))->applyFromArray($font);
                $sheet->getStyle("A" . ($startRow + 2) . ":L" . ($startRow + 2))->applyFromArray($alignRight);
                $sheet->getStyle("D" . ($startRow + 2) . ":L" . ($startRow + 2))->applyFromArray($alignCenter);
                $sheet->getStyle("F" . ($startRow + 2) . ":L" . ($startRow + 2))->applyFromArray($alignRight);

                // Row 4: Rate/hr
                $sheet->setCellValue("A" . ($startRow + 3), "Rate/hr");
                $sheet->setCellValue("B" . ($startRow + 3), $employee['monthly_rate']);
                $sheet->setCellValue("C" . ($startRow + 3), "/");
                $sheet->setCellValue("D" . ($startRow + 3), 22);
                $sheet->setCellValue("E" . ($startRow + 3), "/8");
                $sheet->setCellValue("F" . ($startRow + 3), "=");
                $sheet->setCellValue("G" . ($startRow + 3), $employee['hourly_rate']);
                $sheet->setCellValue("H" . ($startRow + 3), "X");
                $sheet->setCellValue("I" . ($startRow + 3), 0);
                $sheet->setCellValue("J" . ($startRow + 3), "---------");
                $sheet->setCellValue("K" . ($startRow + 3), "₱");
                $sheet->setCellValue("L" . ($startRow + 3), number_format(0 * 2, 2));
                $sheet->getStyle("A" . ($startRow + 3) . ":L" . ($startRow + 3))->applyFromArray($font);
                $sheet->getStyle("A" . ($startRow + 3) . ":L" . ($startRow + 3))->applyFromArray($alignRight);
                $sheet->getStyle("D" . ($startRow + 3) . ":L" . ($startRow + 3))->applyFromArray($alignCenter);
                $sheet->getStyle("F" . ($startRow + 3) . ":L" . ($startRow + 3))->applyFromArray($alignRight);

                // Row 5: Rate/min
                $sheet->setCellValue("A" . ($startRow + 4), "Rate/min");
                $sheet->setCellValue("B" . ($startRow + 4), $employee['monthly_rate']);
                $sheet->setCellValue("C" . ($startRow + 4), "/");
                $sheet->setCellValue("D" . ($startRow + 4), 22);
                $sheet->setCellValue("E" . ($startRow + 4), "/8/60");
                $sheet->setCellValue("F" . ($startRow + 4), "=");
                $sheet->setCellValue("G" . ($startRow + 4), $employee['minute_rate']);
                $sheet->setCellValue("H" . ($startRow + 4), "X");
                $sheet->setCellValue("I" . ($startRow + 4), 0);
                $sheet->setCellValue("J" . ($startRow + 4), "---------");
                $sheet->setCellValue("K" . ($startRow + 4), "₱");
                $sheet->setCellValue("L" . ($startRow + 4), number_format(0 * 2, 2));
                $sheet->getStyle("A" . ($startRow + 4) . ":L" . ($startRow + 4))->applyFromArray($font);
                $sheet->getStyle("A" . ($startRow + 4) . ":L" . ($startRow + 4))->applyFromArray($alignRight);
                $sheet->getStyle("D" . ($startRow + 4) . ":L" . ($startRow + 4))->applyFromArray($alignCenter);
                $sheet->getStyle("F" . ($startRow + 4) . ":L" . ($startRow + 4))->applyFromArray($alignRight);

                // Row 6: TOTAL
                $sheet->setCellValue("J" . ($startRow + 5), "TOTAL");
                $sheet->setCellValue("K" . ($startRow + 5), "₱");
                $sheet->setCellValue("L" . ($startRow + 5), number_format(0 * 2, 2));
                $sheet->getStyle("J" . ($startRow + 5) . ":L" . ($startRow + 5))->applyFromArray($font);
                $sheet->getStyle("J" . ($startRow + 5) . ":L" . ($startRow + 5))->applyFromArray($alignRight);

                // Next block
                $startRow += ($templateEnd - $templateStart + 1);
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $outputPath = storage_path('app/public/absences-leaves-filled.xlsx');
        $writer->save($outputPath);

        return response()->download($outputPath);
    }

    private function getEmployeeRates($payroll_id)
    {
        $employees = DB::table('payroll_salary_employee as pse')
            ->where('pse.payroll_salary_id', $payroll_id)
            ->leftJoinSub(
                DB::table('employee_projects as ep')
                    ->select('ep.*')
                    ->whereRaw('ep.id IN (SELECT MAX(id) FROM employee_projects GROUP BY employee_no)'),
                'latest_proj',
                'pse.employee_no',
                '=',
                'latest_proj.employee_no'
            )
            ->leftJoin('projects as p', 'latest_proj.project_id', '=', 'p.id')
            ->select(
                'pse.*',
                'latest_proj.*',
                'p.name as project_name'
            )
            ->get()
            ->map(function ($employee) {
                $employee->aut = $employee->ut + $employee->absences;

                

                return $employee;
            });

        $grouped = $employees->groupBy('project_name');

        $data = [];

        foreach ($grouped as $unitName => $unitEmployees) {
            $data[$unitName] = $unitEmployees->map(function ($employee) {
                return [
                    'name'         => $employee->name,
                    'position'     => $employee->position,
                    'monthly_rate' => $employee->monthly_rate,
                    'daily_rate'   => number_format($employee->monthly_rate / 22, 2),
                    'hourly_rate'  => number_format(($employee->monthly_rate / 22) / 8, 2),
                    'minute_rate'  => number_format((($employee->monthly_rate / 22) / 8) / 60, 2),
                ];
            })->toArray(); 
        }

        return $data;

    }

    public function downloadPayslip($payroll_no)
    {
        $payroll    = $this->payrollDetails($payroll_no);
        $payroll_id = $payroll->id;
        $registry = json_decode($this->getPayrollRegistry($payroll_id)->getContent(), true);
        
        // dd($registry);

        $templatePath = public_path('templates/cos/payslip.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);
        $sheet        = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()->clearPrintArea();

        // Page setup
        $sheet->getPageSetup()->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);

        $templateStart  = 1;
        $templateEnd    = 20;
        $templateHeight = $templateEnd - $templateStart + 1;

        // Store original drawings
        $originalDrawings = $sheet->getDrawingCollection();
        $currentRow = $templateEnd + 1;

        foreach ($registry as $index => $employee) {

            // 1. Insert space for new payslip
            $sheet->insertNewRowBefore($currentRow, $templateHeight);

            // 2. Copy each row + style + formulas
            for ($row = $templateStart; $row <= $templateEnd; $row++) {
                $newRow = $currentRow + ($row - $templateStart);

                foreach ($sheet->getColumnIterator() as $column) {
                    $col = $column->getColumnIndex();
                    $cell = $sheet->getCell($col . $row);

                    $sheet->setCellValue($col . $newRow, $cell->getValue());
                    $sheet->duplicateStyle($sheet->getStyle($col . $row), $col . $newRow);
                }

                // Copy merged cells for this row
                foreach ($sheet->getMergeCells() as $merged) {
                    [$start, $end] = explode(':', $merged);
                    [$startCol, $startRow] = Coordinate::coordinateFromString($start);
                    [$endCol, $endRow]     = Coordinate::coordinateFromString($end);

                    if ($startRow == $row && $endRow == $row) {
                        $newStart = $startCol . $newRow;
                        $newEnd   = $endCol . $newRow;
                        $sheet->mergeCells("$newStart:$newEnd");
                    }
                }
            }

            // Set row heights
            $sheet->getRowDimension($currentRow + (3 - $templateStart))->setRowHeight(22.20);
            $sheet->getRowDimension($currentRow + (5 - $templateStart))->setRowHeight(33);

            // Header merge + styling
            $headerRow1 = $currentRow + (2 - $templateStart);
            $headerRow2 = $headerRow1 + 1;
            $headerMerge = "A{$headerRow1}:M{$headerRow2}";
            $sheet->mergeCells($headerMerge);
            $sheet->getStyle($headerMerge)->getFont()->setBold(true);
            $sheet->getStyle($headerMerge)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // Duplicate logos
            foreach ($originalDrawings as $drawing) {
                if ($drawing instanceof Drawing) {
                    $coord = $drawing->getCoordinates();
                    [$col, $row] = Coordinate::coordinateFromString($coord);

                    if ($row >= 1 && $row <= 2) {
                        $zip = new \ZipArchive();
                        if ($zip->open($templatePath) === true) {
                            $internal = str_replace('zip://' . $templatePath . '#', '', $drawing->getPath());
                            $stream = $zip->getStream($internal);

                            if ($stream) {
                                $imgData = stream_get_contents($stream);
                                fclose($stream);

                                $tmpImg = public_path('temp_logo_' . uniqid() . '.png');
                                file_put_contents($tmpImg, $imgData);

                                $newLogo = new Drawing();
                                $newLogo->setName($drawing->getName());
                                $newLogo->setDescription($drawing->getDescription());
                                $newLogo->setPath($tmpImg);
                                $newLogo->setOffsetX($drawing->getOffsetX());
                                $newLogo->setOffsetY($drawing->getOffsetY());
                                $newLogo->setHeight($drawing->getHeight());
                                $newLogo->setWidth($drawing->getWidth());
                                $newLogo->setCoordinates($col . ($currentRow + ($row - $templateStart)));
                                $newLogo->setWorksheet($sheet);
                            }
                            $zip->close();
                        }
                    }
                }
            }

            $totalDeductions = 0;

            // Set current row for employee info
            $curRow = $currentRow + (6 - $templateStart);

            // Insert employee name
            $sheet->setCellValue("C{$curRow}", $employee['name'] ?? '');

            // Insert salary label and amount
            $salaryRow = $curRow + 4;
            $sheet->setCellValue("A{$salaryRow}", 'Monthly Salary');
            $sheet->setCellValue("D{$salaryRow}", $employee['monthly_rate'] ?? 0);
            $sheet->getStyle("D{$salaryRow}")
                ->getNumberFormat()
                ->setFormatCode('_("₱"* #,##0.00_);_("₱"* (#,##0.00);_("₱"* "-"??_);_(@_)');

            // Start deductions on the same row as monthly salary
            $deductionStartRow = $salaryRow; 
            $totalRow = $deductionStartRow; // initialize total row to salary row

            if (!empty($employee['deductions'])) {
                foreach ($employee['deductions'] as $i => $deduction) {
                    $row = $deductionStartRow + $i;
                    $totalRow = $row; // last deduction row

                    $amount = isset($deduction['amount']) ? (float)$deduction['amount'] : 0;
                    $totalDeductions += $amount;

                    // Insert deduction type and amount
                    $sheet->setCellValue("F{$row}", $deduction['deduction_type'] ?? '');
                    $sheet->setCellValue("I{$row}", $amount);

                    // Copy style from salary row
                    $sheet->duplicateStyle($sheet->getStyle("F{$salaryRow}"), "F{$row}");
                    $sheet->duplicateStyle($sheet->getStyle("I{$salaryRow}"), "I{$row}");

                    // Apply peso currency format
                    $sheet->getStyle("I{$row}")
                        ->getNumberFormat()
                        ->setFormatCode('_("₱"* #,##0.00_);_("₱"* (#,##0.00);_("₱"* "-"??_);_(@_)');
                }
            }

            // Move to row after last deduction (or salary if no deductions)
            $totalRow += 2;

            // Insert totals in aligned row: D = monthly salary, I = total deductions
            $sheet->setCellValue("D{$totalRow}", $employee['monthly_rate'] ?? 0);
            $sheet->getStyle("D{$totalRow}")
                ->getNumberFormat()
                ->setFormatCode('_("₱"* #,##0.00_);_("₱"* (#,##0.00);_("₱"* "-"??_);_(@_)');
            $sheet->getStyle("D{$totalRow}")->getBorders()->applyFromArray([
                'font' => [
                    'color' => ['argb' => 'C00000'],
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
                'left' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
            ]);

            $sheet->mergeCells("I{$totalRow}:J{$totalRow}");
            $sheet->setCellValue("I{$totalRow}", $totalDeductions);
            $sheet->getStyle("I{$totalRow}:J{$totalRow}")->getFont()->getColor()->setARGB('C00000');
            $sheet->getStyle("I{$totalRow}:J{$totalRow}")
                ->getNumberFormat()
                ->setFormatCode('_("₱"* #,##0.00_);_("₱"* (#,##0.00);_("₱"* "-"??_);_(@_)');
            $sheet->getStyle("I{$totalRow}:J{$totalRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'C00000'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'left' => [
                        'borderStyle' => Border::BORDER_NONE,
                    ],
                    'right' => [
                        'borderStyle' => Border::BORDER_NONE,
                    ],
                ],
            ]);
            
            $currentRow += $templateHeight;

            if (($index + 1) % 2 == 0) {
                $sheet->setBreak("A" . $currentRow, Worksheet::BREAK_ROW);
            }
        }

        // After looping, remove the original template rows (1-20)
        $sheet->removeRow(1, $templateHeight);

        // Save to new Excel
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $outputPath = storage_path('app/public/payslip.xlsx');
        $writer->save($outputPath);

        // Cleanup temp images
        foreach (glob(public_path('temp_logo_*.png')) as $tmp) {
            @unlink($tmp);
        }

        return response()->download($outputPath);
    }





    private function getEmployeePayslip($payroll_id)
    {
        $employees = DB::table('payroll_salary_employee as pse')
            ->where('pse.payroll_salary_id', $payroll_id)
            ->leftJoinSub(
                DB::table('employee_projects as ep')
                    ->select('ep.*')
                    ->whereRaw('ep.id IN (SELECT MAX(id) FROM employee_projects GROUP BY employee_no)'),
                'latest_proj',
                'pse.employee_no',
                '=',
                'latest_proj.employee_no'
            )
            ->leftJoin('projects as p', 'latest_proj.project_id', '=', 'p.id')
            ->select(
                'pse.*',
                'latest_proj.*',
                'p.name as project_name'
            )
            ->get();

        return $employees;
    }











}
