@extends('admin.layouts.app')

@section('content')
<recruitment-list
    page="assessments"
    :items='@json($assessments->items())'
    :pagination='@json($assessments->toArray())'
    application-base-url="{{ url('/admin/recruitment/applications') }}"
></recruitment-list>
@endsection
