@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Web time Access" subtitle="Give permission to use web time">
            
        </x-header>
        <webtime-index url="{{route('webtime.index')}}"/>
    </div>
@endsection