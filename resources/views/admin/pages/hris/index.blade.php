@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Employee Lists" subtitle="Manage employee's informations in this module" >
            <a href="{{route('hris.employee.create')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Add Employee
            </a>
        </x-header>
        <div class="card shadow p-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100 pb-3" id="myTable">
                        <thead>
                            <tr>
                                <th>Employee No</th>
                                <th>Name</th>
                                <th>Date Hired</th>
                                <th style="width: 120px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('hris.employee.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "date_hired", name: 'date_hired' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection


