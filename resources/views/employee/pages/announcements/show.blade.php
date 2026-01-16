@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        <x-employee-navbar>
            <header-vue title="DOST TAPI"></header-vue>
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
                Str::limit($data["announcement"]->slug, 10) 
            ]'
        >
            <a href="{{ route('announcement.index') }}" class="btn btn-danger py-3 px-4 me-5">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header-employee>
        
        <show :data='@json($data)'/>
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
