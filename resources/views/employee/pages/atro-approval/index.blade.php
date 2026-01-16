@extends('employee.layout.app')

@section('content')
@include('employee.pages.atro.show')
    <div class="container-fluid min-vh-100">
        
        <x-employee-navbar>
            <header-vue title="DOST TAPI"></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Overtime Approval" subtitle="Review and approve overtime applications here">
        
        </x-header-employee>
        
        <div class="d-flex justify-content-between">
            <ul class="nav nav-pills mb-4 gap-2">
                @foreach($levels as $key => $item)
                    <li class="nav-item">
                        <a href="{{route('approval-overtime.index', ['level' => $item])}}" class="bg-secondary nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
                            {{ordinal($item)}} Approver
                        </a>
                    </li>
                @endforeach
            </ul>

            <ul class="nav nav-pills mb-4 gap-2">
                @if(count($levels) > 0) 
                    <li class="nav-item">
                        <a href="{{route('approval-overtime.view', ['level' => 'all'])}}" class="bg-secondary nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
                            View All
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        <x-table-employee id="myTable">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Date</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table-employee>
    </div>
@endsection

@section('scripts')

<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('approval-overtime.index', ['level' => $level]) }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "date", name: 'date' },
                { data: "total_hours", name: 'total_hours' },
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