@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container pt-4 px-3">
        <x-header title="Projects" subtitle="Manage projects for this employment type">
            <x-button-link 
                :href="route('projects.create')" 
                icon="fa-solid fa-plus" 
                text="Add Projects" 
                variant="primary"
            />
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


