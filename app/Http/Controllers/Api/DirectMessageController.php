<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectMessageSeen;
use App\Events\DirectMessageSent;
use App\Events\DirectMessageTyping;
use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DirectMessageController extends Controller
{
    public function index(Request $request, User $user)
    {
        $authUser = $request->user();
        $perPage = max(10, min((int) $request->input('per_page', 20), 100));
        $page = max(1, (int) $request->input('page', 1));

        $query = DirectMessage::query()
            ->with('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type')
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
                    'reply_preview' => $this->formatReplyPreview($message->replyTo),
                    'attachment' => $this->formatAttachment($message),
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
            'reply_to_id' => ['nullable', 'exists:direct_messages,id'],
            'attachment' => [
                'nullable',
                'file',
                'max:5120',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension() ?: '');
                    $allowedExtensions = [
                        'jpg',
                        'jpeg',
                        'png',
                        'gif',
                        'doc',
                        'docx',
                        'docs',
                        'pdf',
                        'xlsx',
                        'txt',
                    ];

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $fail('The attachment must be a valid image or document file.');
                    }
                },
            ],
        ]);

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
            'sender_id' => $request->user()->id,
            'recipient_id' => $validated['recipient_id'],
            'body' => trim((string) ($validated['body'] ?? '')),
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
            'attachment_extension' => $attachmentExtension,
            'attachment_type' => $attachmentType,
        ]);

        $message->load('replyTo:id,body,sender_id,recipient_id,created_at,attachment_path,attachment_name,attachment_mime,attachment_size,attachment_extension,attachment_type');

        $payload = $this->formatMessage($message, $request->user(), true);

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

    public function typing(Request $request, User $user)
    {
        $authUser = $request->user();

        event(new DirectMessageTyping([
            'sender_id' => $authUser->id,
            'recipient_id' => $user->id,
            'is_typing' => (bool) $request->boolean('is_typing', true),
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

    protected function formatMessage(DirectMessage $message, User $authUser, bool $includeReadAt = true): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'body' => $message->body,
            'reply_to_id' => $message->reply_to_id,
            'reply_preview' => $this->formatReplyPreview($message->replyTo),
            'attachment' => $this->formatAttachment($message),
            'read_at' => $includeReadAt ? $message->read_at?->toIso8601String() : null,
            'is_mine' => (int) $message->sender_id === (int) $authUser->id,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    protected function formatReplyPreview(?DirectMessage $message): ?string
    {
        if (!$message) {
            return null;
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

        return "Attachment";
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
}
