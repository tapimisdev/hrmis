@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Timelogs" subtitle="View timelogs in this module">
           
        </x-header>

        <div class="row mb-5">
            <div class="col-md-3">
                <label for="filterType" class="form-label">Filter by Type:</label>
                <select id="filterType" class="form-select">
                    <option value="">All</option>
                    @foreach($types as $type)
                        <option value="{{ $type->name }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filterDivision" class="form-label">Filter by Divisions:</label>
                <select id="filterDivision" class="form-select">
                    <option value="">All</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->name }}">{{ $division->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filterUnit" class="form-label">Filter by Unit:</label>
                <select id="filterUnit" class="form-select">
                    <option value="">All</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filterPosition" class="form-label">Filter by Position:</label>
                <select id="filterPosition" class="form-select">
                    <option value="">All</option>
                    @foreach($positions as $position)
                        <option value="{{ $position->name }}">{{ $position->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th style="width: 50px">Profile Image</th>
                    <th>Employee #</th>
                    <th>Fullname</th>
                    <th>Position</th>
                    <th>Unit</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody >
            </tbody>
        </x-table>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                url: '{{ route('timelogs.index') }}',
                data: function (d) {
                    d.type = $('#filterType').val();
                    d.position = $('#filterPosition').val();
                    d.division = $('#filterDivision').val();
                    d.unit = $('#filterUnit').val();
                }
            },
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "picture", name: 'picture' },
                { data: "employee_no", name: 'employee_no' },
                { data: "fullname", name: 'fullname' },
                { data: "position_name", name: 'position_name' },
                { data: "units_name", name: 'units_name' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "columnDefs": [
                {
                    targets: [2, 3, 4, 5],
                    className: 'min-table-width'
                }
            ],
            "scrollX": true,
            "autoWidth": false
        });
        
        // Reload table when filters change
        $('#filterType, #filterPosition, #filterUnit, #filterDivision').change(function () {
            DataTable.ajax.reload();
        });

    });
</script>
@endsection