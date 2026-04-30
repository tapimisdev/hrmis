<?php

namespace App\Services\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LongevityRegistryService
{
    protected $payroll;
    protected $employees;
    protected Spreadsheet $spreadsheet;

    public static function download($payroll_no)
    {
        return app(self::class)->process($payroll_no);
    }

    private function process($payroll_no)
    {
        $this->loadPayrollData($payroll_no);
        $this->spreadsheet = new Spreadsheet();

        $this->writeRegistry();

        return $this->exportFile($payroll_no);
    }

    private function loadPayrollData($payroll_no): void
    {
        $this->payroll = DB::table('payroll_longevity_pay')
            ->where('payroll_no', $payroll_no)
            ->first();

        if (!$this->payroll) {
            abort(404, 'Longevity payroll not found.');
        }

        $organizationEffectiveDate = Carbon::parse($this->payroll->month)->endOfMonth()->toDateString();

        $latestOrganizations = DB::table('employee_organization as eo1')
            ->select('eo1.employee_no', 'eo1.division_id')
            ->where(
                'eo1.id',
                DB::table('employee_organization as eo2')
                    ->select('eo2.id')
                    ->whereColumn('eo2.employee_no', 'eo1.employee_no')
                    ->where('eo2.created_at', '<=', $organizationEffectiveDate)
                    ->orderByDesc('eo2.created_at')
                    ->orderByDesc('eo2.id')
                    ->limit(1)
            );

        $this->employees = DB::table('payroll_longevity_pay_employee as plpe')
            ->leftJoinSub($latestOrganizations, 'latest_org', function ($join) {
                $join->on('latest_org.employee_no', '=', 'plpe.employee_no');
            })
            ->leftJoin('divisions as d', 'latest_org.division_id', '=', 'd.id')
            ->where('plpe.payroll_longevity_pay_id', $this->payroll->id)
            ->select(
                'plpe.*',
                'latest_org.division_id',
                'd.name as division_name',
                'd.code as division_code'
            )
            ->orderBy('d.name')
            ->orderBy('plpe.employee_no')
            ->get();
    }

    private function writeRegistry(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Longevity Pay');

        $period = $this->formatPayrollMonthYear($this->payroll->month ?? '');
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE');
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'PAYROLL OF LONGEVITY PAY FOR THE MONTH OF ' . strtoupper($period));
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', 'Month: ' . $period);

        $headers = [
            'A5' => 'Emp#',
            'B5' => 'Name / Position',
            'C5' => 'Longevity Pay',
            'D5' => 'W/Tax',
            'E5' => 'Total Amount',
            'F5' => 'Adjustments',
            'G5' => 'Net Amount',
            'H5' => 'Remarks',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $row = 6;
        $totals = [
            'longevity_amount' => 0,
            'w_tax' => 0,
            'total' => 0,
            'adjustments' => 0,
            'net_pay' => 0,
        ];

        foreach ($this->groupEmployeesByDivision() as $group) {
            $sheet->mergeCells("A{$row}:H{$row}");
            $sheet->setCellValue("A{$row}", $group['name']);
            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:H{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE9EDF2');
            $row++;

            $groupTotals = [
                'longevity_amount' => 0,
                'w_tax' => 0,
                'total' => 0,
                'adjustments' => 0,
                'net_pay' => 0,
            ];

            foreach ($group['employees'] as $employee) {
                $sheet->setCellValue("A{$row}", $employee->employee_no);
                $sheet->setCellValue("B{$row}", $this->nameWithPosition($employee));
                $sheet->setCellValue("C{$row}", (float) $employee->longevity_amount);
                $sheet->setCellValue("D{$row}", (float) ($employee->w_tax ?? 0));
                $sheet->setCellValue("E{$row}", (float) $employee->total);
                $sheet->setCellValue("F{$row}", (float) $employee->adjustments);
                $sheet->setCellValue("G{$row}", (float) $employee->net_pay);
                $sheet->setCellValue("H{$row}", $employee->remarks);

                $totals['longevity_amount'] += (float) $employee->longevity_amount;
                $totals['w_tax'] += (float) ($employee->w_tax ?? 0);
                $totals['total'] += (float) $employee->total;
                $totals['adjustments'] += (float) $employee->adjustments;
                $totals['net_pay'] += (float) $employee->net_pay;
                $groupTotals['longevity_amount'] += (float) $employee->longevity_amount;
                $groupTotals['w_tax'] += (float) ($employee->w_tax ?? 0);
                $groupTotals['total'] += (float) $employee->total;
                $groupTotals['adjustments'] += (float) $employee->adjustments;
                $groupTotals['net_pay'] += (float) $employee->net_pay;

                $row++;
            }

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", 'SUB TOTAL');
            $sheet->setCellValue("C{$row}", $groupTotals['longevity_amount']);
            $sheet->setCellValue("D{$row}", $groupTotals['w_tax']);
            $sheet->setCellValue("E{$row}", $groupTotals['total']);
            $sheet->setCellValue("F{$row}", $groupTotals['adjustments']);
            $sheet->setCellValue("G{$row}", $groupTotals['net_pay']);
            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:H{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFF7F7F7');
            $row++;
        }

        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL');
        $sheet->setCellValue("C{$row}", $totals['longevity_amount']);
        $sheet->setCellValue("D{$row}", $totals['w_tax']);
        $sheet->setCellValue("E{$row}", $totals['total']);
        $sheet->setCellValue("F{$row}", $totals['adjustments']);
        $sheet->setCellValue("G{$row}", $totals['net_pay']);

        $this->styleSheet($row);
    }

    private function styleSheet(int $lastRow): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $sheet->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE2EFDA'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A5:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C6:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00);-');
        $sheet->getStyle("C6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("B6:B{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("H6:H{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A{$lastRow}:H{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:H{$lastRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFFCE4D6');

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A6');
    }

    private function nameWithPosition($employee): string
    {
        $name = strtoupper((string) $employee->name);
        $position = trim((string) $employee->position);

        return $position === '' ? $name : "{$name}\n{$position}";
    }

    private function groupEmployeesByDivision(): array
    {
        $groups = [];

        foreach ($this->employees as $employee) {
            $groupId = $employee->division_id ?? 'division:' . ($employee->division_name ?? 'none');

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'name' => $this->divisionName($employee),
                    'employees' => [],
                ];
            }

            $groups[$groupId]['employees'][] = $employee;
        }

        return array_values($groups);
    }

    private function divisionName($employee): string
    {
        $name = trim((string) ($employee->division_name ?? 'No Division'));
        $code = trim((string) ($employee->division_code ?? ''));

        if ($code === '' || str_contains($name, "({$code})")) {
            return $name;
        }

        return "{$name} ({$code})";
    }

    private function formatPayrollMonthYear(string $month): string
    {
        $month = trim($month);

        if ($month === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m', $month)->format('F Y');
        }

        return $month;
    }

    private function exportFile(string $payrollNo)
    {
        $exportPath = public_path('exports');

        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0775, true);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $fileName = strtolower("longevity_pay_registry_{$payrollNo}.xlsx");
        $filePath = "{$exportPath}/{$fileName}";

        $writer = new Xlsx($this->spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($filePath);

        return Response::download($filePath, $fileName)->deleteFileAfterSend(true);
    }
}
