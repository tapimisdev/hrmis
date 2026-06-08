@extends('admin.layouts.app')

@section('styles')
@endsection

@section('content')
    @include('admin.pages.taxation.train-law.create')

    <div class="container-fluid">
        <taxation-index/>
    </div>
@endsection