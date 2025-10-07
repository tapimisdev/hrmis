@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Upload Timelog" subtitle="Import timelogs in this module">
            <a href="{{ route('deductions.create') }}" class="btn btn-secondary py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-plus me-2"></i> Import
            </a>
        </x-header>

        <upload-timelog-vue/>
    </div>
@endsection


