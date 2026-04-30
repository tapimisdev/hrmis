<?php

namespace App\Services\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PeraRataRegistryService
{
    protected $payroll;
    protected $employees;
    protected $spreadsheet;
    protected $sheet;

    protected int $templateStartRow = 6;

    public static function download($payroll_no)
    {
        return app(self::class)->process($payroll_no);
    }

    private function process($payroll_no)
    {
        $this->loadPayrollData($payroll_no);
        $this->loadTemplate();
        $this->writeEmployees();

        return $this->exportFile();
    }

    private function loadPayrollData($payroll_no)
    {
        $this->payroll = DB::table('payroll_pera_rata')
            ->where('payroll_no', $payroll_no)
            ->first();

        if (!$this->payroll) {
            abort(404, 'Pera-rata payroll not found.');
        }

        $payrollDate = Carbon::parse($this->payroll->month . '-01')->endOfMonth()->toDateString();
        $latestOrgDate = DB::table('employee_organization')
            ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
            ->whereDate('effectivity_date', '<=', $payrollDate)
            ->groupBy('employee_no');

        $latestOrgId = DB::table('employee_organization')
            ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
            ->groupBy('employee_no', 'effectivity_date');

        $this->employees = DB::table('payroll_pera_rata_employee as pre')
            ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                $join->on('pre.employee_no', '=', 'latest_org_date.employee_no');
            })
            ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                    ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
            })
            ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->where('pre.payroll_pera_rata_id', $this->payroll->id)
            ->select(
                'pre.*',
                'd.id as division_id',
                'd.name as division_name',
                'd.code as division_code'
            )
            ->orderBy('d.name')
            ->orderBy('pre.employee_no')
            ->get()
            ->map(function ($employee) {
                return [
                    'employee_no' => $employee->employee_no,
                    'name' => strtoupper((string) $employee->name),
                    'position' => ucwords(strtolower((string) $employee->position)),
                    'division_id' => $employee->division_id,
                    'division_name' => $employee->division_name ?? 'No Division',
                    'division_code' => $employee->division_code,
                    'pera' => (float) ($employee->pera ?? 0),
                    'representation_allowance' => (float) ($employee->representation_allowance ?? 0),
                    'transportion_allowance' => (float) ($employee->transportion_allowance ?? 0),
                    'absences' => (float) ($employee->absences ?? 0),
                    'ut_deductions' => (float) ($employee->ut_deductions ?? 0),
                    'total' => (float) ($employee->total ?? 0),
                    'healthcard' => (float) ($employee->healthcard ?? 0),
                    'adjustments' => (float) ($employee->adjustments ?? 0),
                    'net_pay' => (float) ($employee->net_pay ?? 0),
                    'remarks' => $employee->remarks,
                ];
            })
            ->values();
    }

    private function loadTemplate()
    {
        $this->spreadsheet = IOFactory::load(public_path('templates/regular/pera_rata_registry.xlsx'));
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->unfreezePane();
        $this->writeReportTitle();
        $this->removeBrokenNamedRanges();
    }

    private function writeEmployees()
    {
        $groups = $this->employees->groupBy(fn ($employee) => $employee['division_id'] ?? 'division:' . ($employee['division_name'] ?? 'none'))->values();
        $employeeCount = $this->employees->count();
        $groupCount = $groups->count();

        if ($employeeCount === 0) {
            return;
        }

        $insertCount = $employeeCount + ($groupCount * 2) + 1;
        $this->sheet->insertNewRowBefore($this->templateStartRow, $insertCount);

        $templateRow = $this->templateStartRow + $insertCount;
        $row = $this->templateStartRow;
        $counter = 1;
        $grandTotals = [
            'pera' => 0,
            'representation_allowance' => 0,
            'transportion_allowance' => 0,
            'absences' => 0,
            'ut_deductions' => 0,
            'total' => 0,
            'healthcard' => 0,
            'adjustments' => 0,
            'net_pay' => 0,
        ];

        foreach ($groups as $group) {
            $divisionName = $this->cleanText($this->divisionName($group->first()));

            $row = $this->writeProjectHeader($row, $divisionName, $templateRow);

            $groupTotals = [
                'pera' => 0,
                'representation_allowance' => 0,
                'transportion_allowance' => 0,
                'absences' => 0,
                'ut_deductions' => 0,
                'total' => 0,
                'healthcard' => 0,
                'adjustments' => 0,
                'net_pay' => 0,
            ];

            foreach ($group as $employee) {
                $this->copyTemplateRow($templateRow, $row);

                $this->sheet->setCellValue("A{$row}", $counter++);
                $this->sheet->setCellValue("B{$row}", $this->cleanText($employee['name']));
                $this->sheet->setCellValue("C{$row}", $this->cleanText($employee['position']));
                $this->sheet->setCellValue("D{$row}", $employee['pera']);
                $this->sheet->setCellValue("E{$row}", $employee['representation_allowance']);
                $this->sheet->setCellValue("F{$row}", $employee['transportion_allowance']);
                $this->sheet->setCellValue("G{$row}", $employee['absences']);
                $this->sheet->setCellValue("H{$row}", $employee['ut_deductions']);
                $this->sheet->setCellValue("I{$row}", $employee['total']);
                $this->sheet->setCellValue("J{$row}", $employee['healthcard']);
                $this->sheet->setCellValue("K{$row}", $employee['adjustments']);
                $this->sheet->setCellValue("L{$row}", $employee['net_pay']);

                foreach ($groupTotals as $field => $value) {
                    $groupTotals[$field] += (float) ($employee[$field] ?? 0);
                    $grandTotals[$field] += (float) ($employee[$field] ?? 0);
                }

                $row++;
            }

            $row = $this->writeSubtotalRow($row, $divisionName, $groupTotals, $templateRow);
        }

        $this->writeGrandTotalRow($row, $grandTotals, $templateRow);
    }

    private function cleanText($value): string
    {
        $text = (string) ($value ?? '');

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';
    }

    private function divisionName(array $employee): string
    {
        $name = trim((string) ($employee['division_name'] ?? 'No Division'));
        $code = trim((string) ($employee['division_code'] ?? ''));

        if ($code === '' || str_contains($name, "({$code})")) {
            return $name;
        }

        return "{$name} ({$code})";
    }

    private function writeReportTitle(): void
    {
        $period = $this->formatPayrollMonthYear($this->payroll->month ?? '');

        if ($period === '') {
            return;
        }

        $this->sheet->mergeCells('A2:L2');
        $this->sheet->setCellValue('A2', 'PAYROLL OF PERA AND RATA FOR THE MONTH OF ' . strtoupper($period));
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

    private function removeBrokenNamedRanges(): void
    {
        foreach (array_keys($this->spreadsheet->getNamedRanges()) as $name) {
            $namedRange = $this->spreadsheet->getNamedRange($name);
            $value = $namedRange?->getValue();

            if ($value && str_contains($value, '#REF!')) {
                $this->spreadsheet->removeNamedRange($name);
            }
        }

        foreach (array_keys($this->spreadsheet->getNamedFormulae()) as $name) {
            $namedFormula = $this->spreadsheet->getNamedFormula($name);
            $value = $namedFormula?->getValue();

            if ($value && str_contains($value, '#REF!')) {
                $this->spreadsheet->removeNamedFormula($name);
            }
        }
    }

    private function copyTemplateRow(int $sourceRow, int $targetRow): void
    {
        for ($col = 'A'; $col !== 'M'; $col++) {
            $this->sheet->duplicateStyle(
                $this->sheet->getStyle("{$col}{$sourceRow}"),
                "{$col}{$targetRow}"
            );
        }

        $this->sheet->getRowDimension($targetRow)->setRowHeight(
            $this->sheet->getRowDimension($sourceRow)->getRowHeight()
        );
    }

    private function writeProjectHeader(int $row, string $projectName, int $templateRow): int
    {
        $this->copyTemplateRow($templateRow, $row);
        $this->sheet->mergeCells("A{$row}:L{$row}");
        $this->sheet->setCellValue("A{$row}", strtoupper($projectName));
        $this->sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
                'italic' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFDE9D9'],
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
        $this->sheet->getRowDimension($row)->setRowHeight(24);

        return $row + 1;
    }

    private function writeSubtotalRow(int $row, string $projectName, array $totals, int $templateRow): int
    {
        $this->copyTemplateRow($templateRow, $row);
        $this->sheet->mergeCells("A{$row}:C{$row}");
        $this->sheet->setCellValue("A{$row}", "SUB TOTAL");
        $this->sheet->setCellValue("D{$row}", $totals['pera']);
        $this->sheet->setCellValue("E{$row}", $totals['representation_allowance']);
        $this->sheet->setCellValue("F{$row}", $totals['transportion_allowance']);
        $this->sheet->setCellValue("G{$row}", $totals['absences']);
        $this->sheet->setCellValue("H{$row}", $totals['ut_deductions']);
        $this->sheet->setCellValue("I{$row}", $totals['total']);
        $this->sheet->setCellValue("J{$row}", $totals['healthcard']);
        $this->sheet->setCellValue("K{$row}", $totals['adjustments']);
        $this->sheet->setCellValue("L{$row}", $totals['net_pay']);

        $this->sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF7F7F7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $this->sheet->getRowDimension($row)->setRowHeight(22);

        return $row + 1;
    }

    private function writeGrandTotalRow(int $row, array $totals, int $templateRow): void
    {
        $this->copyTemplateRow($templateRow, $row);
        $this->sheet->mergeCells("A{$row}:C{$row}");
        $this->sheet->setCellValue("A{$row}", "GRAND TOTAL");
        $this->sheet->setCellValue("D{$row}", $totals['pera']);
        $this->sheet->setCellValue("E{$row}", $totals['representation_allowance']);
        $this->sheet->setCellValue("F{$row}", $totals['transportion_allowance']);
        $this->sheet->setCellValue("G{$row}", $totals['absences']);
        $this->sheet->setCellValue("H{$row}", $totals['ut_deductions']);
        $this->sheet->setCellValue("I{$row}", $totals['total']);
        $this->sheet->setCellValue("J{$row}", $totals['healthcard']);
        $this->sheet->setCellValue("K{$row}", $totals['adjustments']);
        $this->sheet->setCellValue("L{$row}", $totals['net_pay']);

        $this->sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEFEFEF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $this->sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function exportFile()
    {
        $exportDir = storage_path('app/exports');

        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0775, true);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $fileName = "Pera_Rata_Registry_{$this->payroll->payroll_no}.xlsx";
        $filePath = $exportDir . '/' . $fileName;

        $writer = new Xlsx($this->spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($filePath);
        $this->sanitizeWorkbookPackage($filePath);

        return Response::download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    private function sanitizeWorkbookPackage(string $filePath): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($filePath) !== true) {
            return;
        }

        $this->sanitizeWorkbookXml($zip);
        $this->sanitizeWorkbookRels($zip);
        $this->sanitizeContentTypes($zip);
        $this->removeExternalLinkParts($zip);

        $zip->close();
    }

    private function sanitizeWorkbookXml(\ZipArchive $zip): void
    {
        $xml = $zip->getFromName('xl/workbook.xml');

        if ($xml === false) {
            return;
        }

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded) {
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            foreach ($xpath->query('//main:externalReferences') as $externalReferences) {
                $externalReferences->parentNode?->removeChild($externalReferences);
            }

            foreach ($xpath->query('//main:definedName') as $definedName) {
                $value = $definedName->textContent ?? '';

                if (str_contains($value, '#REF!') || preg_match('/\[[^\]]+\]/', $value)) {
                    $definedName->parentNode?->removeChild($definedName);
                }
            }

            $xml = $dom->saveXML() ?: $xml;
        }

        $zip->addFromString('xl/workbook.xml', $xml);
    }

    private function sanitizeWorkbookRels(\ZipArchive $zip): void
    {
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($rels === false) {
            return;
        }

        $rels = preg_replace(
            '/<Relationship[^>]*Type="[^"]*\/externalLink"[^>]*\/>/i',
            '',
            $rels
        ) ?? $rels;

        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
    }

    private function sanitizeContentTypes(\ZipArchive $zip): void
    {
        $contentTypes = $zip->getFromName('[Content_Types].xml');

        if ($contentTypes === false) {
            return;
        }

        $contentTypes = preg_replace(
            '/<Override[^>]*PartName="\/xl\/externalLinks\/[^"]+"[^>]*\/>/i',
            '',
            $contentTypes
        ) ?? $contentTypes;

        $zip->addFromString('[Content_Types].xml', $contentTypes);
    }

    private function removeExternalLinkParts(\ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (!$stat || !isset($stat['name'])) {
                continue;
            }

            if (str_starts_with($stat['name'], 'xl/externalLinks/')) {
                $zip->deleteName($stat['name']);
            }
        }
    }
}
