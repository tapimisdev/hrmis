@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="{{ ucwords($payroll->label) }}" subtitle="Payroll reference no: {{ $payroll->payroll_no }}">
            <x-button-link 
                :href="route('salary-pay.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <show-payroll
            :batch_id="{{ json_encode($batch_id ?? null) }}"
            :payroll_no="{{ json_encode($payroll->payroll_no) }}"
            :payroll_id="{{ json_encode($payroll->id) }}"
            :status="{{ json_encode($payroll->status) }}"
            :is_aut_deducted="{{ json_encode((bool) ($payroll->is_aut_deducted ?? false)) }}"
            :employment_type="{{ json_encode($employmentTypeName) }}"
            :period_covered="{{ json_encode($payroll->period_covered) }}"
        ></show-payroll>
    </div>
@endsection
