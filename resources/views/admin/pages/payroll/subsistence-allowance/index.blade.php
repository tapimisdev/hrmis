@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Subsistence Allowance" subtitle="Manage actual service records for Subsistence Allowance computation">
            <x-button-link
                :href="route('sla-pay.index')"
                icon="fa-solid fa-arrow-left"
                text="Go Back"
                variant="dark"
            />
        </x-header>

        <subsistence-allowance-index/>
    </div>
@endsection
