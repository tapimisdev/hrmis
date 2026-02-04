@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
       <x-header title="Import Credits" subtitle="Upload and manage employee credits by importing files into the system.">
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <import-credits 
            :credit-types='@json($credit_types)' 
            :leave-types='@json($leave_types)' 
            :employee-no='@json($employee_no)'
        ></import-credits>
        
    </div>
@endsection

@section('scripts')