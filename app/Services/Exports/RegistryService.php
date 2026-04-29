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

        (new Xlsx($spreadsheet))->save($savePath);

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

        // dd($this->registry, $this->payroll);

        $templatePath = public_path('templates/regular/payroll_registry.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Update header
        $sheet->setCellValue('A3', $this->payroll->period_covered);
        $sheet->setCellValue('A2', 'GENERAL PAYROLL FOR SALARY');
        $sheet->setCellValue('Q6', 'NET SALARY 15TH');
        $sheet->setCellValue('R6', 'NET SALARY 30TH');
        $sheet->setCellValue('S6', 'NET SALARY');

        $row = 7;
        $counter = 1;

        foreach ($this->registry as $employee) {

            // Map deductions by type
            $deductions = collect($employee['deductions'] ?? [])
                ->mapWithKeys(fn ($d) => [
                    strtoupper($d['deduction_type']) => (float) $d['amount']
                ]);

            $totalDeductions = $deductions->sum();

            $netBasicSalary = (float) $employee['monthly_rate'];
            $netPay = (float) ($employee['net_pay'] ?? $employee['net_salary'] ?? 0);
            [$netSalary15th, $netSalary30th] = $this->splitRegularNetPay($netPay);

            // Basic info
            $sheet->setCellValue("A{$row}", $counter++);
            $sheet->setCellValue("B{$row}", $employee['name']);
            $sheet->setCellValue("C{$row}", ucwords($employee['position']));
            $sheet->setCellValue("D{$row}", $employee['monthly_rate']);
            $sheet->setCellValue("E{$row}", $employee['salary_grade'] ?? '');
            $sheet->setCellValue("F{$row}", $netBasicSalary);

            // Mandatory deductions
            $sheet->setCellValue("G{$row}", $deductions['PAG-IBIG'] ?? 0);
            $sheet->setCellValue("H{$row}", $deductions['PHILHEALTH'] ?? 0);
            $sheet->setCellValue("I{$row}", $deductions['GSIS'] ?? 0);

            // GSIS Loans
            $sheet->setCellValue("J{$row}", $deductions['POLICY LOAN'] ?? 0);
            $sheet->setCellValue("K{$row}", $deductions['EMERGENCY LOAN'] ?? 0);
            $sheet->setCellValue("L{$row}", $deductions['MPL'] ?? 0);
            $sheet->setCellValue("M{$row}", $deductions['MPL LITE'] ?? 0);
            $sheet->setCellValue("N{$row}", $deductions['GSIS EDUC'] ?? 0);
            $sheet->setCellValue("O{$row}", $deductions['GSIS OPTIONAL PREM'] ?? 0);

            // Totals
            $sheet->setCellValue("P{$row}", $totalDeductions);
            $sheet->setCellValue("Q{$row}", $employee['net_salary_15th'] ?? $netSalary15th);
            $sheet->setCellValue("R{$row}", $employee['net_salary_30th'] ?? $netSalary30th);
            $sheet->setCellValue("S{$row}", $netPay);

            $row++;
        }

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

        (new Xlsx($spreadsheet))->save($savePath);

        return Response::download($savePath, $fileName)->deleteFileAfterSend(true);
    }

    private function splitRegularNetPay(float $netPay): array
    {
        $firstCutoff = round($netPay / 2, 2);
        $secondCutoff = round($netPay - $firstCutoff, 2);

        return [$firstCutoff, $secondCutoff];
    }
}
