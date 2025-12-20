@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    <x-employee-navbar>
        <header-vue title="Dashboard"></header-vue>
    </x-employee-navbar>

    <dashboard-index name="{{ $name }}"></dashboard-index>

</div>
@endsection

@section('scripts')
@endsection