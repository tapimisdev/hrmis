<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementsController extends Controller
{
    public function index(Request $request)
    {
        
        if ($request->wantsJson() || $request->query('json')) {

            $announcements = $this->get_announcements(null, 10, $request->search);
            
            return response()->json([
                'data' => $announcements,
                'message' => 'success getting announcements',
            ]);
        }

        return view('employee.pages.announcements.index');
    }

    public function show($slug)
    {
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
                    ->select('eav.user_id', 'ep.firstname', 'ep.lastname')
                    ->get()
                    ->map(function ($d) {
                        $profile = "https://ui-avatars.com/api/?name=" . urlencode($d->firstname . ' ' . $d->lastname) . "&background=random&color=fff&font-size=0.5";
                        $d->profile = $profile;
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

        if (! $exists) {
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

        return view('employee.pages.announcements.show', compact('data'));
    }

    public function get_announcements($count = 4, $paginated_by = null, $search = null)
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
                'ea.slug',
                'ea.posted_on',
                'ea.created_at'
            )
            ->orderByRaw('COALESCE(ea.posted_on, ea.created_at) DESC');

        /* =========================
        * SEARCH FILTER
        * ========================= */
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ea.title', 'LIKE', "%{$search}%")
                ->orWhere('ea.description', 'LIKE', "%{$search}%")
                ->orWhere('eat.name', 'LIKE', "%{$search}%");
            });
        }

        /* =========================
        * PAGINATION / LIMIT
        * ========================= */
        if (is_null($paginated_by) && !is_null($count)) {
            $query->limit($count);
            $announcements = $query->get();
        } else {
            $announcements = $query->paginate($paginated_by);
        }

        /* =========================
        * MAP RESULTS
        * ========================= */
        $announcements->getCollection()->transform(function ($item) {
            $tags = $item->tags ? explode(',', $item->tags) : [];

            $seeners = [];
            if ($item->seeners) {
                foreach (explode(',', $item->seeners) as $seener) {
                    [$id, $name] = explode(':', $seener);
                    $seeners[] = [
                        'id' => (int) $id,
                        'name' => $name,
                    ];
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

}
