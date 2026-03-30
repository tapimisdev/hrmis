<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        $users = DB::table('users')
            ->leftJoin('employee_information as ei', 'users.id', '=', 'ei.user_id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'ei.employee_no',
                'ep.profile',
                'ep.firstname',
                'ep.lastname'
            )
            ->where('users.id', '!=', $authUser->id)
            ->orderBy('users.name')
            ->get()
            ->map(function ($user) use ($authUser) {
                $fullName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
                $displayName = $fullName !== '' ? $fullName : ($user->name ?? 'User');

                if ($user->profile) {
                    $profile = Storage::url(
                        'public/users/' . $user->employee_no . '/profile-image/' . $user->profile
                    );
                } else {
                    $profile = 'https://ui-avatars.com/api/?name='
                        . urlencode($displayName)
                        . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                }

                $latestMessage = DirectMessage::query()
                    ->where(function ($query) use ($authUser, $user) {
                        $query->where('sender_id', $authUser->id)
                            ->where('recipient_id', $user->id);
                    })
                    ->orWhere(function ($query) use ($authUser, $user) {
                        $query->where('sender_id', $user->id)
                            ->where('recipient_id', $authUser->id);
                    })
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

                $unreadCount = DirectMessage::query()
                    ->where('sender_id', $user->id)
                    ->where('recipient_id', $authUser->id)
                    ->whereNull('read_at')
                    ->count();

                $latestAt = $latestMessage?->created_at
                    ? Carbon::parse($latestMessage->created_at)
                    : null;

                $preview = 'Start a conversation';
                if ($latestMessage) {
                    $body = trim((string) $latestMessage->body);
                    $preview = $body !== ''
                        ? Str::limit($body, 68)
                        : ($latestMessage->attachment_name ? 'Sent an attachment' : 'Message thread ready');
                }

                return [
                    'id' => (int) $user->id,
                    'name' => $displayName,
                    'email' => $user->email,
                    'employee_no' => $user->employee_no,
                    'profile' => $profile,
                    'preview' => $preview,
                    'preview_time' => $latestAt?->diffForHumans(),
                    'latest_at' => $latestAt?->toIso8601String(),
                    'unread_count' => $unreadCount,
                    'is_unread' => $unreadCount > 0,
                    'is_selected' => false,
                ];
            })
            ->sortByDesc('latest_at')
            ->values();

        $selectedUserId = (int) $request->query('user');

        if ($selectedUserId > 0 && !$users->firstWhere('id', $selectedUserId)) {
            $selectedUserId = 0;
        }

        if ($selectedUserId <= 0) {
            $selectedUserId = (int) ($users->first()['id'] ?? 0);
        }

        $users = $users->map(function (array $user) use ($selectedUserId) {
            $user['is_selected'] = (int) $user['id'] === (int) $selectedUserId;

            return $user;
        });

        $selectedUser = $users->firstWhere('id', $selectedUserId);

        return view('employee.pages.messages.index', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'selectedUserId' => $selectedUserId,
            'authUser' => [
                'id' => $authUser->id,
                'name' => $authUser->name,
                'email' => $authUser->email,
            ],
        ]);
    }
}
