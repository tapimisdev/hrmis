<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\EmploymentTypesEnum;

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

        return view('employee.pages.announcements.show', compact('announcement'));
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
            $tags = $item->tags ? array_values(array_filter(array_map('trim', explode(',', $item->tags)))) : [];

            $seeners = [];
            if (!empty($item->seeners)) {
                foreach (explode(',', $item->seeners) as $seenerRaw) {
                    $seenerRaw = trim($seenerRaw);
                    if ($seenerRaw === '') continue;

                    // Split into 2 parts only: "id:name"
                    $parts = explode(':', $seenerRaw, 2);

                    // If format is invalid, skip
                    if (count($parts) < 2) continue;

                    [$idRaw, $nameRaw] = $parts;

                    $idRaw = trim($idRaw);
                    $nameRaw = trim($nameRaw);

                    // Validate id + name
                    if ($idRaw === '' || !ctype_digit($idRaw)) continue;
                    if ($nameRaw === '') continue;

                    $seeners[] = [
                        'id' => (int) $idRaw,
                        'name' => $nameRaw,
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
                    : asset('img/placeholder.png'),
                'seeners' => $seeners,
            ];
        });

        return $announcements;
    }
}
