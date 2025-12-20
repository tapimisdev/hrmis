@extends('employee.layout.app')

@section('content')
<div class="container-fluid pb-4 check-in-out position-static min-vh-100">
    
    <x-employee-navbar>
        <header-vue title="DOST TAPI"></header-vue>
    </x-employee-navbar>
        
    <x-header-employee title="Timelogs" subtitle="View your timelogs in this module">

    </x-header-employee>

    <check-in-out-vue></check-in-out-vue>

    <employee-timelog :employee-number='@json($employee_no)' /> 
    
</div>
@endsection
