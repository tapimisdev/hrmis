@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Salary Payroll" subtitle="View salary payroll in this module">
            <a href="{{ route('salary.create') }}" class="btn btn-secondary text-uppercase create-payroll">
                <i class="fa fa-plus py-3 px-2"></i> Create Payroll
            </a>
        </x-header>

        <payroll-index/>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

    })
</script>
@endsection