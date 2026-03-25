<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectMessageSeen;
use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class DirectMessageController extends Controller
{
    public function index(Request $request, User $user)
    {
        $authUser = $request->user();
        $perPage = max(10, min((int) $request->input('per_page', 20), 100));
        $page = max(1, (int) $request->input('page', 1));

        $query = DirectMessage::query()
            ->with('replyTo:id,body,sender_id,recipient_id,created_at')
            ->where(function ($query) use ($authUser, $user) {
                $query->where('sender_id', $authUser->id)
                    ->where('recipient_id', $user->id);
            })
            ->orWhere(function ($query) use ($authUser, $user) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $authUser->id);
            });

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
                    'body' => $message->body,
                    'reply_to_id' => $message->reply_to_id,
                    'reply_to' => $message->replyTo ? [
                        'id' => $message->replyTo->id,
                        'body' => $message->replyTo->body,
                        'sender_id' => $message->replyTo->sender_id,
                        'recipient_id' => $message->replyTo->recipient_id,
                        'created_at' => $message->replyTo->created_at?->toIso8601String(),
                    ] : null,
                    'read_at' => $message->read_at?->toIso8601String(),
                    'is_mine' => (int) $message->sender_id === (int) $authUser->id,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
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
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
            'body' => ['required', 'string', 'max:2000'],
            'reply_to_id' => ['nullable', 'exists:direct_messages,id'],
        ]);

        $message = DirectMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $validated['recipient_id'],
            'body' => $validated['body'],
            'reply_to_id' => $validated['reply_to_id'] ?? null,
        ]);

        $payload = [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'body' => $message->body,
            'reply_to_id' => $message->reply_to_id,
            'read_at' => null,
            'is_mine' => true,
            'created_at' => $message->created_at?->toIso8601String(),
        ];

        event(new DirectMessageSent($payload));

        return response()->json([
            'message' => $payload,
        ], 201);
    }

    public function seen(Request $request, User $user)
    {
        $authUser = $request->user();
        $readAt = Carbon::now();

        $messageIds = DirectMessage::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
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

    protected function markConversationAsSeen(User $authUser, User $user): void
    {
        $messageIds = DirectMessage::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
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
}
