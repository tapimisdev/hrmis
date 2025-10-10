@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
       <x-header title="Import Employees" subtitle="Upload and manage employee records by importing files into the system.">
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <import-employee-vue/>

    </div>
@endsection

@section('scripts')