<?php

namespace App\Http\Controllers\Api;

use App\Events\GroupChatCreated;
use App\Events\GroupChatMessageSent;
use App\Events\GroupChatMessageUpdated;
use App\Events\GroupChatUpdated;
use App\Events\GroupChatRequestUpdated;
use App\Events\GroupChatTyping;
use App\Events\GroupChatSeen;
use App\Http\Controllers\Controller;
use App\Models\GroupChat;
use App\Models\GroupChatMember;
use App\Models\GroupMessage;
use App\Models\User;
use App\Services\EventService;
use App\Services\MessagesPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class GroupChatController extends Controller
{
    protected const REACTION_KEYS = [
        'like',
        'number-one',
        'love',
        'haha',
        'sad',
        'angry',
    ];

    public function __construct(
        protected MessagesPageService $messagesPageService,
        protected EventService $eventService
    ) {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'member_ids' => ['required', 'array', 'min:2'],
            'member_ids.*' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $authUser = $request->user();
        $memberIds = collect($validated['member_ids'])
            ->map(fn ($id) => (int) $id)
            ->push((int) $authUser->id)
            ->unique()
            ->values();
        $isAdmin = $this->messagesPageService->isAdmin($authUser);

        $groupChat = DB::transaction(function () use ($authUser, $validated, $memberIds, $isAdmin) {
            $groupChat = GroupChat::create([
                'name' => trim($validated['name']),
                'created_by_id' => $authUser->id,
                'approval_status' => $isAdmin ? 'approved' : 'pending',
                'approval_level' => 1,
                'approved_by_id' => $isAdmin ? $authUser->id : null,
                'approved_at' => $isAdmin ? now() : null,
            ]);

            $records = $memberIds->map(fn ($userId) => [
                'group_chat_id' => $groupChat->id,
                'user_id' => $userId,
                'added_by_id' => $authUser->id,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            GroupChatMember::insert($records);

            User::query()
                ->whereIn('id', $memberIds->all())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->each(function (User $user) use ($groupChat) {
                    $memberName = $this->groupMemberDisplayName((int) $groupChat->id, (int) $user->id, $user->name);
                    $this->createSystemMessage(
                        $groupChat,
                        (int) $user->id,
                        $memberName . ' joined the chat',
                        false,
                    );
                });

            return $groupChat->fresh(['members.user:id,name', 'creator:id,name']);
        });

        if (!$isAdmin) {
            $this->eventService->pushNotification([
                'type' => 'application',
                'sender' => $authUser->name,
                'receiver' => 'admins',
                'message' => $authUser->name . ' requested approval for group chat "' . $groupChat->name . '".',
                'link' => '/admin/messages',
            ]);

            event(new GroupChatRequestUpdated(
                'created',
                $this->formatPendingRequest($groupChat),
                $this->adminRecipientIds()
            ));
        }

        if ($groupChat->approval_status === 'approved') {
            event(new GroupChatCreated(
                $this->formatConversation($groupChat->fresh()->loadCount('members')),
                $memberIds->all()
            ));
        }

        return response()->json([
            'message' => $groupChat->approval_status === 'approved'
                ? 'Group chat created successfully.'
                : 'Group chat request submitted for admin approval.',
            'group_chat' => [
                'id' => $groupChat->id,
                'name' => $groupChat->name,
                'approval_status' => $groupChat->approval_status,
                'approval_level' => $groupChat->approval_level,
            ],
            'conversation' => $groupChat->approval_status === 'approved'
                ? $this->formatConversation($groupChat->loadCount('members'))
                : null,
        ], 201);
    }

    public function show(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);
        $member = $this->groupMemberRecord($groupChat, (int) $authUser->id);
        $clearedBeforeMessageId = (int) ($member?->cleared_before_message_id ?? 0);

        $messages = GroupMessage::query()
            ->with([
                'sender:id,name',
                'replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at',
            ])
            ->where('group_chat_id', $groupChat->id)
            ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (GroupMessage $message) => $this->formatMessage($message, $authUser->id))
            ->values();

        $groupChat->loadCount('members');

        return response()->json([
            'messages' => $messages,
            'pinned_messages' => $this->pinnedMessagesForConversation($groupChat, $clearedBeforeMessageId),
            'conversation' => $this->formatConversation($groupChat),
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => max(1, $messages->count()),
                'total' => $messages->count(),
                'has_more' => false,
            ],
        ]);
    }

    public function seen(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        $readAt = now();

        GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $authUser->id)
            ->update([
                'last_read_at' => $readAt,
                'updated_at' => $readAt,
            ]);

        $reader = GroupChatMember::query()
            ->with(['user:id,name'])
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $authUser->id)
            ->first();

        event(new GroupChatSeen([
            'group_chat_id' => (int) $groupChat->id,
            'conversation_key' => 'group:' . $groupChat->id,
            'reader_id' => (int) $authUser->id,
            'read_at' => $readAt->toIso8601String(),
            'reader' => $reader ? $this->formatConversationMemberRecord($reader) : [
                'id' => (int) $authUser->id,
                'name' => $authUser->name,
                'nickname' => null,
                'display_name' => $authUser->name,
                'profile' => null,
                'joined_at' => null,
                'last_read_at' => $readAt->toIso8601String(),
                'added_by_id' => null,
                'added_by_name' => null,
                'added_by' => null,
            ],
        ], $this->groupRecipientIds($groupChat)));

        return response()->json([
            'read_at' => $readAt->toIso8601String(),
        ]);
    }

    public function destroyConversationMessages(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        $latestVisibleMessageId = GroupMessage::query()
            ->where('group_chat_id', $groupChat->id)
            ->orderByDesc('id')
            ->value('id');
        $clearedAt = now();

        GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $authUser->id)
            ->update([
                'cleared_before_message_id' => $latestVisibleMessageId ? (int) $latestVisibleMessageId : null,
                'cleared_at' => $clearedAt,
                'updated_at' => $clearedAt,
            ]);

        return response()->json([
            'message' => 'Conversation cleared for you.',
            'conversation_key' => 'group:' . $groupChat->id,
            'conversation_preview' => [
                'preview' => 'Group chat is ready',
                'latest_at' => null,
            ],
        ]);
    }

    public function storeMessage(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'reply_to_id' => ['nullable', 'integer', Rule::exists('group_messages', 'id')],
            'attachment' => [
                'nullable',
                'file',
                'max:5120',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension() ?: '');
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'docs', 'pdf', 'xlsx', 'txt'];

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $fail('The attachment must be a valid image or document file.');
                    }
                },
            ],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $attachment = $request->file('attachment');

        if ($body === '' && !$attachment) {
            return response()->json([
                'message' => 'A message or attachment is required.',
            ], 422);
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;
        $attachmentSize = null;
        $attachmentExtension = null;
        $attachmentType = null;

        if ($attachment) {
            $attachmentPath = $attachment->store('group-messages', 'public');
            $attachmentName = $attachment->getClientOriginalName();
            $attachmentMime = $attachment->getClientMimeType();
            $attachmentSize = (int) $attachment->getSize();
            $attachmentExtension = strtolower($attachment->getClientOriginalExtension() ?: $attachment->extension() ?: '');
            $attachmentType = str_starts_with((string) $attachmentMime, 'image/') || in_array($attachmentExtension, ['jpg', 'jpeg', 'png', 'gif'], true)
                ? 'image'
                : 'file';
        }

        $message = GroupMessage::create([
            'group_chat_id' => $groupChat->id,
            'sender_id' => $authUser->id,
            'message_type' => 'user',
            'body' => $body,
            'reply_to_id' => $this->resolveReplyTargetId($groupChat, $validated['reply_to_id'] ?? null),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
            'attachment_extension' => $attachmentExtension,
            'attachment_type' => $attachmentType,
            'is_unsent' => false,
        ]);

        $groupChat->loadCount('members');
        $message->load([
            'sender:id,name',
            'replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at',
        ]);

        $this->refreshConversationLastMessage($groupChat);
        $formattedMessage = $this->formatMessage($message, $authUser->id);
        $recipientIds = $this->groupRecipientIds($groupChat);

        event(new GroupChatMessageSent(
            $formattedMessage,
            $this->formatConversation($groupChat->fresh()->loadCount('members')),
            $recipientIds
        ));

        return response()->json([
            'message' => $formattedMessage,
            'conversation' => $this->formatConversation($groupChat->fresh()->loadCount('members')),
        ], 201);
    }

    public function media(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        if (!Schema::hasColumn('group_messages', 'attachment_path')) {
            return response()->json([
                'items' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => max(12, min((int) $request->input('per_page', 24), 60)),
                    'total' => 0,
                    'has_more' => false,
                ],
            ]);
        }

        $perPage = max(12, min((int) $request->input('per_page', 24), 60));
        $page = max(1, (int) $request->input('page', 1));
        $member = $this->groupMemberRecord($groupChat, (int) $authUser->id);
        $clearedBeforeMessageId = (int) ($member?->cleared_before_message_id ?? 0);

        $paginator = GroupMessage::query()
            ->with(['sender:id,name'])
            ->where('group_chat_id', $groupChat->id)
            ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
            ->whereNotNull('attachment_path')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'items' => $paginator->getCollection()
                ->map(fn (GroupMessage $message) => $this->formatGroupMediaItem($message))
                ->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function addMembers(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $existingMemberIds = GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $memberIdsToAdd = collect($validated['member_ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $authUser->id || $existingMemberIds->contains($id))
            ->unique()
            ->values();

        if ($memberIdsToAdd->isEmpty()) {
            return response()->json([
                'message' => 'All selected users are already in this group.',
            ], 422);
        }

        GroupChatMember::insert($memberIdsToAdd->map(fn ($userId) => [
            'group_chat_id' => $groupChat->id,
            'user_id' => $userId,
            'added_by_id' => $authUser->id,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        $groupChat = $groupChat->fresh();

        $systemMessages = User::query()
            ->whereIn('id', $memberIdsToAdd->all())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $user) use ($groupChat) {
                $memberName = $this->groupMemberDisplayName((int) $groupChat->id, (int) $user->id, $user->name);

                return $this->createSystemMessage(
                    $groupChat,
                    (int) $user->id,
                    $memberName . ' joined the chat'
                );
            });

        $this->broadcastConversationUpsert($groupChat);

        return response()->json([
            'message' => $memberIdsToAdd->count() === 1
                ? 'Member invited successfully.'
                : 'Members invited successfully.',
            'conversation' => $this->formatConversation($groupChat->fresh()->loadCount('members')),
            'members' => $this->formatConversationMembers($groupChat),
            'system_messages' => $systemMessages->values()->all(),
        ]);
    }

    public function updateSettings(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $photoChanged = $request->hasFile('photo');
        $groupChat->name = trim((string) $validated['name']);

        if ($photoChanged) {
            if ($groupChat->photo_path) {
                Storage::disk('public')->delete($groupChat->photo_path);
            }

            $groupChat->photo_path = $request->file('photo')->store('group-chats/' . $groupChat->id, 'public');
        }

        $groupChat->save();

        GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $authUser->id)
            ->update([
                'nickname' => filled($validated['nickname'] ?? null)
                    ? trim((string) $validated['nickname'])
                    : null,
                'updated_at' => now(),
            ]);

        $groupChat = $groupChat->fresh();
        $systemMessage = null;

        if ($photoChanged) {
            $actorName = $this->groupMemberDisplayName((int) $groupChat->id, (int) $authUser->id, $authUser->name);
            $systemMessage = $this->createSystemMessage(
                $groupChat,
                (int) $authUser->id,
                $actorName . ' changed the group photo'
            );
        }

        $conversation = $this->formatConversation($groupChat->fresh()->loadCount('members'));

        event(new GroupChatUpdated([
            'action' => 'settings_updated',
            'conversation' => $conversation,
            'removed_user_id' => null,
        ], $this->groupRecipientIds($groupChat)));

        return response()->json([
            'message' => 'Group info updated successfully.',
            'conversation' => $conversation,
            'members' => $this->formatConversationMembers($groupChat),
            'system_message' => $systemMessage,
        ]);
    }

    public function leave(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);
        $actorName = $this->groupMemberDisplayName((int) $groupChat->id, (int) $authUser->id, $authUser->name);

        GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $authUser->id)
            ->delete();

        $groupChat = $groupChat->fresh();
        $systemMessage = $this->createSystemMessage($groupChat, (int) $authUser->id, $actorName . ' left the chat');
        $remainingRecipientIds = $this->groupRecipientIds($groupChat);

        event(new GroupChatUpdated([
            'action' => 'member_left',
            'conversation' => $this->formatConversation($groupChat->fresh()->loadCount('members')),
            'removed_user_id' => (int) $authUser->id,
        ], array_merge($remainingRecipientIds, [(int) $authUser->id])));

        return response()->json([
            'message' => 'You left the group chat.',
            'removed_user_id' => (int) $authUser->id,
            'conversation_key' => 'group:' . $groupChat->id,
            'system_message' => $systemMessage,
        ]);
    }

    public function updateMessage(Request $request, GroupMessage $message)
    {
        $authUser = $request->user();
        $groupChat = $message->groupChat;
        $this->authorizeMember($authUser->id, $groupChat, true);

        if ((int) $message->sender_id !== (int) $authUser->id) {
            return response()->json([
                'message' => 'Only the sender can edit this message.',
            ], 403);
        }

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return response()->json([
                'message' => 'This message has already been unsent.',
            ], 422);
        }

        if ($message->message_type !== 'user') {
            return response()->json([
                'message' => 'This message cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message->body = trim((string) $validated['body']);
        $message->edited_at = now();
        $message->save();
        $message->load([
            'sender:id,name',
            'replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at',
        ]);

        $this->refreshConversationLastMessage($groupChat);

        $payload = $this->formatGroupMessageUpdatePayload($groupChat, $message, $authUser->id);
        event(new GroupChatMessageUpdated($payload, $this->groupRecipientIds($groupChat)));

        return response()->json($payload);
    }

    public function destroyMessage(Request $request, GroupMessage $message)
    {
        $authUser = $request->user();
        $groupChat = $message->groupChat;
        $this->authorizeMember($authUser->id, $groupChat, true);

        if ((int) $message->sender_id !== (int) $authUser->id) {
            return response()->json([
                'message' => 'Only the sender can unsend this message.',
            ], 403);
        }

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return response()->json([
                'message' => 'This message has already been unsent.',
            ], 422);
        }

        if ($message->message_type !== 'user') {
            return response()->json([
                'message' => 'This message cannot be unsent.',
            ], 422);
        }

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->unsent_at = now();
        $message->unsent_by_id = $authUser->id;
        $message->is_unsent = true;
        $message->body = null;
        $message->attachment_path = null;
        $message->attachment_name = null;
        $message->attachment_mime = null;
        $message->attachment_size = null;
        $message->attachment_extension = null;
        $message->attachment_type = null;
        $message->edited_at = null;
        $message->reaction = null;
        $message->pinned_at = null;
        $message->pinned_by_id = null;
        $message->save();
        $message->load([
            'sender:id,name',
            'replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at',
        ]);

        $this->refreshConversationLastMessage($groupChat);

        $payload = $this->formatGroupMessageUpdatePayload($groupChat, $message, $authUser->id);
        event(new GroupChatMessageUpdated($payload, $this->groupRecipientIds($groupChat)));

        return response()->json($payload);
    }

    public function reactToMessage(Request $request, GroupMessage $message)
    {
        $validated = $request->validate([
            'reaction' => ['nullable', 'string', 'in:' . implode(',', self::REACTION_KEYS)],
        ]);

        $authUser = $request->user();
        $groupChat = $message->groupChat;
        $this->authorizeMember($authUser->id, $groupChat, true);

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return response()->json([
                'message' => 'This message has already been unsent.',
            ], 422);
        }

        if ($message->message_type !== 'user') {
            return response()->json([
                'message' => 'This message cannot be reacted to.',
            ], 422);
        }

        $message->reaction = $validated['reaction'] ?? null;
        $message->save();
        $message->load([
            'sender:id,name',
            'replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at',
        ]);

        $payload = $this->formatGroupMessageUpdatePayload($groupChat, $message, $authUser->id);
        event(new GroupChatMessageUpdated($payload, $this->groupRecipientIds($groupChat)));

        return response()->json($payload);
    }

    public function pinMessage(Request $request, GroupMessage $message)
    {
        $validated = $request->validate([
            'is_pinned' => ['required', 'boolean'],
        ]);

        $authUser = $request->user();
        $groupChat = $message->groupChat;
        $this->authorizeMember($authUser->id, $groupChat, true);

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return response()->json([
                'message' => 'This message has already been unsent.',
            ], 422);
        }

        if ($message->message_type !== 'user') {
            return response()->json([
                'message' => 'This message cannot be pinned.',
            ], 422);
        }

        if ($validated['is_pinned']) {
            $alreadyPinned = (bool) $message->pinned_at;

            if (!$alreadyPinned) {
                $pinnedCount = GroupMessage::query()
                    ->where('group_chat_id', $groupChat->id)
                    ->whereNotNull('pinned_at')
                    ->count();

                if ($pinnedCount >= 10) {
                    return response()->json([
                        'message' => 'You have reached the maximum number of pinned messages.',
                    ], 422);
                }
            }

            $message->pinned_at = now();
            $message->pinned_by_id = $authUser->id;
        } else {
            $message->pinned_at = null;
            $message->pinned_by_id = null;
        }

        $message->save();
        $message->load([
            'sender:id,name',
            'replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at',
        ]);

        $payload = $this->formatGroupMessageUpdatePayload($groupChat, $message, $authUser->id);
        event(new GroupChatMessageUpdated($payload, $this->groupRecipientIds($groupChat)));

        return response()->json($payload);
    }

    public function typing(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();
        $this->authorizeMember($authUser->id, $groupChat, true);

        $recipientIds = GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', '!=', $authUser->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (count($recipientIds) > 0) {
            event(new GroupChatTyping([
                'group_chat_id' => (int) $groupChat->id,
                'sender_id' => (int) $authUser->id,
                'sender_name' => $this->groupMemberDisplayName((int) $groupChat->id, (int) $authUser->id, $authUser->name),
                'is_typing' => (bool) $request->boolean('is_typing', true),
                'updated_at' => now()->toIso8601String(),
            ], $recipientIds));
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function approve(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();

        abort_unless($this->messagesPageService->isAdmin($authUser), 403);

        if ($groupChat->approval_status !== 'pending') {
            return response()->json([
                'message' => 'This group chat has already been processed.',
            ], 422);
        }

        $groupChat->forceFill([
            'approval_status' => 'approved',
            'approved_by_id' => $authUser->id,
            'approved_at' => now(),
            'rejected_by_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ])->save();

        $groupChat->loadCount('members');

        $recipientIds = GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        event(new GroupChatCreated(
            $this->formatConversation($groupChat),
            $recipientIds
        ));

        event(new GroupChatRequestUpdated(
            'approved',
            $this->formatPendingRequest($groupChat),
            $this->requestUpdateRecipientIds($groupChat)
        ));

        return response()->json([
            'message' => 'Group chat approved successfully.',
            'conversation' => $this->formatConversation($groupChat),
        ]);
    }

    public function reject(Request $request, GroupChat $groupChat)
    {
        $authUser = $request->user();

        abort_unless($this->messagesPageService->isAdmin($authUser), 403);

        if ($groupChat->approval_status !== 'pending') {
            return response()->json([
                'message' => 'This group chat has already been processed.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $groupChat->forceFill([
            'approval_status' => 'rejected',
            'rejected_by_id' => $authUser->id,
            'rejected_at' => now(),
            'rejection_reason' => trim((string) ($validated['reason'] ?? '')),
        ])->save();

        event(new GroupChatRequestUpdated(
            'rejected',
            $this->formatPendingRequest($groupChat),
            $this->requestUpdateRecipientIds($groupChat)
        ));

        return response()->json([
            'message' => 'Group chat request rejected.',
        ]);
    }

    protected function authorizeMember(int $userId, GroupChat $groupChat, bool $mustBeApproved = false): void
    {
        $isMember = GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$isMember) {
            abort(403);
        }

        if ($mustBeApproved && $groupChat->approval_status !== 'approved') {
            abort(403);
        }
    }

    protected function formatConversation(GroupChat $groupChat): array
    {
        $latestAt = $groupChat->last_message_at ?: $groupChat->created_at;
        $memberIds = $groupChat->relationLoaded('members')
            ? $groupChat->members->pluck('user_id')
            : $groupChat->members()->pluck('user_id');
        $memberCount = (int) ($groupChat->members_count ?? $memberIds->count());

        return [
            'id' => (int) $groupChat->id,
            'name' => $groupChat->name,
            'email' => null,
            'employee_no' => null,
            'profile' => $groupChat->photo_path
                ? Storage::url($groupChat->photo_path)
                : 'https://ui-avatars.com/api/?name='
                    . urlencode($groupChat->name)
                    . '&background=1f6feb&color=fff&font-size=0.36&bold=true',
            'conversation_type' => 'group',
            'conversation_key' => 'group:' . $groupChat->id,
            'conversation_token' => $this->messagesPageService->conversationToken('group', (int) $groupChat->id),
            'preview' => $groupChat->last_message_preview ?: 'Group chat is ready',
            'preview_time' => $latestAt?->diffForHumans(),
            'latest_at' => $latestAt?->toIso8601String(),
            'unread_count' => 0,
            'is_unread' => false,
            'member_count' => $memberCount,
            'member_ids' => collect($memberIds)
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'members' => $this->formatConversationMembers($groupChat),
            'is_active' => false,
            'active_label' => $memberCount . ' members',
        ];
    }

    protected function formatMessage(GroupMessage $message, int $authUserId): array
    {
        return [
            'id' => $message->id,
            'group_chat_id' => $message->group_chat_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $this->groupMemberDisplayName($message->group_chat_id, $message->sender_id, $message->sender?->name ?? 'User'),
            'message_type' => $message->message_type ?: 'user',
            'is_system' => ($message->message_type ?: 'user') !== 'user',
            'recipient_id' => null,
            'body' => $message->body,
            'reply_to_id' => $message->reply_to_id,
            'reply_preview' => $this->formatReplyPreview($message->replyTo),
            'attachment' => $this->formatAttachment($message),
            'reaction' => $message->reaction,
            'pinned_at' => $message->pinned_at?->toIso8601String(),
            'pinned_by_id' => $message->pinned_by_id,
            'is_pinned' => (bool) $message->pinned_at,
            'read_at' => null,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'unsent_at' => $message->unsent_at?->toIso8601String(),
            'unsent_by_id' => $message->unsent_by_id,
            'is_unsent' => (bool) ($message->is_unsent ?? $message->unsent_at),
            'is_mine' => (int) $message->sender_id === $authUserId,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    protected function messagePreview(GroupMessage $message): string
    {
        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return 'Unsent Message';
        }

        if (($message->message_type ?: 'user') !== 'user') {
            return trim((string) $message->body) !== '' ? trim((string) $message->body) : 'Group activity';
        }

        if (filled($message->body)) {
            return mb_strimwidth(trim((string) $message->body), 0, 68, '...');
        }

        if ($message->replyTo) {
            return $this->formatReplyPreview($message->replyTo) ?? 'Reply';
        }

        if ($message->attachment_name) {
            return $message->attachment_name;
        }

        if ($message->attachment_path) {
            return basename($message->attachment_path);
        }

        return 'Message';
    }

    protected function formatReplyPreview(?GroupMessage $message): ?string
    {
        if (!$message) {
            return null;
        }

        if (($message->message_type ?: 'user') !== 'user') {
            return trim((string) $message->body) !== '' ? trim((string) $message->body) : 'Group activity';
        }

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return 'Unsent Message';
        }

        if (filled($message->body)) {
            return $message->body;
        }

        if ($message->attachment_name) {
            return $message->attachment_name;
        }

        if ($message->attachment_path) {
            return basename($message->attachment_path);
        }

        return 'Message';
    }

    protected function pinnedMessagesForConversation(GroupChat $groupChat, int $clearedBeforeMessageId = 0): array
    {
        return GroupMessage::query()
            ->with('replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at')
            ->where('group_chat_id', $groupChat->id)
            ->when($clearedBeforeMessageId > 0, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
            ->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (GroupMessage $message) {
                return [
                    'message_id' => $message->id,
                    'preview' => $this->formatReplyPreview($message),
                    'created_at' => $message->created_at?->toIso8601String(),
                    'pinned_at' => $message->pinned_at?->toIso8601String(),
                    'pinned_by_id' => $message->pinned_by_id,
                ];
            })
            ->values()
            ->all();
    }

    protected function refreshConversationLastMessage(GroupChat $groupChat): void
    {
        $latestMessage = GroupMessage::query()
            ->with('replyTo:id,group_chat_id,body,reply_to_id,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type,reaction,pinned_at,pinned_by_id,edited_at,unsent_at,unsent_by_id,is_unsent,created_at')
            ->where('group_chat_id', $groupChat->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $groupChat->forceFill([
            'last_message_preview' => $latestMessage ? $this->messagePreview($latestMessage) : null,
            'last_message_at' => $latestMessage?->created_at,
        ])->save();
    }

    protected function resolveReplyTargetId(GroupChat $groupChat, mixed $replyTargetId): ?int
    {
        if (!$replyTargetId) {
            return null;
        }

        $replyMessage = GroupMessage::query()
            ->where('group_chat_id', $groupChat->id)
            ->find($replyTargetId);

        if (!$replyMessage) {
            throw ValidationException::withMessages([
                'reply_to_id' => 'The selected reply target is invalid.',
            ]);
        }

        return (int) $replyMessage->id;
    }

    protected function groupRecipientIds(GroupChat $groupChat): array
    {
        return GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function formatConversationMembers(GroupChat $groupChat): array
    {
        return GroupChatMember::query()
            ->with(['user:id,name', 'addedBy:id,name'])
            ->where('group_chat_id', $groupChat->id)
            ->orderBy('id')
            ->get()
            ->map(fn (GroupChatMember $member) => $this->formatConversationMemberRecord($member))
            ->values()
            ->all();
    }

    protected function formatConversationMemberRecord(GroupChatMember $member): array
    {
        return [
            'id' => (int) $member->user_id,
            'name' => $member->user?->name ?? 'User',
            'nickname' => $member->nickname,
            'display_name' => filled($member->nickname) ? $member->nickname : ($member->user?->name ?? 'User'),
            'profile' => null,
            'joined_at' => $member->joined_at?->toIso8601String(),
            'last_read_at' => $member->last_read_at?->toIso8601String(),
            'added_by_id' => $member->added_by_id ? (int) $member->added_by_id : null,
            'added_by_name' => $member->addedBy?->name,
            'added_by' => $member->addedBy
                ? [
                    'id' => (int) $member->addedBy->id,
                    'name' => $member->addedBy->name,
                ]
                : null,
        ];
    }

    protected function groupMemberDisplayName(int $groupChatId, int $userId, string $fallbackName): string
    {
        $nickname = GroupChatMember::query()
            ->where('group_chat_id', $groupChatId)
            ->where('user_id', $userId)
            ->value('nickname');

        return filled($nickname) ? (string) $nickname : $fallbackName;
    }

    protected function formatGroupMessageUpdatePayload(GroupChat $groupChat, GroupMessage $message, int $authUserId): array
    {
        $groupChat->loadCount('members');
        $member = $this->groupMemberRecord($groupChat, $authUserId);
        $clearedBeforeMessageId = (int) ($member?->cleared_before_message_id ?? 0);

        return [
            'message' => $this->formatMessage($message, $authUserId),
            'conversation' => $this->formatConversation($groupChat->fresh()->loadCount('members')),
            'pinned_messages' => $this->pinnedMessagesForConversation($groupChat, $clearedBeforeMessageId),
        ];
    }

    protected function formatGroupMediaItem(GroupMessage $message): array
    {
        return [
            'message_id' => (int) $message->id,
            'sender_id' => (int) $message->sender_id,
            'sender_name' => $this->groupMemberDisplayName((int) $message->group_chat_id, (int) $message->sender_id, $message->sender?->name ?? 'User'),
            'created_at' => $message->created_at?->toIso8601String(),
            'attachment' => $this->formatAttachment($message),
        ];
    }

    protected function formatAttachment(GroupMessage $message): ?array
    {
        if (!$message->attachment_path) {
            return null;
        }

        return [
            'path' => $message->attachment_path,
            'url' => url('/storage/' . ltrim($message->attachment_path, '/')),
            'name' => $message->attachment_name ?: basename($message->attachment_path),
            'mime' => $message->attachment_mime,
            'size' => $message->attachment_size,
            'extension' => $message->attachment_extension,
            'type' => $message->attachment_type ?: (str_starts_with((string) $message->attachment_mime, 'image/') ? 'image' : 'file'),
        ];
    }

    protected function groupMemberRecord(GroupChat $groupChat, int $userId): ?GroupChatMember
    {
        return GroupChatMember::query()
            ->where('group_chat_id', $groupChat->id)
            ->where('user_id', $userId)
            ->first();
    }

    protected function createSystemMessage(GroupChat $groupChat, int $senderId, string $body, bool $broadcast = true): array
    {
        $message = GroupMessage::create([
            'group_chat_id' => $groupChat->id,
            'sender_id' => $senderId,
            'message_type' => 'system',
            'body' => $body,
            'is_unsent' => false,
        ]);

        $message->load('sender:id,name');
        $this->refreshConversationLastMessage($groupChat);
        $conversation = $this->formatConversation($groupChat->fresh()->loadCount('members'));
        $formattedMessage = $this->formatMessage($message, $senderId);

        if ($broadcast && count($this->groupRecipientIds($groupChat)) > 0) {
            event(new GroupChatMessageSent(
                $formattedMessage,
                $conversation,
                $this->groupRecipientIds($groupChat)
            ));
        }

        return $formattedMessage;
    }

    protected function broadcastConversationUpsert(GroupChat $groupChat): void
    {
        $recipientIds = $this->groupRecipientIds($groupChat);

        if (count($recipientIds) === 0) {
            return;
        }

        event(new GroupChatUpdated([
            'action' => 'members_updated',
            'conversation' => $this->formatConversation($groupChat->fresh()->loadCount('members')),
            'removed_user_id' => null,
        ], $recipientIds));
    }

    protected function formatPendingRequest(GroupChat $groupChat): array
    {
        $groupChat->loadMissing(['creator:id,name', 'approver:id,name', 'rejector:id,name', 'members.user:id,name']);
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
    }

    protected function adminRecipientIds(): array
    {
        $roleNames = Role::query()
            ->whereIn('name', ['admin', 'super_admin'])
            ->pluck('name')
            ->all();

        if (count($roleNames) === 0) {
            return [];
        }

        return User::query()
            ->role($roleNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function requestUpdateRecipientIds(GroupChat $groupChat): array
    {
        return collect([
            ...$this->adminRecipientIds(),
            (int) ($groupChat->created_by_id ?? 0),
        ])
            ->filter(fn ($userId) => $userId > 0)
            ->unique()
            ->values()
            ->all();
    }
}
