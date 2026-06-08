@extends('admin.layouts.app')

@section('styles')
@endsection

@section('content')
    @include('admin.pages.taxation.train-law.create')

    <div class="container-fluid">
        <x-header title="Train Law Items ({{ $trainLaw->year }})" subtitle="Add tax table in this module">

        </x-header>

        <train-law-index
            :train-law-id="{{ $trainLaw->id }}"
            :items="{{ json_encode($trainLaw->items) }}"
         />
    </div>
@endsection