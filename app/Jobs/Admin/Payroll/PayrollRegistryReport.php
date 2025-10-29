<?php

namespace App\Jobs\Admin\Payroll;

use App\Services\EmployeePayrollComputationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollRegistryReport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $employee;
    public int|string $payroll_id;

    /**
     * Create a new job instance.
     */
    public function __construct($employee, $payroll_id)
    {
        $this->employee = $employee;
        $this->payroll_id = $payroll_id;
    }

    /**
     * Execute the job.
     */
    public function handle(EmployeePayrollComputationService $employee_service): void
    {
        if ($this->batch()?->cancelled()) {
            Log::warning("Batch cancelled for Payroll ID: {$this->payroll_id}");
            return;
        }

        if (empty($this->employee)) {
            Log::warning("No employee found for Payroll ID: {$this->payroll_id}");
            return;
        }

        $employee = (array) $this->employee;

        $employeeNo = $employee['employee_no'] ?? 'unknown';

        $processedData = $employee_service->processEmployeeSalary($employeeNo, $this->payroll_id);

        DB::table('payroll_salary')
        ->where('id', $this->payroll_id)
        ->update([
            'no_employee'      => DB::raw('no_employee + 1'),
            'gross_amount'     => DB::raw("gross_amount + {$processedData['gross_amount']}"),
            'deduction_amount' => DB::raw("deduction_amount + {$processedData['deduction_amount']}"),
            'netpay_amount'    => DB::raw("netpay_amount + {$processedData['net_pay_amount']}")
        ]);

        Log::info("Completed payroll registry generation for Payroll ID: {$this->payroll_id}");
    }

    public function failed(\Throwable $exception)
    {
        // This is called automatically if the job fails
        Log::error("Job failed: " . $exception->getMessage());

        DB::table('payroll_salary')
        ->where('id', $this->payroll_id)
        ->update([
            'status'      => 'failed',
        ]);
    }
}
