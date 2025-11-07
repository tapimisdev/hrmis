<?php

namespace App\Services\Exports;

use App\Services\SalaryPayrollService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Support\Facades\Response;

class RegistryService
{
    protected $payroll;
    protected $registry;

    public static function download($payroll_no)
    {
        $service = new self();
        return $service->process($payroll_no);
    }

    private function process($payroll_no)
    {
        $this->loadPayrollData($payroll_no);

        // Determine which registry to generate
        if ($this->payroll->employment_type_id == 2) {
            return $this->exportCOS();
        } else {
            return $this->exportRegular();
        }
    }

    /* --------------------------
        LOAD PAYROLL DATA
    -------------------------- */
    private function loadPayrollData($payroll_no)
    {
        $payrollService = app(SalaryPayrollService::class);
        $this->payroll  = $payrollService->payrollDetails($payroll_no);
        $this->registry = json_decode(
            $payrollService->getPayrollRegistry($this->payroll->id, true)->getContent(),
            true
        );
    }

    /* --------------------------
        COS REGISTRY
    -------------------------- */
    private function exportCOS()
    {
        $templatePath = public_path('templates/cos/payroll_registry.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        /** ---------- STYLES ---------- */
        $headerStyle = [
            'font' => ['name' => 'Calibri', 'bold' => false, 'size' => 10],
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

        /** ---------- DEDUCTION TYPES ---------- */
        $deductionTypes = collect($this->registry)
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
            $sheet->insertNewColumnBefore($baseCol, $numDeductions);
            $col = $baseCol;
            foreach ($deductionTypes as $deduction) {
                $sheet->setCellValue("{$col}7", strtoupper($deduction));
                $applyStyle("{$col}7", array_merge_recursive($headerStyle, ['fill' => $fillStyles['deduction']]));
                $lastCol = $col;
                $col++;
            }
            $nextAfterDeductions = $col;
        } else {
            $nextAfterDeductions = 'G';
        }

        $sheet->setCellValue("{$nextAfterDeductions}7", 'NET SALARY');
        $applyStyle("{$nextAfterDeductions}7", array_merge_recursive($headerStyle, ['fill' => $fillStyles['netSalary']]));

        /** ---------- EMPLOYEE DATA ---------- */
        $employeeCount = 1;
        $row = 8;
        $projectTotalRows = [];

        [$month, $year, $period] = explode(' ', $this->payroll->period_covered);
        $sheet->setCellValue("A5", $period);
        $sheet->setCellValue("B5", $month . ' ' . $year);

        foreach ($this->registry as $project) {
            $sheet->insertNewRowBefore($row, 1);
            $projectName = strtoupper($project['name'] ?? 'UNTITLED PROJECT');

            $sheet->mergeCells("A{$row}:{$nextAfterDeductions}{$row}");
            $sheet->setCellValue("A{$row}", $projectName);
            $sheet->getStyle("A{$row}:{$nextAfterDeductions}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'italic' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => $fillStyles['project'],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(26);
            $row++;

            $startEmployeeRow = $row;

            foreach ($project['employees'] ?? [] as $employee) {
                $sheet->insertNewRowBefore($row, 1);
                $sheet->getRowDimension($row)->setRowHeight(30);

                // RichText for Name + Position
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

                // Wrap text + alignment
                $sheet->getStyle("B{$row}")->getAlignment()
                    ->setWrapText(true)
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Salary columns
                $sheet->setCellValue("C{$row}", $employee['monthly_rate'] ?? '0.00');
                $sheet->setCellValue("D{$row}", $employee['salary_earned'] ?? '0.00');
                $sheet->setCellValue("E{$row}", $employee['uat'] ?? '0.00');
                $sheet->setCellValue("F{$row}", $employee['total_salary'] ?? '0.00');

                $applyStyle("A{$row}:E{$row}", ['fill' => $fillStyles['white']]);
                $applyStyle("F{$row}", ['fill' => $fillStyles['salary']]);

                // Deductions
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

            // Project Total
            $sheet->insertNewRowBefore($row, 1);
            $totalRow = $row;
            $projectTotalRows[] = $totalRow;

            $sheet->mergeCells("A{$totalRow}:B{$totalRow}");
            $sheet->setCellValue("A{$totalRow}", "TOTAL: {$projectName}");
            $sheet->getRowDimension($totalRow)->setRowHeight(26);

            $sheet->getStyle("A{$totalRow}:{$nextAfterDeductions}{$totalRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => $fillStyles['white'],
            ]);

            $columnsToSum = array_merge(['C', 'D', 'E', 'F'], range($baseCol, chr(ord($baseCol) + $numDeductions - 1)));
            foreach ($columnsToSum as $col) {
                $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}{$startEmployeeRow}:{$col}" . ($totalRow - 1) . ")");
            }

            $sheet->setCellValue("{$nextAfterDeductions}{$totalRow}", "=SUM({$nextAfterDeductions}{$startEmployeeRow}:{$nextAfterDeductions}" . ($totalRow - 1) . ")");
            $row += 2;
        }

        // Grand Total
        if (!empty($projectTotalRows)) {
            $sheet->insertNewRowBefore($row, 1);
            $grandTotalRow = $row;
            $sheet->mergeCells("A{$grandTotalRow}:B{$grandTotalRow}");
            $sheet->setCellValue("A{$grandTotalRow}", "GRAND TOTAL:");

            $sheet->getStyle("A{$grandTotalRow}:{$nextAfterDeductions}{$grandTotalRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                    'inside'  => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                ],
                'fill' => $fillStyles['white'],
            ]);

            $columnsToSum = array_merge(['C', 'D', 'E', 'F'], range($baseCol, chr(ord($baseCol) + $numDeductions - 1)));
            foreach ($columnsToSum as $col) {
                $sumFormula = collect($projectTotalRows)->map(fn($r) => "{$col}{$r}")->implode('+');
                $sheet->setCellValue("{$col}{$grandTotalRow}", "={$sumFormula}");
            }

            $sumFormula = collect($projectTotalRows)->map(fn($r) => "{$nextAfterDeductions}{$r}")->implode('+');
            $sheet->setCellValue("{$nextAfterDeductions}{$grandTotalRow}", "={$sumFormula}");
        }

        // Column widths
        foreach ($sheet->getColumnIterator() as $column) {
            $colLetter = $column->getColumnIndex();
            if (!in_array($colLetter, ['A', 'B'])) {
                $sheet->getColumnDimension($colLetter)->setWidth(15);
            }
        }

        // Ensure exports folder exists
        $exportPath = public_path('exports');
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0775, true);
        }

        $fileName = "COS_Payroll_Registry_{$this->payroll->payroll_no}.xlsx";
        $savePath = $exportPath . '/' . $fileName;

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($savePath);

        return Response::download($savePath, $fileName)->deleteFileAfterSend(true);
    }


    /* --------------------------
        REGULAR REGISTRY
        (similar logic, just different template & columns)
    -------------------------- */
    private function exportRegular()
    {
        $templatePath = public_path('templates/regular/payroll_registry.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Add your regular registry population here
        // For now, just a placeholder row
        $sheet->setCellValue('A1', 'Regular Payroll Registry');
        $sheet->setCellValue('A2', $this->payroll->payroll_no);

        // Ensure exports folder exists
        $exportPath = public_path('exports');
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0775, true);
        }

        $fileName = "Regular_Payroll_Registry_{$this->payroll->payroll_no}.xlsx";
        $savePath = $exportPath . '/' . $fileName;

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($savePath);

        return Response::download($savePath, $fileName)->deleteFileAfterSend(true);
    }
}
