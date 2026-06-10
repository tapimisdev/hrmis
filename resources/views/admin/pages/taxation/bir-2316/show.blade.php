@extends('admin.layouts.app')

@section('content')
    <bir-2316-view
        :api-url='@json(route('taxation.bir-2316.show', $recordId))'
        :back-url='@json(route('taxation.bir-2316.index'))'
        :preview-url='@json(route('taxation.bir-2316.print', $recordId))'
        :pdf-download-url='@json(route('taxation.bir-2316.pdf', $recordId))'
        :excel-download-url='@json(route('taxation.bir-2316.excel', $recordId))'
    />
@endsection
