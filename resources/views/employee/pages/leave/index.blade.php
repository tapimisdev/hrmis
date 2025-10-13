@extends('employee.layout.app')

@section('content')
@include('employee.pages.leave.show')
    <div class="container">
        <x-header title="Leave Applications" subtitle="Manage Leave Applications in this module" >
            <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
            <a href="{{ route('leaves.create') }}" class="btn btn-primary py-3 px-4">
                <i class="fa-solid fa-paper-plane me-2"></i> Apply
            </a>
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Status</th>
                    <th>Leave Type</th>
                    <th>Date</th>
                    <th>No. of Days</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('leaves.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "status", name: 'status' },
                { data: "date", name: 'date' },
                { data: "days", name: 'days' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', '.cancel-button', function() {
            id = $(this).attr('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, cancel it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/employee/leaves/${id}`)
                    .then(response => {
                        DataTable.ajax.reload();
                        Swal.fire({
                            title: "Cancelled!",
                            text: "Your data has been deleted.",
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
            }); // swal end
        });

        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Leave Application');

            axios.get(`/employee/leaves/${id}`)
                .then((response) => {
                    const data = response.data.data;

                    // Fill in modal fields
                    $('#doc-id').text(data.id);
                    $('#employee-no').text(data.employee_no ?? 'N/A');
                    $('#leave-type').text(data.leave_name);

                    // Format and display dates array nicely (e.g. "Oct 14, 16 2025")
                    if (Array.isArray(data.dates) && data.dates.length > 0) {
                        const formattedDates = data.dates
                            .map(date => moment(date).format('MMM D'))
                            .join(', ');
                        // Assuming all dates in same year; append year from first date
                        const year = moment(data.dates[0]).format('YYYY');
                        $('#selectedDates').text(`${formattedDates} ${year}`);
                    } else {
                        $('#selectedDates').text('N/A');
                    }

                    $('#days').text(data.days);
                    $('#reason').text(data.reason);

                    // Status badge styling
                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';

                    $('#status').attr('class', 'badge ' + statusClass)
                                .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    // Approver and approval date
                    $('#approver').text(data.approver_id ?? 'Not Yet Assigned');
                    $('#approved-at').text(data.approved_at 
                        ? moment(data.approved_at).format('MMMM D, YYYY h:mm A') 
                        : '---');

                    // Attachments list
                    $('#attachments ul').empty();

                    const attachments = response.data.attachments;

                    if (attachments && attachments.length > 0) {
                        attachments.forEach(file => {
                            let fileUrl = `/storage/${file.file_path}`; // adjust path if necessary
                            let fileName = file.file_name;

                            $('#attachments ul').append(
                                `<li><a href="${fileUrl}" target="_blank" rel="noopener noreferrer">${fileName}</a></li>`
                            );
                        });
                    } else {
                        $('#attachments ul').append('<li><em>No attachments</em></li>');
                    }

                    // Show the modal
                    $('#myModal').modal('show');
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