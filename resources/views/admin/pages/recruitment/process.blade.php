@extends('admin.layouts.app')

@section('content')
<recruitment-list
    page="process"
    :items='@json($applications->items())'
    :pagination='@json($applications->toArray())'
    :stages='@json($stages)'
    application-base-url="{{ url('/admin/recruitment/applications') }}"
></recruitment-list>
@endsection
