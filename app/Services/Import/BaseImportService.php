<?php

namespace App\Services\Import;

use App\Services\EmployeeService;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

abstract class BaseImportService
{
    protected EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    protected function overridePreviewHeaders(array $previewHeaders, array $overrides): array
    {
        foreach ($overrides as $field => $label) {
            if (array_key_exists($field, $previewHeaders)) {
                $previewHeaders[$field] = $label;
            }
        }

        return $previewHeaders;
    }

    protected function prependFieldOrder(string $field, array $fieldOrder): array
    {
        $fieldOrder = array_values(array_filter($fieldOrder, fn ($item) => $item !== $field));
        array_unshift($fieldOrder, $field);

        return $fieldOrder;
    }

    protected function prependFields(array $fields, array $fieldOrder): array
    {
        foreach (array_reverse($fields) as $field) {
            $fieldOrder = $this->prependFieldOrder($field, $fieldOrder);
        }

        return $fieldOrder;
    }

    protected function extractNamePositionBlock(string $value): array
    {
        $normalizedValue = preg_replace('/<br\s*\/?>/i', "\n", $value);
        $normalizedValue = str_replace(["\r\n", "\r"], "\n", $normalizedValue);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $normalizedValue)), fn ($line) => $line !== ''));

        $name = $lines[0] ?? '';
        $position = count($lines) > 1 ? implode("\n", array_slice($lines, 1)) : '';

        return [$name, $position];
    }

    protected function parseSheetByHeaders(
        string $filePath,
        int $headerRow,
        array $expectedHeaders,
        array $fixedColumns = [],
        array $optionalFields = []
    ): array {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        [$headerIndexes, $headerLabels] = $this->buildHeaderIndexes($sheet, $highestColumn, $headerRow);

        $resolvedIndexes = [];
        $missingHeaders = [];
        $previewHeaders = [];

        foreach ($expectedHeaders as $field => $aliases) {
            $index = array_key_exists($field, $fixedColumns)
                ? $fixedColumns[$field]
                : $this->matchHeaderIndex($headerIndexes, $aliases);

            if ($index === null && !in_array($field, $optionalFields, true)) {
                $missingHeaders[] = $field;
            }

            $resolvedIndexes[$field] = $index;
            $previewHeaders[$field] = $index !== null
                ? ($headerLabels[$index] ?? $field)
                : $field;
        }

        $resolvedIndexes = $this->adjustResolvedIndexesForMergedHeaders(
            $sheet,
            $resolvedIndexes,
            $headerRow,
            $highestColumnIndex
        );

        if ($missingHeaders !== []) {
            throw new \RuntimeException('Missing required header(s): ' . implode(', ', $missingHeaders));
        }

        $rows = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            if ($this->shouldSkipParsedRow($sheet, $row, $highestColumnIndex)) {
                continue;
            }

            $rowData = $sheet->rangeToArray(
                "A{$row}:{$highestColumn}{$row}",
                null,
                true,
                false
            )[0];

            $mappedRow = [];

            foreach ($resolvedIndexes as $field => $index) {
                $mappedRow[$field] = $index !== null
                    ? ($rowData[$index] ?? null)
                    : null;
            }

            $rows[] = $mappedRow;
        }

        return [
            'rows' => $rows,
            'preview_headers' => $previewHeaders,
            'field_order' => array_keys($expectedHeaders),
            'resolved_indexes' => $resolvedIndexes,
        ];
    }

    protected function fieldWasParsed(array $parsed, string $field): bool
    {
        return array_key_exists($field, $parsed['resolved_indexes'] ?? [])
            && $parsed['resolved_indexes'][$field] !== null;
    }

    protected function availableFieldOrder(array $parsed): array
    {
        return array_values(array_filter(
            $parsed['field_order'] ?? [],
            fn ($field) => $this->fieldWasParsed($parsed, $field)
        ));
    }

    protected function normalizeDeductionKey(string $value): string
    {
        return Str::of($value)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->trim()
            ->value();
    }

    protected function regularDeductionFamily(string $normalizedHeader): string
    {
        if (str_contains($normalizedHeader, 'MP 2') || str_contains($normalizedHeader, 'MP2')) {
            return 'pagibig_mp2';
        }
        if (str_contains($normalizedHeader, 'CALAMITY')) {
            return 'pagibig_calamity';
        }
        if (preg_match('/\bMPL\b/', $normalizedHeader) === 1 && str_contains($normalizedHeader, 'PAG')) {
            return 'pagibig_mpl';
        }
        if (str_contains($normalizedHeader, 'PAG')) {
            return 'pagibig';
        }
        if (str_contains($normalizedHeader, 'PHIL')) {
            return 'philhealth';
        }
        if (str_contains($normalizedHeader, 'LAND')) {
            return 'landbank';
        }
        if (str_contains($normalizedHeader, 'GSIS') && str_contains($normalizedHeader, 'FINANCIAL')) {
            return 'gsis_financial_assistance';
        }
        if (str_contains($normalizedHeader, 'GSIS') && str_contains($normalizedHeader, 'POLICY')) {
            return 'gsis_policy';
        }
        if (str_contains($normalizedHeader, 'GSIS') && (str_contains($normalizedHeader, 'EMER') || str_contains($normalizedHeader, 'EMERGENCY'))) {
            return 'gsis_emergency';
        }
        if (str_contains($normalizedHeader, 'GSIS') && str_contains($normalizedHeader, 'LITE')) {
            return 'gsis_mpl_lite';
        }
        if (str_contains($normalizedHeader, 'GSIS') && preg_match('/\bMPL\b/', $normalizedHeader) === 1) {
            return 'gsis_mpl';
        }
        if (str_contains($normalizedHeader, 'GSIS')) {
            return 'gsis';
        }

        return $normalizedHeader;
    }

    protected function toAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return 0.0;
        }

        return (float) $normalized;
    }

    protected function toPercentageDecimal($value): float
    {
        $amount = $this->toAmount($value);
        return $amount > 1 ? $amount / 100 : $amount;
    }

    protected function amountFromKeys(array $row, array $keys): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $this->toAmount($row[$key]);
            }
        }

        return 0.0;
    }

    private function adjustResolvedIndexesForMergedHeaders($sheet, array $resolvedIndexes, int $headerRow, int $highestColumnIndex): array
    {
        foreach (['Name', 'Employee'] as $field) {
            $index = $resolvedIndexes[$field] ?? null;
            if ($index === null) {
                continue;
            }

            $sampleRow = $this->findFirstDataRowAfterHeader($sheet, $headerRow, $highestColumnIndex);
            if ($sampleRow === null) {
                continue;
            }

            $currentValue = $sheet->getCellByColumnAndRow($index + 1, $sampleRow)->getFormattedValue();
            $nextValue = $sheet->getCellByColumnAndRow($index + 2, $sampleRow)->getFormattedValue();

            if (
                $this->looksLikeRowCounter($currentValue) &&
                !$this->looksLikeRowCounter($nextValue) &&
                trim((string) $nextValue) !== ''
            ) {
                $resolvedIndexes[$field] = $index + 1;
            }
        }

        return $resolvedIndexes;
    }

    private function findFirstDataRowAfterHeader($sheet, int $headerRow, int $highestColumnIndex): ?int
    {
        $highestRow = $sheet->getHighestDataRow();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            if (!$this->shouldSkipParsedRow($sheet, $row, $highestColumnIndex)) {
                return $row;
            }
        }

        return null;
    }

    private function looksLikeRowCounter($value): bool
    {
        $value = trim((string) $value);
        return $value !== '' && preg_match('/^\d+$/', $value) === 1;
    }

    private function buildHeaderIndexes($sheet, string $highestColumn, int $row): array
    {
        $previousHeaders = $row > 1
            ? $sheet->rangeToArray("A" . ($row - 1) . ":{$highestColumn}" . ($row - 1), null, true, false)[0]
            : [];

        $currentHeaders = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0];
        $nextHeaders = $row < $sheet->getHighestDataRow()
            ? $sheet->rangeToArray("A" . ($row + 1) . ":{$highestColumn}" . ($row + 1), null, true, false)[0]
            : [];

        $headerIndexes = [];
        $headerLabels = [];

        foreach ($currentHeaders as $index => $header) {
            $candidates = [
                implode(' ', array_filter([(string) ($previousHeaders[$index] ?? ''), (string) $header])),
                implode(' ', array_filter([(string) $header, (string) ($nextHeaders[$index] ?? '')])),
                implode(' ', array_filter([(string) ($previousHeaders[$index] ?? ''), (string) $header, (string) ($nextHeaders[$index] ?? '')])),
                (string) $header,
                (string) ($previousHeaders[$index] ?? ''),
                (string) ($nextHeaders[$index] ?? ''),
            ];

            $normalizedHeader = '';
            $displayHeader = '';

            foreach ($candidates as $candidate) {
                $normalizedCandidate = $this->normalizeSheetHeader($candidate);

                if ($normalizedCandidate !== '') {
                    $normalizedHeader = $normalizedCandidate;
                    $displayHeader = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $candidate)));
                    break;
                }
            }

            if ($normalizedHeader === '' || isset($headerIndexes[$normalizedHeader])) {
                continue;
            }

            $headerIndexes[$normalizedHeader] = $index;
            $headerLabels[$index] = $displayHeader !== '' ? $displayHeader : (string) $header;
        }

        return [$headerIndexes, $headerLabels];
    }

    private function matchHeaderIndex(array $headerIndexes, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeSheetHeader($alias);

            if (array_key_exists($normalizedAlias, $headerIndexes)) {
                return $headerIndexes[$normalizedAlias];
            }

            foreach ($headerIndexes as $normalizedHeader => $index) {
                if (!$this->headersCanShareMatch($normalizedAlias, $normalizedHeader)) {
                    continue;
                }

                if (
                    str_contains($normalizedHeader, $normalizedAlias) ||
                    str_contains($normalizedAlias, $normalizedHeader)
                ) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function headersCanShareMatch(string $normalizedAlias, string $normalizedHeader): bool
    {
        $aliasFamily = $this->regularDeductionFamily($normalizedAlias);
        $headerFamily = $this->regularDeductionFamily($normalizedHeader);

        $trackedFamilies = [
            'pagibig',
            'pagibig_mp2',
            'pagibig_calamity',
            'pagibig_mpl',
            'philhealth',
            'landbank',
            'gsis',
            'gsis_financial_assistance',
            'gsis_policy',
            'gsis_emergency',
            'gsis_mpl',
            'gsis_mpl_lite',
        ];

        if (in_array($aliasFamily, $trackedFamilies, true) || in_array($headerFamily, $trackedFamilies, true)) {
            return $aliasFamily === $headerFamily;
        }

        return true;
    }

    private function shouldSkipParsedRow($sheet, int $row, int $highestColumnIndex): bool
    {
        if ($this->rowHasWideMergedCell($sheet, $row, $highestColumnIndex)) {
            return true;
        }

        $rowValues = $sheet->rangeToArray(
            'A' . $row . ':' . Coordinate::stringFromColumnIndex($highestColumnIndex) . $row,
            null,
            true,
            false
        )[0];

        $rowText = $this->normalizeSheetHeader(implode(' ', array_filter(array_map(
            fn ($value) => is_scalar($value) ? (string) $value : '',
            $rowValues
        ))));

        if ($rowText === '') {
            return true;
        }

        if (str_contains($rowText, 'TOTAL')) {
            return true;
        }

        $footerMarkers = [
            'I CERTIFY',
            'OFFICIAL OATH',
            'SERVICES HAVE BEEN DULY RENDERED',
            'SERVICES DULY RENDERED',
            'CORRECT AND THAT THE SERVICES',
            'CORRECT AND THAT THE ABOVE PAYROLL',
        ];

        foreach ($footerMarkers as $marker) {
            if (str_contains($rowText, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function rowHasWideMergedCell($sheet, int $row, int $highestColumnIndex): bool
    {
        foreach ($sheet->getMergeCells() as $range) {
            [$startCell, $endCell] = explode(':', $range);
            [$startColumn, $startRow] = Coordinate::coordinateFromString($startCell);
            [$endColumn, $endRow] = Coordinate::coordinateFromString($endCell);

            if ((int) $startRow !== $row || (int) $endRow !== $row) {
                continue;
            }

            $startIndex = Coordinate::columnIndexFromString($startColumn);
            $endIndex = Coordinate::columnIndexFromString($endColumn);
            $span = $endIndex - $startIndex + 1;

            if ($startIndex === 1 && $endIndex >= $highestColumnIndex) {
                return true;
            }

            if ($span >= max(3, (int) ceil($highestColumnIndex * 0.7))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSheetHeader(string $value): string
    {
        return Str::of($value)
            ->replace("\n", ' ')
            ->replace("\r", ' ')
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->trim()
            ->value();
    }
}
