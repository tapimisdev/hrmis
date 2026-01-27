<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
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

    public function getCurrentTimelog()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        // Prepare payload to fetch today's DTR
        $payload = [
            'user_id' => $userId,
            'startDate' => $today,
            'endDate' => $today,
        ];

        // Fetch DTR from your service
        $logs = $this->daily_time_record_service->getDtr($payload)['computedData'][0] ?? [];

        return response()->json($logs);

        // Return null if no current incomplete log
        return response()->json(null);
    }

    public function downloadLogs(Request $request)
    {
        // Validate request
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:1900',
        ]);

        $userId = Auth::id();

        // Get first and last day of the month/year
        $firstDay = Carbon::create($request->year, $request->month, 1)->startOfDay();
        $lastDay = Carbon::create($request->year, $request->month, 1)->endOfMonth()->endOfDay();

        $payload = [
            'user_id' => $userId,
            'startDate' => $firstDay->toDateString(),  // or ->format('Y-m-d H:i:s') if needed
            'endDate'   => $lastDay->toDateString(),   // same as above
        ];

        // Fetch logs
        $logs = $this->daily_time_record_service->getDtr($payload) ?? [];

        dd($logs);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }



}
