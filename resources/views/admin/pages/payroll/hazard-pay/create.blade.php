@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Create Hazard Pay Payroll" subtitle="Create and manage salary payroll details in this module">
            <x-button-link 
                :href="route('hazard-pay.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <hazard-pay-stepper/>
    </div>
@endsection