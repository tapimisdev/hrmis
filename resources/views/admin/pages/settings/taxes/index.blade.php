@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Taxes" subtitle="Manage taxes for this employment type">
        </x-header>

        <tax-settings 
            :url="'{{ route('settings.taxes.save') }}'"
            :taxes="{{ json_encode($taxes) }}"
            :menu="{{ json_encode($menu) }}"
        />

    </div>
@endsection


