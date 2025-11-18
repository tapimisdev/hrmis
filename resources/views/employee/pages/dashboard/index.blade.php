@extends('employee.layout.app')

@section('content')
<div class="container-fluid">
    <x-employee-navbar>
        <header-vue title="Dashboard"></header-vue>
    </x-employee-navbar>

    <dashboard-index name="{{ $name }}"></dashboard-index>

</div>
@endsection

@section('scripts')
@endsection