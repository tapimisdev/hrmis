<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $this->middleware(function ($request, $next) {
            if (
                auth()->user()->can('hr.timekeeping.view') ||
                auth()->user()->can('emp.timelogs.checkin-out')
            ) {
                return $next($request);
            }

            abort(403, 'Unauthorized');
        })->only(['index', 'show']);

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
        $user = DB::table('employee_personal as ep')
            ->leftJoin('employee_information as ei', 'ep.employee_no', 'ei.employee_no')
            ->leftJoin('employee_organization as eo', 'ep.employee_no', 'eo.employee_no')
            ->leftJoin('positions as p', 'eo.position_id', 'p.id')
            ->leftJoin('units as u', 'eo.unit_id', 'u.id')
            ->select(
                'ei.user_id',

                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',
                'ep.age',

                'p.code as position_code',
                'p.name as position_name',

                'u.code as unit_code',
                'u.name as unit_name',
            )
            ->where('ep.employee_no', $employee_no)
            ->first();

        try {
            // Get month and year from query params, default to current month/year
            $month = $request->query('month', Carbon::now()->month);
            $year = $request->query('year', Carbon::now()->year);

            // Start and end date boundaries for the selected month
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

            // Fetch daily time records from the service
            $daily_time_record = $this->daily_time_record_service->getDTR([
                'user_id'     => $user->user_id,
                'employee_no' => $employee_no ?? null,
                'startDate'   => $startDate->toDateTimeString(),
                'endDate'     => $endDate->toDateTimeString(),
            ]);
            
            $daily_time_record['information'] = $user;

            return response()->json($daily_time_record, 200);   
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage(), 'status' => 'show dtr fails']);
        }
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
            ->whereIn('roles.name', ['emp_contractual', 'emp_regular'])
            ->where('employee_information.employee_no', $employee_no)
            ->select(
                'users.id',
                'users.email',
                'employee_information.employee_no',
                'employee_personal.profile',
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

        $image = $employee->profile ?? null;

        if ($image) {
            $image = Storage::url('uploads/employees/' . $employee->employee_no . '/profile/' . $employee->profile);
        } else {
            $image = 'https://ui-avatars.com/api/?name='
                . $employee->full_name
                . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
        }

        $profile = [
            'picture' => $image 
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
