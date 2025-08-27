@extends('employee.layout.app')

@section('content')
<div class="container check-in-out pt-4">
    
    <x-header title="Timelogs" subtitle="View your timelogs in this module">
        <a href="{{ route('dashboard.index') }}" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
        <a href="{{ route('checkinout.create') }}" class="btn btn-primary py-3 px-4">
            <i class="fa-solid fa-list me-2"></i> Check In/Out
        </a>
    </x-header>

    <x-table id="myTable">
        <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Break Out</th>
                <th>Break In</th>
                <th>Time Out</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </x-table>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('checkinout.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "date", name: 'date' },
                { data: "time_in", name: 'time_in' },
                { data: "break_out", name: 'break_out' },
                { data: "break_in", name: 'break_in' },
                { data: "time_out", name: 'time_out'},
            ],
        });
    });
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
