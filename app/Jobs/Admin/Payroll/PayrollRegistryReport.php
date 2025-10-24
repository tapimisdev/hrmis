<?php

namespace App\Jobs\Admin\Payroll;

use App\Services\EmployeePayrollComputationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        $employee_service->processEmployee($employeeNo, $this->payroll_id);

        Log::info("Completed payroll registry generation for Payroll ID: {$this->payroll_id}");
    }
}
