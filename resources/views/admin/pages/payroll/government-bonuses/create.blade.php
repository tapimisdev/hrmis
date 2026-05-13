@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Create Government Bonus Payroll" subtitle="Create and manage government bonus payroll details in this module">
            <x-button-link
                :href="route('government-bonuses.index')"
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
            class="js-back-with-fallback"
        />
        </x-header>

        <government-bonus-stepper/>
    </div>
@endsection
