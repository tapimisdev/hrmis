@extends('employee.layout.app')

@section('content')
@include('employee.pages.obs.show') {{-- modal partial for viewing details --}}

<div class="container-fluid">

    <x-employee-navbar>
        <header-vue title="DOST TAPI"></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Pass Slip Approval" subtitle="Review and approve pass slip here">
        <x-button-link 
            href="{{route('approval-obs.index')}}"
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header-employee>

    <x-table-employee id="myTable">
        <thead>
            <tr>
                <th>File No.</th>
                <th>Name</th>
                <th>Dates</th>
                <th>Destination</th>
                <th>Status</th>
                <th style="width: 120px">Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </x-table-employee>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('approval-obs.view', ['level' => $level]) }}',
            "columns": [
                { data: "application_no", name: 'application_no' },
                { data: "name", name: 'name' },
                { data: "date_range", name: 'date_range' },
                { data: "destination", name: 'destination' },
                { data: "status", name: 'status', orderable: false, searchable: false },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
