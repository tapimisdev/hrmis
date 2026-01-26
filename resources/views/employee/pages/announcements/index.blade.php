@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        <x-employee-navbar>
            <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
        </x-employee-navbar>

        <x-header-employee
            subtitle="View all announcements" 
            :breadcrumbs="['Pages', 'Announcements']"
        >
        </x-header-employee>
        
        <Announcements/>

    </div>
@endsection