@extends('employee.layout.app')

@section('content')
<div class="container-fluid pb-4 check-in-out position-static min-vh-100">
    
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>
        
    <x-header-employee title="Timelogs" subtitle="View your timelogs in this module">

    </x-header-employee>
    
    <index-vue :is-allowed="@json($is_allowed)" :employee-number='@json($employee_no)' :supervisor='@json($supervisor)' :is-required-ar='@json($isRequiredAR)' />
    
</div>
@endsection
