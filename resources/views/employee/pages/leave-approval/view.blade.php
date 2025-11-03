@extends('employee.layout.app')

@section('content')
@include('employee.pages.leave.show')
    <div class="container-fluid">
        
        <header-vue title="DOST TAPI"></header-vue>

        <x-header-employee title="All Leaves" subtitle="Review and approve leave applications here">
            <x-button-link 
                href="{{route('approval-leave.index')}}"
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
            "ajax": '{{ route('approval-leave.view', ['level' => $level]) }}',
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