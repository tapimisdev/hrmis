@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Salary Taxes" subtitle="Manage shift schedule in this module">
            <x-button 
                id="create-btn"
                variant="primary"
            >
            <i class="fa-solid fa-plus me-1"></i>
             Add Year
            </x-button>
        </x-header>

        <tax-index 
            employee-url="{{ route('tax.salary.employees.index', ['salary_tax' => '__ID__']) }}"
            fetch-url="{{ route('tax.salary.index') }}"
            show-url="{{ route('tax.salary.show', ['salary' => '__ID__']) }}"
            update-url="{{ route('tax.salary.update', ['salary' => '__ID__']) }}"
            store-url="{{ route('tax.salary.store') }}"
        />

    </div>
@endsection


