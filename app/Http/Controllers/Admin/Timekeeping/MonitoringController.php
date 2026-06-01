<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EmployeeService;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;

class MonitoringController extends Controller
{

    protected $employeeService;
    protected $dtrService;

    public function __construct(
        EmployeeService $employeeService,
        DailyTimeRecordService $dtrService
    )
    {
        $this->middleware('permission:hr.timekeeping.view')->only(['index', 'show']);
        $this->employeeService = $employeeService;
        $this->dtrService = $dtrService;
    }

    public function index(Request $request)
    {
        $date = $request->date ?? Carbon::now()->format('Y-m-d');
        $columns = $this->buildColumns($date);

        return view('admin.pages.timekeeping.monitoring.index', compact('columns', 'date'));
    }

    private function buildColumns(string $date): array
    {
        $employees = $this->employeeService->getEmployees('active', null, null, null);

        $start = $date;
        $end = $start;

        $columns = [
            'clock_in' => [],
            'break' => [],
            'clock_out' => [],
        ];

        foreach ($employees as $employee) {
            $payload = [
                'user_id' => $employee->user_id,
                'startDate' => $start,
                'endDate' => $end
            ];

            $timelogs = $this->dtrService->getDTR($payload);

            $log = $timelogs['computedData'][0] ?? null;

            if ($log) {
                if (!empty($log['time_out'])) {
                    $columns['clock_out'][] = [
                        'employee' => $employee,
                        'log' => $log
                    ];
                } elseif (!empty($log['break']) && str_contains($log['break'], '--')) {
                    $columns['break'][] = [
                        'employee' => $employee,
                        'log' => $log
                    ];
                } elseif (!empty($log['time_in'])) {
                    $columns['clock_in'][] = [
                        'employee' => $employee,
                        'log' => $log
                    ];
                }
            }
        }

        return $columns;
    }

}
