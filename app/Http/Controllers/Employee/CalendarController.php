<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Admin\Services\ApplicationController;

class CalendarController extends Controller
{

    protected $applicationService;

    public function __construct(ApplicationController $applicationService) {
        $this->applicationService = $applicationService;
    }

    public function index(Request $request) {
        if($request->ajax()) {

            $application = $this->applicationService->getData(['leave', 'offset', 'obs', 'special_order'])['applications'];
            
            $applications = $application->map(function ($item) {

                $link = [
                    'leave' => '/employee/leaves',
                    'offset' => '/employee/offset',
                    'obs' => '/employee/pass-slip'
                ];

                return (object) [
                    'title' => $item->title,
                    'date' => $item->date,
                    'type' => strtolower($item->status), 
                    'description' => $item->description ?? '',
                    'redirect' => $link[$item->title] ?? '',
                ];
            });

            $applicationsOnly = $applications->filter(function ($item) {
                return in_array(strtolower($item->title), ['leave', 'offset', 'obs', 'special order']);
            });

            $pendingApplications = $applicationsOnly->filter(function ($item) {
                return $item->type === 'pending';
            })->values();

            $approvedApplications = $applicationsOnly->filter(function ($item) {
                return $item->type === 'approved';
            })->values();

            $events = DB::table('events_announcements')
                ->leftJoin('suspension', 'events_announcements.id', '=', 'suspension.events_announcements_id')
                ->whereNull('suspension.id') 
                ->select(
                    'events_announcements.title',
                    DB::raw('DATE(events_announcements.posted_on) as date'),
                    DB::raw("'event' as type"),
                    DB::raw("CONCAT('/employee/announcements/', events_announcements.slug) as redirect")
                )
                ->get();

            $suspensions = DB::table('suspension_dates')
                ->leftJoin('suspension', 'suspension.id', '=', 'suspension_dates.suspension_id')
                ->leftJoin('events_announcements', 'events_announcements.id', '=', 'suspension.events_announcements_id')
                ->select(
                    'suspension.name as title',
                    'suspension_dates.date',
                    'suspension.description as description',
                    DB::raw("'suspension' as type"),
                    DB::raw("CASE 
                                WHEN suspension.events_announcements_id IS NOT NULL THEN CONCAT('/employee/announcements/', events_announcements.slug)
                                ELSE '#' 
                            END as redirect")
                )
                ->get();

            $holidays = DB::table('holidays')->select(
                    'name as title',
                    'date',
                    DB::raw("'holiday' as type"),
                    DB::raw("'#' as redirect")
                )
                ->get();

            $calendar = collect()
                ->merge($events)
                ->merge($suspensions)
                ->merge($holidays)
                ->merge($pendingApplications)
                ->merge($approvedApplications)
                ->sortBy('date')
                ->groupBy('date')
                ->map(function ($items, $date) {
                    return [
                        'date' => $date,
                        'items' => $items->map(function ($item) {
                            return [
                                'title' => $item->title,
                                'type' => $item->type,
                                'description' => $item->description ?? '',
                                'redirect' => $item->redirect,
                            ];
                        })->values()
                    ];
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'data' => $calendar
            ]);
        }

        return view('employee.pages.calendar.index');
    }
}
