@extends('employee.layout.messages')

@section('content')
    <messages-page
        :initial-users='@json($conversations)'
        :initial-available-users='@json($availableUsers)'
        :initial-pending-group-chat-approvals='@json($pendingGroupChatApprovals)'
        :auth-user='@json($authUser)'
        :initial-selected-conversation-key='@json($selectedConversationKey)'
        :csrf-token='@json(csrf_token())'
    ></messages-page>
@endsection
