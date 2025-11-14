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

        $passlip_request = DB::table('obs_applications')
                        ->where('user_id', $user_id)
                        ->where('status', 'pending')
                        ->count();

        $overtime_request = DB::table('overtime_applications')
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
                'name' => 'Leave Request',
                'icon' => 'fa-solid fa-calendar-days',
                'description' => 'Apply for leave and view leave history',
                'pending' => $leave_request,
                'color' => '#032985',
                'route' => '/leave'
            ],
            [
                'id' => 2,
                'name' => 'Pass Slip Request',
                'icon' => 'fa-solid fa-id-card',
                'description' => 'Submit and monitor pass slip requests',
                'pending' => $passlip_request,
                'color' => '#032985',
                'route' => '/pass-slip'
            ],
            [
                'id' => 3,
                'name' => 'Overtime Request',
                'icon' => 'fa-solid fa-hourglass-half',
                'description' => 'Submit and track overtime applications',
                'pending' => $overtime_request,
                'color' => '#032985',
                'route' => '/overtime'
            ],
            [
                'id' => 4,
                'name' => 'Announcements',
                'icon' => 'fa-solid fa-bullhorn',
                'description' => 'View unread company announcements',
                'pending' => $unviewedCount,
                'color' => '#032985',
                'route' => '/announcements'
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
                    [$id, $name] = explode(':', $seener);
                    $seeners[] = ['id' => (int) $id, 'name' => $name];
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