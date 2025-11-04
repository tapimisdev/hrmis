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
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

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

    public function getPayrollRegistry($payroll_id) 
    {
        $payroll_date = DB::table('payroll_salary')
            ->where('id', '=', $payroll_id)
            ->value('payroll_date');

        $pse = DB::table('payroll_salary_employee as pse')
                    ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                    ->where('payroll_salary_id', $payroll_id)
                    ->select('pse.*', 'ps.payroll_date')
                    ->get();
        
        // Get all projects for this employee
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
        
        // Group employees by project
        $projectGroups = [];
        
        foreach ($enriched as $employee) {

            $emp_project = $projects->firstWhere('id', $employee->project_id);
            
            $projectId = $emp_project->id ?? 'others';
            $projectName = $emp_project->name ?? 'No Projects';

            if(!isset($projectGroups[$projectId])) {
                $projectGroups[$projectId] = [
                    'name' => $projectName,
                    'employees' => []
                ];
            }

            // Calculate values based on your desired output structure
            $projectGroups[$projectId]['employees'][] = [
                'employee_no' => $employee->employee_no,
                'name' => $employee->name,
                'position' => $employee->position,
                'monthly_rate' => $employee->monthly_rate,
                'salary_earned' => $employee->basic_pay, // Or calculate as needed
                'uat' => $employee->ut + $employee->absences,
                'overtime' => $employee->overtime,
                'holiday' => $employee->holiday,
                'total_salary' => $employee->gross_pay,
                'deductions'    => $employee->deductions,
                'earnings'    => $employee->earnings,
                'adjustment'    => $employee->salary_adjustment,
                'net_salary' => $employee->net_pay
            ];

        }

        
        // Convert to indexed array
        $projects = array_values($projectGroups);
        
        return response()->json($projects);
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
            // For REGULAR and other types, you can implement RegularRegistry similarly
            return response()->json(['message' => 'Regular Registry download not implemented yet.'], 501);
        }

    }
   
    public function COSRegistry($payroll_id, $data)
    {
        [$month, $year, $period] = explode(' ', $data->period_covered);
        $cutoff = "$period $month $year";

        $registry = json_decode($this->getPayrollRegistry($payroll_id)->getContent(), true);

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
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                        'inside' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
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










}
