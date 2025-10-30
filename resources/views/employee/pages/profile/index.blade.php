@extends('employee.layout.app')

@section('content')
<div class="container-fluid pb-4 check-in-out ">
    
    <header-vue title="DOST TAPI"></header-vue>
        
    <x-header-employee title="Profile" subtitle="Profile ko to ha!">

    </x-header-employee>

    <profile-index/>

    
</div>
@endsection
