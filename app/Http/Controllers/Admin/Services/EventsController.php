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
use App\Events\NotificationEvents;

class EventsController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('permission:hr.events_and_announcements.view')->only(['index', 'show']);
        $this->middleware('permission:hr.events_and_announcements.create')->only(['create', 'store']);
        $this->middleware('permission:hr.events_and_announcements.edit')->only(['edit', 'update']);
        $this->middleware('permission:hr.events_and_announcements.delete')->only('destroy');
    }

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

        $request->merge([
            'is_suspension' => $request->has('is_suspension') && $request->boolean('is_suspension') ? true : false,
        ]);

        $payload = $request->all();

        $rules = [
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
            'is_suspension'       => 'required|boolean',
        ];

        if ($payload['is_suspension']) {
            $rules = array_merge($rules, [
                'suspensions'                 => 'required|array|min:1',
                'suspensions.*.date'          => 'required|date|after_or_equal:today',
                'suspensions.*.type'          => 'required|in:whole_day,half_day',
                'suspensions.*.from_time'     => 'nullable|required_if:suspensions.*.type,half_day|date_format:H:i',
                'suspensions.*.to_time'       => 'nullable|required_if:suspensions.*.type,half_day|date_format:H:i',
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

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

            foreach ($request->tags as $tags) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $eventId,
                    'name'                  => $tags,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            foreach ($request->posted_by as $userId) {
                DB::table('events_announcements_posted_by')->insert([
                    'user_id'               => $userId,
                    'event_announcement_id' => $eventId,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

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

            if ($request->boolean('is_suspension')) {
                $suspensionId = DB::table('suspension')->insertGetId([
                    'events_announcements_id' => $eventId,
                    'name'                    => $request->input('title'),
                    'description'             => null,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);

                foreach ($request->suspensions as $suspensionDate) {
                    DB::table('suspension_dates')->insert([
                        'suspension_id' => $suspensionId,
                        'date'          => Carbon::parse($suspensionDate['date'])->format('Y-m-d'),
                        'type'          => $suspensionDate['type'],
                        'from_time'     => $suspensionDate['from_time'] ?? null,
                        'to_time'       => $suspensionDate['to_time'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $author = ucwords(Auth::user()->name);
            $message = '%b' . $author . '%b posted %bi' . ucwords($request->title) . '%bi';
            
            event(new NotificationEvents('event', $author, '*', [
                'message' => $message,
                'link'    => route('announcement.show', ['slug' => $slug])
            ]));

            return response()->json([
                'status'   => 'success',
                'message'  => 'Event/Announcement Added',
                'redirect' => route('services.events.create'),
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

        $tags = DB::table('events_announcements_tags')
            ->where('event_announcement_id', $event->id)
            ->pluck('name')
            ->toArray();

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

        $suspension = DB::table('suspension')
            ->where('events_announcements_id', $event->id)
            ->first();

        if ($suspension) {
            $suspensionDates = json_decode(json_encode(
                DB::table('suspension_dates')
                    ->where('suspension_id', $suspension->id)
                    ->select('id', 'date', 'type', 'from_time', 'to_time')
                    ->get()
            ), true);
        } else {
            $suspensionDates = [];
        }

        $data = [
            'id'                  => $event->id,
            'title'               => $event->title,
            'slug'                => $event->slug,
            'banner'              => $event->banner,
            'description'         => $event->description,
            'posted_on'           => $event->posted_on,
            'email_notif'         => $event->email_notif,
            'push_notif'          => $event->push_notif,
            'show_viewers'        => $event->show_viewers,
            'is_suspension'       => $event->is_suspension,
            'posted_by'           => $postedBy,
            'tags'                => $tags,
            'attachments'         => $attachments,
            'suspension_id'       => $suspension->id ?? null,
            'suspension_name'     => $suspension->name ?? null,
            'suspension_description' => $suspension->description ?? null,
            'suspensions'         => $suspensionDates,
        ];

        $id = $data['id'];

        return view('admin.pages.services.events.form', compact('isEdit', 'users', 'id', 'data'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'is_suspension' => $request->has('is_suspension') && $request->boolean('is_suspension') ? true : false,
        ]);

        $payload = $request->all();

        $rules = [
            'title'               => 'required|string|max:255',
            'tags'                => 'required|array|max:5|min:1',
            'banner'              => 'nullable|image|mimes:jpg,jpeg,png',
            'content'             => 'required|string',
            'posted_on'           => 'nullable|date',
            'posted_by'           => 'required',
            'posted_by.*'         => 'exists:users,id',
            'attachment_titles.*' => 'nullable|string|max:255',
            'attachment_files.*'  => 'nullable|file|max:10240',
            'email_notif'         => 'nullable|boolean',
            'push_notification'   => 'nullable|boolean',
            'show_viewers'        => 'nullable|boolean',
            'is_suspension'       => 'required|boolean',
        ];

        if ($payload['is_suspension']) {
            $rules = array_merge($rules, [
                'suspensions'                 => 'required|array|min:1',
                'suspensions.*.date'          => 'required|date|after_or_equal:today',
                'suspensions.*.type'          => 'required|in:whole_day,half_day',
                'suspensions.*.from_time'     => 'nullable|required_if:suspensions.*.type,half_day|date_format:H:i',
                'suspensions.*.to_time'       => 'nullable|required_if:suspensions.*.type,half_day|date_format:H:i',
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $event = DB::table('events_announcements')->where('id', $id)->first();

            if (!$event) {
                return redirect()->route('services.events.index');
            }

            $slug = Str::slug($request->title, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('events_announcements')->where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $banner = $event->banner;

            if ($request->hasFile('banner')) {
                $file = $request->file('banner');
                $path = $file->store('events/attachments', 'public');
                $banner = basename($path);
            }

            DB::table('events_announcements')->where('id', $id)->update([
                'title'         => $request->title,
                'banner'        => $banner,
                'slug'          => $slug,
                'description'   => $request->content,
                'posted_on'     => $request->posted_on,
                'email_notif'   => $request->email_notif ? true : false,
                'push_notif'    => $request->push_notification ? true : false,
                'show_viewers'  => $request->show_viewers ? true : false,
                'is_suspension' => $request->is_suspension ? true : false,
                'updated_at'    => now(),
            ]);

            DB::table('events_announcements_tags')->where('event_announcement_id', $id)->delete();
            DB::table('events_announcements_posted_by')->where('event_announcement_id', $id)->delete();

            DB::table('suspension_dates')->whereIn('suspension_id', function ($query) use ($id) {
                $query->select('id')->from('suspension')->where('events_announcements_id', $id);
            })->delete();

            DB::table('suspension')->where('events_announcements_id', $id)->delete();

            foreach ($request->tags as $tags) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $id,
                    'name'                  => $tags,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            foreach ($request->posted_by as $userId) {
                DB::table('events_announcements_posted_by')->insert([
                    'user_id'               => $userId,
                    'event_announcement_id' => $id,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            if ($request->filled('remove_attachments')) {
                foreach ($request->remove_attachments as $attachmentId) {
                    $attachment = DB::table('events_announcements_attachments')->where('id', $attachmentId)->first();

                    if ($attachment) {
                        Storage::disk('public')->delete('events/attachments/' . $attachment->filename);
                        DB::table('events_announcements_attachments')->where('id', $attachmentId)->delete();
                    }
                }
            }

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

            if ($request->boolean('is_suspension')) {
                $suspensionId = DB::table('suspension')->insertGetId([
                    'events_announcements_id' => $id,
                    'name'                    => $request->input('title'),
                    'description'             => null,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);

                foreach ($request->suspensions as $suspensionDate) {
                    DB::table('suspension_dates')->insert([
                        'suspension_id' => $suspensionId,
                        'date'          => Carbon::parse($suspensionDate['date'])->format('Y-m-d'),
                        'type'          => $suspensionDate['type'],
                        'from_time'     => $suspensionDate['from_time'] ?? null,
                        'to_time'       => $suspensionDate['to_time'] ?? null,
                    ]);
                }
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

    public function destroy(string $id, Request $request)
    {

        DB::beginTransaction();

        try {

            DB::table('events_announcements')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Event/Announcement record has been deleted.',
                'redirect' => route('services.events.index')
            ]);

        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }


}
