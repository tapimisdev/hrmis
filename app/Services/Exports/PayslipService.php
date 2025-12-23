<?php

namespace App\Services\Exports;

use App\Services\SalaryPay\PayrollService;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Str;

class PayslipService
{
    protected $payroll;
    protected $registry;
    protected $spreadsheet;
    protected $sheet;
    protected $templatePath;
    protected $templateStart = 1;
    protected $templateEnd   = 20;
    protected $templateHeight = 20;
    protected $currentRow;
    protected $originalDrawings;

    public static function download($payroll_no)
    {
        $service = new self;
        return $service->process($payroll_no);
    }

    private function process($payroll_no)
    {
        $this->loadPayrollData($payroll_no);
        $this->loadTemplate();
        $this->applyPageSetup();

        $this->currentRow = $this->templateEnd + 1;

        foreach ($this->registry as $index => $employee) {
            $this->insertPayslipSection();
            $this->cloneTemplateRows();
            $this->reapplyImages();
            $this->populateEmployeeData($employee);

            // Add Page Break after every 2 payslips
            if (($index + 1) % 2 == 0) {
                $this->sheet->setBreak('A' . $this->currentRow, Worksheet::BREAK_ROW);
            }

        }

        $this->sheet->removeRow(1, $this->templateHeight);
        return $this->exportFile();
    }

    /* ----------------------------------------------------------
        LOAD DATA
    ---------------------------------------------------------- */
    private function loadPayrollData($payroll_no)
    {
        $payrollService =  app(PayrollService::class);
        $this->payroll = $payrollService->payrollDetails($payroll_no);
        $this->registry = json_decode($payrollService->getPayrollRegistry($this->payroll, $this->payroll->id, false)->getContent(), true);
    }

    /* ----------------------------------------------------------
        LOAD TEMPLATE
    ---------------------------------------------------------- */
    private function loadTemplate()
    {
        $this->templatePath = public_path('templates/cos/payslip.xlsx');
        $this->spreadsheet  = IOFactory::load($this->templatePath);
        $this->sheet        = $this->spreadsheet->getActiveSheet();
        $this->templateHeight = $this->templateEnd - $this->templateStart + 1;
        $this->originalDrawings = $this->sheet->getDrawingCollection();
    }

    private function applyPageSetup()
    {
        $this->sheet->getPageSetup()->clearPrintArea();
        $this->sheet->getPageSetup()
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);

