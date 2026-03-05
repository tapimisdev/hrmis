<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeDashboardService {

    protected $daily_time_record_service;

    public function __construct(DailyTimeRecordService $daily_time_record_service)
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function get_stats($employee_no)
    {
        $user_id = DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('user_id');

        // Get month and year from query params, default to current month/year
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

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
    
        return $daily_time_record['summary'];
    }


    public function get_pendings($user_id)
    {
        
        $leave_request = DB::table('leave_applications')
                        ->where('user_id', $user_id)
                        ->where('status', 'pending')
                        ->count();

        $offset_request = DB::table('offset_applications')
                        ->where('user_id', $user_id)
                        ->where('status', 'pending')
                        ->count();

        $passlip_request = DB::table('obs_applications')
                        ->where('user_id', $user_id)
                        ->where('status', 'pending')
                        ->count();

        $overtime_request = DB::table('overtime_applications')
                        ->where('user_id', $user_id)
                        ->where('status', 'pending')
                        ->count();

        $so_request = DB::table('special_order_applications')
                        ->where('user_id', $user_id)
                        ->where('status', 'pending')
                        ->count();

        $obs_request = DB::table('obs_applications')
                ->where('user_id', $user_id)
                ->where('status', 'pending')
                ->count();

        $unviewedCount = DB::table('events_announcements as ea')
                ->leftJoin('events_announcements_viewers as eav', function ($join) use ($user_id) {
                    $join->on('ea.id', '=', 'eav.event_announcement_id')
                        ->where('eav.user_id', '=', $user_id);
                })
                ->whereNull('eav.viewed_at')
                ->count();

        $cards = [
            [
                'id' => 1,
                'name' => 'Leave',
                'icon' => 'fa-solid fa-plane-departure',
                'description' => 'Request leave and review your leave records',
                'pending' => $leave_request,
                'color' => '#032985',
                'route' => '/employee/leaves'
            ],
            [
                'id' => 2,
                'name' => 'Offset',
                'icon' => 'fa-solid fa-ghost',
                'description' => 'File an offset request and check past submissions',
                'pending' => $offset_request,
                'color' => '#032985',
                'route' => '/employee/offset'
            ],
            [
                'id' => 3,
                'name' => 'Special Order',
                'icon' => 'fa-solid fa-car-on',
                'description' => 'Create and track your pass slip requests',
                'pending' => $passlip_request,
                'color' => '#032985',
                'route' => ''
            ],
            [
                'id' => 4,
                'name' => 'Pass Slip / OBS',
                'icon' => 'fa-solid fa-torii-gate',
                'description' => 'Submit and monitor overtime requests',
                'pending' => $overtime_request,
                'color' => '#032985',
                'route' => '/employee/pass-slip'
            ],
            [
                'id' => 5,
                'name' => 'Overtime Request',
                'icon' => 'fa-solid fa-hourglass-half',
                'description' => 'Apply for overtime and view request status',
                'pending' => $overtime_request,
                'color' => '#032985',
                'route' => '/employee/overtime'
            ],
            [
                'id' => 6,
                'name' => 'Announcements',
                'icon' => 'fa-solid fa-bullhorn',
                'description' => 'Check the latest company updates and notices',
                'pending' => $unviewedCount,
                'color' => '#032985',
                'route' => '/employee/announcements'
            ],
        ];

        return $cards;
    }

    public function get_announcements($count = 4)
    {
        $query = DB::table('events_announcements as ea')
            ->leftJoin('events_announcements_tags as eat', 'ea.id', '=', 'eat.event_announcement_id')
            ->leftJoin('events_announcements_viewers as eav', 'ea.id', '=', 'eav.event_announcement_id')
            ->leftJoin('users as u', 'eav.user_id', '=', 'u.id')
            ->select(
                'ea.id',
                'ea.title',
                'ea.description',
                'ea.banner',
                'ea.slug',
                'ea.posted_on',
                'ea.created_at',
                DB::raw('GROUP_CONCAT(DISTINCT eat.name) as tags'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(u.id, ":", u.name)) as seeners')
            )
            ->groupBy(
                'ea.id',
                'ea.title',
                'ea.description',
                'ea.banner',
                'ea.posted_on',
                'ea.created_at'
            )
            ->orderByRaw('COALESCE(ea.posted_on, ea.created_at) DESC');

        if (!is_null($count)) {
            $query->limit($count);
        }

        $announcements = $query->get()->map(function ($item) {

            $tags = $item->tags ? explode(',', $item->tags) : [];

            $seeners = [];
            if ($item->seeners) {
                foreach (explode(',', $item->seeners) as $seener) {
                    $parts = explode(':', $seener);
                    if (count($parts) === 2) {
                        [$id, $name] = $parts;
                        $seeners[] = ['id' => (int) $id, 'name' => $name];
                    } else {
                        $seeners[] = ['id' => 0, 'name' => $parts[0]];
                    }
                }
            }

            return [
                'id' => $item->id,
                'name' => $item->title,
                'tags' => $tags,
                'url' => route('announcement.show', [ 'slug' => $item->slug ]),
                'body' => $item->description,
                'image' => $item->banner 
                    ? asset(Storage::url('events/attachments/' . $item->banner))
                    : asset('./img/placeholder.png'),
                'seeners' => $seeners,
            ];
        });

        return $announcements;
    }
}