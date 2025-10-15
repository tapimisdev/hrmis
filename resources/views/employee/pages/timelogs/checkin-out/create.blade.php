@extends('employee.layout.app')

@section('content')
<div class="container-fluid pt-3 check-in-out">
    
    <x-header title="Check In and Out" subtitle="Manage check in and outs in this module">
        <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
        <a href="{{ route('checkinout.index') }}" class="btn btn-primary py-3 px-4">
            <i class="fa-solid fa-list me-2"></i> My Logs
        </a>
    </x-header>


</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
</script>
@endsection
