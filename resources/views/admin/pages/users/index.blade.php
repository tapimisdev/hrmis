@extends('admin.layouts.app')

@section('styles')
    {{-- Add DataTables CSS if not yet loaded globally --}}
@endsection

@section('content')

@include('admin.pages.users.show')

<div class="container-fluid">

    <x-header title="Users" subtitle="Manage users in this module">
        <x-button-link 
            :href="route('users.create')" 
            icon="fa-solid fa-plus" 
            text="Add User" 
            variant="primary"
        />
    </x-header>

    <x-table id="myTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Date Added</th>
                <th style="width:120px">Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </x-table>

</div>
@endsection


@section('scripts')
<script>
$(document).ready(function () {

    /* ===============================
     * DataTable Initialization
     * =============================== */
    let dataTable = $('#myTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('users.index') }}",
            type: "GET"
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'date_added', name: 'created_at' },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        scrollX: true,
        autoWidth: false,
        order: [[1, 'desc']]
    });


    /* ===============================
     * Show User Modal
     * =============================== */
    $(document).on('click', '#btn-show', function () {

        const url = $(this).data('target');
        const $container = $('#tranche-items-container');

        $container.html(`
            <div class="d-flex justify-content-center py-5">
                <div class="spinner-border" role="status"></div>
            </div>
        `);

        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                $container.html(response);
                $('#trancheItemsModal').modal('show');
            },
            error: function () {
                $container.html(
                    '<p class="text-danger text-center">Failed to load user data.</p>'
                );
            }
        });
    });

});
</script>
@endsection
