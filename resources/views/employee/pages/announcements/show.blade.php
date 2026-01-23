@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        <x-employee-navbar>
            <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
        </x-employee-navbar>

        @php
            use Illuminate\Support\Str;
        @endphp

        <x-header-employee
            subtitle="View this announcement"
            :breadcrumbs='[
                "Pages", 
                "Dashboard", 
                "Announcements", 
                ucwords(Str::limit($announcement->slug, 10)) 
            ]'
        >
            <a href="{{ route('announcement.index') }}" class="btn btn-danger py-3 px-4 me-5">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header-employee>
        
        <show :slug='@json($announcement->slug)' />
    </div>
@endsection

@section('styles')
 <style>
    @media (max-width: 768px) {
        .btn {
        margin-top: 20px;
        }
    }
</style>
@endsection
