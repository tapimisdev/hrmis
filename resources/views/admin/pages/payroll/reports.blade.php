@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Leave Applications" subtitle="Manage leave applications in this module">
            
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee No</th>
                    <th>Employment Type</th>
                    <th>Cut Off</th>
                    <th>Period</th>
                    <th>Payroll Date</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('reports.index', ['employment_type' => 'cos']) }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "payroll_no", name: 'payroll_no' },
                { data: "employment_type", name: 'employment_type' },
                { data: "cutoff", name: 'cutoff' },
                { data: "period", name: 'period' },
                { data: "payroll_date", name: 'payroll_date' },
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
        });
    });
</script>
@endsection


