@extends('employee.layout.app')

@section('content')
@include('employee.pages.obs.show') {{-- modal partial for viewing details --}}

<div class="container-fluid pt-3">

    <header-vue title="DOST TAPI"></header-vue>

    <x-header-employee title="Pass Slip Approval" subtitle="Review and approve pass slip here">
    </x-header-employee>
    <ul class="nav nav-pills mb-4">
        @foreach($levels as $key => $item)
            <li class="nav-item">
                <a href="{{route('approval-leave.index', ['level' => $item])}}" class="nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
                    {{ordinal($item)}} Approver
                </a>
            </li>
        @endforeach
    </ul>
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
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
    $(document).ready(function() {
        let DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('approval-obs.index', ['level' => $level]) }}',
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
