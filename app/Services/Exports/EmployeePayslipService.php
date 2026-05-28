<?php

namespace App\Services\Exports;

use App\DTO\PayslipData;
use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Services\SalaryPay\PayrollService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeePayslipService
{
    protected $payroll;
    protected $spreadsheet;
    protected $sheet;
    protected $templatePath;
    protected $templateStart = 1;
    protected $templateEnd = 20;
    protected $templateHeight = 20;
    protected $currentRow;
    protected $originalDrawings = [];
    protected array $temporaryImages = [];

    public function download(PayslipData $data)
    {
        $payrollNos = $this->getEmployeePayrollNos($data);

        if ($payrollNos->isEmpty()) {
            throw new HttpException(
                422,
                'Your payroll record for this period is not available. Please contact HR for assistance.'
            );
        }

        $this->loadTemplate();
        $this->applyPageSetup();
        $this->currentRow = $this->templateEnd + 1;

        foreach ($payrollNos as $payrollNo) {
            foreach ($this->getEmployeeRegistry($payrollNo, $data->employee_no) as $employee) {
                $this->insertPayslipSection();
                $this->cloneTemplateRows();
                $this->reapplyImages();
                $this->populateEmployeeData($employee);
            }
        }

        $lastGeneratedRow = $this->currentRow - 1;
        $highestRow = $this->sheet->getHighestRow();

        if ($highestRow > $lastGeneratedRow) {
            $this->sheet->removeRow($lastGeneratedRow + 1, $highestRow - $lastGeneratedRow);
        }

        $this->sheet->removeRow(1, $this->templateHeight);
        $this->sheet->getPageSetup()->setPrintArea('A1:M' . max(1, $lastGeneratedRow - $this->templateHeight));

        return $this->exportFile($data);
    }

    private function getEmployeePayrollNos(PayslipData $data)
    {
        return DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('pse.employee_no', $data->employee_no)
            ->where('ps.employment_type_id', $data->employee_type)
            ->whereYear('ps.payroll_date', $data->year)
            ->whereMonth('ps.payroll_date', $data->month)
            ->when($data->cutoff, fn ($query) => $query->where('ps.cutoff', $data->cutoff))
            ->whereIn('ps.status', [
                PayrollStatusEnum::Approved->value,
                PayrollStatusEnum::Completed->value,
            ])
            ->orderBy('ps.payroll_date')
            ->orderBy('ps.id')
            ->pluck('ps.payroll_no');
    }

    private function getEmployeeRegistry(string $payrollNo, string $employeeNo): array
    {
        $payrollService = app(PayrollService::class);
        $this->payroll = $payrollService->payrollDetails($payrollNo);

        $registry = json_decode(
            $payrollService->getPayrollRegistry($this->payroll, $this->payroll->id, false)->getContent(),
            true
        );

        return collect($registry)
            ->where('employee_no', $employeeNo)
            ->values()
            ->all();
    }

    private function loadTemplate(): void
    {
        $this->templatePath = public_path('templates/cos/payslip.xlsx');
        $this->spreadsheet = IOFactory::load($this->templatePath);
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->templateHeight = $this->templateEnd - $this->templateStart + 1;
        $this->originalDrawings = [];

        foreach ($this->sheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing) {
                $this->originalDrawings[] = $drawing;
                $drawing->setWorksheet(null, true);
            }
        }
    }

    private function applyPageSetup(): void
    {
        $this->sheet->getPageSetup()->clearPrintArea();
        $this->sheet->getSheetView()->setView(SheetView::SHEETVIEW_NORMAL);
        $this->sheet->setSelectedCell('A1');
        $this->sheet->getPageSetup()
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);

        $this->sheet->getPageMargins()
            ->setTop(0.25)->setBottom(0.25)
            ->setLeft(0.25)->setRight(0.25);
    }

    private function insertPayslipSection(): void
    {
        $this->sheet->insertNewRowBefore($this->currentRow, $this->templateHeight);
    }

    private function cloneTemplateRows(): void
    {
        $monthYear = $this->formatMonthYear();
        $templateMerges = $this->sheet->getMergeCells();

        for ($row = $this->templateStart; $row <= $this->templateEnd; $row++) {
            $newRow = $this->currentRow + ($row - $this->templateStart);

            foreach ($this->sheet->getColumnIterator() as $column) {
                $col = $column->getColumnIndex();
                $sourceCell = $col . $row;
                $targetCell = $col . $newRow;

                $this->sheet->setCellValue($targetCell, $this->sheet->getCell($sourceCell)->getValue());
                $this->sheet->duplicateStyle($this->sheet->getStyle($sourceCell), $targetCell);
            }

            $this->sheet->getRowDimension($newRow)->setRowHeight(
                $this->sheet->getRowDimension($row)->getRowHeight()
            );

            foreach ($templateMerges as $merged) {
                [$start, $end] = explode(':', $merged);
                [$startCol, $startRow] = Coordinate::coordinateFromString($start);
                [$endCol, $endRow] = Coordinate::coordinateFromString($end);

                if ((int) $startRow === $row && (int) $endRow === $row) {
                    $this->sheet->mergeCells(
                        $startCol . $newRow . ':' . $endCol . $newRow
                    );
                }
            }
        }

        $this->sheet->getRowDimension($this->currentRow + (3 - $this->templateStart))->setRowHeight(22.20);
        $this->sheet->getRowDimension($this->currentRow + (5 - $this->templateStart))->setRowHeight(33);

        $headerCell = 'A' . ($this->currentRow + (5 - $this->templateStart));
        $this->sheet->setCellValue(
            $headerCell,
            '***** PAY SLIP *****' . chr(10) . "for the month {$monthYear}"
        );
        $this->sheet->getStyle($headerCell)->getAlignment()->setWrapText(true);

        $headerRow1 = $this->currentRow + (2 - $this->templateStart);
        $headerRow2 = $headerRow1 + 1;
        $headerMerge = "A{$headerRow1}:M{$headerRow2}";
        $this->sheet->mergeCells($headerMerge);

        $this->sheet->getStyle($headerMerge)->getFont()->setBold(true);
        $this->sheet->getStyle($headerMerge)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function reapplyImages(): void
    {
        foreach ($this->originalDrawings as $drawing) {
            $new = new Drawing();
            $new->setPath(public_path('img/dost-tapi-small.png'));
            $new->setName($drawing->getName() ?: 'DOST-TAPI Logo');
            $new->setDescription($drawing->getDescription() ?: 'DOST-TAPI Logo');
            $new->setHeight($drawing->getHeight() ?: 39);
            $new->setWidth($drawing->getWidth() ?: 43);
            $new->setOffsetX($drawing->getOffsetX());
            $new->setOffsetY($drawing->getOffsetY());

            [$col, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());
            $new->setCoordinates($col . ($this->currentRow + ($row - $this->templateStart)));
            $new->setWorksheet($this->sheet);
        }
    }

    private function populateEmployeeData(array $employee): void
    {
        $computedDeductions = [
            ['deduction_type' => 'EWT 2%', 'amount' => (float) ($employee['ewt_2'] ?? 0)],
            ['deduction_type' => 'Percentage Tax 3%', 'amount' => (float) ($employee['percentage_tax_3'] ?? 0)],
            ['deduction_type' => 'Tax EWT 5%', 'amount' => (float) ($employee['tax_ewt_5'] ?? 0)],
            ['deduction_type' => 'HMO', 'amount' => (float) ($employee['hmo'] ?? 0)],
        ];

        if ((string) $this->payroll->employment_type_id !== EmploymentTypesEnum::REGULAR->value) {
            array_unshift($computedDeductions, [
                'deduction_type' => 'Absences/Lates/Undertime',
                'amount' => (float) ($employee['ut'] ?? 0) + (float) ($employee['absences'] ?? 0),
            ]);
        }

        $employee['deductions'] = array_merge($employee['deductions'] ?? [], $computedDeductions);

        $row = $this->currentRow + 5;

        $this->sheet->setCellValue("C{$row}", $employee['name'] ?? '');
        $this->sheet->setCellValue('C' . ($row + 1), ucfirst($employee['position'] ?? ''));

        $salaryRow = $row + 4;

        $this->sheet->mergeCells("A{$salaryRow}:C{$salaryRow}");
        $this->sheet->setCellValue("A{$salaryRow}", strtoupper('Monthly Salary'));
        $this->sheet->getStyle("A{$salaryRow}:C{$salaryRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->money("D{$salaryRow}", $employee['monthly_rate'] ?? 0);

        $deductionStart = $salaryRow;
        $deductions = $employee['deductions'] ?? [];
        $deductionCount = max(count($deductions), 1);
        $total = 0;

        if ($deductionCount > 1) {
            $this->sheet->insertNewRowBefore($deductionStart + 1, $deductionCount - 1);
        }

        foreach (range(1, $deductionCount - 1) as $i) {
            foreach (range('A', 'M') as $col) {
                $this->sheet->duplicateStyle(
                    $this->sheet->getStyle("{$col}{$deductionStart}"),
                    "{$col}" . ($deductionStart + $i)
                );
            }
        }

        foreach ($deductions as $i => $deduction) {
            $deductionRow = $deductionStart + $i;
            $amount = (float) ($deduction['amount'] ?? 0);
            $total += $amount;

            $this->sheet->setCellValue("F{$deductionRow}", strtoupper($deduction['deduction_type']));
            $this->sheet->getStyle("F{$deductionRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $this->money("I{$deductionRow}", $amount);
            $this->sheet->mergeCells("I{$deductionRow}:J{$deductionRow}");
        }

        $templateTotalRow = $deductionStart + 1;
        $totalRow = $deductionStart + $deductionCount;

        $this->sheet->insertNewRowBefore($totalRow, 1);

        foreach (range('A', 'M') as $col) {
            $this->sheet->duplicateStyle(
                $this->sheet->getStyle("{$col}{$templateTotalRow}"),
                "{$col}{$totalRow}"
            );
        }

        $this->money("D{$totalRow}", $employee['monthly_rate'] ?? 0);
        $this->sheet->getStyle("D{$totalRow}")->getFont()->setBold(true);

        $this->sheet->getStyle("D{$totalRow}")
            ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $this->sheet->getStyle("D{$totalRow}")
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);

        $this->sheet->mergeCells("I{$totalRow}:J{$totalRow}");
        $this->money("I{$totalRow}", $total, true);

        $this->sheet->getStyle("I{$totalRow}:J{$totalRow}")
            ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $this->sheet->getStyle("I{$totalRow}:J{$totalRow}")
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);

        $payrollDateRow = $salaryRow - 1;
        $this->sheet->mergeCells("N{$payrollDateRow}:O{$payrollDateRow}");
        $this->sheet->setCellValue("N{$payrollDateRow}", $this->formatPayrollDate());
        $this->sheet->getStyle("N{$payrollDateRow}:O{$payrollDateRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_TOP);

        if (!empty($employee['cut_offs'])) {
            $cutoff = $employee['cut_offs'][0];

            $this->sheet->setCellValueExplicit(
                "L{$totalRow}",
                (string) number_format($cutoff['amount'], 2),
                DataType::TYPE_STRING
            );
            $this->sheet->setCellValue("M{$totalRow}", '');
            $this->sheet->getStyle("L{$totalRow}:M{$totalRow}")->getFont()->setSize(9);
            $this->sheet->getStyle("L{$totalRow}")->getFont()->setBold(true);
            $this->sheet->getStyle("L{$totalRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->sheet->insertNewRowBefore($totalRow + 1, 5);
        $netPayRow = $totalRow + 5;

        for ($i = 1; $i <= 5; $i++) {
            $blankRow = $totalRow + $i;
            foreach (range('A', 'M') as $col) {
                $this->sheet->getStyle("{$col}{$blankRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            }
            $this->sheet->getStyle("A{$blankRow}")
                ->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
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

        $netStyle = $this->sheet->getStyle("L{$netPayRow}");
        $netStyle->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
        $netStyle->getFont()->getColor()->setARGB('FF000000');

        if (!empty($employee['remarks'])) {
            $remarksRow = $netPayRow + 1;
            $this->sheet->insertNewRowBefore($remarksRow, 1);

            $this->sheet->mergeCells("N{$remarksRow}:O{$remarksRow}");
            $this->sheet->setCellValue("N{$remarksRow}", $employee['remarks']);

            $this->sheet->getStyle("N{$remarksRow}:O{$remarksRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP);
            $this->sheet->getStyle("N{$remarksRow}:O{$remarksRow}")
                ->getFont()->setBold(false)->setSize(10);
            $this->sheet->getStyle("N{$remarksRow}:O{$remarksRow}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $this->sheet->getStyle("A" . ($netPayRow + 1) . ":O" . ($netPayRow + 1))
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);

        $this->currentRow = $netPayRow + 3;
    }

    private function money(string $cell, mixed $amount, bool $bold = false): void
    {
        $this->sheet->setCellValue($cell, $amount);
        $this->sheet->getStyle($cell)->getNumberFormat()
            ->setFormatCode('_("₱"* #,##0.00_);_("₱"* (#,##0.00);_("₱"* "-"??_);_(@_)');

        if ($bold) {
            $this->sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('C00000');
        }
    }

    private function formatMonthYear(): string
    {
        $date = explode(' ', $this->payroll->period_covered);

        return "{$date[0]} {$date[1]}";
    }

    private function formatPayrollDate(): string
    {
        return Carbon::parse($this->payroll->payroll_date)->format('F j, Y');
    }

    private function exportFile(PayslipData $data)
    {
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);

        $fileName = sprintf('payslip_%s_%04d_%02d.xlsx', $data->employee_no, $data->year, $data->month);
        $directory = storage_path('app/payslips');
        File::ensureDirectoryExists($directory);

        $output = $directory . '/' . Str::uuid() . '_' . $fileName;
        $writer->save($output);

        foreach ($this->temporaryImages as $tmp) {
            @unlink($tmp);
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->download($output, $fileName)->deleteFileAfterSend(true);
    }
}
