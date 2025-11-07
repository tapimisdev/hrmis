<?php

namespace App\Services\Exports;

use App\Services\SalaryPayrollService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class AUTService
{
    protected $payroll;
    protected $rates;
    protected $spreadsheet;
    protected $sheet;
    protected $templateStart = 10;
    protected $templateEnd = 15;
    protected $currentRow;

    public static function download($payroll_no)
    {
        return app(self::class)->process($payroll_no);
    }

    private function process($payroll_no)
    {
        $this->loadPayrollData($payroll_no);
        $this->loadTemplate();
        $this->setHeader();
        $this->currentRow = $this->templateStart;

        foreach ($this->rates as $unit => $employees) {
            $this->insertUnitRow($unit);
            foreach ($employees as $employee) {
                $this->insertEmployeeBlock($employee);
            }
        }

        return $this->exportFile();
    }

    private function loadPayrollData($payroll_no)
    {
        $service        = app(SalaryPayrollService::class);
        $this->payroll  = $service->payrollDetails($payroll_no);
        $this->rates    = $service->employeePayrollRates($this->payroll->id);
    }

    private function loadTemplate()
    {
        $path = public_path('templates/cos/absences-leaves.xlsx');
        $this->spreadsheet = IOFactory::load($path);
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    private function setHeader()
    {
        [$month, $year, $period] = explode(' ', $this->payroll->period_covered);
        $cutoff = strtoupper("$period $month $year");

        $this->sheet->setCellValue("A8", $cutoff);
        $this->sheet->getStyle("A8")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 12,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
        ]);
    }

    private function insertUnitRow($unit)
    {
        $this->sheet->insertNewRowBefore($this->currentRow, 2);
        $this->sheet->setCellValue("A{$this->currentRow}", strtoupper($unit));
        $this->sheet->getRowDimension($this->currentRow)->setRowHeight(21);

        // UNIT STYLE
        $this->sheet->getStyle("A{$this->currentRow}:L{$this->currentRow}")
            ->applyFromArray([
                'font' => [
                    'bold' => false,
                    'size' => 12,
                    'name' => 'Arial',
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

        $this->currentRow++;
    }

    private function insertEmployeeBlock($employee)
    {
        $height = ($this->templateEnd - $this->templateStart + 1);
        $this->sheet->insertNewRowBefore($this->currentRow, $height);

        // Apply employee row base style
        for ($i = 0; $i <= $height; $i++) {
            $r = $this->currentRow + $i;
            $this->sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                'font' => [
                    'name' => 'Calibri',
                    'bold' => false,
                    'size' => 10,
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
                    'startColor' => ['argb' => 'FFFFFFFF'], // white
                ],
            ]);
        }

        // Row 1 - Name, Monthly Rate, Days
        $this->sheet->setCellValue("A{$this->currentRow}", $employee['name']);
        $this->sheet->getStyle("A{$this->currentRow}")->getFont()->setBold(true);
        $this->sheet->getStyle("A{$this->currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $this->sheet->setCellValue("B{$this->currentRow}", "Php" . number_format($employee['monthly_rate'], 2));
        $this->sheet->setCellValue("D{$this->currentRow}", 22);
        $this->sheet->getStyle("B{$this->currentRow}:C{$this->currentRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Position Row
        $this->sheet->setCellValue("A".($this->currentRow+1), $employee['position']);
        $this->sheet->getStyle("A".($this->currentRow+1))->getFont()->setItalic(true);
        $this->sheet->getStyle("A".($this->currentRow+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Formulas Like Original
        $this->rowFormula($employee, 2, "Rate/day",   $employee['daily_rate'], "/8");
        $this->rowFormula($employee, 3, "Rate/hr",    $employee['hourly_rate'], "/8");
        $this->rowFormula($employee, 4, "Rate/min",   $employee['minute_rate'], "/8/60");

        // TOTAL row
        $this->sheet->setCellValue("J".($this->currentRow+5), "TOTAL");
        $this->sheet->setCellValue("K".($this->currentRow+5), "₱");
        $this->sheet->setCellValue("L".($this->currentRow+5), number_format(0,2));
        $this->sheet->getStyle("J".($this->currentRow+5).":L".($this->currentRow+5))
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->currentRow += $height;
    }

    private function rowFormula($employee, $offset, $label, $rate, $divider)
    {
        $r = $this->currentRow + $offset;

        $this->sheet->setCellValue("A{$r}", $label);
        $this->sheet->setCellValue("B{$r}", $employee['monthly_rate']);
        $this->sheet->setCellValue("C{$r}", "/");
        $this->sheet->setCellValue("D{$r}", 22);
        $this->sheet->setCellValue("E{$r}", $divider);
        $this->sheet->setCellValue("F{$r}", "=");
        $this->sheet->setCellValue("G{$r}", $rate);
        $this->sheet->setCellValue("H{$r}", "X");
        $this->sheet->setCellValue("I{$r}", 0);
        $this->sheet->setCellValue("J{$r}", "---------");
        $this->sheet->setCellValue("K{$r}", "₱");
        $this->sheet->setCellValue("L{$r}", number_format(0,2));

        // Right align numerics
        $this->sheet->getStyle("A{$r}:L{$r}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->sheet->getStyle("D{$r}:L{$r}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function exportFile()
    {
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $output = storage_path('app/public/absences-leaves-filled.xlsx');
        $writer->save($output);
        return response()->download($output);
    }
}
