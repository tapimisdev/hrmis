@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Upload Timelog" subtitle="Import timelogs in this module">
 
        </x-header>

        <upload-timelog-vue/>
    </div>
@endsection


