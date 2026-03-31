@extends('employee.layout.messages')

@section('content')
    <messages-page
        :initial-users='@json($conversations)'
        :initial-available-users='@json($availableUsers)'
        :initial-pending-group-chat-approvals='@json($pendingGroupChatApprovals)'
        :initial-group-chat-request-history='@json($groupChatRequestHistory)'
        :auth-user='@json($authUser)'
        :initial-selected-conversation-key='@json($selectedConversationKey)'
        :messages-base-url='@json(route("employee.messages"))'
        :csrf-token='@json(csrf_token())'
    ></messages-page>
@endsection
