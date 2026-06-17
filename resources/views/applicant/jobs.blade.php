@extends('applicant.layout')

@section('content')
<applicant-jobs
    :initial-jobs='@json($jobs->items())'
    :authenticated='@json(auth()->check())'
    apply-base-url="{{ url('/applicant/jobs') }}"
    register-url="{{ route('applicant.register') }}"
></applicant-jobs>
@endsection
