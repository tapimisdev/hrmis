@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Salary Taxes" subtitle="Manage shift schedule in this module">
            <x-button-link 
                :href="route('tax.index', ['slug' => $slug])" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <tax-table
            url="{{ $url }}"
            selected_employee="{{ $selectedEmployee }}"
            :parent_table='@json($component)'
        />
    </div>
@endsection