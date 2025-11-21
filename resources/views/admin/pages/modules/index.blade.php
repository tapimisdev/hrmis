@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{ $module->module_name }}" subtitle="Manage {{ $module->module_name }} in this module">

        </x-header>

        <tab-module
            :tabs='@json($tabs)'
            tab_name="{{ $tab_name }}"
        />
    </div>
@endsection


