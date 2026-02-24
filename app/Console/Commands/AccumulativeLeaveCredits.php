<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Http\Controllers\Admin\Hris\LeaveCreditController;
use App\Services\EmployeeService;

class AccumulativeLeaveCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:accumulation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate leave credits for all regular employees for the new month';

    /**
     * Append a message to the log file.
     *
     * @param string $message
     * @return void
     */
    private function appendLog(string $message): void
    {
        $logDir  = storage_path('logs');
        $logFile = $logDir.'/leave-accumulation.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamped = '['.now()->format('Y-m-d H:i:s').'] '.$message.PHP_EOL;
        file_put_contents($logFile, $timestamped, FILE_APPEND | LOCK_EX);
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $monthYear = now()->addMonth()->format('Y-m');

        $this->appendLog("Starting leave credits update for {$monthYear}.");
        $this->info("Starting leave credits update for {$monthYear}...");

        try {
            /** @var EmployeeService $employeeService */
            $employeeService = app(EmployeeService::class);
            $employees = $employeeService->getRegularEmployees();

            $leaveTypes = DB::table('leaves')
                ->where('cummulative_type', 'monthly')
                ->where('is_active', true)
                ->get();

            /** @var LeaveCreditController $controller */
            $controller = app(LeaveCreditController::class);

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leave) {
                    $request = new Request([
                        'as_of'    => $monthYear,
                        'earned'   => $leave->to_be_credited,
                        'deduction'=> 0,
                        'remarks'  => '',
                        'leave_id' => $leave->id,
                    ]);

                    try {
                        $response = $controller->save($employee->employee_no, $request);

                        if (method_exists($response, 'getStatusCode') && $response->getStatusCode() === 200) {
                            $this->appendLog("Successfully updated credits for employee {$employee->employee_no}, leave {$leave->id}.");
                        } else {
                            $content = method_exists($response, 'getContent') ? $response->getContent() : 'No response content';
                            $this->appendLog("Failed updating credits for employee {$employee->employee_no}, leave {$leave->id}: ".$content);
                        }
                    } catch (\Exception $inner) {
                        $this->appendLog("Exception while updating employee {$employee->employee_no}, leave {$leave->id}: ".$inner->getMessage());
                    }
                }
            }

            $this->appendLog("Leave credits update completed for {$monthYear}.");
            $this->info("Leave credits update completed for {$monthYear}.");
        } catch (\Exception $e) {
            $this->appendLog("Leave credits recalculation failed: ".$e->getMessage());
            $this->error("Leave credits recalculation failed: ".$e->getMessage());
        }
    }
}
