<?php

namespace App\Services;

use App\Models\DirectMessage;
use App\Models\GroupChat;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessagesPageService
{
    public function build(User $authUser, ?string $selectedConversationKey = null): array
    {
        $availableUsers = $this->buildAvailableUsers($authUser);
        $conversations = $this->buildConversations($authUser, $availableUsers);
        $selectedConversationKey = $this->resolveSelectedConversationKey($conversations, $selectedConversationKey);

        return [
            'availableUsers' => $availableUsers->values(),
            'conversations' => $conversations
                ->map(function (array $conversation) use ($selectedConversationKey) {
                    $conversation['is_selected'] = $conversation['conversation_key'] === $selectedConversationKey;

                    return $conversation;
                })
                ->values(),
            'selectedConversationKey' => $selectedConversationKey,
            'pendingGroupChatApprovals' => $this->buildPendingGroupChatApprovals($authUser),
            'groupChatRequestHistory' => $this->buildGroupChatRequestHistory($authUser),
            'authUser' => [
                'id' => $authUser->id,
                'name' => $authUser->name,
                'email' => $authUser->email,
                'role_names' => $authUser->getRoleNames()->values(),
                'is_admin' => $this->isAdmin($authUser),
            ],
        ];
    }

    public function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function buildAvailableUsers(User $authUser): Collection
    {
        return DB::table('users')
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
            ->map(function ($user) {
                $displayName = $this->displayName($user->firstname, $user->lastname, $user->name);

                return [
                    'id' => (int) $user->id,
                    'name' => $displayName,
                    'email' => $user->email,
                    'employee_no' => $user->employee_no,
                    'profile' => $this->profileUrl($user->employee_no, $user->profile, $displayName),
                ];
            });
    }

    public function buildPendingGroupChatApprovals(User $authUser): Collection
    {
        if (!$this->isAdmin($authUser)) {
            return collect();
        }

        return GroupChat::query()
            ->with(['creator:id,name', 'members.user:id,name'])
            ->where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (GroupChat $groupChat) {
                return [
                    'id' => $groupChat->id,
                    'name' => $groupChat->name,
                    'approval_status' => $groupChat->approval_status,
                    'approval_level' => $groupChat->approval_level,
                    'created_at' => $groupChat->created_at?->toIso8601String(),
                    'creator' => [
                        'id' => $groupChat->creator?->id,
                        'name' => $groupChat->creator?->name ?? 'User',
                    ],
                    'members' => $groupChat->members
                        ->map(function ($member) {
                            return [
                                'id' => $member->user?->id,
                                'name' => $member->user?->name ?? 'User',
                            ];
                        })
                        ->filter(fn ($member) => !empty($member['id']))
                        ->values(),
                ];
            });
    }

    public function buildGroupChatRequestHistory(User $authUser): Collection
    {
        return GroupChat::query()
            ->with([
                'creator:id,name',
                'approver:id,name',
                'rejector:id,name',
                'members.user:id,name',
            ])
            ->where('created_by_id', $authUser->id)
            ->whereIn('approval_status', ['approved', 'rejected'])
            ->orderByRaw('COALESCE(approved_at, rejected_at, created_at) DESC')
            ->orderByDesc('id')
            ->get()
            ->map(function (GroupChat $groupChat) {
                $processedAt = $groupChat->approved_at ?? $groupChat->rejected_at ?? $groupChat->created_at;

                return [
                    'id' => (int) $groupChat->id,
                    'name' => $groupChat->name,
                    'approval_status' => $groupChat->approval_status,
                    'approval_level' => $groupChat->approval_level,
                    'created_at' => $groupChat->created_at?->toIso8601String(),
                    'processed_at' => $processedAt?->toIso8601String(),
                    'rejection_reason' => $groupChat->rejection_reason,
                    'creator' => [
                        'id' => $groupChat->creator?->id,
                        'name' => $groupChat->creator?->name ?? 'User',
                    ],
                    'processed_by' => [
                        'id' => $groupChat->approval_status === 'approved'
                            ? $groupChat->approver?->id
                            : $groupChat->rejector?->id,
                        'name' => $groupChat->approval_status === 'approved'
                            ? ($groupChat->approver?->name ?? 'Admin')
                            : ($groupChat->rejector?->name ?? 'Admin'),
                    ],
                    'members' => $groupChat->members
                        ->map(function ($member) {
                            return [
                                'id' => $member->user?->id,
                                'name' => $member->user?->name ?? 'User',
                            ];
                        })
                        ->filter(fn ($member) => !empty($member['id']))
                        ->values()
                        ->all(),
                ];
            });
    }

    protected function buildConversations(User $authUser, Collection $availableUsers): Collection
    {
        $directClearMarkers = DB::table('direct_conversation_clears')
            ->where('user_id', $authUser->id)
            ->pluck('cleared_before_message_id', 'partner_id');
        $directNicknames = Schema::hasTable('direct_conversation_settings')
            ? DB::table('direct_conversation_settings')
                ->where('user_id', $authUser->id)
                ->pluck('nickname', 'partner_id')
            : collect();

        $directConversations = $availableUsers
            ->map(function (array $user) use ($authUser, $directClearMarkers, $directNicknames) {
                $clearedBeforeMessageId = (int) ($directClearMarkers[$user['id']] ?? 0);
                $nickname = trim((string) ($directNicknames[$user['id']] ?? ''));
                $displayName = $nickname !== '' ? $nickname : $user['name'];

                $baseQuery = DirectMessage::query()
                    ->where(function ($query) use ($authUser, $user) {
                        $query->where('sender_id', $authUser->id)
                            ->where('recipient_id', $user['id']);
                    })
                    ->orWhere(function ($query) use ($authUser, $user) {
                        $query->where('sender_id', $user['id'])
                            ->where('recipient_id', $authUser->id);
                    });

                $latestMessage = (clone $baseQuery)
                    ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

                $unreadCount = DirectMessage::query()
                    ->where('sender_id', $user['id'])
                    ->where('recipient_id', $authUser->id)
                    ->whereNull('read_at')
                    ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
                    ->count();

                $latestAt = $latestMessage?->created_at
                    ? Carbon::parse($latestMessage->created_at)
                    : null;

                return [
                    ...$user,
                    'name' => $displayName,
                    'actual_name' => $user['name'],
                    'nickname' => $nickname !== '' ? $nickname : null,
                    'conversation_type' => 'direct',
                    'conversation_key' => 'direct:' . $user['id'],
                    'preview' => $this->directMessagePreview($latestMessage),
                    'preview_time' => $latestAt?->diffForHumans(),
                    'latest_at' => $latestAt?->toIso8601String(),
                    'unread_count' => $unreadCount,
                    'is_unread' => $unreadCount > 0,
                    'member_count' => 2,
                ];
            });

        $groupConversations = GroupChat::query()
            ->with('members:user_id,group_chat_id,last_read_at,cleared_before_message_id')
            ->withCount('members')
            ->where('approval_status', 'approved')
            ->whereHas('members', function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get()
            ->map(function (GroupChat $groupChat) use ($authUser) {
                $displayName = $groupChat->name ?: 'Group Chat';
                $member = $groupChat->members->firstWhere('user_id', $authUser->id);
                $lastReadAt = $member?->last_read_at;
                $clearedBeforeMessageId = (int) ($member?->cleared_before_message_id ?? 0);
                $latestVisibleMessage = \App\Models\GroupMessage::query()
                    ->where('group_chat_id', $groupChat->id)
                    ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();
                $latestAt = $latestVisibleMessage?->created_at;
                $unreadCount = \App\Models\GroupMessage::query()
                    ->where('group_chat_id', $groupChat->id)
                    ->where('sender_id', '!=', $authUser->id)
                    ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
                    ->when($lastReadAt, fn ($query) => $query->where('created_at', '>', $lastReadAt))
                    ->count();

                return [
                    'id' => (int) $groupChat->id,
                    'name' => $displayName,
                    'email' => null,
                    'employee_no' => null,
                    'profile' => $this->groupAvatarUrl($displayName, $groupChat->photo_path),
                    'conversation_type' => 'group',
                    'conversation_key' => 'group:' . $groupChat->id,
                    'preview' => $latestVisibleMessage
                        ? $this->groupMessagePreview($latestVisibleMessage)
                        : 'Group chat is ready',
                    'preview_time' => $latestAt?->diffForHumans(),
                    'latest_at' => $latestAt?->toIso8601String(),
                    'unread_count' => $unreadCount,
                    'is_unread' => $unreadCount > 0,
                    'member_count' => (int) $groupChat->members_count,
                    'member_ids' => $groupChat->members
                        ->pluck('user_id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all(),
                    'is_active' => false,
                    'active_label' => ((int) $groupChat->members_count) . ' members',
                ];
            });

        return $directConversations
            ->concat($groupConversations)
            ->sortByDesc(fn (array $conversation) => $conversation['latest_at'] ?? '')
            ->values();
    }

    protected function resolveSelectedConversationKey(Collection $conversations, ?string $selectedConversationKey): ?string
    {
        if ($selectedConversationKey && $conversations->firstWhere('conversation_key', $selectedConversationKey)) {
            return $selectedConversationKey;
        }

        return $conversations->first()['conversation_key'] ?? null;
    }

    protected function displayName(?string $firstname, ?string $lastname, ?string $fallback): string
    {
        $fullName = trim(($firstname ?? '') . ' ' . ($lastname ?? ''));

        return $fullName !== '' ? $fullName : ($fallback ?? 'User');
    }

    protected function profileUrl(?string $employeeNo, ?string $profile, string $displayName): string
    {
        if ($employeeNo && $profile) {
            return Storage::url('public/users/' . $employeeNo . '/profile-image/' . $profile);
        }

        return 'https://ui-avatars.com/api/?name='
            . urlencode($displayName)
            . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
    }

    protected function groupAvatarUrl(string $groupName, ?string $photoPath = null): string
    {
        if ($photoPath) {
            return Storage::url($photoPath);
        }

        return 'https://ui-avatars.com/api/?name='
            . urlencode($groupName)
            . '&background=1f6feb&color=fff&font-size=0.36&bold=true';
    }

    protected function directMessagePreview(?DirectMessage $message): string
    {
        if (!$message) {
            return 'Start a conversation';
        }

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return 'Unsent Message';
        }

        $body = trim((string) $message->body);

        if ($body !== '') {
            return Str::limit($body, 68);
        }

        return $message->attachment_name ? 'Sent an attachment' : 'Message thread ready';
    }

    protected function groupMessagePreview(\App\Models\GroupMessage $message): string
    {
        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return 'Unsent Message';
        }

        if (($message->message_type ?: 'user') !== 'user') {
            return trim((string) $message->body) !== '' ? trim((string) $message->body) : 'Group activity';
        }

        $body = trim((string) $message->body);

        return $body !== '' ? Str::limit($body, 68) : 'Message';
    }
}
