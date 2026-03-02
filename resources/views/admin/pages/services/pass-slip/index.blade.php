@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Pass Slip Applications" subtitle="Manage pass slip applications in this module">
            
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee No</th>
                    <th>Name</th>
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
            "order": [[0, 'desc']],
            "ajax": '{{ route('services.pass_slip.index') }}',
            "columns": [
                { data: "id", name: 'id', visible: false },
                { data: "employee_no", name: 'employee_no' },
                { data: "name", name: 'name' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "columnDefs": [
                {
                    targets: [1, 2, 3, 4],
                    className: 'min-table-width'
                }
            ],
            "scrollX": true,
            "autoWidth": false
        });
    });
</script>
@endsection


