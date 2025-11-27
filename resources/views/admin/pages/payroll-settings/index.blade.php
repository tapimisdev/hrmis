@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Payroll Settings" subtitle="View payroll settings in this module">
        </x-header>
        <payroll-settings 
            save="{{ route('settings.payroll-settings.save') }}"
            :menu='@json($menu)'
        />
    </div>
@endsection
