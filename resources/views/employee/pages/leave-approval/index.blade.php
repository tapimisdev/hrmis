@extends('employee.layout.app')

@section('content')
@include('employee.pages.leave.show')
    <div class="container-fluid pt-3">
        <header-vue title="DOST TAPI"></header-vue>

        <x-header-employee title="Leaves Approval" subtitle="Review and approve leave applications here">
        
        </x-header-employee>

        <ul class="nav nav-pills mb-4">
            @foreach($levels as $key => $item)
                <li class="nav-item">
                    <a href="{{route('approval-leave.index', ['level' => $item])}}" class="bg-secondary nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
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
                    <th>Leave</th>
                    <th>Date(s)</th>
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
    $(function() {

        let DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('approval-leave.index', ['level' => $level]) }}',
            "columns": [
                { data: "application_no", name: 'application_no' },
                { data: "name", name: 'name' },
                { data: "leave", name: 'leave' },
                { data: "date", name: 'date' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });


      
    });
</script>
@endsection