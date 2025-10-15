@extends('employee.layout.app')

@section('content')
@include('employee.pages.obs.show') {{-- modal partial for viewing details --}}

<div class="container-fluid pt-3">

    <header-vue title="DOST TAPI"></header-vue>

    <x-header-employee title="Official Business Slip" subtitle="Manage Official Official Business Slip in this module">
        <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
        <a href="{{ route('obs.create') }}" class="btn btn-primary py-3 px-4">
            <i class="fa-solid fa-paper-plane me-2"></i> Apply
        </a>
    </x-header-employee>

    <x-table id="myTable">
        <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>OBS No.</th>
                <th>Date</th>
                <th>Destination</th>
                <th>Status</th>
                <th style="width: 120px">Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </x-table>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
    $(document).ready(function() {
        let DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('obs.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "obs_no", name: 'obs_no' },
                { data: "date_range", name: 'date_range' },
                { data: "destination", name: 'destination' },
                { data: "status", name: 'status', orderable: false, searchable: false },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

        // Cancel OBS
        $(document).on('click', '.cancel-button', function() {
            let id = $(this).attr('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "This OBS will be cancelled.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, cancel it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/employee/official-business-slip/${id}`)
                        .then(response => {
                            DataTable.ajax.reload();
                            Swal.fire({
                                title: "Cancelled!",
                                text: "Your official business slip has been cancelled.",
                                icon: "success"
                            });
                        })
                        .catch(error => {
                            Swal.fire({
                                title: "Oops!",
                                text: "Something went wrong, try again later!",
                                icon: "error"
                            });
                        })
                }
            });
        });

        // Show modal
        const myModal = $('#myModal');
        $(document).on('click', '.show-button', function () {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Official Business Slip');

            axios.get(`/employee/official-business-slip/${id}`)
                .then((response) => {
                    const data = response.data.obs;

                    $('#obs-doc-id').text(data.obs_no);
                    $('#obs-employee-no').text(data.employee_no);
                    $('#obs-destination').text(data.destination);
                    $('#obs-purpose').text(data.purpose);

                    // Date range
                    $('#obs-date-from').text(moment(data.date_from).format('MMMM D, YYYY'));
                    $('#obs-date-to').text(moment(data.date_to).format('MMMM D, YYYY'));

                    $('#obs-time-out').text(data.time_out ?? '—');
                    $('#obs-time-in').text(data.time_in ?? '—');
                    $('#obs-transport').text(data.mode_of_transport ?? '—');
                    $('#obs-expense').text(data.estimated_expense ? `₱${parseFloat(data.estimated_expense).toFixed(2)}` : '—');
                    $('#obs-charge-to').text(data.charge_to ?? '—');
                    $('#obs-remarks').text(data.remarks ?? '—');

                    // Status badge
                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';
                    else if (data.status === 'cancelled') statusClass = 'bg-dark';

                    $('#obs-status').attr('class', 'badge ' + statusClass).text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#obs-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));
                    $('#obs-approver').text(data.approver ?? 'Not Yet Assigned');
                    $('#obs-approved-at').text(data.approved_at ? moment(data.approved_at).format('MMMM D, YYYY h:mm A') : '—');

                    // Attachments
                    let attachmentList = $('#obs-attachments ul');
                    attachmentList.empty();
                    if (data.attachments && data.attachments.length > 0) {
                        data.attachments.forEach(file => {
                            attachmentList.append(`<li><a href="${file.url}" target="_blank">${file.name}</a></li>`);
                        });
                    } else {
                        attachmentList.append('<li>No attachments</li>');
                    }

                    myModal.modal('show');
                })
                .catch(error => {
                    Swal.fire({
                        title: "Oops!",
                        text: error.message,
                        icon: "error"
                    });
                });
        });

    });
</script>
@endsection
