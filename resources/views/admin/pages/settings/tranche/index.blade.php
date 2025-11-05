@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.shifts.show')
    <div class="container-fluid">
        <x-header title="Tranches" subtitle="Manage tranches in this module">
            <x-button-link 
                :href="route('settings.tranche.create')" 
                icon="fa-solid fa-plus" 
                text="Add Tranche" 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>Employment Type</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table>

        <div class="modal fade" id="trancheItemsModal" tabindex="-1" aria-labelledby="trancheItemsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-uppercase" id="trancheItemsModalLabel">View Tranche</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="tranche-items-container" class="text-center">
                            <div class="spinner-border" role="status" style="display:none;"></div>
                        </div>
                    </div>
                </div>
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
            "ajax": '{{ route('settings.tranche.index') }}',
            "columns": [
                { data: "name", name: 'name' },
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



