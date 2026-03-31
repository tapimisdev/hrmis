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

    public function index(Request $request)
    {
        $selectedConversationKey = $request->query('conversation');

        if (!$selectedConversationKey && $request->filled('user')) {
            $selectedConversationKey = 'direct:' . (int) $request->query('user');
        }

        $pageData = $this->messagesPageService->build($request->user(), $selectedConversationKey);
        $pageData['messageUserRole'] = 'admin';

        return view('admin.pages.messages.index', $pageData);
    }
}
