@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.deductions.show')
    <div class="container p-4 pb-5">
        <x-header title="Leave Applications" subtitle="Manage leave applications in this module">
            
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee No</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Status</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
    $(function() {

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('services.leaves.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "employee_no", name: 'employee_no' },
                { data: "type", name: 'type' },
                { data: "dates", name: 'dates' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });
    });
</script>
@endsection


