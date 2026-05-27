@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Subsistence and Allowance Payroll" subtitle="View susistence and allowance payroll in this module">
            <x-button-link
                :href="route('subsistence-allowance.index')"
                icon="fa-solid fa-utensils"
                text="Subsistence Allowance"
                variant="primary"
            />

            <x-button-link
                :href="route('registry.sla.index')"
                icon="fa-solid fa-upload"
                text="Import Registry"
                variant="dark"
            />

            <x-button-link 
                :href="route('sla-pay.create')" 
                icon="fa-solid fa-plus" 
                text="Create " 
                variant="primary"
            />
        </x-header>

        <sla-pay-index/>
    </div>
@endsection
