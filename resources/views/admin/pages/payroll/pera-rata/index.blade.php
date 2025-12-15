@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="PERA & RATA Payroll" subtitle="View Personnel Economic Relief Allowance and Representation and Transportation Allowance payroll in this module">

            <x-button-link 
                :href="route('pera-rata.create')" 
                icon="fa-solid fa-plus" 
                text="Create " 
                variant="primary"
            />
        </x-header>

        <pera-rata-index/>
    </div>
@endsection
