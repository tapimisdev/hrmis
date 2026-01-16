@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Leave Applications" subtitle="Manage leave applications in this module">
            
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Type</th>
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
            "ajax": '{{ route('services.leaves.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "employee_no", name: 'employee_no' },
                { data: "name", name: 'name' },
                { data: "type", name: 'type' },
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


