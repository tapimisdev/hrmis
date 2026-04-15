<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Feedback::query()
                ->leftJoin('users', 'feedbacks.user_id', '=', 'users.id')
                ->leftJoin('employee_information as ei', 'users.id', '=', 'ei.user_id')
                ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
                ->select([
                    'feedbacks.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'ei.employee_no',
                    'ep.firstname',
                    'ep.lastname',
                ])
                ->orderByDesc('feedbacks.created_at');

            return DataTables::of($query)
                ->addColumn('name', function ($row) {
                    if ($row->is_anonymous) {
                        return e($row->anonymous_nickname ?: 'Anonymous');
                    }

                    $employeeName = trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? ''));

                    return e($employeeName !== '' ? $employeeName : $row->user_name);
                })
                ->addColumn('date_submitted', function ($row) {
                    return Carbon::parse($row->created_at)->format('M d, Y h:i A');
                })
                ->addColumn('actions', function ($row) {
                    $showRoute = route('feedbacks.show', $row->id);
                    $deleteRoute = route('feedbacks.destroy', $row->id);

                    return '
                        <div class="d-flex flex-wrap gap-1">
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm btn-feedback-view"
                                data-target="' . $showRoute . '"
                                title="View">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button
                                type="button"
                                id="btn-delete"
                                class="btn btn-danger btn-sm"
                                data-target="' . $deleteRoute . '"
                                title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.pages.feedbacks.index');
    }

    public function show(Feedback $feedback)
    {
        $feedback->loadMissing('user');

        $feedback->attachment_url = $feedback->attachment_path
            ? Storage::url($feedback->attachment_path)
            : null;

        return response()->json([
            'id' => $feedback->id,
            'category' => $feedback->category,
            'subject' => $feedback->subject,
            'message' => $feedback->message,
            'is_anonymous' => $feedback->is_anonymous,
            'anonymous_nickname' => $feedback->anonymous_nickname,
            'user_name' => $feedback->user?->name,
            'user_email' => $feedback->user?->email,
            'date_submitted' => Carbon::parse($feedback->created_at)->format('M d, Y h:i A'),
            'attachment_name' => $feedback->attachment_name,
            'attachment_url' => $feedback->attachment_url,
            'attachment_mime' => $feedback->attachment_mime,
            'attachment_size' => $feedback->attachment_size,
        ]);
    }

    public function destroy(Feedback $feedback)
    {
        if ($feedback->attachment_path) {
            Storage::disk('public')->delete($feedback->attachment_path);
        }

        $feedback->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback deleted successfully.',
        ]);
    }
}
