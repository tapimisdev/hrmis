@extends('employee.layout.app')

@section('content')
<div class="container-fluid pb-4 check-in-out min-vh-100">
    
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>
        
    <x-header-employee title="Profile" subtitle="View and manage your information">

    </x-header-employee>

    <profile-index :session-id='@json($session_id)'/>

    
</div>
@endsection
