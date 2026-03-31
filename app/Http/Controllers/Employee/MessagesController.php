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

    public function index(Request $request)
    {
        $authUser = $request->user();
        $selectedConversationKey = $request->query('conversation');

        if (!$selectedConversationKey && $request->filled('user')) {
            $selectedConversationKey = 'direct:' . (int) $request->query('user');
        }

        $pageData = $this->messagesPageService->build($authUser, $selectedConversationKey);

        return view('employee.pages.messages.index', $pageData);
    }
}
