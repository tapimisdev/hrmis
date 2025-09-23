<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EventsController extends Controller
{
    public function index() {

        $data = DB::table('events_announcements')
            ->leftJoin('users', 'events_announcements.posted_by', '=', 'users.id')
            ->select(
                'events_announcements.*'
            )
            ->orderBy('events_announcements.posted_on', 'desc')
            ->get();

        return view('admin.pages.services.events.index', compact('data'));

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
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'posted_on' => 'nullable|date|after_or_equal:today',
            'posted_by' => 'nullable|exists:users,id',
            'attachment_titles.*' => 'nullable|string|max:255',
            'attachment_files.*' => 'nullable|file|max:10240', 
            'email_notif' => 'nullable|boolean',
            'push_notif' => 'nullable|boolean',
            'show_viewers' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Generate unique slug
            $slug = Str::slug($request->title, '-');
            $originalSlug = $slug;
            $counter = 1;
            while (DB::table('events_announcements')->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Insert event/announcement
            $eventId = DB::table('events_announcements')->insertGetId([
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->content,
                'posted_on' => $request->posted_on,
                'email_notif' => $request->email_notif ?? false,
                'push_notif' => $request->push_notif ?? true,
                'show_viewers' => $request->show_viewers ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save attachments
            if ($request->hasFile('attachment_files')) {
                foreach ($request->file('attachment_files') as $index => $file) {
                    $filename = $file->store('events/attachments', 'public');
                    $title = $request->attachment_titles[$index] ?? null;

                    DB::table('events_announcements_attachments')->insert([
                        'event_announcement_id' => $eventId,
                        'filename' => $filename,
                        'title' => $title,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Save posted_by (pivot)
            if ($request->posted_by) {
                DB::table('events_announcements_posted_by')->insert([
                    'user_id' => $request->posted_by,
                    'event_announcement_id' => $eventId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Event/Announcement Added',
                'redirect'=> '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
    }



}
