@extends('applicant.layout')

@section('content')
<applicant-dashboard
    :initial-profile='@json($profile)'
    jobs-url="{{ route('careers.jobs') }}"
    signed-offer-base-url="{{ url('/applicant/applications') }}"
    requirements-base-url="{{ url('/applicant/applications') }}"
></applicant-dashboard>
@endsection
