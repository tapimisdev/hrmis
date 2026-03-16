@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Log Trails" subtitle="View actions and trails made in HRIS" >
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        <div>
            <table id="trailsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Actioned By ID</th>
                        <th>Actioned By Name</th>
                        <th>Method</th>
                        <th>Controller</th>
                        <th>Description</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(function() {
    let trailsTable = $('#trailsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("trails.index") }}', 
        columns: [
            { data: 'id', name: 'id' },
            { data: 'actioned_by_id', name: 'actioned_by_id' },
            { data: 'actioned_by_name', name: 'actioned_by_name' },
            { data: 'method', name: 'method' },
            { data: 'controller', name: 'controller' },
            { data: 'description', name: 'description' },
            { data: 'created_at', name: 'created_at' },
        ],
        columnDefs: [
            {
                targets: "_all",
                className: "min-table-width",
                render: function(data, type, row, meta) {
                    return data ?? "";
                }
            }
        ],
        scrollX: true,
        autoWidth: false
    });
});
</script>

@endsection