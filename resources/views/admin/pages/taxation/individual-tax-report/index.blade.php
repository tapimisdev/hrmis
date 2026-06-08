@extends('admin.layouts.app')

@section('content')
    <individual-tax-monthly-report-index
        :api-url='@json(url('/api/tax/individual-tax-monthly-report'))'
        :selected-year='@json($selectedYear)'
        :selected-month='@json($selectedMonth)'
        :available-years='@json($availableYears)'
        :month-options='@json($monthOptions)'
        :initial-rows='@json($rows)'
        :initial-has-taxation-data='@json($hasTaxationData)'
    />
@endsection
