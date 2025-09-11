<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyTimeRecordController extends Controller
{
    protected $daily_time_record_service;

    public function __construct(DailyTimeRecordService $daily_time_record_service)
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function index($id)
    {
        return view('admin.pages.timekeeping.daily-time-record.index', compact('id'));
    }

    public function show(Request $request, $id)
    {
        $user_id = $id;

        // Get month and year from query, default to current month/year if not provided
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        // Start of the month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();

        // End of the month
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        // Fetch daily time records
        $daily_time_record = $this->daily_time_record_service->getDTR([
            'user_id'     => $user_id,
            'employee_no' => $employee_no ?? null,
            'startDate'   => $startDate->toDateTimeString(),
            'endDate'     => $endDate->toDateTimeString(),
        ]);

        return response()->json($daily_time_record, 200);
    }

}