        $this->sheet->getPageMargins()
            ->setTop(0.25)->setBottom(0.25)
            ->setLeft(0.25)->setRight(0.25);
    }

    /* ----------------------------------------------------------
        TEMPLATE CLONING
    ---------------------------------------------------------- */
    private function insertPayslipSection()
    {
        $this->sheet->insertNewRowBefore($this->currentRow, $this->templateHeight);
    }

    private function cloneTemplateRows()
    {
        $templateStart = $this->templateStart;
        $templateEnd   = $this->templateEnd;
        $sheet         = $this->sheet;
        $currentRow    = $this->currentRow;
        $monthYear     = $this->formatMonthYear();

        // Copy each row + styles + merged cells
        for ($row = $templateStart; $row <= $templateEnd; $row++) {
            $newRow = $currentRow + ($row - $templateStart);

            // Copy cell values + style column-by-column
            foreach ($sheet->getColumnIterator() as $column) {
                $col  = $column->getColumnIndex();
                $cell = $sheet->getCell($col . $row);

                $sheet->setCellValue($col . $newRow, $cell->getValue());
                $sheet->duplicateStyle($sheet->getStyle($col . $row), $col . $newRow);
            }

            // Copy merged cell formatting only if merge exists on the template row
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

        $sheet->getRowDimension($currentRow + (3 - $templateStart))->setRowHeight(22.20);
        $sheet->getRowDimension($currentRow + (5 - $templateStart))->setRowHeight(33);

        $headerCell = "A" . ($currentRow + (5 - $templateStart));
        $sheet->setCellValue(
            $headerCell,
            "***** PAY SLIP *****" . chr(10) . "for the month {$monthYear}"
        );
        $sheet->getStyle($headerCell)->getAlignment()->setWrapText(true);

        $headerRow1  = $currentRow + (2 - $templateStart);
        $headerRow2  = $headerRow1 + 1;
        $headerMerge = "A{$headerRow1}:M{$headerRow2}";
        $sheet->mergeCells($headerMerge);

        $sheet->getStyle($headerMerge)->getFont()->setBold(true);
        $sheet->getStyle($headerMerge)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }


    /* ----------------------------------------------------------
        IMAGE HANDLING
    ---------------------------------------------------------- */
    private function reapplyImages()
    {
        foreach ($this->originalDrawings as $drawing) {
            if (!$drawing instanceof Drawing) continue;

            $coord = $drawing->getCoordinates();
            [$col, $row] = Coordinate::coordinateFromString($coord);

            $zip = new \ZipArchive();
            if ($zip->open($this->templatePath) === true) {
                $internal = str_replace('zip://' . $this->templatePath . '#', '', $drawing->getPath());
                $stream = $zip->getStream($internal);
                if (!$stream) continue;

                $imgData = stream_get_contents($stream);
                fclose($stream);

                $tmp = public_path('temp_img_' . Str::uuid() . '.png');
                file_put_contents($tmp, $imgData);

                $new = new Drawing();
                $new->setPath($tmp);
                $new->setName($drawing->getName());
                $new->setDescription($drawing->getDescription());
                $new->setHeight($drawing->getHeight());
                $new->setWidth($drawing->getWidth());
                $new->setOffsetX($drawing->getOffsetX());
                $new->setOffsetY($drawing->getOffsetY());
                $new->setCoordinates($col . ($this->currentRow + ($row - 1)));
                $new->setWorksheet($this->sheet);
            }
            $zip->close();
        }
    }

    /* ----------------------------------------------------------
        EMPLOYEE DATA
    ---------------------------------------------------------- */
    private function populateEmployeeData($employee)
    {
        
        /** ---------- APPEND COMPUTED DEDUCTIONS ---------- */
        $employee['deductions'] = array_merge(
            $employee['deductions'],
            [
                [
                    'deduction_type' => 'Absences/Lates/Undertime',
                    'amount' => (float) ($employee['ut'] ?? 0) + (float) ($employee['absences'] ?? 0),
                ],
                ['deduction_type' => 'EWT 2%', 'amount' => (float) ($employee['ewt_2'] ?? 0)],
                ['deduction_type' => 'Percentage Tax 3%', 'amount' => (float) ($employee['percentage_tax_3'] ?? 0)],
                ['deduction_type' => 'Tax EWT 5%', 'amount' => (float) ($employee['tax_ewt_5'] ?? 0)],
            ]
        );

        $row = $this->currentRow + 5;

        $this->sheet->setCellValue("C{$row}", $employee['name'] ?? '');
        $this->sheet->setCellValue("C" . ($row + 1), ucfirst($employee['position'] ?? ''));

        /** ---------- SALARY ---------- */
        $salaryRow = $row + 4;

        $this->sheet->mergeCells("A{$salaryRow}:C{$salaryRow}");
        $this->sheet->setCellValue("A{$salaryRow}", strtoupper('Monthly Salary'));
        $this->sheet->getStyle("A{$salaryRow}:C{$salaryRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->money("D{$salaryRow}", $employee['monthly_rate'] ?? 0);

        /** ---------- DEDUCTIONS ---------- */
        $deductionStart = $salaryRow;
        $deductions = $employee['deductions'] ?? [];
        $deductionCount = count($deductions);
        $total = 0;

        // Insert extra rows (template already has 1)
        if ($deductionCount > 1) {
            $this->sheet->insertNewRowBefore(
                $deductionStart + 1,
                $deductionCount - 1
            );
        }

        // Copy full row style
        foreach (range(1, $deductionCount - 1) as $i) {
            foreach (range('A', 'M') as $col) {
                $this->sheet->duplicateStyle(
                    $this->sheet->getStyle("{$col}{$deductionStart}"),
                    "{$col}" . ($deductionStart + $i)
                );
            }
        }

        // Fill deduction values
        foreach ($deductions as $i => $d) {
            $r = $deductionStart + $i;
            $amount = (float) ($d['amount'] ?? 0);
            $total += $amount;
            $this->sheet->setCellValue("F{$r}", $d['deduction_type']);
            $this->sheet->setCellValue("F{$r}", strtoupper($d['deduction_type']));
            $this->sheet->getStyle("F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $this->money("I{$r}", $amount);
            $this->sheet->mergeCells("I{$r}:J{$r}");
        }

        /** ---------- TOTALS / FOOTER ---------- */
        $templateTotalRow = $deductionStart + 1;
        $totalRow = $deductionStart + $deductionCount;

        // Insert footer row
        $this->sheet->insertNewRowBefore($totalRow, 1);

        // Copy footer style
        foreach (range('A', 'M') as $col) {
            $this->sheet->duplicateStyle(
                $this->sheet->getStyle("{$col}{$templateTotalRow}"),
                "{$col}{$totalRow}"
            );
        }

        // Footer values
        $this->money("D{$totalRow}", $employee['monthly_rate'] ?? 0);
        $this->sheet->getStyle("D{$totalRow}")->getFont()->setBold(true);

        $this->sheet->getStyle("D{$totalRow}")
            ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $this->sheet->getStyle("D{$totalRow}")
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        $this->sheet->mergeCells("I{$totalRow}:J{$totalRow}");
        $this->money("I{$totalRow}", $total, true);

        $this->sheet->getStyle("I{$totalRow}:J{$totalRow}")
            ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $this->sheet->getStyle("I{$totalRow}:J{$totalRow}")
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        if (!empty($employee['cut_offs'])) {
            foreach ($employee['cut_offs'] as $index => $cutoffs) {
                if ($index > 0) {
                    // Insert a new row before the current totalRow + index
                    $this->sheet->insertNewRowBefore($totalRow + $index);
                    $totalRow++;

                    // Set cut-off amount and alias (cast amount to string to allow text alignment)
                    $this->sheet->setCellValueExplicit("L{$totalRow}", (string)number_format($cutoffs['amount'], 2), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $this->sheet->setCellValue("M{$totalRow}", $cutoffs['alias']);

                    // Apply font size 9
                    $this->sheet->getStyle("L{$totalRow}:M{$totalRow}")->getFont()->setSize(9);

                    // Column L: bold + center
                    $this->sheet->getStyle("L{$totalRow}")->getFont()->setBold(true);
                    $this->sheet->getStyle("L{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    // Column M: center
                    $this->sheet->getStyle("M{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    // Remove bottom border for columns D, I, and J
                    $columnsToClear = ['D', 'I', 'J'];
                    foreach ($columnsToClear as $col) {
                        $this->sheet->getStyle("{$col}{$totalRow}")->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
                    }

                } else {
                    // First row, no insertion needed
                    $this->sheet->setCellValueExplicit("L{$totalRow}", (string) number_format($cutoffs['amount'], 2), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $this->sheet->setCellValue("M{$totalRow}", $cutoffs['alias']); 

                    // Apply font size 9
                    $this->sheet->getStyle("L{$totalRow}:M{$totalRow}")->getFont()->setSize(9);

                    // Column L: bold + center
                    $this->sheet->getStyle("L{$totalRow}")->getFont()->setBold(true);
                    $this->sheet->getStyle("L{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    // Column M: center
                    $this->sheet->getStyle("M{$totalRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        /** ---------- NET PAY SECTION ---------- */

        // Insert 5 blank rows after footer
        $this->sheet->insertNewRowBefore($totalRow + 1, 5);
        $netPayRow = $totalRow + 5;
        
        // Remove borders from blank rows
        for ($i = 1; $i <= 5; $i++) {
            $blankRow = $totalRow + $i;
            // Remove all borders first
            foreach (range('A', 'M') as $col) {
            $this->sheet->getStyle("{$col}{$blankRow}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            }
            // Add left border to column A
            $this->sheet->getStyle("A{$blankRow}")
            ->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            // Add right border to column M
            $this->sheet->getStyle("M{$blankRow}")
            ->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        }

        $this->sheet->mergeCells("I{$netPayRow}:J{$netPayRow}");
        $this->sheet->setCellValue("I{$netPayRow}", 'NET PAY');

        $this->sheet->getStyle("I{$netPayRow}")
            ->getFont()->setBold(true)->getColor()->setARGB('FF000000');

        $this->sheet->getStyle("I{$netPayRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $this->sheet->setCellValue("K{$netPayRow}", ':');

        $this->money("L{$netPayRow}", $employee['net_pay'] ?? 0, true);
        $this->sheet->getStyle("L{$netPayRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Underline NET PAY value (CORRECT API)
        $netStyle = $this->sheet->getStyle("L{$netPayRow}");
        $netStyle->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
        $netStyle->getFont()->getColor()->setARGB('FF000000');

        /** ---------- EMPTY ROW AFTER ---------- */
        $this->sheet->insertNewRowBefore($netPayRow + 1, 1);
    }


    private function money($cell, $amount, $bold = false)
    {
        $this->sheet->setCellValue($cell, $amount);
        $this->sheet->getStyle($cell)->getNumberFormat()
            ->setFormatCode('_("₱"* #,##0.00_);_("₱"* (#,##0.00);_("₱"* "-"??_);_(@_)');

        if ($bold) {
            $this->sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('C00000');
        }
    }

    private function formatMonthYear()
    {
        $date = explode(' ', $this->payroll->period_covered);
        return "{$date[0]} {$date[1]}";
    }

    private function formatOrdinal()
    {
        $date = explode(' ', $this->payroll->period_covered);
        $dayParts = explode('-', $date[2]);
        $day = end($dayParts);
        return ordinal($day); 
    }

    /* ----------------------------------------------------------
        EXPORT
    ---------------------------------------------------------- */
    private function exportFile()
    {
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $output = storage_path('app/public/payslip.xlsx');
        $writer->save($output);

        foreach (glob(public_path('temp_img_*.png')) as $tmp) {
            @unlink($tmp);
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->download($output);
    }
}
