<?php

namespace App\Services\Taxation;

use App\Models\Bir2316;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;

class Bir2316PdfService
{
    public function generate(Bir2316 $bir2316): string
    {
        $templatePath = base_path('2316_template.pdf');
        $outputPath = $this->outputPath($bir2316);
        $payload = $this->payload($bir2316);
        $coordinates = config('bir2316_coordinates.fields', []);

        $pdf = new Fpdi('P', 'mm');
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle(sprintf('BIR 2316 %s %d', $bir2316->employee_no, $bir2316->taxable_year));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        $pageCount = $pdf->setSourceFile($templatePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            if ($pageNumber !== 1) {
                continue;
            }

            foreach ($coordinates as $field => $options) {
                $this->writeField(
                    $pdf,
                    $field,
                    $payload[$field] ?? '',
                    (array) $options
                );
            }
        }

        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }

    public function inline(Bir2316 $bir2316, string $filename = 'BIR_2316.pdf')
    {
        $pdfPath = $this->generate($bir2316);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function download(Bir2316 $bir2316)
    {
        $pdfPath = $this->generate($bir2316);

        return response()->download(
            $pdfPath,
            sprintf('BIR_2316_%s_%d.pdf', $bir2316->employee_no, $bir2316->taxable_year)
        );
    }

    private function payload(Bir2316 $bir2316): array
    {
        $snapshot = (array) ($bir2316->snapshot_data ?? []);
        $employee = (array) data_get($snapshot, 'employee', []);
        $pdfValues = (array) data_get($snapshot, 'pdf_values', []);
        $allowables = (array) data_get($pdfValues, 'allowables', [
            'sss' => 0,
            'gsis' => 0,
            'philhealth' => 0,
            'pagibig' => 0,
        ]);
        $monthlyBreakdown = collect((array) data_get($snapshot, 'monthly_breakdown', []));
        $summary = (array) data_get($snapshot, 'summary', []);
        $totalTaxableCompensationIncome = (float) $bir2316->gross_taxable_income;
        $dateSigned = data_get($snapshot, 'certification.date_signed')
            ?: optional($bir2316->generated_at)->format('m/d/Y')
            ?: '';
        $employeeName = $this->reverseEmployeeName((string) $bir2316->employee_name);
        $employerAddress = $this->normalizeAddress((string) $bir2316->employer_address);
        $employeeAddress = $this->normalizeAddress((string) $bir2316->employee_address);
        $taxableYear = (string) $bir2316->taxable_year;

        return [
            'taxable_year' => $taxableYear,
            'year_from' => $taxableYear,
            'year_to' => $taxableYear,
            'employee_tin' => $this->formatTinDigits($bir2316->employee_tin),
            'employee_name' => Str::upper($employeeName),
            'employee_address' => Str::upper($employeeAddress),
            'employee_birth_date' => data_get($employee, 'birth_date') ? date('m/d/Y', strtotime((string) data_get($employee, 'birth_date'))) : '',
            'employee_contact_number' => (string) data_get($employee, 'contact_number', ''),
            'employee_zip_code' => (string) data_get($employee, 'zip_code', ''),
            'employee_rdo_code' => (string) ($bir2316->rdo_code ?? ''),
            'employer_tin' => $this->formatTinDigits($bir2316->employer_tin),
            'employer_name' => Str::upper((string) $bir2316->employer_name),
            'employer_address' => Str::upper($employerAddress),
            'employer_zip_code' => '',
            'main_employer' => true,
            'secondary_employer' => false,
            'gross_compensation_income' => $this->money($bir2316->gross_compensation_income),
            'gross_compensation_income_prev' => $this->money(0),
            'non_taxable_compensation' => $this->money((float) data_get($pdfValues, 'non_taxable_compensation', 0)),
            'taxable_compensation_income' => $this->money($bir2316->net_taxable_income),
            'tax_due' => $this->money($bir2316->annual_tax_due),
            'tax_withheld' => $this->money($bir2316->tax_withheld),
            'total_tax_payable_refundable' => $this->money($bir2316->tax_refund_or_payable),
            'tax_withheld_present' => $this->money($bir2316->tax_withheld),
            'tax_withheld_previous' => $this->money(0),
            'year_end_adjustment' => $this->money(0),
            'tax_refund' => $this->money((float) data_get($pdfValues, 'tax_refund', 0)),
            'tax_payable' => $this->money((float) data_get($pdfValues, 'tax_payable', 0)),
            'basic_salary' => $this->money($bir2316->annual_basic_salary),
            'statutory_minimum_wage' => $this->money(0),
            'holiday_pay' => $this->money(0),
            'overtime_pay' => $this->money(0),
            'night_shift_diff' => $this->money(0),
            'hazard_pay_exempt' => $this->money(0),
            'thirteenth_month_benefits' => $this->money($bir2316->tax_exempt_bonus),
            'hazard_pay' => $this->money($bir2316->hazard_pay),
            'government_bonuses' => $this->money($bir2316->government_bonuses),
            'de_minimis_benefits' => $this->money($bir2316->de_minimis),
            'sss_gsis_philhealth_pagibig_contributions' => $this->money((float) data_get($pdfValues, 'total_contributions', 0)),
            'union_dues' => $this->money(0),
            'salaries_other_compensation' => $this->money(0),
            'other_taxable_compensation' => $this->money((float) data_get($pdfValues, 'other_taxable_compensation', 0)),
            'other_nontaxable_compensation' => $this->money((float) data_get($pdfValues, 'other_nontaxable_compensation', 0)),
            'representation' => $this->money(0),
            'transportation' => $this->money(0),
            'cola' => $this->money(0),
            'housing_allowance' => $this->money(0),
            'supplementary_other' => $this->money($bir2316->government_bonuses),
            'commission' => $this->money(0),
            'profit_sharing' => $this->money(0),
            'directors_fees' => $this->money(0),
            'taxable_13th_month_benefits' => $this->money($bir2316->net_taxable_benefit),
            'overtime_pay_taxable' => $this->money(0),
            'other_pay' => $this->money($bir2316->longevity_pay),
            'other_benefits' => $this->money(0),
            'total_taxable_compensation_income' => $this->money($totalTaxableCompensationIncome),
            'gsis_contribution' => $this->money($allowables['gsis']),
            'philhealth_contribution' => $this->money($allowables['philhealth']),
            'pagibig_contribution' => $this->money($allowables['pagibig']),
            'sss_contribution' => $this->money($allowables['sss']),
            'position' => Str::upper((string) $bir2316->position),
            'employment_type' => Str::upper((string) $bir2316->employment_type),
            'tax_exempt_bonus' => $this->money($bir2316->tax_exempt_bonus),
            'net_taxable_benefit' => $this->money($bir2316->net_taxable_benefit),
            'gross_taxable_income' => $this->money($bir2316->gross_taxable_income),
            'allowable_deductions' => $this->money($bir2316->allowable_deductions),
            'longevity_pay' => $this->money($bir2316->longevity_pay),
            'contact_number' => (string) data_get($employee, 'contact_number', ''),
            'month_13_and_other_benefits' => $this->money($bir2316->government_bonuses),
            'withholding_tax_per_month_total' => $this->money((float) data_get($summary, 'total_tax_withheld', $monthlyBreakdown->sum('tax_withheld'))),
            'employee_signature_name' => Str::upper($employeeName),
            'employer_signatory_name' => Str::upper((string) data_get($snapshot, 'certification.authorized_signatory', '')),
            'date_signed' => $dateSigned,
            'date_signed_employee' => $dateSigned,
            'substituted_filing' => (bool) data_get($snapshot, 'certification.substitute_filing', false),
        ];
    }

    private function writeField(Fpdi $pdf, string $field, string $value, array $options): void
    {
        $type = (string) ($options['type'] ?? 'text');
        $x = (float) ($options['x'] ?? 0);
        $y = (float) ($options['y'] ?? 0);
        $w = (float) ($options['w'] ?? 0);
        $h = (float) ($options['h'] ?? 4);
        $font = (string) ($options['font'] ?? 'helvetica');
        $style = (string) ($options['style'] ?? '');
        $size = (float) ($options['size'] ?? 8);
        $minSize = (float) ($options['min_size'] ?? max(4.5, $size - 2));
        $align = (string) ($options['align'] ?? 'L');
        $lineHeight = (float) ($options['line_height'] ?? $h);
        $maxLines = (int) ($options['max_lines'] ?? 1);

        $pdf->SetFont($font, $style, $size);
        $pdf->SetTextColor(0, 0, 0);

        if ($type === 'segmented') {
            $this->writeSegmentedField($pdf, $value, $options);

            return;
        }

        if ($type === 'checkbox') {
            $this->writeCheckbox($pdf, $value, $options);

            return;
        }

        $fitSize = $this->fitFontSize($pdf, (string) $value, $w, $size, $minSize, $font, $style);
        $pdf->SetFont($font, $style, $fitSize);
        $pdf->SetXY($x, $y);

        if ($maxLines > 1) {
            $pdf->MultiCell($w, $lineHeight, (string) $value, 0, $align, false, 1, $x, $y, true, 0, false, true, $lineHeight * $maxLines, 'T', false);

            return;
        }

        $pdf->Cell($w, $h, (string) $value, 0, 0, $align, false, '', 0, false, 'T', 'M');
    }

    private function writeSegmentedField(Fpdi $pdf, string $value, array $options): void
    {
        $chars = preg_split('//u', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $segments = (int) ($options['segments'] ?? count($chars));
        $x = (float) ($options['x'] ?? 0);
        $y = (float) ($options['y'] ?? 0);
        $w = (float) ($options['w'] ?? 0);
        $h = (float) ($options['h'] ?? 4);
        $gap = (float) ($options['gap'] ?? 0);
        $font = (string) ($options['font'] ?? 'helvetica');
        $style = (string) ($options['style'] ?? '');
        $size = (float) ($options['size'] ?? 8);

        if ($segments <= 0) {
            return;
        }

        $boxWidth = ($w - (($segments - 1) * $gap)) / $segments;
        $pdf->SetFont($font, $style, $size);

        for ($index = 0; $index < $segments; $index++) {
            $pdf->SetXY($x + ($index * ($boxWidth + $gap)), $y);
            $pdf->Cell($boxWidth, $h, $chars[$index] ?? '', 0, 0, 'C', false, '', 0, false, 'T', 'M');
        }
    }

    private function writeCheckbox(Fpdi $pdf, string $value, array $options): void
    {
        $checked = filter_var($value, FILTER_VALIDATE_BOOL);

        if (!$checked) {
            return;
        }

        $x = (float) ($options['x'] ?? 0);
        $y = (float) ($options['y'] ?? 0);
        $w = (float) ($options['w'] ?? 4);
        $h = (float) ($options['h'] ?? 4);
        $font = (string) ($options['font'] ?? 'helvetica');
        $style = (string) ($options['style'] ?? 'B');
        $size = (float) ($options['size'] ?? 8);

        $pdf->SetFont($font, $style, $size);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, 'X', 0, 0, 'C', false, '', 0, false, 'T', 'M');
    }

    private function fitFontSize(Fpdi $pdf, string $value, float $width, float $startSize, float $minSize, string $font, string $style): float
    {
        if ($value === '' || $width <= 0) {
            return $startSize;
        }

        $current = $startSize;

        while ($current > $minSize) {
            $pdf->SetFont($font, $style, $current);

            if ($pdf->GetStringWidth($value) <= $width) {
                return $current;
            }

            $current -= 0.2;
        }

        return $minSize;
    }

    private function money(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }

    private function formatTinDigits(?string $tin): string
    {
        $digits = preg_replace('/\D+/', '', (string) $tin);

        if ($digits === '') {
            return '';
        }

        return substr($digits, 0, 12);
    }

    private function normalizeAddress(string $address): string
    {
        return preg_replace('/\s+/', ' ', trim($address)) ?: '';
    }

    private function reverseEmployeeName(string $employeeName): string
    {
        if (!str_contains($employeeName, ',')) {
            return $employeeName;
        }

        [$lastName, $remaining] = array_map('trim', explode(',', $employeeName, 2));

        return trim($remaining . ' ' . $lastName);
    }

    private function outputPath(Bir2316 $bir2316): string
    {
        $directory = sys_get_temp_dir() . '/orbit-bir2316';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return sprintf(
            '%s/bir-2316-%d-%d-%s.pdf',
            $directory,
            $bir2316->employee_id,
            $bir2316->taxable_year,
            md5((string) $bir2316->updated_at)
        );
    }
}
