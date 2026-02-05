@extends('admin.layouts.app')

@section('styles')
@endsection

@section('content')
    @include('admin.pages.taxation.train-law.create')

    <div class="container-fluid">
        <x-header title="Train Law" subtitle="Manage Train Law in this module">
            <x-button id="create-btn" variant="primary">
                <i class="fa-solid fa-plus me-1"></i>
                Create
            </x-button>
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th style="width: 140px">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </x-table>
    </div>
@endsection

@section('scripts')
<script>
$(function () {

    const trainLawModal = $('#trainLawModal');
    const form = $('#trainLawForm');

    // Datatable
    const DataTable = $('#myTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('taxation.train-law.index') }}',
        columns: [
            { data: "DT_RowIndex", name: 'index' },
            { data: "year", name: 'year' },
            { data: "status", name: 'status' },
            { data: "created_at", name: 'created_at' },
            { data: "updated_at", name: 'updated_at' },
            { data: "actions", name: 'actions', orderable: false, searchable: false },
        ],
        scrollX: true,
        autoWidth: false
    });

    function resetErrors() {
        $('#err_year').text('');
        $('#year').removeClass('is-invalid');
    }

    function resetForm() {
        resetErrors();
        form[0].reset();
        $('#trainlaw_id').val('');
        $('#form_method').val('POST');
        $('#modalTitle').text('Train Law');
        $('#modalSubtitle').text('Create a Train Law year');
        $('#saveBtn').html('<i class="fa-solid fa-floppy-disk me-1"></i> Save');
    }

    // Create button
    $(document).on('click', '#create-btn', function () {
        resetForm();
        trainLawModal.modal('show');
    });

    // Edit button (loads data into modal)
    $(document).on('click', '.edit-button', function () {
        resetForm();

        const id = $(this).data('id');

        axios.get(`{{ url('admin/taxation/train-law') }}/${id}/edit`)
            .then(res => {
                $('#trainlaw_id').val(res.data.id);
                $('#year').val(res.data.year);
                $('#form_method').val('PUT');

                $('#modalTitle').text('Edit Train Law');
                $('#modalSubtitle').text('Update Train Law year');
                $('#saveBtn').html('<i class="fa-solid fa-pen-to-square me-1"></i> Update');

                trainLawModal.modal('show');
            })
            .catch(err => {
                Swal.fire('Oops!', err?.response?.data?.message ?? 'Failed to load data.', 'error');
            });
    });

    // Submit (Create/Update)
    form.on('submit', function (e) {
        e.preventDefault();
        resetErrors();

        const id = $('#trainlaw_id').val();
        const method = $('#form_method').val();
        const payload = {
            year: $('#year').val(),
        };

        let url = `{{ url('admin/taxation/train-law') }}`;
        let request;

        if (method === 'PUT') {
            url = `{{ url('admin/taxation/train-law') }}/${id}`;
            request = axios.put(url, payload);
        } else {
            request = axios.post(url, payload);
        }

        request.then(res => {
            trainLawModal.modal('hide');
            DataTable.ajax.reload(null, false);

            Swal.fire('Success!', res.data.message, 'success');
        }).catch(err => {
            if (err.response && err.response.status === 422) {
                const errors = err.response.data.errors || {};
                if (errors.year) {
                    $('#year').addClass('is-invalid');
                    $('#err_year').text(errors.year[0]);
                }
                return;
            }

            Swal.fire('Oops!', err?.response?.data?.message ?? 'Something went wrong.', 'error');
        });
    });

    // Set Inactive (no delete)
    $(document).on('click', '.inactive-button', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: "Set this Train Law inactive?",
            text: "It will be hidden from active list and computations.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, set inactive",
            cancelButtonText: "Cancel",
        }).then((result) => {
            if (!result.isConfirmed) return;

            axios.patch(`{{ url('admin/taxation/train-law') }}/${id}/inactive`)
                .then(res => {
                    DataTable.ajax.reload(null, false);
                    Swal.fire("Updated!", res.data.message, "success");
                })
                .catch(err => {
                    Swal.fire("Oops!", err?.response?.data?.message ?? "Failed to set inactive.", "error");
                });
        });
    });

});
</script>
@endsection
