<?php

namespace App\Jobs\Admin\Payroll;

use App\Services\GovernmentBonus\ComputationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GovernmentBonusReport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $employee;
    public int|string $payroll_id;

    public function __construct($employee, $payrollId)
    {
        $this->employee = $employee;
        $this->payroll_id = $payrollId;
    }

    public function handle(ComputationService $service): void
    {
        if ($this->batch()?->cancelled()) {
            Log::warning("Batch cancelled for payroll ID: {$this->payroll_id}");
            return;
        }

        if (empty($this->employee)) {
            Log::warning("No employee found for payroll ID: {$this->payroll_id}");
            return;
        }

        $employee = (array) $this->employee;
        $employeeNo = $employee['employee_no'] ?? 'unknown';

        $service->process($employeeNo, $this->payroll_id);

        DB::table('payroll_government_bonus')
            ->where('id', $this->payroll_id)
            ->update([
                'no_employee' => DB::raw('no_employee + 1'),
            ]);

        Log::info("Completed government bonus generation for payroll ID: {$this->payroll_id}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Government bonus job failed: " . $exception->getMessage());

        DB::table('payroll_government_bonus')
            ->where('id', $this->payroll_id)
            ->update([
                'status' => 'failed',
            ]);
    }
}
