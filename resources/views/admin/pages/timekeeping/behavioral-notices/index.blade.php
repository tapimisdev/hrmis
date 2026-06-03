@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <x-header title="Behavioral Notices" subtitle="Monitor employee behavioral notices in this module">
    </x-header>

    <admin-behavioral-notice-index></admin-behavioral-notice-index>
</div>
@endsection
