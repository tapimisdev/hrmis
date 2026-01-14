<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;

class LogsController extends Controller
{

    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    /**
     * Get all incomplete logs for the current month for the authenticated user.
     */
    public function getIncompleteLogs()
    {
        $userId = Auth::id();
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        $payload = [
            'user_id' => $userId,
            'startDate' => $startOfMonth,
            'endDate' => $endOfMonth
        ];

        $logs = $this->daily_time_record_service->getDtr($payload)['computedData'] ?? [];

        $incompleteLogs = collect($logs)
            ->filter(function ($log) {
                return isset($log['remarks']) &&
                    is_array($log['remarks']) &&
                    collect($log['remarks'])
                            ->map('strtolower')
                            ->contains('incomplete log');
            })
            ->map(function ($log) {
                // Remove "incomplete log" only if there are other remarks
                if (count($log['remarks']) > 1) {
                    $log['remarks'] = array_values(
                        array_filter($log['remarks'], function ($remark) {
                            return strtolower($remark) !== 'incomplete log';
                        })
                    );
                }

                return $log;
            })
            ->values();

        return response()->json($incompleteLogs);
    }

}
