<?php

namespace App\Services\Exports;

use App\Services\SalaryPayrollService;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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

            $this->currentRow += $this->templateHeight;
        }

        $this->sheet->removeRow(1, $this->templateHeight);
        return $this->exportFile();
    }

    /* ----------------------------------------------------------
        LOAD DATA
    ---------------------------------------------------------- */
    private function loadPayrollData($payroll_no)
    {
        $payrollService =  app(SalaryPayrollService::class);
        $this->payroll = $payrollService->payrollDetails($payroll_no);
        $this->registry = json_decode($payrollService->getPayrollRegistry($this->payroll->id, false)->getContent(), true);
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
        $row = $this->currentRow + 5;

        $this->sheet->setCellValue("C{$row}", $employee['name'] ?? '');
        $this->sheet->setCellValue("C" . ($row + 1), ucfirst($employee['position'] ?? ''));

        // Salary
        $salaryRow = $row + 4;
        $this->sheet->setCellValue("A{$salaryRow}", 'Monthly Salary');
        $this->money("D{$salaryRow}", $employee['monthly_rate'] ?? 0);

        // Deductions
        $deductionStart = $salaryRow;
        $total = 0;

        if (!empty($employee['deductions'])) {
            foreach ($employee['deductions'] as $i => $d) {
                $r = $deductionStart + $i;
                $total += $d['amount'] ?? 0;

                $this->sheet->setCellValue("F{$r}", $d['deduction_type'] ?? '');
                $this->money("I{$r}", $d['amount'] ?? 0);
            }
        }

        $totalRow = $deductionStart + count($employee['deductions'] ?? []) + 2;

        $this->money("D{$totalRow}", $employee['monthly_rate'] ?? 0);

        $this->sheet->mergeCells("I{$totalRow}:J{$totalRow}");
        $this->money("I{$totalRow}", $total, true);

        $this->money("L{$totalRow}", $employee['net_salary'] ?? 0, true);
        $this->sheet->setCellValue("M{$totalRow}", strtoupper($this->formatOrdinal()));
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

        // Clean output buffer to prevent corruption
        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->download($output);
    }
}
