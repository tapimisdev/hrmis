@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4">
        <x-header title="Employment Types" subtitle="Manage employment types in this module">
            <a href="{{route('employment-types.create')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Add New
            </a>
        </x-header>
        <div class="card shadow p-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100 pb-3" id="myTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Date Added</th>
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
            "ajax": '{{ route('employment-types.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "code", name: 'code' },
                { data: "name", name: 'name' },
                { data: "date_created", name: 'date_created' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection


