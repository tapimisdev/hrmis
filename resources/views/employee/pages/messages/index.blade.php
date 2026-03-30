@extends('employee.layout.messages')

@section('content')
    <messages-page
        :initial-users='@json($users)'
        :auth-user='@json($authUser)'
        :initial-selected-user-id='@json($selectedUserId)'
        :csrf-token='@json(csrf_token())'
    ></messages-page>
@endsection
