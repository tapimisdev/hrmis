@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Salary Payroll" subtitle="View salary payroll in this module">
            <button class="btn btn-secondary">
            <i class="fas fa-file-invoice-dollar py-3 px-2"></i> Generate Payroll
            </button>
        </x-header>

        <div class="row">
            <div class="col-md-6">
                
            </div>
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
        });
        
        // Reload table when filters change
        $('#filterType, #filterPosition, #filterUnit, #filterDivision').change(function () {
            DataTable.ajax.reload();
        });

    });
</script>
@endsection