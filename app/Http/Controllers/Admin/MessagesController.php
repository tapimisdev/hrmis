<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MessagesPageService;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function __construct(
        protected MessagesPageService $messagesPageService
    ) {
    }

    public function index(Request $request, ?string $conversationToken = null)
    {
        $selectedConversationKey = $this->messagesPageService->conversationKeyFromToken($conversationToken)
            ?? $request->query('conversation');

        if (!$selectedConversationKey && $request->filled('user')) {
            $selectedConversationKey = 'direct:' . (int) $request->query('user');
        }

        $pageData = $this->messagesPageService->build($request->user(), $selectedConversationKey);
        $pageData['messageUserRole'] = 'admin';

        if (!$conversationToken && !$request->filled('conversation') && !$request->filled('user')) {
            $selectedConversationToken = $pageData['selectedConversationToken'] ?? null;

            if ($selectedConversationToken) {
                return redirect()->route('admin.messages', [
                    'conversationToken' => $selectedConversationToken,
                ]);
            }
        }

        return view('admin.pages.messages.index', $pageData);
    }
}
