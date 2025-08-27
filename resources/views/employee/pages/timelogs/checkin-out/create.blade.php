@extends('employee.layout.app')

@section('content')
<div class="container check-in-out pt-4">
    
    <x-header title="Check In and Out" subtitle="Manage check in and outs in this module">
        <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
        <a href="{{ route('checkinout.index') }}" class="btn btn-primary py-3 px-4">
            <i class="fa-solid fa-list me-2"></i> My Logs
        </a>
    </x-header>

    <check-in-out-vue></check-in-out-vue>

    <div class="fw-bold d-flex justify-content-between time text-uppercase">
        <p id="current-date">{{ \Carbon\Carbon::now()->format('l, F d, Y') }}</p>
        <p id="current-time">{{ \Carbon\Carbon::now()->format('h:i A') }}</p>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function updateTime() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const timeString = hours.toString().padStart(2, '0') + ':' + minutes + ' ' + ampm;
        $('#current-time').text(timeString);
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>
@endsection
