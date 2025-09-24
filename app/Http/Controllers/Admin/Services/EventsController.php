<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\EventAnnouncement;

class EventsController extends Controller
{
    
    public function index()
    {
        $perPage = 10;

        $data = EventAnnouncement::with(['tags', 'attachments', 'posted_by', 'viewers'])
            ->paginate($perPage);

        return view('admin.pages.services.events.index', compact('data'));
    }


    public function show(string $slug) 
    {

        $data = DB::table('events_announcements')
            ->leftJoin('events_announcements_posted_by', 'events_announcements.id', '=', 'events_announcements_posted_by.event_announcement_id')
            ->leftJoin('users', 'events_announcements_posted_by.user_id', '=', 'users.id')
            ->leftJoin('events_announcements_tags', 'events_announcements.id', '=', 'events_announcements_tags.event_announcement_id')
            ->leftJoin('events_announcements_attachments', 'events_announcements.id', '=', 'events_announcements_attachments.event_announcement_id')
            ->select(
                'events_announcements.id',
                'events_announcements.title',
                'events_announcements.slug',
                'events_announcements.banner',
                'events_announcements.description',
                'events_announcements.posted_on',
                'events_announcements.email_notif',
                'events_announcements.push_notif',
                'events_announcements.show_viewers',
                'events_announcements.is_suspension',
                'users.name as posted_by_name',
                'users.id as posted_by_id',
                'events_announcements_tags.name as tag_name',
                'events_announcements_attachments.filename as attachment_filename',
                'events_announcements_attachments.title as attachment_title'
            )
            ->where('events_announcements.slug', $slug)
            ->get()
            ->groupBy('id')
            ->map(function ($items) {
                $event = $items->first(); 

                return [
                    'id'            => $event->id,
                    'title'         => $event->title,
                    'slug'          => $event->slug,
                    'banner'        => $event->banner,
                    'description'   => $event->description,
                    'posted_on'     => $event->posted_on,
                    'email_notif'   => $event->email_notif,
                    'push_notif'    => $event->push_notif,
                    'show_viewers'  => $event->show_viewers,
                    'is_suspension' => $event->is_suspension,

                    'posted_by' => $items->map(function ($item) {
                            if ($item->posted_by_id && $item->posted_by_name) {
                                return [
                                    'id'   => $item->posted_by_id,
                                    'name' => $item->posted_by_name,
                                ];
                            }
                        })
                        ->filter()
                        ->unique('id')
                        ->values()
                        ->toArray(),

                    'tags' => $items->pluck('tag_name')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray(),

                    'attachments' => $items->map(function ($item) {
                            if ($item->attachment_filename) {
                                return [
                                    'filename' => $item->attachment_filename,   
                                    'title'    => $item->attachment_title,
                                ];
                            }
                        })
                        ->filter()
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->first();

        $others = DB::table('events_announcements')
            ->where('id', '!=', $data['id'])
            ->orderBy('posted_on', 'desc')
            ->limit(10) 
            ->get();

        return view('admin.pages.services.events.show', compact('data', 'others'));
    }



    public function create() {

        $isEdit = false;
        $id = null;

        $users = User::whereDoesntHave('roles', function ($q) {
                $q->where('name', 'employee');
            })->get();

        $posted_by = Auth::user()->id;

        return view('admin.pages.services.events.form', compact('isEdit', 'users', 'posted_by', 'id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'              => 'required|string|max:255',
            'tags'               => 'required|array|max:5|min:1',
            'banner'             => 'required|image|mimes:jpg,jpeg,png',
            'content'            => 'required|string',
            'posted_on'          => 'nullable|date|after_or_equal:today',
            'posted_by'          => 'required',
            'posted_by.*'        => 'exists:users,id',
            'attachment_titles.*'=> 'nullable|string|max:255',
            'attachment_files.*' => 'nullable|file|max:10240',
            'email_notif'        => 'nullable|boolean',
            'push_notification'  => 'nullable|boolean',
            'show_viewers'       => 'nullable|boolean',
            'is_suspension'      => 'nullable|boolean',
            'suspension'         => 'required_if:is_suspension,1',
        ]);

        try {
            DB::beginTransaction();

            // Generate unique slug
            $slug = Str::slug($request->title, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('events_announcements')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $banner = null;

            if ($request->hasFile('banner')) {
                $file = $request->file('banner');
                // Store file in storage but save only filename
                $path = $file->store('events/attachments', 'public');
                $banner = basename($path);
            }

            // Insert main event/announcement
            $eventId = DB::table('events_announcements')->insertGetId([
                'title'         => $request->title,
                'banner'        => $banner,
                'slug'          => $slug,
                'description'   => $request->content,
                'posted_on'     => $request->posted_on ?? now(),
                'email_notif'   => $request->boolean('email_notif'),
                'push_notif'    => $request->boolean('push_notification'),
                'show_viewers'  => $request->boolean('show_viewers'),
                'is_suspension' => $request->boolean('is_suspension'),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Save tags (pivot, multiple)
            foreach ($request->tags as $tags) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $eventId,
                    'name'                  => $tags,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Save posted_by (pivot, multiple)
            foreach ($request->posted_by as $userId) {
                DB::table('events_announcements_posted_by')->insert([
                    'user_id'               => $userId,
                    'event_announcement_id' => $eventId,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Save attachments
            if ($request->hasFile('attachment_files')) {
                foreach ($request->file('attachment_files') as $index => $file) {
                    if ($file) {
                        $path = $file->store('events/attachments', 'public');
                        $filename = basename($path); // only filename
                        $title = $request->attachment_titles[$index] ?? null;

                        DB::table('events_announcements_attachments')->insert([
                            'event_announcement_id' => $eventId,
                            'filename'              => $filename,
                            'title'                 => $title,
                            'created_at'            => now(),
                            'updated_at'            => now(),
                        ]);
                    }
                }
            }

            // Save suspension if marked
            if ($request->boolean('is_suspension')) {
                [$start, $end] = explode(' - ', $request->suspension);

                $start = Carbon::parse(trim($start))->format('Y-m-d');
                $end   = Carbon::parse(trim($end))->format('Y-m-d');

                DB::table('suspensions')->insert([
                    'events_announcements_id' => $eventId,
                    'start_date'              => $start,
                    'end_date'                => $end,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Event/Announcement Added',
                'redirect' => '_self',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ]);
        }
    }



}
