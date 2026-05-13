@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{ ucwords($payroll->label) }}" subtitle="Payroll reference no: {{ $payroll->payroll_no }}">
            <x-button-link
                :href="route('longevity-pay.index')"
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
            class="js-back-with-fallback"
        />
        </x-header>

        <longevity-pay-view
            :batch_id="{{ json_encode($batch_id ?? null) }}"
            :payroll_no="{{ json_encode($payroll->payroll_no) }}"
            :payroll_id="{{ json_encode($payroll->id) }}"
            :status="{{ json_encode($payroll->status) }}"
            :employment_type="{{ json_encode($employmentTypeName) }}"
        ></longevity-pay-view>
    </div>
@endsection
