<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Http\Request;

class DirectMessageController extends Controller
{
    public function index(Request $request, User $user)
    {
        $authUser = $request->user();

        $messages = DirectMessage::query()
            ->with('replyTo:id,body,sender_id,recipient_id,created_at')
            ->where(function ($query) use ($authUser, $user) {
                $query->where('sender_id', $authUser->id)
                    ->where('recipient_id', $user->id);
            })
            ->orWhere(function ($query) use ($authUser, $user) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $authUser->id);
            })
            ->orderBy('created_at')
            ->get()
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
                    'is_mine' => (int) $message->sender_id === (int) $authUser->id,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'messages' => $messages,
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
            'is_mine' => true,
            'created_at' => $message->created_at?->toIso8601String(),
        ];

        event(new DirectMessageSent($payload));

        return response()->json([
            'message' => $payload,
        ], 201);
    }
}
