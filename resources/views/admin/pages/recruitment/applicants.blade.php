@extends('admin.layouts.app')

@section('content')
<recruitment-list
    page="applicants"
    :items='@json($applicants->items())'
    :pagination='@json($applicants->toArray())'
    application-base-url="{{ url('/admin/recruitment/applications') }}"
></recruitment-list>
@endsection
