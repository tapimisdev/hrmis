@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.shifts.show')
    <div class="container-fluid">
        <x-header title="Suspensions" subtitle="Manage suspensions in this module">
            <x-button-link 
                :href="route('services.suspensions.create')" 
                icon="fa-solid fa-plus" 
                text="Add Suspension" 
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
            "ajax": '{{ route('services.suspensions.index') }}',
            "columns": [
                { data: "name", name: 'name' },
                { data: "date_added", name: 'date_added' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', '#btn-show', function () {
            const url = $(this).data('target');
            const $container = $('#tranche-items-container');

            $container.html('<div class="spinner-border" role="status"></div>');

            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    $container.html(response); 
                    $('#trancheItemsModal').modal('show');
                },
                error: function (xhr) {
                    $container.html('<p class="text-danger">Failed to load tranche items.</p>');
                }
            });
        });

    });
</script>
@endsection



