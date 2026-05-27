@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Create Longevity Payroll" subtitle="Create and manage longevity pay details in this module">
            <x-button-link
                :href="route('longevity-pay.index')"
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
            class="js-back-with-fallback"
        />
        </x-header>

        <longevity-pay-stepper/>
    </div>
@endsection
