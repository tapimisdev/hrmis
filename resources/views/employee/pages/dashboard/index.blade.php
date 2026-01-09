@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    <x-employee-navbar>
        <header-vue title="Dashboard"></header-vue>
    </x-employee-navbar>

    @php
        $isRegular = Auth::user()->employment_type_id == \App\Enums\EmploymentTypesEnum::REGULAR->value;
    @endphp
    
    <dashboard-index
        :is-regular='@json($isRegular)'
        name="{{ $name }}"
    ></dashboard-index>



</div>
@endsection

@section('scripts')
@endsection