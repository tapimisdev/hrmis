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
use Illuminate\Support\Facades\Storage;

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
        $event = DB::table('events_announcements')
            ->where('slug', $slug)
            ->select(
                'id',
                'title',
                'slug',
                'banner',
                'description',
                'posted_on',
                'email_notif',
                'push_notif',
                'show_viewers',
                'is_suspension'
            )
            ->first();

        if (!$event) {
            return redirect()->route('services.events.index');
        }

        // posted_by → plain array
        $postedBy = DB::table('events_announcements_posted_by')
            ->join('users', 'events_announcements_posted_by.user_id', '=', 'users.id')
            ->where('event_announcement_id', $event->id)
            ->select('users.id', 'users.name')
            ->get()
            ->map(function ($row) {
                return [
                    'id'   => $row->id,
                    'name' => $row->name,
                ];
            })
            ->toArray();

        // tags → array of strings
        $tags = DB::table('events_announcements_tags')
            ->where('event_announcement_id', $event->id)
            ->pluck('name')
            ->toArray();

        // attachments → plain array
        $attachments = DB::table('events_announcements_attachments')
            ->where('event_announcement_id', $event->id)
            ->select('id', 'filename', 'title')
            ->get()
            ->map(function ($row) {
                return [
                    'id'       => $row->id,
                    'filename' => $row->filename,
                    'title'    => $row->title,
                ];
            })
            ->toArray();

        // Build final array
        $data = [
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
            'posted_by'     => $postedBy,
            'tags'          => $tags,
            'attachments'   => $attachments,
        ];

        // Fetch 10 other events
        $others = DB::table('events_announcements')
            ->where('id', '!=', $event->id)
            ->orderBy('posted_on', 'desc')
            ->limit(10) 
            ->get();

        // dd($data);

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
            'title'               => 'required|string|max:255',
            'tags'                => 'required|array|max:5|min:1',
            'banner'              => 'required|image|mimes:jpg,jpeg,png',
            'content'             => 'required|string',
            'posted_on'           => 'nullable|date|after_or_equal:today',
            'posted_by'           => 'required',
            'posted_by.*'         => 'exists:users,id',
            'attachment_titles.*' => 'nullable|string|max:255',
            'attachment_files.*'  => 'nullable|file|max:10240',
            'email_notif'         => 'nullable|boolean',
            'push_notification'   => 'nullable|boolean',
            'show_viewers'        => 'nullable|boolean',
            'is_suspension'       => 'nullable|boolean',

            // Multiple suspension validations
            'suspensions'                      => 'nullable|array',
            'suspensions.*.date'              => 'required_if:is_suspension,1|date|after_or_equal:today',
            'suspensions.*.type'              => 'required_if:is_suspension,1|in:whole_day,half_day',
            'suspensions.*.from_time'         => 'nullable|date_format:H:i',
            'suspensions.*.to_time'           => 'nullable|date_format:H:i',
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

            // Save tags
            foreach ($request->tags as $tags) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $eventId,
                    'name'                  => $tags,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Save posted_by
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
                        $filename = basename($path);
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

            // Save suspensions (if any)
            if ($request->boolean('is_suspension') && is_array($request->suspensions)) {
                foreach ($request->suspensions as $suspension) {
                    DB::table('suspensions')->insert([
                        'events_announcements_id' => $eventId,
                        'date'                    => Carbon::parse($suspension['date'])->format('Y-m-d'),
                        'type'                    => $suspension['type'],
                        'from_time'               => $suspension['from_time'] ?? null,
                        'to_time'                 => $suspension['to_time'] ?? null,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                }
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


    public function edit(string $slug)
    {
        $isEdit = true;
        $users = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'employee');
        })->get();

        $event = DB::table('events_announcements')
            ->where('slug', $slug)
            ->select(
                'id',
                'title',
                'slug',
                'banner',
                'description',
                'posted_on',
                'email_notif',
                'push_notif',
                'show_viewers',
                'is_suspension'
            )
            ->first();

        if (!$event) {
            return redirect()->route('services.events.index');
        }

        // posted_by → plain array
        $postedBy = DB::table('events_announcements_posted_by')
            ->join('users', 'events_announcements_posted_by.user_id', '=', 'users.id')
            ->where('event_announcement_id', $event->id)
            ->select('users.id', 'users.name')
            ->get()
            ->map(fn ($row) => [
                'id'   => $row->id,
                'name' => $row->name,
            ])
            ->toArray();

        // tags → array of strings
        $tags = DB::table('events_announcements_tags')
            ->where('event_announcement_id', $event->id)
            ->pluck('name')
            ->toArray();

        // attachments → plain array
        $attachments = DB::table('events_announcements_attachments')
            ->where('event_announcement_id', $event->id)
            ->select('id', 'filename', 'title')
            ->get()
            ->map(fn ($row) => [
                'id'       => $row->id,
                'filename' => $row->filename,
                'title'    => $row->title,
            ])
            ->toArray();

        // suspension → single record (if exists)
        $suspension = json_decode(json_encode(
            DB::table('suspensions')
                ->where('events_announcements_id', $event->id)
                ->get()
        ), true);

        // Build final array
        $data = [
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
            'posted_by'     => $postedBy,
            'tags'          => $tags,
            'attachments'   => $attachments,
            'suspensions'   => $suspension
        ];

        $id = $data['id'];

        return view('admin.pages.services.events.form', compact('isEdit', 'users', 'id', 'data'));
    }


    public function update(Request $request, $id)
    {

        $request->validate([
            'title'              => 'required|string|max:255',
            'tags'               => 'required|array|max:5|min:1',
            'banner'             => 'nullable|image|mimes:jpg,jpeg,png',
            'content'            => 'required|string',
            'posted_on'          => 'nullable|after_or_equal:today',
            'posted_by'          => 'required',
            'posted_by.*'        => 'exists:users,id',
            'attachment_files.*'  => 'nullable',
            'attachment_titles.*' => 'required_with:attachment_files.*|string|max:255',
            'email_notif'        => 'nullable|boolean',
            'push_notification'  => 'nullable|boolean',
            'show_viewers'       => 'nullable|boolean',
            'is_suspension'      => 'nullable|boolean',

            'suspensions' => 'nullable|array|required_if:is_suspension,1',
            'suspensions.*.date'      => 'required_with:suspensions.*.type|date|after_or_equal:today',
            'suspensions.*.type'      => 'required_with:suspensions.*.date|in:whole_day,half_day',
            'suspensions.*.from_time' => 'nullable|date_format:H:i|required_if:suspensions.*.type,half_day',
            'suspensions.*.to_time'   => 'nullable|date_format:H:i|required_if:suspensions.*.type,half_day|after:suspensions.*.from_time',
        ]);

        try {

            DB::beginTransaction();

            // Check if record exists
            $event = DB::table('events_announcements')->where('id', $id)->first();

            if (!$event) {
                return redirect()->route('services.events.index');
            }

            $eventId = $event->id;

            // Generate unique slug if title changed
            $slug = Str::slug($request->title, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('events_announcements')->where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $banner = $event->banner;

            // Handle banner update
            if ($request->hasFile('banner')) {
                $file = $request->file('banner');
                $path = $file->store('events/attachments', 'public');
                $banner = basename($path);
            }

            // Update main event/announcement
            DB::table('events_announcements')->where('id', $id)->update([
                'title'         => $request->title,
                'banner'        => $banner,
                'slug'          => $slug,
                'description'   => $request->content,
                'posted_on'     => $request->posted_on,
                'email_notif'   => $request->email_notif ? true: false,
                'push_notif'    => $request->push_notif ? true : false,
                'show_viewers'  => $request->show_viewers ? true : false,
                'is_suspension' => $request->is_suspension ? true : false,
                'updated_at'    => now(),
            ]);

            DB::table('events_announcements_tags')->where('event_announcement_id', $id)->delete();
            DB::table('events_announcements_posted_by')->where('event_announcement_id', $id)->delete();
            DB::table('suspensions')->where('events_announcements_id', $id)->delete();

            // Re-insert tags
            foreach ($request->tags as $tags) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $id,
                    'name'                  => $tags,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Re-insert posted_by
            foreach ($request->posted_by as $userId) {
                DB::table('events_announcements_posted_by')->insert([
                    'user_id'               => $userId,
                    'event_announcement_id' => $id,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Handle removing attachments (if user clicked delete)
            if ($request->filled('remove_attachments')) {
                foreach ($request->remove_attachments as $attachmentId) {
                    $attachment = DB::table('events_announcements_attachments')->where('id', $attachmentId)->first();

                    if ($attachment) {
                        // Delete file from storage
                        Storage::disk('public')->delete('events/attachments/' . $attachment->filename);

                        // Delete DB record
                        DB::table('events_announcements_attachments')->where('id', $attachmentId)->delete();
                    }
                }
            }
  
            // Handle new uploads (only add, don't delete existing)
            if ($request->hasFile('attachment_files')) {
                foreach ($request->file('attachment_files') as $index => $file) {
                    if ($file) {
                        $path = $file->store('events/attachments', 'public');
                        $filename = basename($path);
                        $title = $request->attachment_titles[$index] ?? null;

                        DB::table('events_announcements_attachments')->insert([
                            'event_announcement_id' => $id,
                            'filename'              => $filename,
                            'title'                 => $title,
                            'created_at'            => now(),
                            'updated_at'            => now(),
                        ]);
                    }
                }
            }


            // Re-insert suspension if applicable
            if ($request->boolean('is_suspension')) {
                DB::table('suspensions')->insert([
                    'events_announcements_id' => $eventId,
                    'from_date'               => Carbon::parse($request->suspension_from_date)->format('Y-m-d'),
                    'from_time'               => Carbon::parse($request->suspension_from_time)->format('H:i:s'),
                    'to_date'                 => Carbon::parse($request->suspension_to_date)->format('Y-m-d'),
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }


            DB::commit();
            
            return response()->json([
                'status'   => 'success',
                'message'  => 'Event/Announcement Updated',
                'redirect' => route('services.events.edit', ['event' => $slug]),
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
