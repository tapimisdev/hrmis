@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        
        <x-employee-navbar>
            <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Payslips" subtitle="View payslip in this module" >

        </x-header-employee>

        <payslip-index></payslip-index>
        
    </div>
@endsection

@section('scripts')
<script>
    
</script>
@endsection