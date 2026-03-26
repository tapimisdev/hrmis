@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Hazard Payroll" subtitle="View hazard payroll in this module">
            <x-button-link
                :href="route('registry.hazard.index')"
                icon="fa-solid fa-upload"
                text="Import Registry"
                variant="dark"
            />

            <x-button-link 
                :href="route('hazard-pay.create')" 
                icon="fa-solid fa-plus" 
                text="Create " 
                variant="primary"
            />
        </x-header>

        <hazard-pay-index/>
    </div>
@endsection
