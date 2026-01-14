@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.role-permission.create')
    <div class="container p-4">
        <x-header title="Role and Permission" subtitle="Manage role and permission in this module" >

        </x-header>
        <x-table id="myTable">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Name</th>
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
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('role-and-permission.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "scrollX": true,
            "autoWidth": false
        });
    });
</script>
@endsection


