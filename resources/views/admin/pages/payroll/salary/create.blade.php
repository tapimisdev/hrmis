@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Create Salary Payroll" subtitle="Create and manage salary payroll details in this module">
            <a href="{{ route('salary.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header>

        <payroll-stepper/>
    </div>
@endsection