@extends('admin.layouts.app')

@php
    $initialFilters = [
        'taxable_year' => null,
        'employee_id' => null,
        'division_id' => null,
        'employment_type_id' => null,
        'status' => '',
    ];

    $initialSummary = [
        'net_taxable_income' => 0,
        'annual_tax_due' => 0,
        'tax_withheld' => 0,
        'tax_refund_or_payable' => 0,
    ];
@endphp

@section('content')
    <bir-2316-index
        :base-url="@json(route('taxation.bir-2316.index'))"
        :api-url="@json(route('taxation.bir-2316.index'))"
        :generate-url="@json(route('taxation.bir-2316.generate'))"
        :initial-filters='@json($initialFilters)'
        :available-years="@json([])"
        :employees="@json([])"
        :divisions="@json([])"
        :employment-types="@json([])"
        :statuses="@json([])"
        :initial-rows="@json([])"
        :initial-summary='@json($initialSummary)'
        :initial-view-id="@json($recordId)"
    />
@endsection
