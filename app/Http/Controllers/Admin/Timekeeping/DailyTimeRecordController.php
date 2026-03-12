<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Services\DailyTimeRecordService;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DailyTimeRecordController extends Controller
{
    /**
     * Service instance for handling Daily Time Record logic.
     *
     * @var DailyTimeRecordService
     */
    protected $daily_time_record_service;
    protected $employeeService;

    /**
     * Inject the DailyTimeRecordService dependency.
     *
     * @param DailyTimeRecordService $daily_time_record_service
     */
    public function __construct(
        DailyTimeRecordService $daily_time_record_service,
        EmployeeService $employeeService
    )
    {
        $this->daily_time_record_service = $daily_time_record_service;
        $this->employeeService = $employeeService;

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
        $employee_id = DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('user_id');

        if(is_null($employee_id)) {
            return redirect()->route('timelogs.index');
        }

        $employee = $this->employeeService->getEmployee('information', $employee_no);
        $supervisor = $employee->division_supervisor ?? '';

        return view('admin.pages.timekeeping.timelogs.daily-time-record.index', compact('employee_no', 'employee_id', 'supervisor'));
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
        $user = DB::table('employee_organization')
            ->leftJoin('employee_information', 'employee_organization.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->select(
                'employee_personal.employee_no',
                'employee_personal.firstname',
                'employee_personal.middlename',
                'employee_personal.lastname',
                'employee_personal.suffix',
                'employee_organization.employment_type_id',
                'positions.name as position_name',
                'users.id as user_id'
            )
            ->where('employee_organization.employee_no', $employee_no)
            ->orderBy('employee_organization.id', 'desc') // Order by id descending to get the latest (max id)
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
        // Subquery: latest shift work schedule
        $latestShift = DB::table('employee_shift_work_schedule as esws1')
            ->select('esws1.*')
            ->whereRaw('esws1.id = (
                SELECT MAX(esws2.id)
                FROM employee_shift_work_schedule esws2
                WHERE esws2.employee_no = esws1.employee_no
            )');

        // Subquery: latest organization
        $latestOrg = DB::table('employee_organization as eo1')
            ->select('eo1.*')
            ->whereRaw('eo1.id = (
                SELECT MAX(eo2.id)
                FROM employee_organization eo2
                WHERE eo2.employee_no = eo1.employee_no
            )');

        $employee = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('employee_information', 'users.id', '=', 'employee_information.user_id')
            ->leftJoinSub($latestShift, 'employee_shift_work_schedule', function ($join) {
                $join->on('employee_information.employee_no', '=', 'employee_shift_work_schedule.employee_no');
            })
            ->leftJoinSub($latestOrg, 'employee_organization', function ($join) {
                $join->on('employee_information.employee_no', '=', 'employee_organization.employee_no');
            })
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
            $image = Storage::url(
                'public/users/' . $employee->employee_no . '/profile-image/' . $employee->profile
            );
        } else {
            $image = 'https://ui-avatars.com/api/?name='
                . urlencode($employee->full_name)
                . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
        }

        $profile = [
            'picture' => $image,
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

    public function downloadDAR(Request $request)
    {

        $user = Auth::user();
        $roles = $user->getRoleNames()->toArray() ?? [];
        $user_id = $request->user_id;

        $isEmpRole = collect($roles)->contains(function ($role) {
            return str_contains($role, 'emp_');
        });

        if($isEmpRole) {
            $employee_no = $user->employee_no?? null;
        } else {
            $employee_no = $this->employeeService->getEmployeeNo($user_id) ?? null;
            $employee = $this->employeeService->getEmployee('information', $employee_no) ?? null;
        }


        if(is_null($employee_no)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are no longer allowed to proceess action'
            ]);
        }

        $file_path = $request->path;

        preg_match('#/users/([^/]+)#', $file_path, $matches);
        
        $folderName = $matches[1] ?? null;

        if ($folderName !== $employee_no) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not allowed to download the Daily Accomplishment Report of employee no. `'.$employee_no.'`'
            ]);
        }

        $storage_path = ltrim($file_path, '/storage/');

        if (!Storage::disk('public')->exists($storage_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, we\'re unable to find this file. Please contact HR / Administrator. Thank you.'
            ]);
        }

        return response()->json([
            'status' => 'success',
            "message" => "Your file is ready for download",
            'file' => $file_path 
        ]);
    }

}
