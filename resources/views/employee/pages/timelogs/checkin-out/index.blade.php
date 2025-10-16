@extends('employee.layout.app')

@section('content')
<div class="container-fluid pt-3 check-in-out ">
    
    <header-vue title="DOST TAPI"></header-vue>
        
    <x-header-employee title="Timelogs" subtitle="View your timelogs in this module">

    </x-header-employee>

    <check-in-out-vue></check-in-out-vue>

    <x-table-employee id="myTable">
        <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Break Out</th>
                <th>Break In</th>
                <th>Time Out</th>
                <th>Overtime In</th>
                <th>Overtime Out</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </x-table-employee>
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
                { data: "overtime_in", name: 'overtime_in'},
                { data: "overtime_out", name: 'overtime_out'},
            ],
        });

        window.addEventListener('reload-datatable', function () {
            DataTable.ajax.reload(null, false);
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
