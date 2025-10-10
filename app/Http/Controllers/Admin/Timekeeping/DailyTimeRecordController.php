<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyTimeRecordController extends Controller
{
    /**
     * Service instance for handling Daily Time Record logic.
     *
     * @var DailyTimeRecordService
     */
    protected $daily_time_record_service;

    /**
     * Inject the DailyTimeRecordService dependency.
     *
     * @param DailyTimeRecordService $daily_time_record_service
     */
    public function __construct(DailyTimeRecordService $daily_time_record_service)
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    /**
     * Display the Daily Time Record index page.
     *
     * @param  int  $id  The user ID for which to view DTRs.
     * @return \Illuminate\View\View
     */
    public function index($employee_no)
    {
        return view('admin.pages.timekeeping.timelogs.daily-time-record.index', compact('employee_no'));
    }

    /**
     * Retrieve daily time records for a given user and month.
     *
     * Accepts optional `month` and `year` query parameters.
     * If not provided, defaults to the current month and year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  The user ID whose DTR will be fetched.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $employee_no)
    {
        $user_id = DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('user_id');

        // Get month and year from query params, default to current month/year
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        // Start and end date boundaries for the selected month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        // Fetch daily time records from the service
        $daily_time_record = $this->daily_time_record_service->getDTR([
            'user_id'     => $user_id,
            'employee_no' => $employee_no ?? null,
            'startDate'   => $startDate->toDateTimeString(),
            'endDate'     => $endDate->toDateTimeString(),
        ]);

        return response()->json($daily_time_record, 200);
    }

    public function employee_information_with_summary(Request $request, $employee_no)
    {
        $employee = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id') // spatie roles table
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('employee_information', 'users.id', '=', 'employee_information.user_id')
            ->leftJoin('employee_shift_work_schedule', 'employee_information.employee_no', '=', 'employee_shift_work_schedule.employee_no')
            ->leftJoin('employee_organization', 'employee_information.employee_no', '=', 'employee_organization.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
            ->leftJoin('work_schedule', 'employee_shift_work_schedule.work_schedule_id', '=', 'work_schedule.id')
            ->leftJoin('units', 'employee_organization.unit_id', '=', 'units.id')
            ->leftJoin('shifts', 'employee_shift_work_schedule.shift_id', '=', 'shifts.id')
            ->where('roles.name', 'employee')
            ->where('employee_information.employee_no', $employee_no)
            ->select(
                'users.id',
                'users.email',
                'employee_information.employee_no',
                DB::raw("CONCAT(employee_personal.firstname, ' ', employee_personal.lastname) as full_name"),
                'positions.name as position_name',
                'work_schedule.name as work_schedule_name',
                'units.name as units_name',
                'shifts.name as shift_name'
            )
            ->first();

        if (!$employee) {
            abort(404, 'No employee found.');
        }

        $profile = [
            'picture' => $employee->picture 
                ?? "https://ui-avatars.com/api/?name=" . urlencode($employee->full_name) . "&background=random&color=fff&font-size=0.5",
            'name'    => $employee->full_name,
        ];

        $infoCards = [
            ['label' => "Position",      'value' => $employee->position_name ?? 'N/A'],
            ['label' => "Unit",          'value' => $employee->units_name ?? 'N/A'],
            ['label' => "Official Time", 'value' => $employee->shift_name ?? 'N/A'],
            ['label' => "Schedule",      'value' => $employee->work_schedule_name ?? 'N/A'],
        ];

        return response()->json([
            'profile'   => $profile,
            'infoCards' => $infoCards
        ], 200);
    }

}
