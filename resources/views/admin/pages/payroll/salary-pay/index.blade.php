@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Salary Payroll" subtitle="View salary payroll in this module">
            <x-button-link 
                :href="route('salary-pay.create')" 
                icon="fa-solid fa-upload" 
                text="Import Registry" 
                variant="dark"
            />
            <x-button-link 
                :href="route('salary-pay.create')" 
                icon="fa-solid fa-plus" 
                text="Create Payroll" 
                variant="primary"
            />
        </x-header>

        <salary-pay-index/>
    </div>
@endsection
