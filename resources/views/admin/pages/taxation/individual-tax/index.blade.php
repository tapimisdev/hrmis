@extends('admin.layouts.app')

@section('content')
    <individual-tax-index
        :base-url='@json(route('taxation.individual-tax.index'))'
        :save-url='@json(route('taxation.individual-tax.save'))'
        :api-url='@json(url('/api/tax/individual-tax'))'
        :employee='@json($employee)'
        :employees='@json($employees)'
        :selected-year='@json($selectedYear)'
        :available-years='@json($availableYears)'
        :monthly-breakdown='@json($monthlyBreakdown)'
        :other-components='@json($otherComponents)'
        :summary='@json($summary)'
        :train-law-options='@json($trainLawOptions)'
        :selected-train-law-id='@json($selectedTrainLawId)'
    />
@endsection
