@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Longevity Payroll" subtitle="View longevity pay payroll in this module">
            <x-button-link
                :href="route('longevity-pay.create')"
                icon="fa-solid fa-plus"
                text="Create "
                variant="primary"
            />
        </x-header>

        <longevity-pay-index/>
    </div>
@endsection
