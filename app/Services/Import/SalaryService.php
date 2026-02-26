<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\Import\SalaryJobImport;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class SalaryService
{
    /**
     * Clean uploaded Excel and return data as array
     *
     * @param string $filePath
     * @return array
     */

    protected $employeeService;

    public function __construct(EmployeeService $employeeService) {
        $this->employeeService = $employeeService;
    }
    
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
            "Employee No", "Name", "Monthly Rate", "Salary Earned", "AUT",
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

        $errors = [];

        // Clean Name column
        foreach ($cleaned as &$row) {

            $row['Name'] = trim(explode("\n", $row['Name'])[0]);
            $employee_no = $this->employeeService->getEmployeeNoBasedOnFullName($row['Name']);
            $row['Employee No'] = $employee_no ?? 'N/A';

            if (!is_null($employee_no)) {

                $employee = $this->employeeService->getEmployee('information', $employee_no);
            
                if ($employee) {
                    $row['Position'] = $employee->position_name ?? '';
                    $row['Salary Grade'] = $employee->salary_grade ?? '';
                } else {
                    $row['Position'] = '';
                    $row['Salary Grade'] = '';
                }

            } else {

                $row['Position'] = '';
                $row['Salary Grade'] = '';

                $errors[] = [
                    'name'   => $row['Name'],
                    'reason' => 'Unable to find employee number'
                ];
            }
        }

        // Remove rows with missing key fields
        $cleaned = array_filter($cleaned, function($row) {
            return !empty($row['Employee No']) && !empty($row['Name']) && !empty($row['Monthly Rate']);
        });

        return array_values($cleaned); // reset keys
    }

    public function cleanRegular($filePath): array
    {

    }

    public function importCOS(array $data)
    {
        $label = $data['label'];
        $payroll_no = generateNo('SL-', 4);
        $employment_type_id = $data['employment_type'];
        $cutoff = $data['cutoff'];
        $period_covered = $data['period_covered'];
        $payroll_date = $data['date'];
        $no_employee = count($data['data']);

        // Calculate totals
        $grossAmount = $deductionAmount = $netPayAmount = 0;
        foreach ($data['data'] as $employee) {
            $grossAmount += $employee['Total Salary'] ?? 0;
            $deductionAmount += 
                ($employee['AUT'] ?? 0) +
                ($employee['Two Percent'] ?? 0) +
                ($employee['Three Percent'] ?? 0) +
                ($employee['Five Percent'] ?? 0) +
                ($employee['Healthcard'] ?? 0);
            $netPayAmount += $employee['Net Salary'] ?? 0;
        }

        // Insert payroll summary (batch_id will be updated after batch creation)
        $payroll_id = DB::table('payroll_salary')->insertGetId([
            'batch_id' => null,
            'label' => $label,
            'payroll_no' => $payroll_no,
            'employment_type_id' => $employment_type_id,
            'cutoff' => $cutoff,
            'period_covered' => $period_covered,
            'no_employee' => $no_employee,
            'gross_amount' => $grossAmount,
            'deduction_amount' => $deductionAmount,
            'netpay_amount' => $netPayAmount,
            'payroll_date' => $payroll_date,
            'processed_by_id' => Auth::id(),
            'status' => 'approved', 
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Build employee jobs
        $jobs = [];
        foreach ($data['data'] as $employee) {
            $jobs[] = new SalaryJobImport($employee, $payroll_id);
        }


        // Dispatch the batch
        $batch = Bus::batch($jobs)
            ->then(function ($batch) use ($payroll_id) {
                DB::table('payroll_salary')->where('id', $payroll_id)
                    ->update(['status' => 'completed']);
            })
            ->catch(function ($batch, \Throwable $e) use ($payroll_id) {
                DB::table('payroll_salary')->where('id', $payroll_id)
                    ->update(['status' => 'failed']);
            })
            ->dispatch();

        // Update payroll with batch ID
        DB::table('payroll_salary')->where('id', $payroll_id)
            ->update(['batch_id' => $batch->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Importing Started!'
        ]);
    }
}