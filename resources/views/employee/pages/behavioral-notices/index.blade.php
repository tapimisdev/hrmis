@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        <x-employee-navbar>
            <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Behavioral Notices" subtitle="View attendance and conduct notices in this module">
        </x-header-employee>

        <behavioral-notice-index></behavioral-notice-index>
    </div>
@endsection
