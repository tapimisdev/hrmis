<?php

namespace App\Http\Controllers\Employee;

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
        $authUser = $request->user();
        $selectedConversationKey = $this->messagesPageService->conversationKeyFromToken($conversationToken)
            ?? $request->query('conversation');

        if (!$selectedConversationKey && $request->filled('user')) {
            $selectedConversationKey = 'direct:' . (int) $request->query('user');
        }

        $pageData = $this->messagesPageService->build($authUser, $selectedConversationKey);

        if (!$conversationToken && !$request->filled('conversation') && !$request->filled('user')) {
            $selectedConversationToken = $pageData['selectedConversationToken'] ?? null;

            if ($selectedConversationToken) {
                return redirect()->route('employee.messages', [
                    'conversationToken' => $selectedConversationToken,
                ]);
            }
        }

        return view('employee.pages.messages.index', $pageData);
    }
}
