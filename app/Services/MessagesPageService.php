<?php

namespace App\Services;

use App\Models\DirectMessage;
use App\Models\GroupChat;
use App\Models\GroupMessage;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
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
            'selectedConversationToken' => $this->conversationTokenFromKey($selectedConversationKey),
            'pendingGroupChatApprovals' => $this->buildPendingGroupChatApprovals($authUser),
            'groupChatRequestHistory' => $this->buildGroupChatRequestHistory($authUser),
            'authUser' => [
                'id' => $authUser->id,
                'name' => $authUser->name,
                'actual_name' => $this->resolveUserDisplayName($authUser),
                'email' => $authUser->email,
                'role_names' => $authUser->getRoleNames()->values(),
                'is_admin' => $this->isAdmin($authUser),
            ],
        ];
    }

    public function conversationToken(string $conversationType, int $conversationId): string
    {
        return Crypt::encryptString($conversationType . ':' . $conversationId);
    }

    public function conversationTokenFromKey(?string $conversationKey): ?string
    {
        if (!filled($conversationKey)) {
            return null;
        }

        [$conversationType, $conversationId] = array_pad(explode(':', $conversationKey, 2), 2, null);

        if (!in_array($conversationType, ['direct', 'group'], true) || !is_numeric($conversationId)) {
            return null;
        }

        return $this->conversationToken($conversationType, (int) $conversationId);
    }

    public function conversationKeyFromToken(?string $conversationToken): ?string
    {
        if (!filled($conversationToken)) {
            return null;
        }

        try {
            $decryptedValue = Crypt::decryptString($conversationToken);
        } catch (DecryptException) {
            return null;
        }

        [$conversationType, $conversationId] = array_pad(explode(':', $decryptedValue, 2), 2, null);

        if (!in_array($conversationType, ['direct', 'group'], true) || !is_numeric($conversationId)) {
            return null;
        }

        return $conversationType . ':' . (int) $conversationId;
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
            ->whereIn('approval_status', ['pending', 'approved', 'rejected'])
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
                            : ($groupChat->approval_status === 'rejected'
                                ? $groupChat->rejector?->id
                                : null),
                        'name' => $groupChat->approval_status === 'approved'
                            ? ($groupChat->approver?->name ?? 'Admin')
                            : ($groupChat->approval_status === 'rejected'
                                ? ($groupChat->rejector?->name ?? 'Admin')
                                : null),
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
        $directNicknames = Schema::hasTable('direct_conversation_settings')
            ? DB::table('direct_conversation_settings')
                ->where('partner_id', $authUser->id)
                ->pluck('nickname', 'user_id')
            : collect();
        $directSelfNicknames = Schema::hasTable('direct_conversation_settings')
            ? DB::table('direct_conversation_settings')
                ->where('user_id', $authUser->id)
                ->pluck('nickname', 'partner_id')
            : collect();
        $directStats = $this->directConversationStats($authUser);
        $latestDirectMessages = DirectMessage::query()
            ->with('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type')
            ->whereIn('id', $directStats->pluck('latest_message_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        $directConversations = $availableUsers
            ->map(function (array $user) use ($directStats, $directNicknames, $directSelfNicknames, $latestDirectMessages) {
                $stats = $directStats->get($user['id']);
                $nickname = trim((string) ($directNicknames[$user['id']] ?? ''));
                $displayName = $nickname !== '' ? $nickname : $user['name'];
                $selfNickname = trim((string) ($directSelfNicknames[$user['id']] ?? ''));
                $latestMessage = $stats?->latest_message_id
                    ? $latestDirectMessages->get((int) $stats->latest_message_id)
                    : null;
                $latestAt = $latestMessage?->created_at ? Carbon::parse($latestMessage->created_at) : null;
                $unreadCount = (int) ($stats?->unread_count ?? 0);

                return [
                    ...$user,
                    'name' => $displayName,
                    'actual_name' => $user['name'],
                    'nickname' => $nickname !== '' ? $nickname : null,
                    'self_nickname' => $selfNickname !== '' ? $selfNickname : null,
                    'conversation_type' => 'direct',
                    'conversation_key' => 'direct:' . $user['id'],
                    'conversation_token' => $this->conversationToken('direct', (int) $user['id']),
                    'preview' => $this->directMessagePreview($latestMessage),
                    'preview_time' => $latestAt?->diffForHumans(),
                    'latest_at' => $latestAt?->toIso8601String(),
                    'unread_count' => $unreadCount,
                    'is_unread' => $unreadCount > 0,
                    'member_count' => 2,
                ];
            });

        $groupStats = $this->groupConversationStats($authUser);
        $latestGroupMessages = GroupMessage::query()
            ->with('replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at')
            ->whereIn('id', $groupStats->pluck('latest_visible_message_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        $groupConversations = GroupChat::query()
            ->with('members:user_id,group_chat_id,last_read_at,cleared_before_message_id')
            ->withCount('members')
            ->where('approval_status', 'approved')
            ->whereHas('members', function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get()
            ->map(function (GroupChat $groupChat) use ($groupStats, $latestGroupMessages) {
                $displayName = $groupChat->name ?: 'Group Chat';
                $stats = $groupStats->get($groupChat->id);
                $latestVisibleMessage = $stats?->latest_visible_message_id
                    ? $latestGroupMessages->get((int) $stats->latest_visible_message_id)
                    : null;
                $latestAt = $latestVisibleMessage?->created_at;
                $unreadCount = (int) ($stats?->unread_count ?? 0);

                return [
                    'id' => (int) $groupChat->id,
                    'name' => $displayName,
                    'email' => null,
                    'employee_no' => null,
                    'profile' => $this->groupAvatarUrl($displayName, $groupChat->photo_path),
                    'conversation_type' => 'group',
                    'conversation_key' => 'group:' . $groupChat->id,
                    'conversation_token' => $this->conversationToken('group', (int) $groupChat->id),
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

    protected function resolveUserDisplayName(User $user): string
    {
        $profile = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select('ep.firstname', 'ep.lastname')
            ->where('ei.user_id', $user->id)
            ->first();

        return $this->displayName(
            $profile?->firstname,
            $profile?->lastname,
            $user->name,
        );
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

        if (($message->message_type ?: 'user') !== 'user') {
            return trim((string) $message->body) !== '' ? trim((string) $message->body) : 'Conversation activity';
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

    protected function directConversationStats(User $authUser): Collection
    {
        $baseQuery = DirectMessage::query()
            ->leftJoin('direct_conversation_clears as dcc', function ($join) use ($authUser) {
                $join->where('dcc.user_id', '=', $authUser->id)
                    ->whereRaw(
                        'dcc.partner_id = CASE WHEN direct_messages.sender_id = ? THEN direct_messages.recipient_id ELSE direct_messages.sender_id END',
                        [$authUser->id]
                    );
            })
            ->where(function ($query) use ($authUser) {
                $query->where('direct_messages.sender_id', $authUser->id)
                    ->orWhere('direct_messages.recipient_id', $authUser->id);
            })
            ->when(
                Schema::hasColumn('direct_messages', 'visible_to_user_id'),
                fn ($query) => $query->where(function ($visibilityQuery) use ($authUser) {
                    $visibilityQuery->whereNull('direct_messages.visible_to_user_id')
                        ->orWhere('direct_messages.visible_to_user_id', $authUser->id);
                })
            )
            ->whereRaw('direct_messages.id > COALESCE(dcc.cleared_before_message_id, 0)')
            ->selectRaw(
                'CASE WHEN direct_messages.sender_id = ? THEN direct_messages.recipient_id ELSE direct_messages.sender_id END as partner_id',
                [$authUser->id]
            )
            ->addSelect([
                'direct_messages.id',
                'direct_messages.sender_id',
                'direct_messages.recipient_id',
                'direct_messages.read_at',
            ]);

        return DB::query()
            ->fromSub($baseQuery, 'conversation_messages')
            ->select('partner_id')
            ->selectRaw('MAX(id) as latest_message_id')
            ->selectRaw(
                'SUM(CASE WHEN sender_id != ? AND recipient_id = ? AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count',
                [$authUser->id, $authUser->id]
            )
            ->groupBy('partner_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->partner_id);
    }

    protected function groupConversationStats(User $authUser): Collection
    {
        return DB::table('group_chat_members')
            ->leftJoin('group_messages', 'group_messages.group_chat_id', '=', 'group_chat_members.group_chat_id')
            ->where('group_chat_members.user_id', $authUser->id)
            ->select('group_chat_members.group_chat_id')
            ->selectRaw(
                'MAX(CASE WHEN group_messages.id > COALESCE(group_chat_members.cleared_before_message_id, 0) THEN group_messages.id ELSE NULL END) as latest_visible_message_id'
            )
            ->selectRaw(
                'SUM(CASE WHEN group_messages.sender_id != ? AND group_messages.id > COALESCE(group_chat_members.cleared_before_message_id, 0) AND (group_chat_members.last_read_at IS NULL OR group_messages.created_at > group_chat_members.last_read_at) THEN 1 ELSE 0 END) as unread_count',
                [$authUser->id]
            )
            ->groupBy('group_chat_members.group_chat_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->group_chat_id);
    }
}
