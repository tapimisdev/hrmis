<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Services\EmployeeService;
use App\Services\EventService;

class Employee extends Controller
{
    
    public $EventService;

    public function __construct(EventService $EventService) {
        $this->EventService = $EventService;
    }

    public function children(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_children')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->firstname . ' ' . $row->lastname)
                ->editColumn('birthday', fn($row) => $row->birthdate ? Carbon::parse($row->birthdate)->format('F d, Y') : '')
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/children/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.children', [
                        'employee_no' => $row->employee_no, 
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function education(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_education')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('level', fn($row) => $row->level)
                ->editColumn('school_name', fn($row) => $row->school_name)
                ->editColumn('course', fn($row) => $row->course)
                ->editColumn('year_graduated', fn($row) => Carbon::parse($row->year_graduated)->format('M d, Y'))
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/education/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.education', [
                        'employee_no' => $row->employee_no, 
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function civil_service(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_civil_service')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('certification', fn($row) => $row->certification)
                ->editColumn('rating', fn($row) => $row->rating)
                ->editColumn('license_no', fn($row) => $row->license_no)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/civil-service/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.civil-service', [
                        'employee_no' => $row->employee_no, 
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function work_experience(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_work_experience')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('position', fn($row) => $row->position)
                ->editColumn('department', fn($row) => $row->department)
                ->editColumn('employment_status', fn($row) => $row->employment_status)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/work-experience/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.work-experience', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function voluntary_works(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_voluntary_works')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('organization', fn($row) => $row->organization)
                ->editColumn('consumed_hours', fn($row) => $row->consumed_hours)
                ->editColumn('position', fn($row) => $row->position)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/voluntary-works/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.voluntary-works', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function trainings(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_trainings')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->name)
                ->editColumn('consumed_hours', fn($row) => $row->consumed_hours)
                ->editColumn('sponsored_by', fn($row) => $row->sponsored_by)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/trainings/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.trainings', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function skills(Request $request)
    {
        $employee_no = $request->employee_no;
        $isDT = filter_var($request->isDT, FILTER_VALIDATE_BOOLEAN);
        $id = $request->id ?? null;

        $query = DB::table('employee_skills_hobbies')
            ->where('employee_no', $employee_no);

        if (!$isDT && $id) {
            $query->where('id', $id);
            $data = $query->first();
            return response()->json($data);
        }

        if ($isDT) {

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->name)
                ->editColumn('recognition', fn($row) => $row->recognition)
                ->editColumn('organization', fn($row) => $row->organization)
                ->editColumn('documents', function($row) {
                    
                    if(is_null($row->documents) || empty($row->documents)) {
                        return 'N/A';
                    }

                    $file = Storage::url('public/users/' . $row->employee_no . '/pds/skills/' . $row->documents);

                    return '<button type="button" class="open-document btn btn-primary text-center text-uppercase fw-bold" data-src="'.$file.'">View</button>';

                })
                ->addColumn('actions', function ($row) {

                    $deleteRoute = route('hris.employee.skills', [
                        'employee_no' => $row->employee_no,
                        'id' => $row->id
                    ]);

                    return '
                        <div class="d-block d-md-flex gap-2 justify-content-start">
                            <button id="btn-edit"
                                class="btn btn-secondary btn ms-1 my-1" 
                                data-id="'.$row->id.'"
                                title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button id="btn-delete"
                                class="btn btn-danger btn ms-1 my-1" 
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['documents', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request'], 400);
    }

    public function generateEmployeeNo(Request $request) {
        $dateHired = $request->dateHired;
        $service = app(EmployeeService::class);
        return $service->generateEmployeeNo($dateHired);
    }

    public function getAnnouncement(string $slug) {
        
        $announcement = DB::table('events_announcements')
                                ->where('slug', '=', $slug)
                                ->first();

        if (!$announcement) {
            return redirect()->route('announcement.index');
        }

        $tags = DB::table('events_announcements_tags')
            ->where('event_announcement_id', $announcement->id)
            ->pluck('name');

        $posted_by = DB::table('events_announcements_posted_by as eapb')
                    ->leftJoin('users as u', 'eapb.user_id', '=', 'u.id')
                    ->where('eapb.event_announcement_id', $announcement->id)
                    ->select('u.name')
                    ->pluck('name');

        $seeners = DB::table('events_announcements_viewers as eav')
                    ->leftJoin('employee_information as ei', 'eav.user_id', '=', 'ei.user_id')
                    ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
                    ->where('eav.event_announcement_id', $announcement->id)
                    ->select('eav.user_id', 'ei.employee_no', 'ep.profile', 'ep.firstname', 'ep.lastname')
                    ->get()
                    ->map(function ($d) {
                        if($d->profile != null) {
                            $d->profile = '/storage/users/' . $d->employee_no . '/profile-image/'. $d->profile;
                        } else {
                            $d->profile = "https://ui-avatars.com/api/?name=" . urlencode($d->firstname . ' ' . $d->lastname) . "&background=random&color=fff&font-size=0.5";
                        }
                        return $d;
                    });

        $attachments = DB::table('events_announcements_attachments')
                        ->where('event_announcement_id', $announcement->id)
                        ->get()
                        ->map(function ($d) {
                            $path = 'events/attachments/' . $d->filename;

                            if (Storage::disk('public')->exists($path)) {
                                $d->size = number_format(Storage::disk('public')->size($path) / 1024, 2) . ' KB';
                            } else {
                                $d->size = '0 KB'; // or 'N/A'
                            }

                            return $d;
                        });

        $exists = DB::table('events_announcements_viewers')
            ->where('event_announcement_id', $announcement->id)
            ->where('user_id', auth()->user()->id)
            ->exists();

        $employment_type_id = auth()->user()->employment_type_id;

        if (!$exists && in_array($employment_type_id, array_map(fn($case) => $case->value, EmploymentTypesEnum::cases()))) {
            DB::table('events_announcements_viewers')->insert([
                'event_announcement_id' => $announcement->id,
                'user_id' => auth()->user()->id,
                'viewed_at' => now()
            ]);
        }
        
        $randomAnnouncements = $this->get_random_announcements(4, $announcement->id);
            
        $data = [
            'announcement' => $announcement,
            'tags' => $tags,
            'posted_by' => $posted_by,
            'seeners' => $seeners,
            'attachments' => $attachments,
            'random_announcements' => $randomAnnouncements
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement retrieved successfully',
            'data' => $data,
        ]);
    }

    public function get_random_announcements($count = 4, $where_not = null)
    {
        $query = DB::table('events_announcements as ea')
            ->leftJoin('events_announcements_tags as eat', 'ea.id', '=', 'eat.event_announcement_id')
            ->leftJoin('events_announcements_viewers as eav', 'ea.id', '=', 'eav.event_announcement_id')
            ->leftJoin('users as u', 'eav.user_id', '=', 'u.id')
            ->when($where_not, function ($q) use ($where_not) {
                return $q->where('ea.id', '!=', $where_not);  // FIXED
            })
            ->inRandomOrder()
            ->take($count)
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
                'url' => route('announcement.show', ['slug' => $item->slug]),
                'body' => $item->description,
                'image' => $item->banner
                    ? asset(Storage::url('events/attachments/' . $item->banner))
                    : asset('./img/placeholder.png'),
                'seeners' => $seeners,
            ];
        });

        return $announcements;
    }

    public function getNotifications(Request $request)
    {
        $data = $this->EventService->getNotifications($request, ['employees', Auth::id()]);
        return response()->json($data);
    }

    public function saveReadNotification(Request $request)
    {
        $data = $this->EventService->saveReadNotification($request);

        return response()->json($data);
    } 



}
