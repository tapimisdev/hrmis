@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Salary Payroll" subtitle="View salary payroll in this module">
            <button class="btn btn-secondary text-uppercase">
                <i class="fa fa-plus py-3 px-2"></i> Create Payroll
            </button>
        </x-header>

        <payroll-index/>
    </div>
@endsection