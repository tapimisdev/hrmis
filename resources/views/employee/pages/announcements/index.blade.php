@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        <x-employee-navbar>
            <header-vue title="DOST TAPI"></header-vue>
        </x-employee-navbar>

        <x-header-employee
            subtitle="View all announcements" 
            :breadcrumbs="['Pages', 'Dashboard', 'Announcements']"
        >
        </x-header-employee>
        
        <Announcements/>

    </div>
@endsection