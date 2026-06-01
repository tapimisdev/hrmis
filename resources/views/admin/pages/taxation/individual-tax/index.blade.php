@extends('admin.layouts.app')

@section('content')
    <individual-tax-index
        :base-url='@json(route('taxation.individual-tax.index'))'
        :api-url='@json(url('/api/tax/individual-tax'))'
        :employee='@json($employee)'
        :employees='@json($employees)'
        :selected-year='@json($selectedYear)'
        :available-years='@json($availableYears)'
        :monthly-breakdown='@json($monthlyBreakdown)'
        :other-components='@json($otherComponents)'
        :summary='@json($summary)'
    />
@endsection
