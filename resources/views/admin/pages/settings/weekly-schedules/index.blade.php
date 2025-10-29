@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.weekly-schedules.show')
    <div class="container pt-4 px-3">
        <x-header title="Weekly Schedules" subtitle="Manage weekly schedule in this module">
            <x-button-link 
                :href="route('weekly-schedules.create')" 
                icon="fa-solid fa-plus" 
                text="Add Weekly Schedule" 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
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
            "ajax": '{{ route('weekly-schedules.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });

        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Weekly Schedule Details');

            axios.get(`weekly-schedules/${id}`)
                .then((response) => {
                    const data = response.data.schedule;
                    // Fill modal fields
                    $('#schedule-id').text(data.id);
                    $('#schedule-name').text(data.name);
                    // Boolean badges for days
                    $('#is-monday').attr('class', 'badge ' + (data.is_monday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_monday ? 'Yes' : 'No');
                    $('#is-tuesday').attr('class', 'badge ' + (data.is_tuesday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_tuesday ? 'Yes' : 'No');
                    $('#is-wednesday').attr('class', 'badge ' + (data.is_wednesday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_wednesday ? 'Yes' : 'No');
                    $('#is-thursday').attr('class', 'badge ' + (data.is_thursday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_thursday ? 'Yes' : 'No');
                    $('#is-friday').attr('class', 'badge ' + (data.is_friday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_friday ? 'Yes' : 'No');
                    $('#is-saturday').attr('class', 'badge ' + (data.is_saturday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_saturday ? 'Yes' : 'No');
                    $('#is-sunday').attr('class', 'badge ' + (data.is_sunday ? 'bg-success' : 'bg-danger'))
                        .text(data.is_sunday ? 'Yes' : 'No');
                    $('#is-active').attr('class', 'badge ' + (data.is_active ? 'bg-success' : 'bg-danger'))
                        .text(data.is_active ? 'Active' : 'Inactive');
                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));
                    // Show modal
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
                    axios.delete(`weekly-schedules/${id}`)
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


