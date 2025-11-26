@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Payroll Components" subtitle="Manage payroll components in this module">
            <x-button-link 
                :href="route('settings.payroll-components.create')" 
                id="create-btn"
                variant="primary"
            >
            <i class="fa-solid fa-plus me-1"></i>
             Add Entry
            </x-button-link>
        </x-header>

        <payroll-component-index
            fetch-url="{{ route('settings.payroll-components.index') }}"
            update-url="{{ route('settings.payroll-components.update', ['id' => '__ID__']) }}"
            delete-url="{{ route('settings.payroll-components.delete', ['id' => '__ID__']) }}"
        />


    </div>
@endsection


