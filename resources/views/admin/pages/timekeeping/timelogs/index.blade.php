@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
{{-- @include('admin.pages.settings.leaves.show') --}}
    <div class="container p-4 pb-5">
        <x-header title="Timelogs" subtitle="View timelogs in this module">

        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th style="width: 50px">Img</th>
                    <th>Employee No.</th>
                    <th>Fullname</th>
                    <th>employment Type</th>
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
            "ajax": '{{ route('timelogs.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "picture", name: 'picture' },
                { data: "name", name: 'name' },
                { data: "email", name: 'email' },
                { data: "email", name: 'email' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

    });
</script>
@endsection


