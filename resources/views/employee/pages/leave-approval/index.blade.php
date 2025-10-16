@extends('employee.layout.app')

@section('content')
@include('employee.pages.leave.show')
    <div class="container-fluid pt-3">
        <header-vue title="DOST TAPI"></header-vue>

        <x-header-employee title="Leaves Approval" subtitle="Review and approve leave applications here">
        </x-header-employee>

        <x-table-employee id="myTable">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Status</th>
                    <th>Leave Type</th>
                    <th>Date</th>
                    <th>No. of Days</th>
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
       


      
    });
</script>
@endsection