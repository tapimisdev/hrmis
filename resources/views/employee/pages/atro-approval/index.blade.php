@extends('employee.layout.app')

@section('content')
@include('employee.pages.atro.show')
    <div class="container-fluid pt-3">
        
        <header-vue title="DOST TAPI"></header-vue>

        <x-header-employee title="Overtime Approval" subtitle="Review and approve overtime applications here">
        </x-header-employee>
        <ul class="nav nav-pills mb-4">
            @foreach($levels as $key => $item)
                <li class="nav-item">
                    <a href="{{route('approval-leave.index', ['level' => $item])}}" class="nav-link {{ $level == $item ? 'active' : '' }}" aria-current="page" href="#">
                        {{ordinal($item)}} Approver
                    </a>
                </li>
            @endforeach
        </ul>
        <x-table-employee id="myTable">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Date</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table-employee>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('approval-overtime.index', ['level' => $level]) }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "date", name: 'date' },
                { data: "total_hours", name: 'total_hours' },
                { data: "status", name: 'status' },
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
                    axios.delete(`/employee/overtime/${id}`)
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

            axios.get(`/employee/overtime/${id}`)
                .then((response) => {
                    const data = response.data.atro;
                    // Fill in modal
                    $('#doc-id').text(data.id);
                    $('#date').text(moment(data.date).format('MMMM D, YYYY'));
                    $('#start-time').text(moment(data.start_time, 'HH:mm:ss').format('h:mm A'));
                    $('#end-time').text(moment(data.end_time, 'HH:mm:ss').format('h:mm A'));
                    $('#total-hours').text(data.total_hours);
                    $('#reason').text(data.reason);

                    // Status badge
                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';

                    $('#status').attr('class', 'badge ' + statusClass).text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    $('#approver').text(data.approver_id ?? 'Not Yet Assigned');
                    $('#approved-at').text(data.approved_at ? moment(data.approved_at).format('MMMM D, YYYY h:mm A') : '---');

                    $('#attachments ul').empty();

                    // Show modal
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