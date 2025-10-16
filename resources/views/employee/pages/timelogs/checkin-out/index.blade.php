@extends('employee.layout.app')

@section('content')
<div class="container-fluid pt-3 pb-4 check-in-out ">
    
    <header-vue title="DOST TAPI"></header-vue>
        
    <x-header-employee title="Timelogs" subtitle="View your timelogs in this module">

    </x-header-employee>

    <check-in-out-vue></check-in-out-vue>

    <employee-timelog :employee-number='@json($employee_no)' /> 
    
</div>
@endsection
