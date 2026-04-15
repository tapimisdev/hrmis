@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Feedbacks" subtitle="Review feedback submitted from the employee portal">
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Date Submitted</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </x-table>

        <feedback-detail-modal></feedback-detail-modal>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    let dataTable = $('#myTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('feedbacks.index') }}",
            type: "GET"
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'category', name: 'feedbacks.category' },
            { data: 'date_submitted', name: 'feedbacks.created_at' },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        columnDefs: [
            {
                targets: "_all",
                className: "min-table-width",
                render: function(data) {
                    return data ?? "";
                }
            }
        ],
        scrollX: true,
        autoWidth: false,
        order: [[2, 'desc']]
    });

    $(document).on('click', '.btn-feedback-view', function () {
        const url = $(this).data('target');

        axios.get(url)
            .then(function (response) {
                window.feedbackDetailModal?.open(response.data);
            })
            .catch(function () {
                if (window.ErrorToast) {
                    window.ErrorToast.fire({
                        title: 'Unable to load feedback details'
                    });
                }
            });
    });

    $(document).on('click', '#btn-delete', function () {
        const url = $(this).data('target');

        confirmAction(
            'Delete Feedback?',
            'This action cannot be undone!',
            'Yes, delete it!',
            () => {
                axios.delete(url)
                    .then(response => {
                        const res = response.data;
                        alert(res.status, res.message);
                        dataTable.ajax.reload(null, false);
                    })
                    .catch(() => {
                        if (window.ErrorToast) {
                            window.ErrorToast.fire({
                                title: 'Failed to delete feedback'
                            });
                        }
                    });
            }
        );
    });
});
</script>
@endsection
