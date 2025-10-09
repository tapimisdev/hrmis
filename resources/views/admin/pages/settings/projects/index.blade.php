@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Projects" subtitle="Manage projects for this employment type">
            <a href="{{route('projects.create')}}" class="btn btn-secondary py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-plus me-2"></i> Add Projects
            </a>
        </x-header>
        <x-table id="myTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date Added</th>
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
            "ajax": '{{ route('projects.index') }}',
            "columns": [
                { data: "name", name: 'name' },
                { data: "date_created", name: 'date_created' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
        
    });
</script>
@endsection


