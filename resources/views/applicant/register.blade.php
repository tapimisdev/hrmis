@extends('applicant.layout')

@section('content')
<applicant-register
    :interests='@json($interests)'
    store-url="{{ route('applicant.register.store') }}"
></applicant-register>
@endsection
