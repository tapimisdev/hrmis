@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Overtime Applications" subtitle="Manage overtime applications in this module">
            
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table>
    </div>
@endsection

@section('scripts')
<script>
   $(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('services.overtime.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                 { data: "employee_no", name: 'employee_no' },
                { data: "name", name: 'name' },
                { data: "date_time", name: 'date_time' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "columnDefs": [
                {
                    targets: "_all",
                    className: "min-table-width",
                    render: function(data, type, row, meta) {
                        return data ?? "";
                    }
                }
            ],
            "scrollX": true,
            "autoWidth": false
        });
    });
</script>
@endsection


