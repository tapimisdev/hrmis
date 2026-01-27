@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{ $component->name }}" subtitle="Manage {{ $component->employment_type }} payroll {{ $component->name }} for employees">
            <x-button 
                id="create-btn"
                variant="primary"
            >
            <i class="fa-solid fa-plus me-1"></i>
             Add Year
            </x-button>
        </x-header>

        <payroll-employee-component-index
            slug="{{ $slug }}"
            employee-url="{{ route('payroll-employee-components.index', ['slug' => $slug, 'year' => '__YEAR__']) }}"
            fetch-url="{{ route('payroll-components.index', ['slug' => '__SLUG__']) }}"
            show-url="{{ route('payroll-components.show', ['slug' => '__SLUG__', 'year' => '__YEAR__']) }}"
            update-url="{{ route('payroll-components.update', ['slug' => '__SLUG__', 'year' => '__YEAR__']) }}"
            store-url="{{ route('payroll-components.store', ['slug' => '__SLUG__']) }}"
        />
    </div>
@endsection


