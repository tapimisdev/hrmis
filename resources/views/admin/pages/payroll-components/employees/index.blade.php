@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{ $component->name }}" subtitle="Manage employee data in this module">
            <x-button-link 
                :href="route('payroll-components.index', ['slug' => $slug])" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        
        <payroll-employee-component-form
            url="{{ $url }}"
            selected_employee="{{ $selectedEmployee }}"
            year="{{ $year }}"
            :parent_table='@json($component)'
        />
    </div>
@endsection