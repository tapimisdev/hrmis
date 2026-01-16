@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">

    <x-header 
        title="Dashboard" 
        subtitle="Monitor employee attendance, workforce movement, and daily activity across the organization. Gain real-time insights into operational trends and key performance indicators. Use this dashboard to support informed decision-making and efficient workforce management.">
    </x-header>
    
    <dashboard-vue></dashboard-vue>
</div>
@endsection
