@extends('admin.layouts.app')


@section('content')
<div class="container-fluid">

    <x-header title="Credits" subtitle="View credits in this module"></x-header>

    <x-table id="myTable">
        <thead>
            <tr>
                <th>Employee No</th>
                <th>Name</th>
                <th>Vacation Leave (VL)</th>
                <th>Sick Leave (SL)</th>
                <th>Wellness Leave (WL)</th>
                <th>Offset</th>
            </tr>
        </thead>
    </x-table>

</div>
@endsection


@section('scripts')
<script>
    $(document).ready(function () {
        $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('services.credits') }}",
            autoWidth: false,

            columns: [
                { data: 'employee_no', name: 'employee_no', width: '10px' },
                { data: 'name', name: 'name', width: '200px' },
                { data: 'vl', name: 'vl', width: '80px'},
                { data: 'sl', name: 'sl', width: '80px'},
                { data: 'wl', name: 'wl', width: '80px'},
                { data: 'offset', name: 'offset', width: '100px'}
            ]
        });
    });
</script>
@endsection