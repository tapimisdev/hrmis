<?php

namespace App\Services\Exports;

use App\Services\SalaryPay\PayrollService;
use App\Enums\EmploymentTypesEnum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        if($this->payroll->employment_type_id == EmploymentTypesEnum::COS->value) {
            return $this->exportCOSFile();
        } else {
            return $this->exportRegularFile();
        }
    }

    /* --------------------------
        LOAD PAYROLL DATA
    -------------------------- */
    private function loadPayrollData($payroll_no)
    {
        $payrollService = app(PayrollService::class);
        $this->payroll  = $payrollService->payrollDetails($payroll_no);

        $isCOS = $this->payroll->employment_type_id == EmploymentTypesEnum::COS->value;

        $this->registry = json_decode(
            $payrollService->getPayrollRegistry($this->payroll, $this->payroll->id, $isCOS)->getContent(),
            true
        );
    }

    /* --------------------------
        COS REGISTRY
    -------------------------- */
    private function exportCOSFile()
    {

        /* =========================================================
        | 1. LOAD TEMPLATE
        ========================================================= */
        $spreadsheet = IOFactory::load(public_path('templates/cos/payroll_registry.xlsx'));
        $sheet       = $spreadsheet->getActiveSheet();

        /* =========================================================
        | 2. STYLES
        ========================================================= */
        $fill = [
            'white'     => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            'salary'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'EBF1DE']],
            'deduction' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'F2DBDB']],
            'net'       => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'DBE5F2']],
            'project'   => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDE9D9']],
        ];

        $borderAll = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $applyStyle = fn ($range, $style) =>
            $sheet->getStyle($range)->applyFromArray($style);

        $sheet->getStyle('A:Z')->getFont()->setName('Calibri')->setSize(10);
        $sheet->getStyle('A:Z')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setWrapText(true);

        /* =========================================================
        | 3. HEADER DATA
        ========================================================= */
        [$month, $year, $period] = explode(' ', $this->payroll->period_covered);
        $sheet->setCellValue('A5', $period);
        $sheet->setCellValue('B5', "{$month} {$year}");

        /* =========================================================
        | 4. COLUMN SETUP
        ========================================================= */
        $salaryFields = [
            'monthly_rate', 'salary_earned', 'aut', 'overtime', 'holiday',
            'total_salary', 'ewt_2', 'percentage_tax_3', 'tax_ewt_5', 'w_tax', 
            'hmo', 'adjustments', 'net_salary', 'remarks',
        ];

        $totalSalaryCol = 'H';
        $netSalaryCol   = 'O';
        $deductionCols  = ['E', 'I', 'J', 'K', 'L', 'M'];

        /* =========================================================
        | 5. WRITE DATA (insert without overwriting existing rows)
        ========================================================= */
        $startRow         = 8; // starting insert row
        $employeeCounter  = 1;
        $projectTotalRows = [];

        // dd($this->registry);

        foreach ($this->registry as $project) {

            /* Insert Project Header */
            $sheet->insertNewRowBefore($startRow, 1); // push down rows
            $sheet->mergeCells("A{$startRow}:P{$startRow}");
            $sheet->setCellValue("A{$startRow}", strtoupper($project['name']));
            $applyStyle("A{$startRow}:O{$startRow}", [
                'font' => ['name' => 'Arial', 'bold' => true, 'italic' => true, 'size' => 12],
                'fill' => $fill['project'],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $applyStyle("A{$startRow}:O{$startRow}", $borderAll);
            $sheet->getRowDimension($startRow)->setRowHeight(26);

            $employeeStartRow = $startRow + 1; // employees start below header
            $currentRow = $employeeStartRow;

            /* Insert Employees */
            foreach ($project['employees'] as $employee) {
                $sheet->insertNewRowBefore($currentRow, 1);
                $sheet->getRowDimension($currentRow)->setRowHeight(30);

                $richText = new RichText();
                $richText->createTextRun($employee['name'])
                    ->getFont()->setBold(false)->setName('Calibri')->getColor()->setARGB('FF000000');

                if (!empty($employee['position'])) {
                    $richText->createText("\n");
                    $richText->createTextRun($employee['position'])
                        ->getFont()->setItalic(false)->setName('Calibri')->getColor()->setARGB('FF000000');
                }

                // Row number
                $sheet->setCellValue("A{$currentRow}", $employeeCounter++);
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(false)->setItalic(false)
                    ->getColor()->setARGB('FF000000');

                // Name & Position
                $sheet->setCellValueExplicit("B{$currentRow}", $richText, DataType::TYPE_INLINE);
                $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Salary & deductions
                $col = 'C';
                foreach ($salaryFields as $field) {
                    $sheet->setCellValue("{$col}{$currentRow}", $employee[$field] ?? '0.00');
                    $col++;
                }

                $this->addCosDeductionBreakdownComments($sheet, $currentRow, $employee);

                // Apply fill styles
                $applyStyle("A{$currentRow}:D{$currentRow}", ['fill' => $fill['white']]);
                $applyStyle("E{$currentRow}", ['fill' => $fill['deduction']]);
                $applyStyle("F{$currentRow}:G{$currentRow}", ['fill' => $fill['white']]);
                $applyStyle("H{$currentRow}", ['fill' => $fill['salary']]);
                $applyStyle("I{$currentRow}:M{$currentRow}", ['fill' => $fill['deduction']]);
                $applyStyle("N{$currentRow}", ['fill' => $fill['white']]);
                $applyStyle("O{$currentRow}", ['fill' => $fill['net']]);
                $applyStyle("A{$currentRow}:O{$currentRow}", $borderAll);

                $currentRow++;
            }

            /* Insert Project Total */
            $sheet->insertNewRowBefore($currentRow, 1);
            $projectTotalRows[] = $currentRow;
            $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", "TOTAL: " . strtoupper($project['name']));

            foreach (range('C', 'O') as $col) {
                $sheet->setCellValue("{$col}{$currentRow}", "=SUM({$col}{$employeeStartRow}:{$col}" . ($currentRow - 1) . ")");
            }

            $applyStyle("A{$currentRow}:O{$currentRow}", [
                'font' => ['name' => 'Arial', 'bold' => true, 'italic' => false, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $applyStyle("H{$currentRow}", ['fill' => $fill['salary']]);
            $applyStyle("N{$currentRow}", ['fill' => $fill['white']]);
            foreach ($deductionCols as $dCol) {
                $applyStyle("{$dCol}{$currentRow}", ['fill' => $fill['deduction']]);
            }
            $applyStyle("C{$currentRow}:D{$currentRow}", ['fill' => $fill['white']]);
            $applyStyle("M{$currentRow}", ['fill' => $fill['deduction']]);
            $applyStyle("O{$currentRow}", ['fill' => $fill['net']]);
            $applyStyle("A{$currentRow}:O{$currentRow}", $borderAll);

            $startRow = $currentRow + 1; 
        }

        /* GRAND TOTAL */
        if ($projectTotalRows) {
            $sheet->insertNewRowBefore($startRow, 1);
            $sheet->mergeCells("A{$startRow}:B{$startRow}");
            $sheet->setCellValue("A{$startRow}", "GRAND TOTAL:");

            foreach (range('C', 'O') as $col) {
                $formula = collect($projectTotalRows)->map(fn ($r) => "{$col}{$r}")->implode('+');
                $sheet->setCellValue("{$col}{$startRow}", "={$formula}");
            }

            $applyStyle("A{$startRow}:O{$startRow}", [
                'font' => ['name' => 'Arial', 'bold' => true, 'italic' => false, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $applyStyle("H{$startRow}", ['fill' => $fill['salary']]);
            $applyStyle("N{$startRow}", ['fill' => $fill['white']]);
            foreach ($deductionCols as $dCol) {
                $applyStyle("{$dCol}{$startRow}", ['fill' => $fill['deduction']]);
            }
            $applyStyle("C{$startRow}:D{$startRow}", ['fill' => $fill['white']]);
            $applyStyle("M{$startRow}", ['fill' => $fill['deduction']]);
            $applyStyle("O{$startRow}", ['fill' => $fill['net']]);
            $applyStyle("A{$startRow}:O{$startRow}", $borderAll);
        }

        /* =========================================================
        | EXPORT
        ========================================================= */
        $exportPath = public_path('exports');
        if (!is_dir($exportPath)) mkdir($exportPath, 0775, true);

        while (ob_get_level()) {
            ob_end_clean();
        }

        $fileName = strtolower("COS_Payroll_Registry_{$this->payroll->payroll_no}.xlsx");
        $savePath = "{$exportPath}/{$fileName}";

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($savePath);

        return Response::download($savePath, $fileName)
            ->deleteFileAfterSend(true);
    }

    private function addCosDeductionBreakdownComments($sheet, int $row, array $employee): void
    {
        $columns = [
            'E' => ['key' => 'aut', 'label' => 'AUT Deduction'],
            'I' => ['key' => 'ewt_2', 'label' => 'EWT 2%'],
            'J' => ['key' => 'percentage_tax_3', 'label' => 'Percentage Tax 3%'],
            'K' => ['key' => 'tax_ewt_5', 'label' => 'Tax EWT 5%'],
            'L' => ['key' => 'w_tax', 'label' => 'Overall Tax'],
            'M' => ['key' => 'hmo', 'label' => 'HMO'],
        ];

        foreach ($columns as $column => $meta) {
            $items = $employee['deduction_breakdowns'][$meta['key']] ?? [];

            if (empty($items)) {
                continue;
            }

            $lines = [$meta['label'] . ' breakdown'];

            foreach ($items as $item) {
                $source = trim(implode(' ', array_filter([
                    $item['label'] ?? null,
                    $item['payroll_no'] ?? null,
                    $item['period_covered'] ?? null,
                ])));
                $amount = number_format((float) ($item['amount'] ?? 0), 2);
                $lines[] = ($source ?: 'Payroll') . ': ' . $amount;
            }

            $comment = $sheet->getComment("{$column}{$row}");
            $comment->setAuthor('DOST Payroll');
            $comment->getText()->createTextRun(implode("\n", $lines));
        }
    }


    /* --------------------------
        REGULAR REGISTRY
        (similar logic, just different template & columns)
    -------------------------- */
    private function exportRegularFile()
    {

        $templatePath = public_path('templates/regular/payroll_registry.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Update header
        $sheet->setCellValue('A3', $this->payroll->period_covered);
        $sheet->setCellValue('A2', 'GENERAL PAYROLL FOR SALARY');
        $this->styleRegularHeader($sheet);
        $this->clearRegularTemplateConditionalFormatting($sheet);

        $row = 7;
        $counter = 1;
        $divisionGroups = $this->regularRegistryByDivision();
        $rowCount = $divisionGroups->sum(fn ($employees) => count($employees) + 2) + 1;

        if ($rowCount > 1) {
            $sheet->insertNewRowBefore(8, $rowCount - 1);
        }

        $subtotalRows = [];

        foreach ($divisionGroups as $divisionName => $employees) {
            $divisionStartRow = $row + 1;

            $this->writeRegularDivisionHeader($sheet, $row, (string) $divisionName);
            $row++;

            foreach ($employees as $employee) {
                $this->writeRegularEmployeeRow($sheet, $row, $employee, $counter++);
                $row++;
            }

            $this->writeRegularTotalRow(
                $sheet,
                $row,
                'SUBTOTAL:',
                $divisionStartRow,
                $row - 1,
                'FFFCE4D6'
            );
            $subtotalRows[] = $row;
            $row++;
        }

        $this->writeRegularGrandTotalRow($sheet, $row, $subtotalRows);

        // Save file
        $exportPath = public_path('exports');
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0775, true);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $fileName = "Salary_Payroll_{$this->payroll->payroll_no}.xlsx";
        $savePath = $exportPath . '/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($savePath);
        $this->sanitizeRegularWorkbook($savePath);

        return Response::download($savePath, $fileName)->deleteFileAfterSend(true);
    }

    private function splitRegularNetPay(float $netPay): array
    {
        $firstCutoff = round($netPay / 2, 2);
        $secondCutoff = round($netPay - $firstCutoff, 2);

        return [$firstCutoff, $secondCutoff];
    }

    private function regularRegistryByDivision()
    {
        return collect($this->registry)
            ->groupBy(fn ($employee) => trim((string) ($employee['division_name'] ?? 'No Division')) ?: 'No Division')
            ->sortKeys();
    }

    private function writeRegularDivisionHeader($sheet, int $row, string $divisionName): void
    {
        $sheet->mergeCells("A{$row}:AB{$row}");
        $sheet->setCellValue("A{$row}", strtoupper($divisionName));
        $sheet->getRowDimension($row)->setRowHeight(20);
        $sheet->getStyle("A{$row}:AB{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD9EAD3'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
    }

    private function writeRegularEmployeeRow($sheet, int $row, array $employee, int $counter): void
    {
        $values = $this->regularEmployeeRowValues($employee);

        foreach ($values as $column => $value) {
            $sheet->setCellValue("{$column}{$row}", $value);
        }

        $sheet->setCellValue("A{$row}", $counter);
        $sheet->setCellValue("B{$row}", $employee['name']);
        $sheet->setCellValue("C{$row}", ucwords($employee['position']));
        $sheet->getStyle("A{$row}:AB{$row}")->applyFromArray([
            'font' => [
                'bold' => false,
                'color' => ['argb' => 'FF000000'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $this->styleRegularDeductionRange($sheet, "F{$row}:Y{$row}");
        $this->styleRegularNetPayRange($sheet, "Z{$row}:AB{$row}");
    }

    private function regularEmployeeRowValues(array $employee): array
    {
        $deductions = collect($employee['deductions'] ?? [])
            ->groupBy(fn ($deduction) => $this->normalizeRegularDeductionKey($deduction['deduction_type'] ?? ''))
            ->map(fn ($items) => $items->sum(fn ($deduction) => (float) ($deduction['amount'] ?? 0)));

        $netPay = (float) ($employee['net_pay'] ?? $employee['net_salary'] ?? 0);
        [$netSalary15th, $netSalary30th] = $this->splitRegularNetPay($netPay);

        $values = [
            'D' => $employee['monthly_rate'],
            'E' => $employee['salary_grade'] ?? '',
        ];

        foreach ($this->regularDeductionColumns() as $column => $aliases) {
            $values[$column] = $this->regularDeductionAmount($deductions, $aliases);
        }

        $values['Y'] = (float) ($employee['total_deductions'] ?? $deductions->sum());
        $values['Z'] = $netPay;
        $values['AA'] = $employee['net_salary_15th'] ?? $netSalary15th;
        $values['AB'] = $employee['net_salary_30th'] ?? $netSalary30th;

        return $values;
    }

    private function regularDeductionColumns(): array
    {
        return [
            'F' => ['GSIS'],
            'G' => ['Withholding Tax', 'Witholding Tax', 'Withholding tax'],
            'H' => ['PAG-IBIG', 'Pag-ibig'],
            'I' => ['PHIL-HEALTH', 'PhilHealth', 'Phil-Health'],
            'J' => ['Pag-ibig MP2 (Savings)', 'Pag-Ibig MP 2 (Savings)', 'MP2'],
            'K' => ['Pag-ibig Calamity Loan', 'Pag-Ibig Calamity Loan', 'Calamity Loan'],
            'L' => ['Pag-ibig MPL', 'Pag-Ibig MPL'],
            'M' => ['GSIS Financial Assistance Loan', 'Financial Assistance Loan'],
            'N' => ['GSIS MPL', 'MPL'],
            'O' => ['GSIS Conso Loan', 'GSIS Consol Loan', 'GSIS Consolidated Loan'],
            'P' => ['GSIS Policy Loan', 'Policy Loan'],
            'Q' => ['GSIS Emer. Loan', 'GSIS Emergency Loan', 'Emergency Loan'],
            'R' => ['GSIS Optional Prem', 'GSIS Optional Premium'],
            'S' => ['GSIS MPL LITE', 'MPL LITE'],
            'T' => ['GSIS Educ', 'GSIS Education', 'GSIS Educational Loan'],
            'U' => ['Real Estate', 'Real Estate Loan'],
            'V' => ['Landbank'],
            'W' => ['Computer Loan', 'Compute Loan'],
            'X' => ['HMO c/o TAPIEA', 'HMO C/O TAPIEA', 'HMO'],
        ];
    }

    private function writeRegularTotalRow($sheet, int $row, string $label, int $startRow, int $endRow, string $fillColor): void
    {
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("E{$row}", '');

        foreach ($this->regularTotalColumns() as $column) {
            $sheet->setCellValue("{$column}{$row}", $this->sumRegularColumn($sheet, $column, $startRow, $endRow));
        }

        $this->styleRegularTotalRow($sheet, $row, $fillColor);
    }

    private function writeRegularGrandTotalRow($sheet, int $row, array $subtotalRows): void
    {
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL:');
        $sheet->setCellValue("E{$row}", '');

        foreach ($this->regularTotalColumns() as $column) {
            $sheet->setCellValue("{$column}{$row}", $this->sumRegularRows($sheet, $column, $subtotalRows));
        }

        $this->styleRegularTotalRow($sheet, $row, 'FFDBE5F1');
    }

    private function regularTotalColumns(): array
    {
        return array_merge(['D'], range('F', 'Z'), ['AA', 'AB']);
    }

    private function sumRegularColumn($sheet, string $column, int $startRow, int $endRow): float
    {
        if ($endRow < $startRow) {
            return 0.0;
        }

        $total = 0.0;

        for ($row = $startRow; $row <= $endRow; $row++) {
            $total += (float) $sheet->getCell("{$column}{$row}")->getCalculatedValue();
        }

        return $total;
    }

    private function sumRegularRows($sheet, string $column, array $rows): float
    {
        return (float) collect($rows)
            ->sum(fn ($row) => (float) $sheet->getCell("{$column}{$row}")->getCalculatedValue());
    }

    private function styleRegularHeader($sheet): void
    {
        $sheet->getStyle('A1:AB6')->getAlignment()->setWrapText(false);
        $this->applyRegularColumnWidths($sheet);
        $this->styleRegularDeductionRange($sheet, 'F5:Y6');
        $this->styleRegularNetPayRange($sheet, 'Z5:AB6');
    }

    private function clearRegularTemplateConditionalFormatting($sheet): void
    {
        foreach (array_keys($sheet->getConditionalStylesCollection()) as $coordinate) {
            $sheet->removeConditionalStyles($coordinate);
        }
    }

    private function applyRegularColumnWidths($sheet): void
    {
        $widths = [
            'A' => 5,
            'B' => 24,
            'C' => 18,
            'D' => 14,
            'E' => 12,
            'F' => 12,
            'G' => 16,
            'H' => 12,
            'I' => 13,
            'J' => 20,
            'K' => 22,
            'L' => 14,
            'M' => 27,
            'N' => 12,
            'O' => 16,
            'P' => 16,
            'Q' => 16,
            'R' => 18,
            'S' => 15,
            'T' => 12,
            'U' => 14,
            'V' => 14,
            'W' => 16,
            'X' => 16,
            'Y' => 18,
            'Z' => 14,
            'AA' => 22,
            'AB' => 22,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setAutoSize(false);
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function styleRegularDeductionRange($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'color' => ['argb' => 'FF000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFC7CE'],
            ],
        ]);
    }

    private function styleRegularNetPayRange($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDBEFF4'],
            ],
        ]);
    }

    private function styleRegularTotalRow($sheet, int $row, string $fillColor): void
    {
        $sheet->getRowDimension($row)->setRowHeight(18);
        $sheet->getStyle("A{$row}:AB{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $fillColor],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $this->styleRegularDeductionRange($sheet, "F{$row}:Y{$row}");
        $this->styleRegularNetPayRange($sheet, "Z{$row}:AB{$row}");
    }

    private function sanitizeRegularWorkbook(string $path): void
    {
        if (!class_exists(\ZipArchive::class)) {
            return;
        }

        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return;
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (!preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $name)) {
                continue;
            }

            $xml = $zip->getFromName($name);

            if ($xml === false) {
                continue;
            }

            $cleanXml = preg_replace('/<tableParts\s+count="0"\s*\/>/', '', $xml);
            $cleanXml = preg_replace('/\s+r:id="rId\d+ps"/', '', $cleanXml ?? $xml);

            if ($cleanXml !== null && $cleanXml !== $xml) {
                $zip->addFromString($name, $cleanXml);
            }
        }

        for ($index = $zip->numFiles - 1; $index >= 0; $index--) {
            $name = $zip->getNameIndex($index);

            if (
                preg_match('/^xl\/printerSettings\/printerSettings\d+\.bin$/', $name)
                || preg_match('/^xl\/worksheets\/_rels\/sheet\d+\.xml\.rels$/', $name)
            ) {
                $zip->deleteName($name);
            }
        }

        $zip->close();
    }

    private function regularDeductionAmount($deductions, array $aliases): float
    {
        foreach ($aliases as $alias) {
            $amount = $deductions[$this->normalizeRegularDeductionKey($alias)] ?? null;

            if ($amount !== null) {
                return (float) $amount;
            }
        }

        return 0.0;
    }

    private function normalizeRegularDeductionKey(string $deduction): string
    {
        $deduction = strtoupper($deduction);
        $deduction = str_replace(['&', '/', '.', '-', '(', ')'], ' ', $deduction);
        $deduction = str_replace(['PAG IBIG', 'PAGIBIG'], 'PAG IBIG', $deduction);
        $deduction = str_replace(['PHIL HEALTH', 'PHILHEALTH'], 'PHIL HEALTH', $deduction);
        $deduction = str_replace('WITHOLDING', 'WITHHOLDING', $deduction);
        $deduction = str_replace('EMERGENCY', 'EMER', $deduction);
        $deduction = str_replace('EDUCATIONAL', 'EDUC', $deduction);
        $deduction = str_replace('PREMIUM', 'PREM', $deduction);
        $deduction = str_replace('CONSOLIDATED', 'CONSO', $deduction);
        $deduction = preg_replace('/\bSAVINGS\b/', '', $deduction);
        $deduction = preg_replace('/\bC\s+O\b/', '', $deduction);
        $deduction = preg_replace('/\bTAPIEA\b/', '', $deduction);
        $deduction = preg_replace('/\s+/', ' ', $deduction);

        return trim($deduction);
    }
}
