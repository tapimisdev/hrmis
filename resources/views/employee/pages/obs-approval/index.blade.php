@extends('employee.layout.app')

@section('content')
@include('employee.pages.obs.show') {{-- modal partial for viewing details --}}

<div class="container-fluid min-vh-100">

    <x-employee-navbar>
        <header-vue title="DOST TAPI"></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Pass Slip Approval" subtitle="Review and approve pass slip here">
    </x-header-employee>
    
    <div class="d-flex justify-content-between">
        <ul class="nav nav-pills mb-4 gap-2">
            @foreach($levels as $key => $item)
                <li class="nav-item">
                    <a href="{{route('approval-obs.index', ['level' => $item])}}" class="bg-secondary nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
                        {{ordinal($item)}} Approver
                    </a>
                </li>
            @endforeach
        </ul>

        <ul class="nav nav-pills mb-4 gap-2">
            @if(count($levels) > 0) 
                <li class="nav-item">
                    <a href="{{route('approval-obs.view', ['level' => 'all'])}}" class="bg-secondary nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
                        View All
                    </a>
                </li>
            @endif
        </ul>
    </div>

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
            "ajax": '{{ route('approval-obs.index', ['level' => $level]) }}',
            "columns": [
                { data: "application_no", name: 'application_no' },
                { data: "name", name: 'name' },
                { data: "date_range", name: 'date_range' },
                { data: "destination", name: 'destination' },
                { data: "status", name: 'status', orderable: false, searchable: false },
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
            scrollX: true,
            autoWidth: false
        });
    });
</script>
@endsection
