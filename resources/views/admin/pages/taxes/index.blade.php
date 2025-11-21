@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{ $tax->name }}" subtitle="Manage shift schedule in this module">
            <x-button 
                id="create-btn"
                variant="primary"
            >
            <i class="fa-solid fa-plus me-1"></i>
             Add Year
            </x-button>
        </x-header>

        <tax-index
            slug="{{ $slug }}"
            employee-url="{{ route('tax.employees.index', ['slug' => $slug, 'year' => '__YEAR__']) }}"
            fetch-url="{{ route('tax.index', ['slug' => '__SLUG__']) }}"
            show-url="{{ route('tax.show', ['slug' => '__SLUG__', 'year' => '__YEAR__']) }}"
            update-url="{{ route('tax.update', ['slug' => '__SLUG__', 'year' => '__YEAR__']) }}"
            store-url="{{ route('tax.store', ['slug' => '__SLUG__']) }}"
        />


    </div>
@endsection


