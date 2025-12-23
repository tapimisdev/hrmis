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
        if ($this->payroll->employment_type_id == 2) {
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
        if($this->payroll->id == EmploymentTypesEnum::COS->value) {
            $this->registry = json_decode(
                $payrollService->getPayrollRegistry($this->payroll, $this->payroll->id, true)->getContent(),
                true
            );
        } else {
            $this->registry = json_decode(
                $payrollService->getPayrollRegistry($this->payroll, $this->payroll->id, false)->getContent(),
                true
            );
        }
    }

    /* --------------------------
        COS REGISTRY
    -------------------------- */
    private function exportCOSFile()
    {
        /* =========================================================
        | 1. NORMALIZE REGISTRY DATA
        ========================================================= */
        $this->registry = collect($this->registry)
            ->groupBy(fn ($e) => $e['project_name'] ?? 'NO PROJECT')
            ->map(function ($employees, $projectName) {
                return [
                    'name'      => $projectName,
                    'employees' => $employees->map(fn ($e) => [
                        'name'             => strtoupper($e['name']),
                        'position'         => ucwords($e['position']),
                        'monthly_rate'     => $e['monthly_rate'],
                        'salary_earned'    => $e['basic_pay'],
                        'aut'              => $e['ut'],
                        'overtime'         => $e['overtime'],
                        'holiday'          => $e['holiday'],
                        'total_salary'     => $e['gross_pay'],
                        'ewt_2'            => $e['ewt_2'],
                        'percentage_tax_3' => $e['percentage_tax_3'],
                        'tax_ewt_5'        => $e['tax_ewt_5'],
                        'w_tax'            => $e['w_tax'],
                        'adjustments'      => $e['salary_adjustment'],
                        'net_salary'       => $e['net_pay'],
                        'remarks'          => $e['remarks'] ?? '',
                    ])->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        /* =========================================================
        | 2. LOAD TEMPLATE
        ========================================================= */
        $spreadsheet = IOFactory::load(public_path('templates/cos/payroll_registry.xlsx'));
        $sheet       = $spreadsheet->getActiveSheet();

        /* =========================================================
        | 3. STYLES
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

        // Default font Calibri
        $sheet->getStyle('A:Z')->getFont()->setName('Calibri')->setSize(10);

        // Default alignment center for all cells
        $sheet->getStyle('A:Z')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Column B left-aligned + wrap text
        $sheet->getStyle('B')->getAlignment()->setWrapText(true);

        /* =========================================================
        | 4. HEADER DATA
        ========================================================= */
        [$month, $year, $period] = explode(' ', $this->payroll->period_covered);
        $sheet->setCellValue('A5', $period);
        $sheet->setCellValue('B5', "{$month} {$year}");

        /* =========================================================
        | 5. COLUMN SETUP
        ========================================================= */
        $salaryFields = [
            'monthly_rate',     // C
            'salary_earned',    // D
            'aut',              // E
            'overtime',         // F
            'holiday',          // G
            'total_salary',     // H
            'ewt_2',            // I
            'percentage_tax_3', // J
            'tax_ewt_5',        // K
            'w_tax',            // L
            'adjustments',      // M
            'net_salary',       // N
            'remarks',          // O
        ];

        $totalSalaryCol = 'H';
        $netSalaryCol   = 'N';
        $deductionCols  = ['E', 'I', 'J', 'K', 'L'];

        /* =========================================================
        | 6. WRITE DATA
        ========================================================= */
        $row              = 8;
        $employeeCounter  = 1;
        $projectTotalRows = [];

        foreach ($this->registry as $project) {
            /* PROJECT HEADER (Arial, bold, italic, size 12) */
            $sheet->insertNewRowBefore($row);
            $sheet->mergeCells("A{$row}:O{$row}");
            $sheet->setCellValue("A{$row}", strtoupper($project['name']));
            $applyStyle("A{$row}:O{$row}", [
                'font' => ['name' => 'Arial', 'bold' => true, 'italic' => true, 'size' => 12],
                'fill' => $fill['project'],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $applyStyle("A{$row}:O{$row}", $borderAll);
            $sheet->getRowDimension($row++)->setRowHeight(26);

            $startEmployeeRow = $row;

            /* EMPLOYEES */
            foreach ($project['employees'] as $employee) {
                $sheet->insertNewRowBefore($row);
                $sheet->getRowDimension($row)->setRowHeight(30);

                $richText = new RichText();
                $richText->createTextRun($employee['name'])
                    ->getFont()->setBold(false)->setName('Calibri')->getColor()->setARGB('FF000000');

                if (!empty($employee['position'])) {
                    $richText->createText("\n");
                    $richText->createTextRun($employee['position'])
                        ->getFont()->setItalic(false)->setName('Calibri')->getColor()->setARGB('FF000000');
                }

                // Row number (A)
                $sheet->setCellValue("A{$row}", $employeeCounter++);
                $sheet->getStyle("A{$row}")->getFont()->setBold(false)->setItalic(false)
                    ->getColor()->setARGB('FF000000');

                // Name & Position (B)
                $sheet->setCellValueExplicit("B{$row}", $richText, DataType::TYPE_INLINE);
                $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("B{$row}")->getFont()->setItalic(false)->getColor()->setARGB('FF000000');

                // Salary & deductions (C-O)
                $col = 'C';
                foreach ($salaryFields as $field) {
                    $sheet->setCellValue("{$col}{$row}", $employee[$field] ?? '0.00');
                    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(false)->setItalic(false)
                        ->getColor()->setARGB('FF000000');
                    $col++;
                }

                // Fill colors
                $applyStyle("A{$row}:D{$row}", ['fill' => $fill['white']]);
                $applyStyle("E{$row}", ['fill' => $fill['deduction']]);
                $applyStyle("F{$row}:G{$row}", ['fill' => $fill['white']]);
                $applyStyle("H{$row}", ['fill' => $fill['salary']]);
                $applyStyle("I{$row}:L{$row}", ['fill' => $fill['deduction']]);
                $applyStyle("M{$row}", ['fill' => $fill['white']]);
                $applyStyle("N{$row}", ['fill' => $fill['net']]);
                $applyStyle("O{$row}", ['fill' => $fill['white']]);
                $applyStyle("A{$row}:O{$row}", $borderAll);

                $row++;
            }

            /* PROJECT TOTAL */
            $sheet->insertNewRowBefore($row);
            $projectTotalRows[] = $row;

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", "TOTAL: " . strtoupper($project['name']));

            foreach (range('C', 'N') as $col) {
                $sheet->setCellValue("{$col}{$row}", "=SUM({$col}{$startEmployeeRow}:{$col}" . ($row - 1) . ")");
            }

            // Bold, non-italic
            $applyStyle("A{$row}:O{$row}", [
                'font' => ['name' => 'Arial', 'bold' => true, 'italic' => false, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $applyStyle("H{$row}", ['fill' => $fill['salary']]);
            $applyStyle("N{$row}", ['fill' => $fill['net']]);
            foreach ($deductionCols as $dCol) {
                $applyStyle("{$dCol}{$row}", ['fill' => $fill['deduction']]);
            }
            $applyStyle("C{$row}:D{$row}", ['fill' => $fill['white']]);
            $applyStyle("M{$row}", ['fill' => $fill['white']]);
            $applyStyle("O{$row}", ['fill' => $fill['white']]);
            $applyStyle("A{$row}:O{$row}", $borderAll);

            $row += 2;
        }

        /* GRAND TOTAL */
        if ($projectTotalRows) {
            $sheet->insertNewRowBefore($row);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", "GRAND TOTAL:");

            foreach (range('C', 'N') as $col) {
                $formula = collect($projectTotalRows)->map(fn ($r) => "{$col}{$r}")->implode('+');
                $sheet->setCellValue("{$col}{$row}", "={$formula}");
            }

            $applyStyle("A{$row}:O{$row}", [
                'font' => ['name' => 'Arial', 'bold' => true, 'italic' => false, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $applyStyle("H{$row}", ['fill' => $fill['salary']]);
            $applyStyle("N{$row}", ['fill' => $fill['net']]);
            foreach ($deductionCols as $dCol) {
                $applyStyle("{$dCol}{$row}", ['fill' => $fill['deduction']]);
            }
            $applyStyle("C{$row}:D{$row}", ['fill' => $fill['white']]);
            $applyStyle("M{$row}", ['fill' => $fill['white']]);
            $applyStyle("O{$row}", ['fill' => $fill['white']]);
            $applyStyle("A{$row}:O{$row}", $borderAll);
        }

        /* =========================================================
        | EXPORT
        ========================================================= */
        $exportPath = public_path('exports');
        if (!is_dir($exportPath)) mkdir($exportPath, 0775, true);

        if (ob_get_length()) ob_end_clean();

        $fileName = strtolower("COS_Payroll_Registry_{$this->payroll->payroll_no}.xlsx");
        $savePath = "{$exportPath}/{$fileName}";

        (new Xlsx($spreadsheet))->save($savePath);

        return Response::download($savePath, $fileName)
            ->deleteFileAfterSend(true);
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

        $row = 7;
        $counter = 1;

        foreach ($this->registry as $employee) {

            // Map deductions by type
            $deductions = collect($employee['deductions'] ?? [])
                ->mapWithKeys(fn ($d) => [
                    strtoupper($d['deduction_type']) => (float) $d['amount']
                ]);

            $totalDeductions = $deductions->sum();

            $netBasicSalary = (float) $employee['monthly_rate'] - (float) $employee['absences'];

            // Basic info
            $sheet->setCellValue("A{$row}", $counter++);
            $sheet->setCellValue("B{$row}", $employee['name']);
            $sheet->setCellValue("C{$row}", ucwords($employee['position']));
            $sheet->setCellValue("D{$row}", $employee['monthly_rate']);
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
            $sheet->setCellValue("Q{$row}", $employee['net_salary']);

            $row++;
        }

        // Save file
        $exportPath = public_path('exports');
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0775, true);
        }

        if (ob_get_length()) ob_end_clean();

        $fileName = "Salary_Payroll_{$this->payroll->payroll_no}.xlsx";
        $savePath = $exportPath . '/' . $fileName;

        (new Xlsx($spreadsheet))->save($savePath);

        return Response::download($savePath, $fileName)->deleteFileAfterSend(true);
    }
}
