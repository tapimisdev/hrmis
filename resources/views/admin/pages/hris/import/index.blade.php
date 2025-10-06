@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
       <x-header title="Import Employees" subtitle="Upload and manage employee records by importing files into the system.">
            <a href="{{route('hris.employee.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header>

        <import-employee-vue/>

    </div>
@endsection

@section('scripts')