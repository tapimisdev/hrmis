@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="{{ ucwords($payroll->label) }}" subtitle="Payroll reference no: {{ $payroll->payroll_no }}">
            <x-button-link 
                :href="route('hazard-pay.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <hazard-pay-view
            :batch_id="{{ json_encode($batch_id ?? null) }}"
            :payroll_no="{{ json_encode($payroll->payroll_no) }}"
            :payroll_id="{{ json_encode($payroll->id) }}"
            :status="{{ json_encode($payroll->status) }}"
            :employment_type="{{ json_encode($employmentTypeName) }}"
        ></hazard-pay-view>
    </div>
@endsection