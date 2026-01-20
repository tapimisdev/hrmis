@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Create PERA Payroll" subtitle="Create and manage subsistence and allowance details in this module">
            <x-button-link 
                :href="route('pera-rata.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <pera-rata-stepper/>
    </div>
@endsection