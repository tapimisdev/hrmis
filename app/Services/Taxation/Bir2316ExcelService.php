<?php

namespace App\Services\Taxation;

use App\Models\Bir2316;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Bir2316ExcelService
{
    private const TEMPLATE_PATH = 'exports/2316_template.xlsx';

    public function generate(Bir2316 $bir2316): string
    {
        $templatePath = public_path(self::TEMPLATE_PATH);

        if (!is_file($templatePath)) {
            throw new \RuntimeException('BIR 2316 Excel template was not found.');
        }

        $spreadsheet = IOFactory::createReader('Xlsx')
            ->setReadDataOnly(false)
            ->load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->configureLayout($sheet);
        $this->writeEmployeeInformation($sheet, $bir2316);
        $this->writeEmployerInformation($sheet, $bir2316);
        $this->writeCompensationInformation($sheet, $bir2316);
        $this->writeCertification($sheet, $bir2316);

        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle(sprintf('BIR 2316 %s %d', $bir2316->employee_no, $bir2316->taxable_year));
        $spreadsheet->setActiveSheetIndex(0);

        $outputPath = $this->outputPath($bir2316);
        (new Xlsx($spreadsheet))->save($outputPath);
        $spreadsheet->disconnectWorksheets();

        return $outputPath;
    }

    public function download(Bir2316 $bir2316)
    {
        $outputPath = $this->generate($bir2316);
        $fileName = sprintf('BIR_2316_%s_%d.xlsx', $bir2316->employee_no, $bir2316->taxable_year);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download(
            $outputPath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, must-revalidate',
                'Pragma' => 'public',
            ]
        )->deleteFileAfterSend(true);
    }

    public function preview(Bir2316 $bir2316)
    {
        $outputPath = $this->generate($bir2316);

        try {
            $spreadsheet = IOFactory::load($outputPath);
            $writer = new Html($spreadsheet);
            $writer->writeAllSheets();
            $writer->setEmbedImages(true);

            $html = $writer->generateHtmlAll();
            $spreadsheet->disconnectWorksheets();

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    private function writeEmployeeInformation(Worksheet $sheet, Bir2316 $bir2316): void
    {
        $employee = $this->employeePersonalInformation($bir2316);
        $year = (int) $bir2316->taxable_year;

        $this->text($sheet, 'G11', (string) $year, Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'AB11', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'AI11', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'B15', $this->formatTin($employee['tin']));
        $this->text($sheet, 'A17', strtoupper($employee['name']));
        $this->text($sheet, 'Q17', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'A20', strtoupper($employee['registered_address']));
        $this->text($sheet, 'Q20', $employee['registered_zip'], Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'A23', strtoupper($employee['local_address']));
        $this->text($sheet, 'Q23', $employee['local_zip'], Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'A30', $this->date($employee['birthday']), Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'J30', $employee['contact_number'], Alignment::HORIZONTAL_CENTER);
    }

    private function writeEmployerInformation(Worksheet $sheet, Bir2316 $bir2316): void
    {
        $snapshot = (array) ($bir2316->snapshot_data ?? []);
        $employer = (array) data_get($snapshot, 'employer', []);
        $employerAddress = $this->validEmployerAddress((string) $bir2316->employer_address);

        $this->text($sheet, 'B42', $this->formatTin($bir2316->employer_tin));
        $this->text($sheet, 'A44', strtoupper((string) $bir2316->employer_name));
        $this->text($sheet, 'A47', strtoupper($employerAddress));
        $this->text($sheet, 'Q47', (string) data_get($employer, 'zip_code', ''), Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'G49', 'X', Alignment::HORIZONTAL_CENTER);
    }

    private function writeCompensationInformation(Worksheet $sheet, Bir2316 $bir2316): void
    {
        $snapshot = (array) ($bir2316->snapshot_data ?? []);
        $pdfValues = (array) data_get($snapshot, 'pdf_values', []);
        $contributions = (float) data_get($pdfValues, 'total_contributions', 0);
        $otherNonTaxable = (float) data_get($pdfValues, 'other_nontaxable_compensation', 0);
        $nonTaxable = round(
            (float) $bir2316->tax_exempt_bonus
            + (float) $bir2316->de_minimis
            + $contributions
            + $otherNonTaxable,
            2
        );
        $taxable = (float) $bir2316->gross_taxable_income;
        $grossCompensation = round($nonTaxable + $taxable, 2);

        $amounts = [
            // Part IV-B, non-taxable/exempt compensation.
            'AH16' => 0,
            'AH18' => 0,
            'AH20' => 0,
            'AH23' => 0,
            'AH26' => 0,
            'AH28' => $bir2316->tax_exempt_bonus,
            'AH30' => $bir2316->de_minimis,
            'AH33' => $contributions,
            'AH36' => $otherNonTaxable,
            'AH39' => $nonTaxable,

            // Part IV-B, taxable compensation.
            'AH43' => $bir2316->annual_basic_salary,
            'AH45' => 0,
            'AH47' => 0,
            'AH50' => 0,
            'AH52' => 0,
            'AH55' => 0,
            'AH57' => 0,
            'AH60' => 0,
            'AH62' => 0,
            'AH64' => 0,
            'AH66' => $bir2316->net_taxable_benefit,
            'AH68' => $bir2316->hazard_pay,
            'AH70' => 0,
            'AH73' => $bir2316->longevity_pay,
            'AH75' => data_get($pdfValues, 'other_taxable_compensation', 0),
            'AH77' => $taxable,

            // Part IV-A, summary.
            'M61' => $grossCompensation,
            'M63' => $nonTaxable,
            'M65' => $taxable,
            'M67' => 0,
            'M69' => $taxable,
            'M71' => $bir2316->annual_tax_due,
            'M74' => $bir2316->tax_withheld,
            'M75' => 0,
            'M77' => $bir2316->tax_withheld,
            'M79' => 0,
            'M80' => $bir2316->tax_withheld,
        ];

        foreach ($amounts as $cell => $amount) {
            $this->amount($sheet, $cell, $amount);
        }
    }

    private function writeCertification(Worksheet $sheet, Bir2316 $bir2316): void
    {
        $snapshot = (array) ($bir2316->snapshot_data ?? []);
        $certification = (array) data_get($snapshot, 'certification', []);
        $employee = $this->employeePersonalInformation($bir2316);
        $employeeName = strtoupper($employee['name']);

        $this->text($sheet, 'C86', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'AC86', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'C89', $employeeName, Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'AC89', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'C99', '', Alignment::HORIZONTAL_CENTER);
        $this->text($sheet, 'Z101', $employeeName, Alignment::HORIZONTAL_CENTER);
    }

    private function configureLayout(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(85);
        $sheet->getPageSetup()
            ->setPrintArea('A2:AN103')
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(1);

        foreach ([
            13 => 15,
            14 => 15,
            15 => 18,
            16 => 15,
            17 => 18,
            19 => 15,
            20 => 18,
            22 => 15,
            23 => 18,
            26 => 15,
            27 => 18,
            29 => 15,
            30 => 18,
            32 => 15,
            33 => 18,
            35 => 15,
            36 => 18,
            38 => 15,
            39 => 18,
            40 => 15,
            41 => 15,
            42 => 18,
            43 => 15,
            44 => 20,
            46 => 15,
            47 => 20,
            49 => 15,
            50 => 18,
            51 => 15,
            52 => 15,
            53 => 18,
            54 => 15,
            55 => 18,
            57 => 15,
            58 => 18,
            86 => 22,
            87 => 18,
            89 => 22,
            90 => 18,
            93 => 16,
            94 => 16,
            95 => 16,
            96 => 16,
            97 => 16,
            98 => 16,
            99 => 22,
            100 => 18,
            101 => 22,
            102 => 18,
        ] as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }

        foreach ([
            'G11:L12',
            'AB11:AE12',
            'AI11:AL12',
            'B15:T15',
            'A17:P17',
            'Q17:T17',
            'A20:P20',
            'Q20:T20',
            'A23:P23',
            'Q23:T23',
            'A27:T27',
            'A30:I30',
            'J30:T30',
            'M33:T34',
            'M36:T37',
            'B38:B39',
            'B42:T42',
            'A44:T44',
            'A47:P47',
            'Q47:T47',
            'G49:G50',
            'M49:M50',
            'B53:T53',
            'A55:T55',
            'A58:P58',
            'Q58:T58',
            'M61:T62',
            'M63:T64',
            'M65:T66',
            'M67:T68',
            'M69:T70',
            'M71:T72',
            'M74:T74',
            'M75:T76',
            'M77:T78',
            'M79:T79',
            'M80:T81',
            'AH16:AN17',
            'AH18:AN19',
            'AH20:AN22',
            'AH23:AN25',
            'AH26:AN27',
            'AH28:AN29',
            'AH30:AN32',
            'AH33:AN35',
            'AH36:AN38',
            'AH39:AN40',
            'AH43:AN44',
            'AH45:AN46',
            'AH47:AN49',
            'AH50:AN51',
            'AH52:AN53',
            'W55:AF56',
            'AH55:AN56',
            'W57:AF58',
            'AH57:AN58',
            'AH60:AN61',
            'AH62:AN63',
            'AH64:AN65',
            'AH66:AN67',
            'AH68:AN69',
            'AH70:AN71',
            'W73:AF74',
            'AH73:AN74',
            'W75:AF76',
            'AH75:AN76',
            'AH77:AN81',
            'AC86:AH87',
            'AC89:AH90',
        ] as $range) {
            $this->inputBox($sheet, $range);
        }

        $this->signatureLine($sheet, 'C99:S99');
        $this->signatureLine($sheet, 'Z101:AN101');
        $this->signatureLine($sheet, 'C89:S89');
    }

    private function inputBox(Worksheet $sheet, string $range): void
    {
        foreach ($sheet->getMergeCells() as $mergedRange) {
            if ($this->rangesIntersect($range, $mergedRange)) {
                $sheet->unmergeCells($mergedRange);
            }
        }

        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 8,
                'color' => ['argb' => 'FF000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFFFF'],
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
                'shrinkToFit' => true,
            ],
        ]);
    }

    private function rangesIntersect(string $firstRange, string $secondRange): bool
    {
        [$firstStart, $firstEnd] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($firstRange);
        [$secondStart, $secondEnd] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($secondRange);

        return $firstStart[0] <= $secondEnd[0]
            && $firstEnd[0] >= $secondStart[0]
            && $firstStart[1] <= $secondEnd[1]
            && $firstEnd[1] >= $secondStart[1];
    }

    private function signatureLine(Worksheet $sheet, string $range): void
    {
        foreach ($sheet->getMergeCells() as $mergedRange) {
            if ($this->rangesIntersect($range, $mergedRange)) {
                $sheet->unmergeCells($mergedRange);
            }
        }

        $sheet->mergeCells($range);
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 8,
                'color' => ['argb' => 'FF000000'],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_BOTTOM,
                'shrinkToFit' => true,
            ],
        ]);
    }

    private function employeePersonalInformation(Bir2316 $bir2316): array
    {
        $employee = DB::table('employee_information as ei')
            ->leftJoin('users as u', 'u.id', '=', 'ei.user_id')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'ei.employee_no')
            ->where('ei.id', $bir2316->employee_id)
            ->first([
                'ei.employee_no',
                'u.name as user_name',
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',
                'ep.tin_no',
                'ep.birthday',
                'ep.mobile_number',
                'ep.tel_no',
                'ep.present_block',
                'ep.present_street',
                'ep.present_subdivision',
                'ep.present_barangay',
                'ep.present_city',
                'ep.present_province',
                'ep.present_zip',
                'ep.permanent_block',
                'ep.permanent_street',
                'ep.permanent_subdivision',
                'ep.permanent_barangay',
                'ep.permanent_city',
                'ep.permanent_province',
                'ep.permanent_zip',
            ]);

        if (!$employee) {
            return [
                'name' => (string) $bir2316->employee_name,
                'tin' => (string) $bir2316->employee_tin,
                'birthday' => '',
                'contact_number' => '',
                'registered_address' => $this->normalizeText((string) $bir2316->employee_address),
                'registered_zip' => '',
                'local_address' => $this->normalizeText((string) $bir2316->employee_address),
                'local_zip' => '',
            ];
        }

        $name = $this->employeeName($employee)
            ?: $this->normalizeText((string) ($employee->user_name ?? ''))
            ?: (string) $bir2316->employee_name;
        $registeredAddress = $this->address([
            $employee->permanent_block,
            $employee->permanent_street,
            $employee->permanent_subdivision,
            $employee->permanent_barangay,
            $employee->permanent_city,
            $employee->permanent_province,
        ]);
        $localAddress = $this->address([
            $employee->present_block,
            $employee->present_street,
            $employee->present_subdivision,
            $employee->present_barangay,
            $employee->present_city,
            $employee->present_province,
        ]);

        return [
            'name' => $name,
            'tin' => (string) ($employee->tin_no ?: $bir2316->employee_tin),
            'birthday' => (string) ($employee->birthday ?? ''),
            'contact_number' => (string) ($employee->mobile_number ?: $employee->tel_no ?: ''),
            'registered_address' => $registeredAddress ?: $localAddress,
            'registered_zip' => (string) ($employee->permanent_zip ?: $employee->present_zip ?: ''),
            'local_address' => $localAddress ?: $registeredAddress,
            'local_zip' => (string) ($employee->present_zip ?: $employee->permanent_zip ?: ''),
        ];
    }

    private function employeeName(object $employee): string
    {
        $lastName = trim((string) ($employee->lastname ?? ''));
        $firstName = trim((string) ($employee->firstname ?? ''));
        $middleName = trim((string) ($employee->middlename ?? ''));
        $suffix = trim((string) ($employee->suffix ?? ''));

        if ($lastName === '' && $firstName === '') {
            return '';
        }

        return trim(implode(' ', array_filter([
            $lastName !== '' ? $lastName . ',' : '',
            $firstName,
            $middleName,
            $suffix,
        ])));
    }

    private function address(array $parts): string
    {
        return collect($parts)
            ->map(fn ($part) => $this->normalizeText((string) $part))
            ->filter()
            ->implode(', ');
    }

    private function validEmployerAddress(string $address): string
    {
        $address = $this->normalizeText($address);

        if (strlen($address) > 150 || str_contains(strtolower($address), 'created by virtue')) {
            return '';
        }

        return $address;
    }

    private function text(Worksheet $sheet, string $cell, string $value, string $alignment = Alignment::HORIZONTAL_LEFT): void
    {
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
        $sheet->getStyle($cell)->getAlignment()
            ->setHorizontal($alignment)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setShrinkToFit(true);
    }

    private function amount(Worksheet $sheet, string $cell, float|int|string|null $value): void
    {
        $sheet->setCellValue($cell, round((float) $value, 2));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle($cell)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function formatTin(?string $tin): string
    {
        $digits = substr((string) preg_replace('/\D+/', '', (string) $tin), 0, 12);

        if ($digits === '') {
            return '';
        }

        return implode('-', array_filter(str_split($digits, 3)));
    }

    private function date(string $date): string
    {
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);

        return $timestamp === false ? $date : date('m/d/Y', $timestamp);
    }

    private function normalizeText(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?: '';
    }

    private function outputPath(Bir2316 $bir2316): string
    {
        $directory = sys_get_temp_dir() . '/orbit-bir2316';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return sprintf(
            '%s/bir-2316-%d-%d-%s.xlsx',
            $directory,
            $bir2316->employee_id,
            $bir2316->taxable_year,
            bin2hex(random_bytes(6))
        );
    }
}
