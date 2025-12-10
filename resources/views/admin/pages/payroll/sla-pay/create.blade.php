@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Create Subsistence and Allowance Payroll" subtitle="Create and manage subsistence and allowance details in this module">
            <x-button-link 
                :href="route('sla-pay.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <sla-pay-stepper/>
    </div>
@endsection