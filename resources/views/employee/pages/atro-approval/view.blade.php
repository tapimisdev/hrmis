@extends('employee.layout.app')

@section('content')
@include('employee.pages.atro.show')
    <div class="container-fluid">
        
        <header-vue title="DOST TAPI"></header-vue>

        <x-header-employee title="Overtime Approval" subtitle="Review and approve overtime applications here">
            <x-button-link 
                href="{{route('approval-overtime.index')}}"
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header-employee>
       
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
            "ajax": '{{ route('approval-overtime.view', ['level' => $level]) }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "date", name: 'date' },
                { data: "total_hours", name: 'total_hours' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

    });
</script>
@endsection