@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.shifts.show')
    <div class="container pt-4 px-3">
        <x-header title="Shift Schedules" subtitle="Manage shift schedule in this module">
            <x-button-link 
                :href="route('shift.create')" 
                icon="fa-solid fa-plus" 
                text="Add Shift" 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Earliest In</th>
                    <th>Flexible</th>
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
    $(function() {

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('shift.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "earliest_time", name: 'earliest_time' },
                { data: "is_flexible", name: 'is_flexible' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });

        const shiftModal = $('#shiftModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Shift Details');

            axios.get(`shift/${id}`)
                .then((response) => {
                    const data = response.data.shift; // adjust according to your API response
                    console.log(data);

                    // Fill modal fields
                    $('#shift-id').text(data.id);
                    $('#shift-name').text(data.name);

                    $('#earliest-time').text(data.earliest_time ? moment(data.earliest_time, 'HH:mm:ss').format('h:mm A') : '---');
                    $('#start-time').text(moment(data.start_time, 'HH:mm:ss').format('h:mm A'));
                    $('#break-out-time').text(data.break_out_time ? moment(data.break_out_time, 'HH:mm:ss').format('h:mm A') : '---');
                    $('#break-in-time').text(data.break_in_time ? moment(data.break_in_time, 'HH:mm:ss').format('h:mm A') : '---');
                    $('#end-time').text(data.end_time ? moment(data.end_time, 'HH:mm:ss').format('h:mm A') : '---');

                    $('#minimum-overtime-hours').text(data.minimum_overtime_hours);

                    // Boolean badges
                    $('#is-flexible').attr('class', 'badge ' + (data.is_flexible ? 'bg-success' : 'bg-secondary'))
                                    .text(data.is_flexible ? 'Yes' : 'No');

                    $('#is-night-shift').attr('class', 'badge ' + (data.is_night_shift ? 'bg-dark' : 'bg-secondary'))
                                        .text(data.is_night_shift ? 'Yes' : 'No');

                    $('#is-break-required').attr('class', 'badge ' + (data.is_break_required ? 'bg-warning text-dark' : 'bg-secondary'))
                                        .text(data.is_break_required ? 'Yes' : 'No');

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    // Show modal
                    shiftModal.modal('show');
                })
                .catch(error => {
                    Swal.fire({
                        title: "Oops!",
                        text: error.message,
                        icon: "error"
                    });
                });
        });

        $(document).on('click', '.delete-button', function() {
            id = $(this).attr('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`shift/${id}`)
                    .then(response => {
                        DataTable.ajax.reload();
                        Swal.fire({
                            title: "Deleted!",
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

    });
</script>
@endsection


