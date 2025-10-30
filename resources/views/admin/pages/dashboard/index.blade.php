@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">

    <x-header 
        title="Dashboard" 
        subtitle="Lorem ipsum dolor sit amet consectetur adipisicing elit. Eligendi sit reiciendis officia! Neque porro eligendi harum repellat error. Totam, quidem!">
    </x-header>
    
    <dashboard-vue></dashboard-vue>
</div>
@endsection
