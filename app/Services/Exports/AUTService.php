<?php

namespace App\Services\Exports;

use App\Services\SalaryPay\PayrollService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AUTService
{
    protected $payroll;
    protected $rates;
    protected $spreadsheet;
    protected $sheet;

    protected $templateStart = 10;
    protected $templateEnd   = 15;
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

        /**
         * STRUCTURE:
         * [
         *   [
         *     'name' => 'sample',
         *     'employees' => Collection [
         *        employee data...
         *     ]
         *   ]
         * ]
         */
        foreach ($this->rates as $unitData) {
            $this->insertUnitRow($unitData['name']);

            foreach ($unitData['employees'] as $employee) {
                $this->insertEmployeeBlock($employee);
            }
        }

        return $this->exportFile();
    }

    private function loadPayrollData($payroll_no)
    {
        $service       = app(PayrollService::class);
        $this->payroll = $service->payrollDetails($payroll_no);
        $this->rates   = $service->employeePayrollRates($this->payroll);
    }

    private function loadTemplate()
    {
        $path = public_path('templates/cos/absences-leaves.xlsx');
        $this->spreadsheet = IOFactory::load($path);
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    private function setHeader()
    {
        $parts = explode(' ', $this->payroll->period_covered);
        $cutoff = count($parts) >= 3
            ? strtoupper("{$parts[2]} {$parts[0]} {$parts[1]}")
            : strtoupper($this->payroll->period_covered);

        $this->sheet->setCellValue("A8", $cutoff);
        $this->sheet->getStyle("A8")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 12,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function insertUnitRow($unit)
    {
        $this->sheet->insertNewRowBefore($this->currentRow, 2);

        $this->sheet->setCellValue("A{$this->currentRow}", strtoupper($unit));
        $this->sheet->getRowDimension($this->currentRow)->setRowHeight(21);

        $this->sheet->getStyle("A{$this->currentRow}:L{$this->currentRow}")
            ->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 12,
                    'bold' => true,
                    'underline' => 'single',
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'F2DCDB'],
                ],
            ]);

        $this->currentRow++;
    }

    private function insertEmployeeBlock($employee)
    {
        $height = ($this->templateEnd - $this->templateStart + 1);
        $this->sheet->insertNewRowBefore($this->currentRow, $height);

        for ($i = 0; $i <= $height; $i++) {
            $r = $this->currentRow + $i;

            $this->sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                'font' => [
                    'name' => 'Calibri',
                    'size' => 10,
                    'bold' => false,
                    'underline' => 'none',
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
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
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFFFF'],
                ],
            ]);
        }


        // HEADER
        $this->sheet->setCellValue("A{$this->currentRow}", $employee['name']);
        $this->sheet->getStyle("A{$this->currentRow}")->getFont()->setBold(true);
        $this->sheet->getStyle("A{$this->currentRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $this->sheet->setCellValue(
            "B{$this->currentRow}",
            number_format($this->num($employee['monthly_rate']), 2)
        );

        $this->sheet->setCellValue("D{$this->currentRow}", 22);

        // POSITION
        $this->sheet->setCellValue(
            "A" . ($this->currentRow + 1),
            $employee['position']
        );
        $this->sheet->getStyle("A" . ($this->currentRow + 1))
            ->getFont()->setItalic(true);
        $this->sheet->getStyle("A" . ($this->currentRow + 1))
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        /**
         * ROWS
         * 1️⃣ ABSENCES → Rate/day × absent_days
         * 2️⃣ UNDERTIME HOURS → Rate/hr × ut_hours
         * 3️⃣ UNDERTIME MINUTES → Rate/min × ut_minutes
         */
        $this->row(
            2,
            'Rate/day',
            $employee,
            $this->num($employee['daily_rate']),
            $employee['absent_days'],
            $employee['absent_amount'],
            '/8'
        );

        $this->row(
            3,
            'Rate/hr',
            $employee,
            $this->num($employee['hourly_rate']),
            $employee['ut_hours'],
            $employee['ut_hours_amount'],
            '/8'
        );

        $this->row(
            4,
            'Rate/min',
            $employee,
            $this->num($employee['minute_rate']),
            $employee['ut_minutes'],
            $employee['ut_minutes_amount'],
            '/8/60'
        );

        // TOTAL
        $totalRow = $this->currentRow + 5;
        $this->sheet->setCellValue("J{$totalRow}", "TOTAL");
        $this->sheet->setCellValue("K{$totalRow}", "₱");
        $this->sheet->setCellValue(
            "L{$totalRow}",
            number_format($this->num($employee['total_aut_amount']), 2)
        );

        $this->currentRow += $height;
    }

    private function row($offset, $label, $employee, $rate, $qty, $amount, $divider)
    {
        $r = $this->currentRow + $offset;

        $this->sheet->setCellValue("A{$r}", $label);
        $this->sheet->setCellValue("B{$r}", $this->num($employee['monthly_rate']));
        $this->sheet->setCellValue("C{$r}", "/");
        $this->sheet->setCellValue("D{$r}", 22);
        $this->sheet->setCellValue("E{$r}", $divider);
        $this->sheet->setCellValue("F{$r}", "=");
        $this->sheet->setCellValue("G{$r}", $rate);
        $this->sheet->setCellValue("H{$r}", "X");
        $this->sheet->setCellValue("I{$r}", $qty);
        $this->sheet->setCellValue("J{$r}", "---------");
        $this->sheet->setCellValue("K{$r}", "₱");
        $this->sheet->setCellValue("L{$r}", number_format($this->num($amount), 2));
    }

    private function num($value)
    {
        return (float) str_replace(',', '', $value);
    }

    private function exportFile()
    {
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $output = storage_path('app/public/absences-leaves-filled.xlsx');
        $writer->save($output);

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->download($output);
    }
}
