@extends('admin.layouts.app')

@section('content')
    <bir-2316-index
        :base-url='@json(route('taxation.bir-2316.index'))'
        :api-url='@json(route('taxation.bir-2316.index'))'
        :generate-url='@json(route('taxation.bir-2316.generate'))'
        :initial-filters='@json($filters)'
        :available-years='@json($availableYears)'
        :employees='@json($employees)'
        :divisions='@json($divisions)'
        :employment-types='@json($employmentTypes)'
        :statuses='@json($statuses)'
        :initial-rows='@json($rows)'
        :initial-summary='@json($summary)'
    />
@endsection
