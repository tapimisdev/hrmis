<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectConversationInfoUpdated;
use App\Events\DirectMessageSeen;
use App\Events\DirectMessageUpdated;
use App\Events\DirectMessageSent;
use App\Events\DirectMessageTyping;
use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\DirectMessageReaction;
use App\Models\User;
use App\Services\MessagesPageService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DirectMessageController extends Controller
{
    private const REACTION_KEYS = ['like', 'number-one', 'love', 'haha', 'sad', 'angry'];

    public function __construct(
        protected MessagesPageService $messagesPageService
    ) {
    }

    public function index(Request $request, User $user)
    {
        $authUser = $request->user();
        $perPage = max(10, min((int) $request->input('per_page', 20), 100));
        $page = max(1, (int) $request->input('page', 1));

        $query = $this->conversationQuery($authUser, $user);

        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $messages = $paginator->getCollection()
            ->map(function (DirectMessage $message) use ($authUser) {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'recipient_id' => $message->recipient_id,
                    'message_type' => $message->message_type ?: 'user',
                    'is_system' => ($message->message_type ?: 'user') !== 'user',
                    'body' => $message->body,
                    'reply_to_id' => $message->reply_to_id,
                    'reply_preview' => $this->formatReplyPreview($message->replyTo),
                    'attachment' => $this->formatAttachment($message),
                    'reaction' => $message->reaction,
                    'reactions' => $message->getReactionsWithUsers(),
                    'pinned_at' => $message->pinned_at?->toIso8601String(),
                    'pinned_by_id' => $message->pinned_by_id,
                    'is_pinned' => (bool) $message->pinned_at,
                    'edited_at' => $message->edited_at?->toIso8601String(),
                    'unsent_at' => $message->unsent_at?->toIso8601String(),
                    'unsent_by_id' => $message->unsent_by_id,
                    'is_unsent' => (bool) ($message->is_unsent ?? $message->unsent_at),
                    'reply_to' => $message->replyTo ? [
                        'id' => $message->replyTo->id,
                        'body' => $message->replyTo->body,
                        'sender_id' => $message->replyTo->sender_id,
                        'recipient_id' => $message->replyTo->recipient_id,
                        'created_at' => $message->replyTo->created_at?->toIso8601String(),
                        'attachment' => $this->formatAttachment($message->replyTo),
                    ] : null,
                    'read_at' => $message->read_at?->toIso8601String(),
                    'is_mine' => (int) $message->sender_id === (int) $authUser->id,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->reverse()
            ->values();

        $pinnedMessages = $this->supportsPinnedMessages()
            ? $this->conversationQuery($authUser, $user)
                ->whereNotNull('pinned_at')
                ->orderByDesc('pinned_at')
                ->orderByDesc('id')
                ->get()
                ->map(function (DirectMessage $message) {
                    return [
                        'message_id' => $message->id,
                        'preview' => $this->formatReplyPreview($message),
                        'created_at' => $message->created_at?->toIso8601String(),
                        'pinned_at' => $message->pinned_at?->toIso8601String(),
                        'pinned_by_id' => $message->pinned_by_id,
                    ];
                })
                ->values()
            : collect();

        return response()->json([
            'messages' => $messages,
            'pinned_messages' => $pinnedMessages,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
            'body' => [
                'nullable',
                'string',
                'max:2000',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->hasFile('attachment')) {
                        return;
                    }

                    if (trim((string) $value) === '') {
                        $fail('Message or attachment is required.');
                    }
                },
            ],
            'reply_to_id' => ['nullable', 'integer', 'exists:direct_messages,id'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,gif,doc,docx,pdf,xlsx,txt',
                'max:5120',
            ],
        ]);

        $recipient = User::query()->findOrFail((int) $validated['recipient_id']);
        $body = trim((string) ($validated['body'] ?? ''));

        $attachment = $request->file('attachment');
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;
        $attachmentSize = null;
        $attachmentExtension = null;
        $attachmentType = null;

        if ($attachment) {
            $attachmentPath = $attachment->store('direct-messages', 'public');
            $attachmentName = $attachment->getClientOriginalName();
            $attachmentMime = $attachment->getClientMimeType();
            $attachmentSize = (int) $attachment->getSize();
            $attachmentExtension = strtolower($attachment->getClientOriginalExtension() ?: $attachment->extension() ?: '');
            $attachmentType = str_starts_with((string) $attachmentMime, 'image/') || in_array($attachmentExtension, ['jpg', 'jpeg', 'png', 'gif'], true)
                ? 'image'
                : 'file';
        }

        $message = DirectMessage::create([
            'sender_id' => $authUser->id,
            'recipient_id' => $recipient->id,
            'body' => $body,
            'reply_to_id' => $this->resolveReplyTargetId($authUser, $recipient, $validated['reply_to_id'] ?? null),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
            'attachment_extension' => $attachmentExtension,
            'attachment_type' => $attachmentType,
            'is_unsent' => false,
        ]);

        $message->load('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type');

        $payload = $this->formatMessage($message, $authUser, true);

        event(new DirectMessageSent($payload));

        return response()->json([
            'message' => $payload,
        ], 201);
    }

    public function info(Request $request, User $user)
    {
        $authUser = $request->user();

        return response()->json([
            'conversation' => $this->formatConversationInfo($authUser, $user),
        ]);
    }

    public function updateInfo(Request $request, User $user)
    {
        $authUser = $request->user();
        abort_unless(Schema::hasTable('direct_conversation_settings'), 422, 'Direct conversation nicknames are not available yet.');
        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:120'],
            'self_nickname' => ['nullable', 'string', 'max:120'],
        ]);

        $nickname = trim((string) ($validated['nickname'] ?? ''));
        $selfNickname = trim((string) ($validated['self_nickname'] ?? ''));
        $now = Carbon::now();
        $existingNickname = $this->existingDirectConversationNickname((int) $user->id, (int) $authUser->id);
        $existingSelfNickname = $this->existingDirectConversationNickname((int) $authUser->id, (int) $user->id);
        $systemMessages = [
            (int) $authUser->id => [],
            (int) $user->id => [],
        ];
        $authActualName = $this->resolveDirectConversationActualName($authUser);
        $partnerActualName = $this->resolveDirectConversationActualName($user);
        DB::transaction(function () use (
            $authUser,
            $user,
            $nickname,
            $selfNickname,
            $now,
            $authActualName,
            $partnerActualName,
            $existingNickname,
            $existingSelfNickname,
            &$systemMessages
        ) {
            $this->persistDirectConversationNickname(
                (int) $user->id,
                (int) $authUser->id,
                $nickname,
                $now,
            );

            $this->persistDirectConversationNickname(
                (int) $authUser->id,
                (int) $user->id,
                $selfNickname,
                $now,
            );

            if ($nickname !== $existingNickname) {
                $systemMessages[(int) $authUser->id][] = $this->createDirectConversationSystemMessage(
                    $authUser,
                    $user,
                    filled($nickname)
                        ? 'You set nickname for ' . $partnerActualName . ' to ' . $nickname
                        : 'You cleared nickname for ' . $partnerActualName,
                    (int) $authUser->id,
                );
                $systemMessages[(int) $user->id][] = $this->createDirectConversationSystemMessage(
                    $authUser,
                    $user,
                    filled($nickname)
                        ? $authActualName . ' set your nickname to ' . $nickname
                        : $authActualName . ' cleared your nickname',
                    (int) $user->id,
                );
            }

            if ($selfNickname !== $existingSelfNickname) {
                $systemMessages[(int) $authUser->id][] = $this->createDirectConversationSystemMessage(
                    $authUser,
                    $user,
                    filled($selfNickname)
                        ? 'You set your nickname to ' . $selfNickname
                        : 'You cleared your nickname',
                    (int) $authUser->id,
                );
                $systemMessages[(int) $user->id][] = $this->createDirectConversationSystemMessage(
                    $authUser,
                    $user,
                    filled($selfNickname)
                        ? $authActualName . ' set your nickname to ' . $selfNickname
                        : $authActualName . ' cleared your nickname',
                    (int) $user->id,
                );
            }
        });

        $authConversation = $this->formatConversationInfo($authUser, $user);
        $partnerConversation = $this->formatConversationInfo($user, $authUser);

        event(new DirectConversationInfoUpdated([
            'participants' => [(int) $authUser->id, (int) $user->id],
            'conversations' => [
                (int) $authUser->id => $authConversation,
                (int) $user->id => $partnerConversation,
            ],
            'system_messages' => $systemMessages,
        ]));

        return response()->json([
            'message' => 'Conversation info updated successfully.',
            'conversation' => $authConversation,
            'system_messages' => $systemMessages[(int) $authUser->id] ?? [],
        ]);
    }

    protected function existingDirectConversationNickname(int $userId, int $partnerId): string
    {
        return trim((string) (
            DB::table('direct_conversation_settings')
                ->where('user_id', $userId)
                ->where('partner_id', $partnerId)
                ->value('nickname') ?? ''
        ));
    }

    protected function persistDirectConversationNickname(int $userId, int $partnerId, string $nickname, Carbon $now): void
    {
        $settingsQuery = DB::table('direct_conversation_settings')
            ->where('user_id', $userId)
            ->where('partner_id', $partnerId);

        if ($nickname === '') {
            $settingsQuery->delete();
            return;
        }

        DB::table('direct_conversation_settings')->updateOrInsert(
            [
                'user_id' => $userId,
                'partner_id' => $partnerId,
            ],
            [
                'nickname' => $nickname,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    protected function createDirectConversationSystemMessage(User $authUser, User $user, string $body, int $visibleToUserId): array
    {
        $message = DirectMessage::create([
            'sender_id' => (int) $authUser->id,
            'recipient_id' => (int) $user->id,
            'visible_to_user_id' => $visibleToUserId,
            'message_type' => 'system',
            'body' => $body,
            'is_unsent' => false,
        ]);
        $viewer = (int) $authUser->id === $visibleToUserId ? $authUser : $user;

        return $this->formatMessage(
            $message->load('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type'),
            $viewer,
            true,
        );
    }

    public function media(Request $request, User $user)
    {
        $authUser = $request->user();
        $perPage = max(12, min((int) $request->input('per_page', 24), 60));
        $page = max(1, (int) $request->input('page', 1));

        $paginator = $this->conversationQuery($authUser, $user)
            ->with(['sender:id,name'])
            ->whereNotNull('attachment_path')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'items' => $paginator->getCollection()
                ->map(fn (DirectMessage $message) => $this->formatMediaItem($message))
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

    public function update(Request $request, DirectMessage $message)
    {
        $authUser = $request->user();
        $this->authorizeMessageParticipant($authUser, $message);

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

        $validated = $request->validate([
            'body' => [
                'required',
                'string',
                'max:2000',
                function ($attribute, $value, $fail) {
                    if (trim((string) $value) === '') {
                        $fail('Message body cannot be empty.');
                    }
                },
            ],
        ]);

        $message->body = trim((string) $validated['body']);
        $message->edited_at = Carbon::now();
        $message->save();
        $message->load('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type');

        $payload = $this->formatMessage($message, $authUser, true);
        $conversationPreview = $this->conversationPreviewForMessage($message, $authUser);

        event(new DirectMessageUpdated([
            'message' => $payload,
            'conversation_preview' => $conversationPreview,
        ]));

        return response()->json([
            'message' => $payload,
            'conversation_preview' => $conversationPreview,
        ]);
    }

    public function destroy(Request $request, DirectMessage $message)
    {
        $authUser = $request->user();
        $this->authorizeMessageParticipant($authUser, $message);

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

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->unsent_at = Carbon::now();
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
        $message->load('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type');

        $payload = $this->formatMessage($message, $authUser, true);
        $conversationPreview = $this->conversationPreviewForMessage($message, $authUser);

        event(new DirectMessageUpdated([
            'message' => $payload,
            'conversation_preview' => $conversationPreview,
            'is_unsent' => true,
        ]));

        return response()->json([
            'message' => $payload,
            'conversation_preview' => $conversationPreview,
        ]);
    }

    public function react(Request $request, DirectMessage $message)
    {
        if (!$this->supportsReactionFeatures()) {
            return response()->json([
                'message' => 'Reactions are not available yet.',
            ], 422);
        }

        $validated = $request->validate([
            'reaction' => ['nullable', 'string', 'in:' . implode(',', self::REACTION_KEYS)],
        ]);

        $authUser = $request->user();
        $this->authorizeMessageParticipant($authUser, $message);

        // Handle multiple reactions for direct messages
        $reactionValue = $validated['reaction'] ?? null;
        
        if ($reactionValue) {
            // Add or update user's reaction
            DirectMessageReaction::updateOrCreate(
                [
                    'direct_message_id' => $message->id,
                    'user_id' => $authUser->id,
                ],
                [
                    'reaction' => $reactionValue,
                ]
            );
        } else {
            // Remove user's reaction
            DirectMessageReaction::where('direct_message_id', $message->id)
                ->where('user_id', $authUser->id)
                ->delete();
        }

        $message->load([
            'replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type',
            'reactions.user:id,name',
        ]);

        $payload = $this->formatMessage($message, $authUser, true);

        event(new DirectMessageUpdated([
            'message' => $payload,
            'pinned_messages' => $this->pinnedMessagesForConversation($authUser, $message),
        ]));

        return response()->json([
            'message' => $payload,
        ]);
    }

    public function pin(Request $request, DirectMessage $message)
    {
        if (!$this->supportsPinnedMessages()) {
            return response()->json([
                'message' => 'Pins are not available yet.',
            ], 422);
        }

        $validated = $request->validate([
            'is_pinned' => ['required', 'boolean'],
        ]);

        $authUser = $request->user();
        $this->authorizeMessageParticipant($authUser, $message);

        $partnerId = (int) $message->sender_id === (int) $authUser->id
            ? $message->recipient_id
            : $message->sender_id;
        $partner = User::findOrFail($partnerId);
        $conversationQuery = $this->conversationQuery($authUser, $partner);

        if ($validated['is_pinned']) {
            $alreadyPinned = (bool) $message->pinned_at;
            if (!$alreadyPinned) {
                $pinnedCount = (clone $conversationQuery)->whereNotNull('pinned_at')->count();
                if ($pinnedCount >= 10) {
                    return response()->json([
                        'message' => 'You have reached the maximum number of pinned messages.',
                    ], 422);
                }
            }

            $message->pinned_at = Carbon::now();
            $message->pinned_by_id = $authUser->id;
        } else {
            $message->pinned_at = null;
            $message->pinned_by_id = null;
        }

        $message->save();
        $message->load('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type');

        $payload = $this->formatMessage($message, $authUser, true);
        $pinnedMessages = $this->pinnedMessagesForConversation($authUser, $message);

        event(new DirectMessageUpdated([
            'message' => $payload,
            'pinned_messages' => $pinnedMessages,
        ]));

        return response()->json([
            'message' => $payload,
            'pinned_messages' => $pinnedMessages,
        ]);
    }

    public function seen(Request $request, User $user)
    {
        $authUser = $request->user();
        $readAt = Carbon::now();
        $clearedBeforeMessageId = $this->conversationClearMarker($authUser, $user);

        $messageIds = DirectMessage::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
            ->when($clearedBeforeMessageId, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
            ->pluck('id')
            ->all();

        if (count($messageIds) > 0) {
            DirectMessage::query()
                ->whereIn('id', $messageIds)
                ->update(['read_at' => $readAt]);

            event(new DirectMessageSeen([
                'reader_id' => $authUser->id,
                'partner_id' => $user->id,
                'message_ids' => $messageIds,
                'read_at' => $readAt->toIso8601String(),
            ]));
        }

        return response()->json([
            'message_ids' => $messageIds,
            'read_at' => $readAt->toIso8601String(),
        ]);
    }

    public function destroyConversation(Request $request, User $user)
    {
        $authUser = $request->user();
        $latestVisibleMessageId = $this->conversationQuery($authUser, $user)
            ->orderByDesc('id')
            ->value('id');
        $clearedAt = Carbon::now();

        DB::table('direct_conversation_clears')->updateOrInsert(
            [
                'user_id' => $authUser->id,
                'partner_id' => $user->id,
            ],
            [
                'cleared_before_message_id' => $latestVisibleMessageId ? (int) $latestVisibleMessageId : null,
                'cleared_at' => $clearedAt,
                'updated_at' => $clearedAt,
                'created_at' => $clearedAt,
            ],
        );

        return response()->json([
            'message' => 'Conversation cleared for you.',
            'conversation_preview' => [
                'preview' => 'Start a conversation',
                'latest_at' => null,
            ],
            'conversation_key' => 'direct:' . $user->id,
        ]);
    }

    public function typing(Request $request, User $user)
    {
        $authUser = $request->user();
        $validated = $request->validate([
            'is_typing' => ['nullable', 'boolean'],
        ]);

        event(new DirectMessageTyping([
            'sender_id' => $authUser->id,
            'recipient_id' => $user->id,
            'is_typing' => (bool) ($validated['is_typing'] ?? true),
            'updated_at' => Carbon::now()->toIso8601String(),
        ]));

        return response()->json([
            'success' => true,
        ]);
    }

    public function attachment(Request $request, DirectMessage $message)
    {
        $authUser = $request->user();

        if (
            !$authUser ||
            ((int) $message->sender_id !== (int) $authUser->id &&
                (int) $message->recipient_id !== (int) $authUser->id)
        ) {
            abort(403);
        }

        if (!$message->attachment_path) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($message->attachment_path)) {
            abort(404);
        }

        return response()->file(
            $disk->path($message->attachment_path),
            [
                'Content-Disposition' => 'attachment; filename="' . ($message->attachment_name ?: basename($message->attachment_path)) . '"',
            ]
        );
    }

    protected function markConversationAsSeen(User $authUser, User $user): void
    {
        $clearedBeforeMessageId = $this->conversationClearMarker($authUser, $user);
        $messageIds = DirectMessage::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
            ->when($clearedBeforeMessageId, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId))
            ->pluck('id')
            ->all();

        if (count($messageIds) === 0) {
            return;
        }

        $readAt = Carbon::now();

        DirectMessage::query()
            ->whereIn('id', $messageIds)
            ->update(['read_at' => $readAt]);

        event(new DirectMessageSeen([
            'reader_id' => $authUser->id,
            'partner_id' => $user->id,
            'message_ids' => $messageIds,
            'read_at' => $readAt->toIso8601String(),
        ]));
    }

    protected function conversationQuery(User $authUser, User $user)
    {
        return $this->conversationQueryByPartnerId($authUser, (int) $user->id);
    }

    protected function conversationQueryByPartnerId(User $authUser, int $partnerId)
    {
        $clearedBeforeMessageId = $this->conversationClearMarkerByPartnerId((int) $authUser->id, $partnerId);

        return DirectMessage::query()
            ->with([
                'replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type',
                'reactions.user:id,name',
            ])
            ->where(function ($query) use ($authUser, $partnerId) {
                $query->where(function ($query) use ($authUser, $partnerId) {
                    $query->where('sender_id', $authUser->id)
                        ->where('recipient_id', $partnerId);
                })->orWhere(function ($query) use ($authUser, $partnerId) {
                    $query->where('sender_id', $partnerId)
                        ->where('recipient_id', $authUser->id);
                });
            })
            ->when($this->supportsDirectSystemMessages(), function ($query) use ($authUser) {
                $query->where(function ($visibilityQuery) use ($authUser) {
                    $visibilityQuery->whereNull('visible_to_user_id')
                        ->orWhere('visible_to_user_id', $authUser->id);
                });
            })
            ->when($clearedBeforeMessageId, fn ($query) => $query->where('id', '>', $clearedBeforeMessageId));
    }

    protected function conversationClearMarker(User $authUser, User $user): ?int
    {
        return $this->conversationClearMarkerByPartnerId((int) $authUser->id, (int) $user->id);
    }

    protected function conversationClearMarkerByPartnerId(int $authUserId, int $partnerId): ?int
    {
        $clearMarker = DB::table('direct_conversation_clears')
            ->where('user_id', $authUserId)
            ->where('partner_id', $partnerId)
            ->value('cleared_before_message_id');

        return $clearMarker ? (int) $clearMarker : null;
    }

    protected function pinnedMessagesForConversation(User $authUser, DirectMessage $message): array
    {
        if (!$this->supportsPinnedMessages()) {
            return [];
        }

        $partnerId = (int) $message->sender_id === (int) $authUser->id
            ? $message->recipient_id
            : $message->sender_id;

        return $this->conversationQueryByPartnerId($authUser, (int) $partnerId)
            ->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (DirectMessage $item) {
                return [
                    'message_id' => $item->id,
                    'preview' => $this->formatReplyPreview($item),
                    'created_at' => $item->created_at?->toIso8601String(),
                    'pinned_at' => $item->pinned_at?->toIso8601String(),
                    'pinned_by_id' => $item->pinned_by_id,
                ];
            })
            ->values()
            ->all();
    }

    protected function conversationPreviewForMessage(DirectMessage $message, User $authUser): array
    {
        $partnerId = (int) $message->sender_id === (int) $authUser->id
            ? $message->recipient_id
            : $message->sender_id;

        $latestMessage = $this->conversationQueryByPartnerId($authUser, (int) $partnerId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return [
            'partner_id' => $partnerId,
            'latest_message' => $latestMessage ? $this->formatMessage($latestMessage, $authUser, true) : null,
            'preview' => $latestMessage ? $this->formatReplyPreview($latestMessage) : 'Start a conversation',
            'latest_at' => $latestMessage?->created_at?->toIso8601String(),
        ];
    }

    protected function supportsPinnedMessages(): bool
    {
        return Schema::hasColumn('direct_messages', 'pinned_at') &&
            Schema::hasColumn('direct_messages', 'pinned_by_id');
    }

    protected function supportsDirectSystemMessages(): bool
    {
        return Schema::hasColumn('direct_messages', 'message_type') &&
            Schema::hasColumn('direct_messages', 'visible_to_user_id');
    }

    protected function supportsReactionFeatures(): bool
    {
        return Schema::hasColumn('direct_messages', 'reaction');
    }

    protected function authorizeMessageParticipant(User $authUser, DirectMessage $message): void
    {
        if (
            (int) $message->sender_id !== (int) $authUser->id &&
            (int) $message->recipient_id !== (int) $authUser->id
        ) {
            abort(403);
        }
    }

    protected function formatMessage(DirectMessage $message, User $authUser, bool $includeReadAt = true): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'message_type' => $message->message_type ?: 'user',
            'is_system' => ($message->message_type ?: 'user') !== 'user',
            'body' => $message->body,
            'reply_to_id' => $message->reply_to_id,
            'reply_preview' => $this->formatReplyPreview($message->replyTo),
            'attachment' => $this->formatAttachment($message),
            'reaction' => $message->reaction,
            'reactions' => $message->getReactionsWithUsers(),
            'pinned_at' => $message->pinned_at?->toIso8601String(),
            'pinned_by_id' => $message->pinned_by_id,
            'is_pinned' => (bool) $message->pinned_at,
            'read_at' => $includeReadAt ? $message->read_at?->toIso8601String() : null,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'unsent_at' => $message->unsent_at?->toIso8601String(),
            'unsent_by_id' => $message->unsent_by_id,
            'is_unsent' => (bool) ($message->is_unsent ?? $message->unsent_at),
            'is_mine' => (int) $message->sender_id === (int) $authUser->id,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    protected function formatReplyPreview(?DirectMessage $message): ?string
    {
        if (!$message) {
            return null;
        }

        if (($message->message_type ?: 'user') !== 'user') {
            return trim((string) $message->body) !== '' ? trim((string) $message->body) : 'Conversation activity';
        }

        if (filled($message->body)) {
            return $message->body;
        }

        if ((bool) ($message->is_unsent ?? $message->unsent_at)) {
            return 'Unsent Message';
        }

        if ($message->attachment_name) {
            return $message->attachment_name;
        }

        if ($message->attachment_path) {
            return basename($message->attachment_path);
        }

        return "Attachment";
    }

    protected function resolveReplyTargetId(User $authUser, User $recipient, mixed $replyTargetId): ?int
    {
        if (!$replyTargetId) {
            return null;
        }

        $replyMessage = $this->conversationQuery($authUser, $recipient)
            ->whereKey((int) $replyTargetId)
            ->first();

        if (!$replyMessage) {
            throw ValidationException::withMessages([
                'reply_to_id' => 'The selected reply target is invalid.',
            ]);
        }

        return (int) $replyMessage->id;
    }

    protected function formatAttachment(DirectMessage $message): ?array
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

    protected function formatConversationInfo(User $authUser, User $user): array
    {
        $nickname = Schema::hasTable('direct_conversation_settings')
            ? DB::table('direct_conversation_settings')
                ->where('user_id', $user->id)
                ->where('partner_id', $authUser->id)
                ->value('nickname')
            : null;
        $selfNickname = Schema::hasTable('direct_conversation_settings')
            ? DB::table('direct_conversation_settings')
                ->where('user_id', $authUser->id)
                ->where('partner_id', $user->id)
                ->value('nickname')
            : null;
        $actualName = $this->resolveDirectConversationActualName($user);

        return [
            'id' => (int) $user->id,
            'conversation_key' => 'direct:' . $user->id,
            'conversation_token' => $this->messagesPageService->conversationToken('direct', (int) $user->id),
            'conversation_type' => 'direct',
            'actual_name' => $actualName,
            'nickname' => filled($nickname) ? (string) $nickname : null,
            'self_nickname' => filled($selfNickname) ? (string) $selfNickname : null,
            'name' => filled($nickname) ? (string) $nickname : $actualName,
        ];
    }

    protected function resolveDirectConversationActualName(User $user): string
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

    protected function displayName(?string $firstname, ?string $lastname, ?string $fallback): string
    {
        $fullName = trim(($firstname ?? '') . ' ' . ($lastname ?? ''));

        return $fullName !== '' ? $fullName : ($fallback ?? 'User');
    }

    protected function formatMediaItem(DirectMessage $message): array
    {
        return [
            'message_id' => (int) $message->id,
            'sender_id' => (int) $message->sender_id,
            'sender_name' => $message->sender?->name ?? 'User',
            'created_at' => $message->created_at?->toIso8601String(),
            'attachment' => $this->formatAttachment($message),
        ];
    }
}
