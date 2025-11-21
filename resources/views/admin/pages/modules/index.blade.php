@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{ $module->module_name }}" subtitle="Manage {{ $module->module_name }} in this module">
            @if (count($tabs) > 0)
                <x-button 
                    id="create-btn"
                    variant="primary"
                >
                <i class="fa-solid fa-plus me-1"></i>
                Add Tab
                </x-button>
            @endif
        </x-header>

        <tab-module
            :tabs='@json($tabs)'
            store_url="{{ $store_url }}"
            slug="{{ $slug }}"
            highest_order="{{ $highest_order }}"
        />
    </div>
@endsection


