<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\EmployeeService;

class SalaryService
{
    /**
     * Clean uploaded Excel and return data as array
     *
     * @param string $filePath
     * @return array
     */
    
    public function cleanCOS($filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = 'K'; // A-K

        $data = [];
        // Skip first 6 rows
        for ($row = 7; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray("A{$row}:K{$row}", null, true, false)[0];
            $data[] = $rowData;
        }

        // Rename columns
        $columns = [
            "Employee No", "Name", "Monthly rate", "Salary Earned", "AUT",
            "Total Salary", "Two Percent", "Three Percent", "Five Percent",
            "Healthcard", "Net Salary"
        ];

        $cleaned = array_map(function($row) use ($columns) {
            return array_combine($columns, $row);
        }, $data);

        // Remove TOTAL rows
        $cleaned = array_filter($cleaned, function($row) {
            return stripos((string)$row['Employee No'], 'TOTAL') === false;
        });

        // Remove rows after Grand Total
        $grandTotalIndex = null;
        foreach ($cleaned as $i => $row) {
            if (stripos($row['Employee No'], 'Grand Total') !== false) {
                $grandTotalIndex = $i;
                break;
            }
        }
        if (!is_null($grandTotalIndex)) {
            $cleaned = array_slice($cleaned, 0, $grandTotalIndex);
        }

        // Clean Name column
        foreach ($cleaned as &$row) {
            $row['Name'] = trim(explode("\n", $row['Name'])[0]);
            $row['Employee No'] = app(EmployeeService::class)
                ->getEmployeeNoBasedOnFullName($row['Name']) ?? 'N/A';     
       }

        // Remove rows with missing key fields
        $cleaned = array_filter($cleaned, function($row) {
            return !empty($row['Employee No']) && !empty($row['Name']) && !empty($row['Monthly rate']);
        });

        return array_values($cleaned); // reset keys
    }

    public function cleanRegular($filePath): array
    {

    }

    public function importCOS(array $data) {
        
        $label = $data['label'];
        $payroll_no = generateNo('SL-', 4);
        $employment_type_id = $data['employment_type'];
        $cutoff = $data['cutoff'];
        $period_covered = $data['period_covered'];
        $no_employee = count($data['data']);

        $grossAmount = 0;
        $deductionAmount = 0;
        $netPayAmount = 0;

        foreach($data['data'] as $employee) {
            $grossAmount += $employee['Total Salary'] ?? 0;

            $deductionAmount += 
                ($employee['AUT'] ?? 0) +
                ($employee['Two Percent'] ?? 0) +
                ($employee['Three Percent'] ?? 0) +
                ($employee['Five Percent'] ?? 0) +
                ($employee['Healthcard'] ?? 0);

            $netPayAmount += $employee['Net Salary'] ?? 0;
        }

        dd($data);


    }
}