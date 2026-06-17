@extends('admin.layouts.app')

@section('content')
<recruitment-jobs
    :initial-jobs='@json($jobs->items())'
    store-url="{{ route('recruitment.jobs.store') }}"
    process-url="{{ route('recruitment.process') }}"
    :can-manage='@json(auth()->user()->can("hr.recruitment.manage"))'
></recruitment-jobs>
@endsection
